<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RebuildAttendanceSummary extends Command
{
    protected $signature = 'attendance:rebuild-summary {year?} {month?}';
    protected $description = 'Rebuild attendance monthly summaries from raw attendances table';

    public function handle()
    {
        $year = $this->argument('year') ?? now()->year;
        $month = $this->argument('month') ?? now()->month;

        $this->info("Rebuilding summaries for {$year}-{$month}...");

        // Delete existing summaries for that month
        \App\Models\AttendanceMonthlySummary::where('year', $year)->where('month', $month)->delete();

        // Get aggregate data
        $attendances = \App\Models\Attendance::selectRaw('employee_id, status, count(*) as total')
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->groupBy('employee_id', 'status')
            ->get();

        $summaries = [];
        foreach ($attendances as $att) {
            if (!isset($summaries[$att->employee_id])) {
                $summaries[$att->employee_id] = [
                    'employee_id' => $att->employee_id,
                    'year' => $year,
                    'month' => $month,
                    'hadir' => 0,
                    'terlambat' => 0,
                    'izin' => 0,
                    'sakit' => 0,
                    'cuti' => 0,
                    'alpha' => 0,
                    'libur' => 0,
                    'cuti_bersama' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            $summaryStatus = in_array($att->status, ['cuti_tahunan', 'cuti_umrah'], true) ? 'cuti' : $att->status;
            if (array_key_exists($summaryStatus, $summaries[$att->employee_id])) {
                $summaries[$att->employee_id][$summaryStatus] += $att->total;
            }
        }

        if (!empty($summaries)) {
            foreach (array_chunk(array_values($summaries), 500) as $chunk) {
                \App\Models\AttendanceMonthlySummary::insert($chunk);
            }
        }

        $this->info('Successfully rebuilt ' . count($summaries) . ' employee summaries.');
    }
}
