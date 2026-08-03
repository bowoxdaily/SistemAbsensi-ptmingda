<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecalculateOvertimeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:recalculate-overtime
                            {--from= : Start date (Y-m-d)}
                            {--to= : End date (Y-m-d)}
                            {--employee= : Employee ID}
                            {--dry-run : Preview changes without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate overtime minutes for existing attendance records';

    /**
     * Chunk size for processing attendance records.
     * Keeps memory usage low and bulk UPDATE queries small.
     */
    private const CHUNK_SIZE = 200;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $from = $this->option('from');
        $to = $this->option('to');
        $employeeId = $this->option('employee');
        $dryRun = $this->option('dry-run');

        $this->info('Starting overtime recalculation...');
        $this->newLine();

        // Build query
        $query = Attendance::with(['employee.workSchedule', 'employee.position'])
            ->whereNotNull('check_out')
            ->whereIn('status', ['hadir', 'terlambat']);

        // Apply filters
        if ($from) {
            $query->whereDate('attendance_date', '>=', $from);
            $this->info("Filter: From date >= {$from}");
        }

        if ($to) {
            $query->whereDate('attendance_date', '<=', $to);
            $this->info("Filter: To date <= {$to}");
        }

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
            $this->info("Filter: Employee ID = {$employeeId}");
        }

        $this->newLine();

        // Count first (lightweight query) to set up progress bar
        $total = (clone $query)->count();

        if ($total === 0) {
            $this->warn('No attendance records found matching the criteria.');
            return Command::SUCCESS;
        }

        $this->info("Found {$total} attendance record(s) to process.");
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be saved');
            $this->newLine();
        }

        $processed = 0;
        $updated = 0;
        $skipped = 0;
        $bulkUpdateCount = 0;
        $weeklyUsage = [];

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        // Process in chunks to avoid loading all records into memory at once
        $query->orderBy('employee_id')->orderBy('attendance_date')->orderBy('id')
            ->chunk(self::CHUNK_SIZE, function ($attendances) use (&$processed, &$updated, &$skipped, &$bulkUpdateCount, &$weeklyUsage, $dryRun, $progressBar) {
                $chunkUpdates = [];

                foreach ($attendances as $attendance) {
                    $processed++;

                    // Skip if no work schedule
                    if (!$attendance->employee || !$attendance->employee->workSchedule) {
                        $skipped++;
                        $progressBar->advance();
                        continue;
                    }

                    $schedule = $attendance->employee->workSchedule;

                    try {
                        $attendanceDate = Carbon::parse($attendance->attendance_date);
                        $weekKey = $attendance->employee_id . '|' . $attendanceDate->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
                        $currentWeeklyUsed = $weeklyUsage[$weekKey] ?? 0;

                        // Parse check-out time - handle both H:i:s and H:i formats
                        $checkOutTimeStr = $attendance->check_out;

                        // If check_out is already a Carbon instance, use it directly
                        if ($checkOutTimeStr instanceof \Carbon\Carbon) {
                            $checkOutTime = Carbon::parse($attendanceDate->format('Y-m-d') . ' ' . $checkOutTimeStr->format('H:i:s'));
                        } else {
                            // Parse as string
                            $checkOutTime = Carbon::parse($attendanceDate->format('Y-m-d') . ' ' . $checkOutTimeStr);
                        }

                        $checkInTime = Carbon::parse($attendanceDate->format('Y-m-d') . ' ' . ($attendance->check_in instanceof Carbon ? $attendance->check_in->format('H:i:s') : $attendance->check_in));
                        $overtimeMinutes = app(\App\Services\OvertimeCalculator::class)->calculate(
                            $attendance,
                            $attendanceDate,
                            $checkInTime,
                            $checkOutTime,
                            $schedule,
                            $attendance->employee->isEligibleForWeekdayOvertime(),
                            $currentWeeklyUsed
                        );

                        $weeklyUsage[$weekKey] = $currentWeeklyUsed + $overtimeMinutes;

                        // Collect update if different from current value
                        if ($attendance->overtime_minutes != $overtimeMinutes) {
                            $chunkUpdates[] = [
                                'id' => $attendance->id,
                                'overtime_minutes' => $overtimeMinutes
                            ];
                            $updated++;
                        }
                    } catch (\Exception $e) {
                        // Skip errors silently unless verbose
                        $skipped++;
                    }

                    $progressBar->advance();
                }

                // Bulk update per chunk — keeps each UPDATE query small (max CHUNK_SIZE rows)
                // preventing long table locks and giant SQL statements
                if (!$dryRun && !empty($chunkUpdates)) {
                    $this->performBulkUpdate($chunkUpdates);
                    $bulkUpdateCount++;
                }
            });

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        if (!$dryRun && $updated > 0) {
            $this->info("✓ Bulk updated {$updated} records in {$bulkUpdateCount} batch(es)");
        } elseif ($dryRun && $updated > 0) {
            $this->info("Would perform bulk update for {$updated} records");
        }

        $this->info('=== Recalculation Complete ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $processed],
                ['Updated', $updated],
                ['Skipped', $skipped],
                ['No Changes', $processed - $updated - $skipped],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('This was a DRY RUN. No data was modified.');
            $this->info('Run without --dry-run to apply changes.');
        } else {
            $this->newLine();
            $this->info('Overtime recalculation completed successfully!');
        }

        return Command::SUCCESS;
    }

    /**
     * Perform a bulk UPDATE using CASE WHEN statement.
     *
     * @param array $updates Array of ['id' => int, 'overtime_minutes' => int]
     */
    private function performBulkUpdate(array $updates): void
    {
        $cases = [];
        $ids = [];

        foreach ($updates as $update) {
            $cases[] = "WHEN {$update['id']} THEN {$update['overtime_minutes']}";
            $ids[] = $update['id'];
        }

        $casesStr = implode(' ', $cases);
        $idsStr = implode(',', $ids);

        DB::update("UPDATE attendances SET overtime_minutes = CASE id {$casesStr} END WHERE id IN ({$idsStr})");
    }
}
