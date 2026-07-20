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

        $attendances = $query->orderBy('employee_id')->orderBy('attendance_date')->orderBy('id')->get();
        $total = $attendances->count();

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
        $bulkUpdates = []; // Collect updates for bulk operation

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $weeklyUsage = [];
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
                    $bulkUpdates[] = [
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

        $progressBar->finish();
        $this->newLine(2);

        // Perform bulk update if not dry run
        if (!$dryRun && !empty($bulkUpdates)) {
            $this->info('Performing bulk update...');

            // Build CASE WHEN statement for bulk update
            $cases = [];
            $ids = [];

            foreach ($bulkUpdates as $update) {
                $cases[] = "WHEN {$update['id']} THEN {$update['overtime_minutes']}";
                $ids[] = $update['id'];
            }

            $casesStr = implode(' ', $cases);
            $idsStr = implode(',', $ids);

            DB::update("UPDATE attendances SET overtime_minutes = CASE id {$casesStr} END WHERE id IN ({$idsStr})");

            $this->info("✓ Bulk updated {$updated} records in a single query");
        } elseif ($dryRun && !empty($bulkUpdates)) {
            $this->info("Would perform bulk update for {$updated} records");
        }

        // Summary
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
}
