<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Karyawans;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service untuk analytics dan insights data absensi
 */
class AnalyticsService
{
    /**
     * Get attendance overview untuk period tertentu
     * Returns: total hadir, terlambat, izin, sakit, alpha, cuti
     */
    public function getAttendanceOverview($startDate, $endDate)
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $data = Attendance::whereBetween('attendance_date', [$startDate, $endDate])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'hadir' => $data['hadir'] ?? 0,
            'terlambat' => $data['terlambat'] ?? 0,
            'izin' => $data['izin'] ?? 0,
            'sakit' => $data['sakit'] ?? 0,
            'alpha' => $data['alpha'] ?? 0,
            'cuti' => $data['cuti'] ?? 0,
            'total' => array_sum($data),
        ];
    }

    /**
     * Get daily attendance trend (untuk chart garis)
     */
    public function getAttendanceTrend($startDate, $endDate, $statusFilter = null)
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $query = Attendance::whereBetween('attendance_date', [$startDate, $endDate])
            ->selectRaw('DATE(attendance_date) as date, status, COUNT(*) as count')
            ->groupBy('date', 'status')
            ->orderBy('date');

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $raw = $query->get();

        // Transform ke format chart-friendly
        $dates = $raw->pluck('date')->unique()->sort();
        $statuses = $raw->pluck('status')->unique();

        $datasets = [];
        $colors = [
            'hadir' => '#10B981',      // green
            'terlambat' => '#F59E0B', // amber
            'izin' => '#3B82F6',       // blue
            'sakit' => '#F87171',       // red
            'alpha' => '#1F2937',      // gray-900
            'cuti' => '#8B5CF6',       // purple
        ];

        foreach ($statuses as $status) {
            $data = [];
            foreach ($dates as $date) {
                $count = $raw->where('date', $date)->where('status', $status)->first()?->count ?? 0;
                $data[] = $count;
            }

            $datasets[] = [
                'label' => ucfirst($status),
                'data' => $data,
                'borderColor' => $colors[$status] ?? '#6B7280',
                'backgroundColor' => $colors[$status] ?? '#6B7280',
                'fill' => false,
                'tension' => 0.1,
            ];
        }

        return [
            'labels' => $dates->map(fn($d) => Carbon::parse($d)->format('d M')),
            'datasets' => $datasets,
        ];
    }

    /**
     * Get attendance by department
     */
    public function getAttendanceByDepartment($startDate, $endDate)
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $data = Attendance::whereBetween('attendance_date', [$startDate, $endDate])
            ->join('employees', 'attendances.employee_id', '=', 'employees.id')
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->selectRaw('departments.name, attendances.status, COUNT(*) as count')
            ->groupBy('departments.name', 'attendances.status')
            ->get()
            ->groupBy('name')
            ->mapWithKeys(fn($items, $dept) => [
                $dept => [
                    'hadir' => $items->where('status', 'hadir')->sum('count'),
                    'terlambat' => $items->where('status', 'terlambat')->sum('count'),
                    'izin' => $items->where('status', 'izin')->sum('count'),
                    'sakit' => $items->where('status', 'sakit')->sum('count'),
                    'alpha' => $items->where('status', 'alpha')->sum('count'),
                    'cuti' => $items->where('status', 'cuti')->sum('count'),
                    'total' => $items->sum('count'),
                ]
            ])
            ->toArray();

        return $data;
    }

    /**
     * Get overtime statistics
     */
    public function getOvertimeStats($startDate, $endDate)
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $totalOvertimeMinutes = Attendance::whereBetween('attendance_date', [$startDate, $endDate])
            ->where('overtime_minutes', '>', 0)
            ->sum('overtime_minutes');

        $employeesWithOT = Attendance::whereBetween('attendance_date', [$startDate, $endDate])
            ->where('overtime_minutes', '>', 0)
            ->selectRaw('employee_id, SUM(overtime_minutes) as total_ot')
            ->groupBy('employee_id')
            ->with('employee')
            ->limit(10)
            ->get()
            ->map(fn($att) => [
                'employee_name' => $att->employee->name ?? 'Unknown',
                'total_ot_minutes' => $att->total_ot,
                'total_ot_hours' => round($att->total_ot / 60, 2),
            ])
            ->toArray();

        return [
            'total_ot_minutes' => $totalOvertimeMinutes,
            'total_ot_hours' => round($totalOvertimeMinutes / 60, 2),
            'employees_with_ot' => $employeesWithOT,
            'employee_count_with_ot' => count($employeesWithOT),
        ];
    }

    /**
     * Get top late employees
     */
    public function getTopLateEmployees($startDate, $endDate, $limit = 10)
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        return Attendance::whereBetween('attendance_date', [$startDate, $endDate])
            ->where('status', 'terlambat')
            ->selectRaw('employee_id, COUNT(*) as late_count, AVG(late_minutes) as avg_late_minutes')
            ->groupBy('employee_id')
            ->orderByDesc('late_count')
            ->limit($limit)
            ->with('employee')
            ->get()
            ->map(fn($att) => [
                'employee_id' => $att->employee_id,
                'employee_name' => $att->employee?->name ?? 'Unknown',
                'late_count' => $att->late_count,
                'avg_late_minutes' => round($att->avg_late_minutes, 1),
            ])
            ->toArray();
    }

    /**
     * Get top absent employees (alpha)
     */
    public function getTopAbsentEmployees($startDate, $endDate, $limit = 10)
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        return Attendance::whereBetween('attendance_date', [$startDate, $endDate])
            ->where('status', 'alpha')
            ->selectRaw('employee_id, COUNT(*) as alpha_count')
            ->groupBy('employee_id')
            ->orderByDesc('alpha_count')
            ->limit($limit)
            ->with('employee')
            ->get()
            ->map(fn($att) => [
                'employee_id' => $att->employee_id,
                'employee_name' => $att->employee?->name ?? 'Unknown',
                'alpha_count' => $att->alpha_count,
            ])
            ->toArray();
    }

    /**
     * Get attendance rate percentage
     */
    public function getAttendanceRate($startDate, $endDate)
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $overview = $this->getAttendanceOverview($startDate, $endDate);

        $presentCount = $overview['hadir'] + $overview['terlambat'];
        $total = $overview['total'];

        $presentRate = $total > 0 ? round(($presentCount / $total) * 100, 2) : 0;
        $onTimeRate = $total > 0 ? round(($overview['hadir'] / $total) * 100, 2) : 0;
        $absenceRate = $total > 0 ? round((($overview['alpha'] + $overview['sakit']) / $total) * 100, 2) : 0;

        return [
            'present_rate' => $presentRate,
            'on_time_rate' => $onTimeRate,
            'absence_rate' => $absenceRate,
            'leave_request_rate' => $total > 0 ? round((($overview['izin'] + $overview['cuti']) / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get heatmap data (attendance by day of week and hour)
     */
    public function getAttendanceHeatmap($startDate, $endDate)
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $data = Attendance::whereBetween('attendance_date', [$startDate, $endDate])
            ->whereNotNull('check_in')
            ->selectRaw('
                DAYNAME(attendance_date) as day_name,
                DAYOFWEEK(attendance_date) as day_of_week,
                HOUR(check_in) as hour,
                COUNT(*) as count
            ')
            ->groupBy('day_of_week', 'day_name', 'hour')
            ->orderBy('day_of_week')
            ->orderBy('hour')
            ->get();

        $heatmap = [];
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        foreach ($days as $day) {
            $hourData = [];
            for ($hour = 6; $hour <= 18; $hour++) {
                $count = $data->where('day_name', $day)->where('hour', $hour)->first()?->count ?? 0;
                $hourData[] = [
                    'hour' => "{$hour}:00",
                    'count' => $count,
                ];
            }
            $heatmap[$day] = $hourData;
        }

        return $heatmap;
    }

    /**
     * Get employee attendance performance per supervisor
     */
    public function getSupervisorPerformance($startDate, $endDate)
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $supervisors = Karyawans::whereNotNull('supervisor_id')
            ->distinct()
            ->pluck('supervisor_id')
            ->unique();

        $performance = [];

        foreach ($supervisors as $supervisorId) {
            $supervisor = Karyawans::find($supervisorId);
            if (!$supervisor) continue;

            $employeeIds = Karyawans::where('supervisor_id', $supervisorId)->pluck('id');

            $attendance = Attendance::whereBetween('attendance_date', [$startDate, $endDate])
                ->whereIn('employee_id', $employeeIds)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $total = array_sum($attendance);
            $presentCount = ($attendance['hadir'] ?? 0) + ($attendance['terlambat'] ?? 0);

            $performance[] = [
                'supervisor_id' => $supervisorId,
                'supervisor_name' => $supervisor->name,
                'employee_count' => $employeeIds->count(),
                'present_rate' => $total > 0 ? round(($presentCount / $total) * 100, 2) : 0,
                'on_time_count' => $attendance['hadir'] ?? 0,
                'late_count' => $attendance['terlambat'] ?? 0,
                'absent_count' => $attendance['alpha'] ?? 0,
                'sick_count' => $attendance['sakit'] ?? 0,
            ];
        }

        return collect($performance)->sortByDesc('present_rate')->values()->toArray();
    }
}
