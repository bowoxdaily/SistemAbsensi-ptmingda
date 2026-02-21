<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Karyawans;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $departmentId = $request->get('department_id');
        $positionId = $request->get('position_id');
        $employeeId = $request->get('employee_id');

        \Log::info('RekapitulasiController::getData called', [
            'month' => $month,
            'year' => $year,
            'department_id' => $departmentId,
            'position_id' => $positionId,
            'employee_id' => $employeeId
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
            ->orderBy('employee_code')
            ->get();

        // Calculate total working days in the month
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        
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
            // Count attendance by status
            $attendances = Attendance::where('employee_id', $employee->id)
                ->whereYear('attendance_date', $year)
                ->whereMonth('attendance_date', $month)
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

        return response()->json([
            'success' => true,
            'data' => $rekapitulasi,
            'summary' => $summary,
            'period' => [
                'month' => $month,
                'year' => $year,
                'month_name' => Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'),
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
            \Log::info("Employee {$employee->employee_code} has no work schedule, using weekdays: {$weekdays}");
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

        \Log::info("Employee {$employee->employee_code} calculated working days: {$workingDays}", [
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
            \Log::warning("Work schedule exists for {$employee->employee_code} but no working days configured, falling back to weekdays");
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
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $departmentId = $request->get('department_id');
        $positionId = $request->get('position_id');
        $employeeId = $request->get('employee_id');

        $monthName = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');
        $filename = 'Rekapitulasi_Absensi_' . str_replace(' ', '_', $monthName) . '.xlsx';

        return \Excel::download(
            new \App\Exports\RekapitulasiExport($month, $year, $departmentId, $positionId, $employeeId),
            $filename
        );
    }

    /**
     * Export to PDF (using browser print)
     * Returns a printable HTML view instead of PDF
     */
    public function exportPdf(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $departmentId = $request->get('department_id');
        $positionId = $request->get('position_id');
        $employeeId = $request->get('employee_id');

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
            ->orderBy('employee_code')
            ->get();

        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        
        $rekapitulasi = [];
        foreach ($employees as $employee) {
            $attendances = Attendance::where('employee_id', $employee->id)
                ->whereYear('attendance_date', $year)
                ->whereMonth('attendance_date', $month)
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

        $data = [
            'rekapitulasi' => $rekapitulasi,
            'period' => Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'),
            'generated_at' => now()->translatedFormat('d F Y H:i'),
        ];

        // Return printable view instead of PDF download
        // User can use browser's print to PDF feature
        return view('admin.rekapitulasi.pdf', $data);
    }
}
