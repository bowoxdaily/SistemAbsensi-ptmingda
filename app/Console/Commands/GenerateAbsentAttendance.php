<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Holiday;
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
    protected $description = 'Generate attendance records for absent employees (default: yesterday). For today, requires check-in + 10 minutes grace period.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get date parameter or use yesterday (untuk auto-run di pagi hari)
        // Jika command jalan otomatis jam 08:00 pagi, generate alpha untuk hari kemarin
        // Jika ada parameter date, gunakan tanggal yang ditentukan
        $date = $this->argument('date')
            ? Carbon::parse($this->argument('date'))
            : Carbon::yesterday();

        $this->info("Generating absent attendance for: " . $date->format('Y-m-d') . " (" . $date->format('l') . ")");

        // Get day of week (0 = Sunday, 1 = Monday, ..., 6 = Saturday)
        $dayOfWeek = $date->dayOfWeek;

        // Check if date is a holiday FIRST (before checking weekend)
        // Because some holidays can fall on weekends
        $isHoliday = Holiday::isHoliday($date);
        $holiday = null;

        if ($isHoliday) {
            $holiday = Holiday::where('date', $date->format('Y-m-d'))->where('is_active', true)->first();
            $this->info("Processing holiday: " . $date->format('l, d F Y') . " - {$holiday->name}");

            // Generate holiday attendance for all active employees
            return $this->generateHolidayAttendance($date, $holiday);
        }

        // If not a holiday and it's Sunday (0) or Saturday (6), skip (weekend)
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

            // Parse check-in time dari work schedule
            // Handle if start_time is datetime or time only
            try {
                if ($workSchedule->start_time instanceof \Carbon\Carbon) {
                    // Already Carbon instance
                    $checkinTime = $workSchedule->start_time;
                } elseif (strlen((string) $workSchedule->start_time) > 8) {
                    // Format datetime: "2025-10-23 08:00:00"
                    $checkinTime = Carbon::parse($workSchedule->start_time);
                } else {
                    // Format time only: "08:00:00"
                    preg_match('/(\d{1,2}):(\d{2})/', (string) $workSchedule->start_time, $matches);
                    if ($matches) {
                        $checkinTime = Carbon::createFromFormat('H:i', $matches[1] . ':' . $matches[2]);
                    } else {
                        throw new \Exception("Cannot parse time");
                    }
                }
            } catch (\Exception $e) {
                $this->warn("Invalid start_time format for {$employee->name}: {$e->getMessage()}, skipped.");
                $skippedCount++;
                continue;
            }

            // Set tanggal check-in ke tanggal yang dicek
            $checkinDateTime = Carbon::parse($date->format('Y-m-d') . ' ' . $checkinTime->format('H:i:s'));

            // Tambahkan grace period 10 menit setelah jam check-in
            // Grace period hanya dicek jika tanggal yang di-generate adalah hari ini
            // Untuk hari kemarin/sebelumnya, langsung generate tanpa cek grace period
            $isToday = $date->isToday();
            $gracePeriodEnd = null;
            
            if ($isToday) {
                $gracePeriodEnd = $checkinDateTime->copy()->addMinutes(10);
                
                // Hanya generate alpha jika sudah melewati grace period
                if ($currentTime->lt($gracePeriodEnd)) {
                    $skippedCount++;
                    continue;
                }
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
                'notes' => 'Auto-generated: Tidak melakukan absensi',
            ]);

            $gracePeriodInfo = $isToday ? " (Grace period: {$gracePeriodEnd->format('H:i')})" : "";
            $this->line("✓ Generated alpha for: {$employee->name} ({$employee->employee_code}) - Check-in time: {$checkinTime->format('H:i')}{$gracePeriodInfo}");
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

    /**
     * Generate attendance for all active employees on holiday
     * Status will match the holiday type:
     * - Libur Nasional → 'libur'
     * - Cuti Bersama → 'cuti_bersama'
     * - Custom → 'libur'
     * If employee checks in, the check-in process will override this to "Hadir"
     */
    private function generateHolidayAttendance($date, $holiday)
    {
        // Get all active employees
        $employees = Employee::where('status', 'active')->get();

        $generatedCount = 0;
        $skippedCount = 0;

        foreach ($employees as $employee) {
            // Check if attendance record already exists
            $existingAttendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('attendance_date', $date)
                ->first();

            if ($existingAttendance) {
                // Attendance already exists (mungkin sudah check-in), skip
                $skippedCount++;
                continue;
            }

            // Check if employee has approved leave for this date
            $approvedLeave = Leave::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();

            if ($approvedLeave) {
                // Employee has approved leave, create attendance with leave status
                $leaveStatus = $approvedLeave->leave_type;

                Attendance::create([
                    'employee_id' => $employee->id,
                    'attendance_date' => $date->format('Y-m-d'),
                    'check_in' => null,
                    'check_out' => null,
                    'status' => $leaveStatus,
                    'late_minutes' => 0,
                    'notes' => "Hari Libur ({$holiday->name}) - {$leaveStatus} (approved): {$approvedLeave->reason}",
                ]);

                $this->line("✓ Generated {$leaveStatus} for: {$employee->name} ({$employee->employee_code})");
                $generatedCount++;
                continue;
            }

            // Create attendance record for holiday based on holiday type
            // Map holiday type to attendance status:
            // - 'nasional' → 'libur'
            // - 'cuti_bersama' → 'cuti_bersama'
            // - 'custom' → 'libur'
            $attendanceStatus = match($holiday->type) {
                'cuti_bersama' => 'cuti_bersama',
                'nasional' => 'libur',
                'custom' => 'libur',
                default => 'libur'
            };

            Attendance::create([
                'employee_id' => $employee->id,
                'attendance_date' => $date->format('Y-m-d'),
                'check_in' => null,
                'check_out' => null,
                'status' => $attendanceStatus,
                'late_minutes' => 0,
                'notes' => "Hari Libur ({$holiday->type_label}): {$holiday->name}",
            ]);

            $this->line("✓ Generated {$attendanceStatus} for: {$employee->name} ({$employee->employee_code})");
            $generatedCount++;
        }

        $this->newLine();
        $this->info("Holiday attendance generation completed!");
        $this->info("Holiday: {$holiday->name} ({$holiday->type_label})");
        $this->info("Generated: {$generatedCount} records");
        $this->info("Skipped: {$skippedCount} employees (already have attendance)");

        return 0;
    }
}
