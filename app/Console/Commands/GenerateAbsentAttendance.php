<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Leave;
use Carbon\Carbon;

class GenerateAbsentAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:generate-absent {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate attendance records for absent employees (mark as alpha)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get date parameter or use today (check untuk hari ini)
        $date = $this->argument('date')
            ? Carbon::parse($this->argument('date'))
            : Carbon::today();

        $this->info("Generating absent attendance for: " . $date->format('Y-m-d'));

        // Get day of week (0 = Sunday, 1 = Monday, ..., 6 = Saturday)
        $dayOfWeek = $date->dayOfWeek;

        // If Sunday (0) or Saturday (6), skip (weekend)
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            $this->warn("Skipping weekend date: " . $date->format('l, d F Y'));
            return 0;
        }

        // Get current time
        $currentTime = Carbon::now();

        // Get all active employees with work schedule
        $employees = Employee::with('workSchedule')
            ->where('status', 'active')
            ->whereNotNull('work_schedule_id')
            ->get();

        $generatedCount = 0;
        $skippedCount = 0;

        foreach ($employees as $employee) {
            // Check if employee has work schedule
            if (!$employee->workSchedule) {
                $this->warn("Employee {$employee->name} has no work schedule, skipped.");
                $skippedCount++;
                continue;
            }

            $workSchedule = $employee->workSchedule;

            // Check if this day is a working day for the employee's shift
            if (!$this->isWorkingDay($workSchedule, $dayOfWeek)) {
                $skippedCount++;
                continue;
            }

            // Parse checkout time dari work schedule
            // Handle if end_time is datetime or time only
            try {
                if (strlen($workSchedule->end_time) > 8) {
                    // Format datetime: "2025-10-23 17:00:00"
                    $checkoutTime = Carbon::parse($workSchedule->end_time);
                } else {
                    // Format time only: "17:00:00"
                    $checkoutTime = Carbon::createFromFormat('H:i:s', $workSchedule->end_time);
                }
            } catch (\Exception $e) {
                $this->warn("Invalid end_time format for {$employee->name}, skipped.");
                $skippedCount++;
                continue;
            }

            // Set tanggal checkout ke tanggal yang dicek
            $checkoutDateTime = Carbon::parse($date->format('Y-m-d') . ' ' . $checkoutTime->format('H:i:s'));

            // Tambahkan grace period 30 menit setelah checkout
            $gracePeriodEnd = $checkoutDateTime->copy()->addMinutes(30);

            // Hanya generate alpha jika sudah melewati grace period
            if ($currentTime->lt($gracePeriodEnd)) {
                $skippedCount++;
                continue;
            }

            // Check if attendance record already exists
            $existingAttendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('attendance_date', $date)
                ->first();

            if ($existingAttendance) {
                // Attendance already exists, skip
                $skippedCount++;
                continue;
            }

            // IMPORTANT: Check if employee has approved leave for this date
            $approvedLeave = Leave::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();

            if ($approvedLeave) {
                // Employee has approved leave, create attendance with leave status
                $leaveStatus = $approvedLeave->leave_type; // cuti, izin, or sakit

                Attendance::create([
                    'employee_id' => $employee->id,
                    'attendance_date' => $date->format('Y-m-d'),
                    'check_in' => null,
                    'check_out' => null,
                    'status' => $leaveStatus,
                    'late_minutes' => 0,
                    'notes' => "Auto-generated: {$leaveStatus} (approved) - {$approvedLeave->reason}",
                ]);

                $this->line("✓ Generated {$leaveStatus} for: {$employee->name} ({$employee->employee_code}) - Approved leave");
                $generatedCount++;
                continue;
            }

            // Create alpha attendance record
            Attendance::create([
                'employee_id' => $employee->id,
                'attendance_date' => $date->format('Y-m-d'),
                'check_in' => null,
                'check_out' => null,
                'status' => 'alpha',
                'late_minutes' => 0,
                'notes' => 'Auto-generated: Tidak melakukan absensi (melewati jam checkout + 30 menit)',
            ]);

            $this->line("✓ Generated alpha for: {$employee->name} ({$employee->employee_code}) - Checkout time: {$checkoutTime->format('H:i')}");
            $generatedCount++;
        }

        $this->newLine();
        $this->info("Generation completed!");
        $this->info("Generated: {$generatedCount} alpha records");
        $this->info("Skipped: {$skippedCount} employees");
        $this->info("Current time: " . $currentTime->format('H:i:s'));

        return 0;
    }

    /**
     * Check if the given day is a working day for the schedule
     */
    private function isWorkingDay($workSchedule, $dayOfWeek)
    {
        // Assuming work_schedules table has columns like:
        // - is_monday, is_tuesday, etc. (boolean)
        // OR
        // - working_days (JSON array)

        // Map dayOfWeek (0-6) to day name
        $dayMap = [
            0 => 'sunday',
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
        ];

        $dayName = $dayMap[$dayOfWeek];

        // Check if schedule has this specific day column
        $columnName = 'is_' . $dayName;

        if (isset($workSchedule->$columnName)) {
            return $workSchedule->$columnName == 1;
        }

        // Fallback: check working_days JSON if exists
        if (isset($workSchedule->working_days)) {
            $workingDays = is_string($workSchedule->working_days)
                ? json_decode($workSchedule->working_days, true)
                : $workSchedule->working_days;

            return in_array($dayOfWeek, $workingDays ?? []);
        }

        // Default: Monday to Friday (1-5)
        return $dayOfWeek >= 1 && $dayOfWeek <= 5;
    }
}
