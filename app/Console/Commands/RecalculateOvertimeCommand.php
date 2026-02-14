<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;

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
        $query = Attendance::with(['employee.workSchedule'])
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

        $attendances = $query->get();
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

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

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
                // Parse check-out time - handle both H:i:s and H:i formats
                $checkOutTimeStr = $attendance->check_out;

                // If check_out is already a Carbon instance, use it directly
                if ($checkOutTimeStr instanceof \Carbon\Carbon) {
                    $checkOutTime = Carbon::parse($attendance->attendance_date->format('Y-m-d') . ' ' . $checkOutTimeStr->format('H:i:s'));
                } else {
                    // Parse as string
                    $checkOutTime = Carbon::parse($attendance->attendance_date->format('Y-m-d') . ' ' . $checkOutTimeStr);
                }

                // Parse schedule end time
                $endTime = Carbon::parse($schedule->end_time);
                $overtimeThreshold = $schedule->overtime_threshold ?? 50;

                // Create scheduled end time
                $scheduledEndTime = Carbon::parse($attendance->attendance_date->format('Y-m-d'))
                    ->setTime($endTime->hour, $endTime->minute, 0);

                // Calculate threshold time
                $thresholdTime = Carbon::parse($attendance->attendance_date->format('Y-m-d'))
                    ->setTime($endTime->hour, $endTime->minute, 0)
                    ->addMinutes($overtimeThreshold);

                // Calculate overtime
                $overtimeMinutes = 0;
                if ($checkOutTime->greaterThan($thresholdTime)) {
                    $overtimeMinutes = $scheduledEndTime->diffInMinutes($checkOutTime);
                }

                // Update if different from current value
                if ($attendance->overtime_minutes != $overtimeMinutes) {
                    if (!$dryRun) {
                        $attendance->overtime_minutes = $overtimeMinutes;
                        $attendance->save();
                    }
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
