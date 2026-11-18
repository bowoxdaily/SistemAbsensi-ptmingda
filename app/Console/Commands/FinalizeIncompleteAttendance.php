<?php

namespace App\Console\Commands;

use App\Jobs\SendAlphaNotificationEmailJob;
use App\Models\Attendance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FinalizeIncompleteAttendance extends Command
{
    protected $signature = 'attendance:finalize-incomplete';

    protected $description = 'Mark eligible incomplete attendances as alpha after two weekdays unless clarification exists.';

    public function handle(): int
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $cutoff = now()->subWeekdays(2)->toDateString();
        $delaySeconds = (int) round(60 / max(1, (int) config('mail.outbound_rate_per_minute', 12)));
        $emailIndex = 0;

        Attendance::query()
            ->whereDate('attendance_date', '>=', $monthStart)
            ->whereDate('attendance_date', '<=', $cutoff)
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->whereIn('status', ['hadir', 'terlambat'])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('attendance_clarifications')
                    ->whereColumn('attendance_clarifications.attendance_id', 'attendances.id');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('attendance_edit_requests')
                    ->whereColumn('attendance_edit_requests.attendance_id', 'attendances.id');
            })
            ->orderBy('id')
            ->chunkById(100, function ($attendances) use (&$emailIndex, $delaySeconds) {
                foreach ($attendances as $attendance) {
                    $attendance->status = 'alpha';
                    $attendance->late_minutes = 0;
                    $attendance->save();

                    SendAlphaNotificationEmailJob::dispatch($attendance->id)
                        ->delay(now()->addSeconds($emailIndex++ * $delaySeconds));
                }
            });

        $this->info("Finalized {$emailIndex} incomplete attendance record(s).");

        return self::SUCCESS;
    }
}
