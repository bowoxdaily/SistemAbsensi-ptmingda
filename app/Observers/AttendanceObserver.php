<?php

namespace App\Observers;

use App\Models\Attendance;

class AttendanceObserver
{
    private function updateSummary(Attendance $attendance, $oldStatus, $newStatus)
    {
        if (!$attendance->attendance_date || !$attendance->employee_id) {
            return;
        }

        $date = \Carbon\Carbon::parse($attendance->attendance_date);
        
        $summary = \App\Models\AttendanceMonthlySummary::firstOrCreate(
            [
                'employee_id' => $attendance->employee_id,
                'year' => $date->year,
                'month' => $date->month,
            ]
        );

        // If oldStatus exists, decrement it
        if ($oldStatus && isset($summary->{$oldStatus})) {
            $summary->{$oldStatus} = max(0, $summary->{$oldStatus} - 1);
        }

        // If newStatus exists, increment it
        if ($newStatus && isset($summary->{$newStatus})) {
            $summary->{$newStatus} += 1;
        }

        $summary->save();
    }

    public function created(Attendance $attendance): void
    {
        $this->updateSummary($attendance, null, $attendance->status);
    }

    public function updated(Attendance $attendance): void
    {
        if ($attendance->wasChanged('status') || $attendance->wasChanged('attendance_date') || $attendance->wasChanged('employee_id')) {
            if ($attendance->wasChanged('attendance_date') || $attendance->wasChanged('employee_id')) {
                // Remove from old
                $oldDate = clone \Carbon\Carbon::parse($attendance->getOriginal('attendance_date'));
                $oldEmpId = $attendance->getOriginal('employee_id');
                
                $oldSummary = \App\Models\AttendanceMonthlySummary::where('employee_id', $oldEmpId)
                    ->where('year', $oldDate->year)
                    ->where('month', $oldDate->month)
                    ->first();
                    
                if ($oldSummary && isset($oldSummary->{$attendance->getOriginal('status')})) {
                    $oldSummary->{$attendance->getOriginal('status')} = max(0, $oldSummary->{$attendance->getOriginal('status')} - 1);
                    $oldSummary->save();
                }
                
                // Add to new
                $this->updateSummary($attendance, null, $attendance->status);
            } else {
                // Only status changed
                $this->updateSummary($attendance, $attendance->getOriginal('status'), $attendance->status);
            }
        }
    }

    public function deleted(Attendance $attendance): void
    {
        $this->updateSummary($attendance, $attendance->status, null);
    }

    public function restored(Attendance $attendance): void
    {
        $this->updateSummary($attendance, null, $attendance->status);
    }

    public function forceDeleted(Attendance $attendance): void
    {
        $this->updateSummary($attendance, $attendance->status, null);
    }
}
