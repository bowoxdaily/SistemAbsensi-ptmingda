<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixJoinDateAttendanceStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:fix-join-date {--dry-run : Only show affected records without updating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix past attendance records on employee join_date so they are marked as hadir with 0 late minutes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $query = DB::table('attendances')
            ->join('employees', 'employees.id', '=', 'attendances.employee_id')
            ->whereNotNull('employees.join_date')
            ->whereRaw('DATE(attendances.attendance_date) = DATE(employees.join_date)')
            ->where(function ($q) {
                $q->where('attendances.status', 'terlambat')
                  ->orWhere('attendances.late_minutes', '>', 0);
            })
            ->select(
                'attendances.id',
                'attendances.employee_id',
                'employees.employee_code',
                'employees.name',
                'attendances.attendance_date',
                'attendances.check_in',
                'attendances.status',
                'attendances.late_minutes'
            );

        $affected = $query->get();

        if ($affected->isEmpty()) {
            $this->info('No late attendance records found on employee join dates.');
            return 0;
        }

        $this->info("Found {$affected->count()} record(s) on join date with late status/minutes:");
        
        $tableData = $affected->map(function ($item) {
            return [
                'ID' => $item->id,
                'NIP' => $item->employee_code,
                'Nama' => $item->name,
                'Tanggal' => substr((string)$item->attendance_date, 0, 10),
                'Check In' => $item->check_in,
                'Status Lama' => $item->status,
                'Late Min' => $item->late_minutes,
            ];
        })->toArray();

        $this->table(['ID', 'NIP', 'Nama', 'Tanggal', 'Check In', 'Status Lama', 'Late Min'], $tableData);

        if ($dryRun) {
            $this->warn('Dry-run mode active. No database updates were executed.');
            return 0;
        }

        $affectedIds = $affected->pluck('id')->toArray();

        $updatedCount = DB::table('attendances')
            ->whereIn('id', $affectedIds)
            ->update([
                'status' => 'hadir',
                'late_minutes' => 0,
                'updated_at' => now(),
            ]);

        $this->info("Successfully updated {$updatedCount} attendance record(s) to status 'hadir' and late_minutes = 0.");

        return 0;
    }
}
