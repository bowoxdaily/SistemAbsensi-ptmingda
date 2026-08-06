<?php

namespace App\Observers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AttendanceObserver
{
    /**
     * Hapus cache Redis untuk summary attendance karyawan pada bulan terkait.
     * Dipanggil setiap kali record attendance berubah agar cache tidak stale.
     */
    private function invalidateSummaryCache(Attendance $attendance, ?string $overrideEmployeeId = null, ?string $overrideDate = null): void
    {
        $employeeId = $overrideEmployeeId ?? $attendance->employee_id;
        $date       = Carbon::parse($overrideDate ?? $attendance->attendance_date);

        // Hapus cache key yang dibuat oleh AttendanceController::summary()
        Cache::forget("attendance_summary:{$employeeId}:{$date->year}:{$date->month}");

        // Hapus juga cache overtime_weekly jika ada (TTL 10 menit)
        $weekStart = $date->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        Cache::forget("overtime_weekly:{$employeeId}:{$weekStart}");
    }

    private function updateSummary(Attendance $attendance, $oldStatus, $newStatus): void
    {
        if (!$attendance->attendance_date || !$attendance->employee_id) {
            return;
        }

        $date = Carbon::parse($attendance->attendance_date);
        
        $summary = \App\Models\AttendanceMonthlySummary::firstOrCreate(
            [
                'employee_id' => $attendance->employee_id,
                'year'        => $date->year,
                'month'       => $date->month,
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
        $this->invalidateSummaryCache($attendance);
    }

    public function updated(Attendance $attendance): void
    {
        if ($attendance->wasChanged('status') || $attendance->wasChanged('attendance_date') || $attendance->wasChanged('employee_id')) {
            if ($attendance->wasChanged('attendance_date') || $attendance->wasChanged('employee_id')) {
                // Invalidate cache untuk data LAMA (sebelum perubahan)
                $this->invalidateSummaryCache(
                    $attendance,
                    (string) $attendance->getOriginal('employee_id'),
                    (string) $attendance->getOriginal('attendance_date')
                );

                $oldDate  = clone Carbon::parse($attendance->getOriginal('attendance_date'));
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

        // Selalu invalidate cache untuk data BARU setelah update
        $this->invalidateSummaryCache($attendance);
    }

    public function deleted(Attendance $attendance): void
    {
        $this->updateSummary($attendance, $attendance->status, null);
        $this->invalidateSummaryCache($attendance);
    }

    public function restored(Attendance $attendance): void
    {
        $this->updateSummary($attendance, null, $attendance->status);
        $this->invalidateSummaryCache($attendance);
    }

    public function forceDeleted(Attendance $attendance): void
    {
        $this->updateSummary($attendance, $attendance->status, null);
        $this->invalidateSummaryCache($attendance);
    }
}
