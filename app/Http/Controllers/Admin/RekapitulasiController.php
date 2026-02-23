<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Karyawans;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class RekapitulasiController extends Controller
{
    /**
     * Display rekapitulasi page
     */
    public function index()
    {
        return view('admin.rekapitulasi.index');
    }

    /**
     * Get rekapitulasi data
     */
    public function getData(Request $request)
    {
        $periodType = $request->get('period_type', 'monthly'); // 'monthly', 'quarterly', or 'range'
        $month = $request->get('month', now()->month);
        $quarter = $request->get('quarter', now()->quarter);
        $year = $request->get('year', now()->year);
        $rangeFromMonth = $request->get('range_from_month');
        $rangeFromYear = $request->get('range_from_year');
        $rangeToMonth = $request->get('range_to_month');
        $rangeToYear = $request->get('range_to_year');
        $departmentId = $request->get('department_id');
        $positionId = $request->get('position_id');
        $employeeId = $request->get('employee_id');
        $joinDateFrom = $request->get('join_date_from');
        $joinDateTo = $request->get('join_date_to');

        Log::info('RekapitulasiController::getData called', [
            'period_type' => $periodType,
            'month' => $month,
            'quarter' => $quarter,
            'year' => $year,
            'range_from_month' => $rangeFromMonth,
            'range_from_year' => $rangeFromYear,
            'range_to_month' => $rangeToMonth,
            'range_to_year' => $rangeToYear,
            'department_id' => $departmentId,
            'position_id' => $positionId,
            'employee_id' => $employeeId,
            'join_date_from' => $joinDateFrom,
            'join_date_to' => $joinDateTo,
        ]);

        // Get all active employees with filters
        $employees = Karyawans::where('status', 'active')
            ->with(['department', 'position', 'workSchedule'])
            ->when($employeeId, function($q) use ($employeeId) {
                return $q->where('id', $employeeId);
            })
            ->when($departmentId, function($q) use ($departmentId) {
                return $q->where('department_id', $departmentId);
            })
            ->when($positionId, function($q) use ($positionId) {
                return $q->where('position_id', $positionId);
            })
            ->when($joinDateFrom, function($q) use ($joinDateFrom) {
                return $q->whereDate('join_date', '>=', $joinDateFrom);
            })
            ->when($joinDateTo, function($q) use ($joinDateTo) {
                return $q->whereDate('join_date', '<=', $joinDateTo);
            })
            ->orderBy('employee_code')
            ->get();

        // Calculate date range based on period type
        if ($periodType === 'quarterly') {
            // Calculate quarter start and end dates
            $quarterStartMonth = ($quarter - 1) * 3 + 1;
            $startDate = Carbon::createFromDate($year, $quarterStartMonth, 1)->startOfMonth();
            $endDate = $startDate->copy()->addMonths(2)->endOfMonth();
        } elseif ($periodType === 'range') {
            // Custom month range (can span across years)
            $startDate = Carbon::createFromDate($rangeFromYear, $rangeFromMonth, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($rangeToYear, $rangeToMonth, 1)->endOfMonth();
        } else {
            // Monthly view
            $startDate = Carbon::createFromDate($year, $month, 1);
            $endDate = $startDate->copy()->endOfMonth();
        }
        
        $rekapitulasi = [];
        $totalStats = [
            'hadir' => 0,
            'terlambat' => 0,
            'izin' => 0,
            'sakit' => 0,
            'cuti' => 0,
            'alpha' => 0,
        ];

        foreach ($employees as $employee) {
            // Count attendance by status within the date range
            $attendances = Attendance::where('employee_id', $employee->id)
                ->whereBetween('attendance_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->get();

            $stats = [
                'hadir' => $attendances->where('status', 'hadir')->count(),
                'terlambat' => $attendances->where('status', 'terlambat')->count(),
                'izin' => $attendances->where('status', 'izin')->count(),
                'sakit' => $attendances->where('status', 'sakit')->count(),
                'cuti' => $attendances->where('status', 'cuti')->count(),
                'alpha' => $attendances->where('status', 'alpha')->count(),
            ];

            // Calculate working days for this employee based on their work schedule
            $workingDays = $this->calculateWorkingDays($employee, $startDate, $endDate);
            
            // Total present (hadir + terlambat)
            $totalPresent = $stats['hadir'] + $stats['terlambat'];
            
            // Attendance percentage
            $percentage = $workingDays > 0 ? round(($totalPresent / $workingDays) * 100, 1) : 0;

            $rekapitulasi[] = [
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'name' => $employee->name,
                'department' => $employee->department->name ?? '-',
                'position' => $employee->position->name ?? '-',
                'hadir' => $stats['hadir'],
                'terlambat' => $stats['terlambat'],
                'izin' => $stats['izin'],
                'sakit' => $stats['sakit'],
                'cuti' => $stats['cuti'],
                'alpha' => $stats['alpha'],
                'total_present' => $totalPresent,
                'working_days' => $workingDays,
                'percentage' => $percentage,
            ];

            // Accumulate totals
            foreach ($stats as $key => $value) {
                $totalStats[$key] += $value;
            }
        }

        $summary = [
            'total_karyawan' => $employees->count(),
            'total_hadir' => $totalStats['hadir'],
            'total_terlambat' => $totalStats['terlambat'],
            'total_izin' => $totalStats['izin'],
            'total_sakit' => $totalStats['sakit'],
            'total_cuti' => $totalStats['cuti'],
            'total_alpha' => $totalStats['alpha'],
            'avg_attendance' => $employees->count() > 0 
                ? round(collect($rekapitulasi)->avg('percentage'), 1) 
                : 0,
        ];

        // Format period name
        if ($periodType === 'quarterly') {
            $periodName = 'Kuartal ' . $quarter . ' ' . $year . ' (' . 
                $startDate->translatedFormat('F') . ' - ' . 
                $endDate->translatedFormat('F Y') . ')';
        } elseif ($periodType === 'range') {
            $periodName = $startDate->translatedFormat('F Y') . ' - ' . $endDate->translatedFormat('F Y');
        } else {
            $periodName = $startDate->translatedFormat('F Y');
        }

        return response()->json([
            'success' => true,
            'data' => $rekapitulasi,
            'summary' => $summary,
            'period' => [
                'type' => $periodType,
                'month' => $month,
                'quarter' => $quarter,
                'year' => $year,
                'period_name' => $periodName,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]
        ]);
    }

    /**
     * Calculate working days for employee based on work schedule
     */
    private function calculateWorkingDays($employee, $startDate, $endDate)
    {
        if (!$employee->workSchedule) {
            // Default to weekdays if no schedule
            $weekdays = $this->countWeekdays($startDate, $endDate);
            Log::info("Employee {$employee->employee_code} has no work schedule, using weekdays: {$weekdays}");
            return $weekdays;
        }

        $schedule = $employee->workSchedule;
        $workingDays = 0;
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dayOfWeek = strtolower($current->format('l'));
            $workColumn = 'work_' . $dayOfWeek;
            
            if (isset($schedule->$workColumn) && $schedule->$workColumn) {
                $workingDays++;
            }
            
            $current->addDay();
        }

        Log::info("Employee {$employee->employee_code} calculated working days: {$workingDays}", [
            'schedule_days' => [
                'monday' => $schedule->work_monday ?? null,
                'tuesday' => $schedule->work_tuesday ?? null,
                'wednesday' => $schedule->work_wednesday ?? null,
                'thursday' => $schedule->work_thursday ?? null,
                'friday' => $schedule->work_friday ?? null,
                'saturday' => $schedule->work_saturday ?? null,
                'sunday' => $schedule->work_sunday ?? null,
            ]
        ]);

        // Fallback if workSchedule exists but has no working days configured
        if ($workingDays === 0) {
            Log::warning("Work schedule exists for {$employee->employee_code} but no working days configured, falling back to weekdays");
            return $this->countWeekdays($startDate, $endDate);
        }

        return $workingDays;
    }

    /**
     * Count weekdays (Mon-Fri) in date range
     */
    private function countWeekdays($startDate, $endDate)
    {
        $count = 0;
        $current = $startDate->copy();

        while ($current <= $endDate) {
            if ($current->isWeekday()) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    /**
     * Get filter options (departments & positions)
     */
    public function getFilterOptions()
    {
        $departments = Department::orderBy('name')->get(['id', 'name']);
        $positions = Position::where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $employees = Karyawans::where('status', 'active')
            ->orderBy('employee_code')
            ->get(['id', 'name', 'employee_code']);

        return response()->json([
            'success' => true,
            'departments' => $departments,
            'positions' => $positions,
            'employees' => $employees,
        ]);
    }

    /**
     * Export to Excel
     */
    public function exportExcel(Request $request)
    {
        $periodType = $request->get('period_type', 'monthly');
        $month = $request->get('month', now()->month);
        $quarter = $request->get('quarter', now()->quarter);
        $year = $request->get('year', now()->year);
        $rangeFromMonth = $request->get('range_from_month');
        $rangeFromYear = $request->get('range_from_year');
        $rangeToMonth = $request->get('range_to_month');
        $rangeToYear = $request->get('range_to_year');
        $departmentId = $request->get('department_id');
        $positionId = $request->get('position_id');
        $employeeId = $request->get('employee_id');
        $joinDateFrom = $request->get('join_date_from');
        $joinDateTo = $request->get('join_date_to');

        if ($periodType === 'quarterly') {
            $periodName = 'Q' . $quarter . '_' . $year;
        } elseif ($periodType === 'range') {
            $startDate = Carbon::createFromDate($rangeFromYear, $rangeFromMonth, 1);
            $endDate = Carbon::createFromDate($rangeToYear, $rangeToMonth, 1);
            $periodName = $startDate->format('M_Y') . '_to_' . $endDate->format('M_Y');
        } else {
            $periodName = Carbon::createFromDate($year, $month, 1)->translatedFormat('F_Y');
        }
        
        $filename = 'Rekapitulasi_Absensi_' . $periodName . '.xlsx';

        return Excel::download(
            new \App\Exports\RekapitulasiExport([
                'period_type' => $periodType,
                'month' => $month,
                'quarter' => $quarter,
                'year' => $year,
                'range_from_month' => $rangeFromMonth,
                'range_from_year' => $rangeFromYear,
                'range_to_month' => $rangeToMonth,
                'range_to_year' => $rangeToYear,
                'department_id' => $departmentId,
                'position_id' => $positionId,
                'employee_id' => $employeeId,
                'join_date_from' => $joinDateFrom,
                'join_date_to' => $joinDateTo,
            ]),
            $filename
        );
    }

    /**
     * Export to PDF (using browser print)
     * Returns a printable HTML view instead of PDF
     */
    public function exportPdf(Request $request)
    {
        $periodType = $request->get('period_type', 'monthly');
        $month = $request->get('month', now()->month);
        $quarter = $request->get('quarter', now()->quarter);
        $year = $request->get('year', now()->year);
        $rangeFromMonth = $request->get('range_from_month');
        $rangeFromYear = $request->get('range_from_year');
        $rangeToMonth = $request->get('range_to_month');
        $rangeToYear = $request->get('range_to_year');
        $departmentId = $request->get('department_id');
        $positionId = $request->get('position_id');
        $employeeId = $request->get('employee_id');
        $joinDateFrom = $request->get('join_date_from');
        $joinDateTo = $request->get('join_date_to');

        // Get data
        $employees = Karyawans::where('status', 'active')
            ->with(['department', 'position', 'workSchedule'])
            ->when($employeeId, function($q) use ($employeeId) {
                return $q->where('id', $employeeId);
            })
            ->when($departmentId, function($q) use ($departmentId) {
                return $q->where('department_id', $departmentId);
            })
            ->when($positionId, function($q) use ($positionId) {
                return $q->where('position_id', $positionId);
            })
            ->when($joinDateFrom, function($q) use ($joinDateFrom) {
                return $q->whereDate('join_date', '>=', $joinDateFrom);
            })
            ->when($joinDateTo, function($q) use ($joinDateTo) {
                return $q->whereDate('join_date', '<=', $joinDateTo);
            })
            ->orderBy('employee_code')
            ->get();

        // Calculate date range based on period type
        if ($periodType === 'quarterly') {
            $quarterStartMonth = ($quarter - 1) * 3 + 1;
            $startDate = Carbon::createFromDate($year, $quarterStartMonth, 1)->startOfMonth();
            $endDate = $startDate->copy()->addMonths(2)->endOfMonth();
        } elseif ($periodType === 'range') {
            $startDate = Carbon::createFromDate($rangeFromYear, $rangeFromMonth, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($rangeToYear, $rangeToMonth, 1)->endOfMonth();
        } else {
            $startDate = Carbon::createFromDate($year, $month, 1);
            $endDate = $startDate->copy()->endOfMonth();
        }
        
        $rekapitulasi = [];
        foreach ($employees as $employee) {
            $attendances = Attendance::where('employee_id', $employee->id)
                ->whereBetween('attendance_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->get();

            $stats = [
                'hadir' => $attendances->where('status', 'hadir')->count(),
                'terlambat' => $attendances->where('status', 'terlambat')->count(),
                'izin' => $attendances->where('status', 'izin')->count(),
                'sakit' => $attendances->where('status', 'sakit')->count(),
                'cuti' => $attendances->where('status', 'cuti')->count(),
                'alpha' => $attendances->where('status', 'alpha')->count(),
            ];

            $workingDays = $this->calculateWorkingDays($employee, $startDate, $endDate);
            $totalPresent = $stats['hadir'] + $stats['terlambat'];
            $percentage = $workingDays > 0 ? round(($totalPresent / $workingDays) * 100, 1) : 0;

            $rekapitulasi[] = [
                'employee_code' => $employee->employee_code,
                'name' => $employee->name,
                'department' => $employee->department->name ?? '-',
                'position' => $employee->position->name ?? '-',
                'hadir' => $stats['hadir'],
                'terlambat' => $stats['terlambat'],
                'izin' => $stats['izin'],
                'sakit' => $stats['sakit'],
                'cuti' => $stats['cuti'],
                'alpha' => $stats['alpha'],
                'total_present' => $totalPresent,
                'working_days' => $workingDays,
                'percentage' => $percentage,
            ];
        }

        // Format period name
        if ($periodType === 'quarterly') {
            $periodName = 'Kuartal ' . $quarter . ' ' . $year . ' (' . 
                $startDate->translatedFormat('F') . ' - ' . 
                $endDate->translatedFormat('F Y') . ')';
        } elseif ($periodType === 'range') {
            $periodName = $startDate->translatedFormat('F Y') . ' - ' . $endDate->translatedFormat('F Y');
        } else {
            $periodName = $startDate->translatedFormat('F Y');
        }

        $data = [
            'rekapitulasi' => $rekapitulasi,
            'period' => $periodName,
            'generated_at' => now()->translatedFormat('d F Y H:i'),
        ];

        // Return printable view instead of PDF download
        // User can use browser's print to PDF feature
        return view('admin.rekapitulasi.pdf', $data);
    }
}
