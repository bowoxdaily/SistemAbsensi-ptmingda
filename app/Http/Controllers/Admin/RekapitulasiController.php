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

    /**
     * Display geographic-based rekapitulasi page
     */
    public function geographicIndex()
    {
        return view('admin.rekapitulasi.geographic');
    }

    /**
     * Get rekapitulasi data grouped by geographic locations (Desa, Kecamatan, Kabupaten, Provinsi)
     */
    public function getGeographicData(Request $request)
    {
        $groupLevel = $request->get('group_level', 'kabupaten'); // 'provinsi', 'kabupaten', 'kecamatan', 'desa'
        $province = $request->get('province');
        $kabupaten = $request->get('kabupaten');
        $kecamatan = $request->get('kecamatan');
        
        try {
            // Get all active employees with geographic filtering
            $query = Karyawans::where('status', 'active')
                ->with(['department', 'position']);

            if ($province) {
                $query->where('province', $province);
            }
            if ($kabupaten) {
                $query->where('kabupaten', $kabupaten);
            }
            if ($kecamatan) {
                $query->where('kecamatan', $kecamatan);
            }

            $employees = $query->orderBy('province')
                ->orderBy('kabupaten')
                ->orderBy('kecamatan')
                ->orderBy('desa')
                ->orderBy('name')
                ->get();

            // Group employees by specified level
            $grouped = $this->groupEmployeesByLevel($employees, $groupLevel);

            // Calculate statistics for each group - NO nested employees array
            $rekapitulasi = [];
            foreach ($grouped as $location => $groupEmployees) {
                $stats = $this->calculateLocationStats($groupEmployees);
                
                $rekapitulasi[] = [
                    'location' => $location,
                    'total_karyawan' => count($groupEmployees),
                    'active_count' => $stats['active'],
                    'inactive_count' => $stats['inactive'],
                    'resign_count' => $stats['resign'],
                    'departments' => $stats['departments'],
                    'positions' => $stats['positions'],
                    // Store employee IDs separately for detail modal (not in main table)
                    '_employee_ids' => $groupEmployees->pluck('id')->toArray(),
                ];
            }

            // Calculate summary
            $summary = [
                'total_karyawan' => $employees->count(),
                'total_active' => $employees->where('status', 'active')->count(),
                'total_inactive' => $employees->where('status', 'inactive')->count(),
                'total_resign' => $employees->where('status', 'resign')->count(),
                'group_count' => count($rekapitulasi),
            ];

            // Get available locations for filters
            $locations = $this->getAvailableLocations();

            return response()->json([
                'success' => true,
                'data' => $rekapitulasi,
                'summary' => $summary,
                'group_level' => $groupLevel,
                'locations' => $locations,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getGeographicData: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching geographic data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Group employees by geographic level
     */
    private function groupEmployeesByLevel($employees, $level)
    {
        $grouped = collect();

        foreach ($employees as $employee) {
            $key = match($level) {
                'provinsi' => $employee->province ?? 'Unknown',
                'kabupaten' => ($employee->province ?? 'Unknown') . ' - ' . ($employee->kabupaten ?? 'Unknown'),
                'kecamatan' => ($employee->province ?? 'Unknown') . ' - ' . ($employee->kabupaten ?? 'Unknown') . ' - ' . ($employee->kecamatan ?? 'Unknown'),
                'desa' => ($employee->province ?? 'Unknown') . ' - ' . ($employee->kabupaten ?? 'Unknown') . ' - ' . ($employee->kecamatan ?? 'Unknown') . ' - ' . ($employee->desa ?? 'Unknown'),
                default => $employee->province ?? 'Unknown',
            };

            if (!$grouped->has($key)) {
                $grouped->put($key, collect());
            }

            $grouped->get($key)->push($employee);
        }

        return $grouped;
    }

    /**
     * Calculate statistics for a group of employees
     */
    private function calculateLocationStats($employees)
    {
        $departments = [];
        $positions = [];

        foreach ($employees as $employee) {
            $dept = $employee->department->name ?? '-';
            $pos = $employee->position->name ?? '-';

            if (!isset($departments[$dept])) {
                $departments[$dept] = 0;
            }
            $departments[$dept]++;

            if (!isset($positions[$pos])) {
                $positions[$pos] = 0;
            }
            $positions[$pos]++;
        }

        return [
            'active' => $employees->where('status', 'active')->count(),
            'inactive' => $employees->where('status', 'inactive')->count(),
            'resign' => $employees->where('status', 'resign')->count(),
            'departments' => $departments,
            'positions' => $positions,
        ];
    }

    /**
     * Get available geographic locations for filter
     */
    private function getAvailableLocations()
    {
        $provinces = Karyawans::where('status', 'active')
            ->whereNotNull('province')
            ->distinct()
            ->pluck('province')
            ->sort()
            ->values();

        $kabupatens = Karyawans::where('status', 'active')
            ->whereNotNull('kabupaten')
            ->distinct()
            ->pluck('kabupaten')
            ->sort()
            ->values();

        $kecamatans = Karyawans::where('status', 'active')
            ->whereNotNull('kecamatan')
            ->distinct()
            ->pluck('kecamatan')
            ->sort()
            ->values();

        $desas = Karyawans::where('status', 'active')
            ->whereNotNull('desa')
            ->distinct()
            ->pluck('desa')
            ->sort()
            ->values();

        return [
            'provinces' => $provinces,
            'kabupatens' => $kabupatens,
            'kecamatans' => $kecamatans,
            'desas' => $desas,
        ];
    }

    /**
     * Export geographic recap to Excel
     */
    public function exportGeographicExcel(Request $request)
    {
        // Export can be memory intensive on large datasets.
        @ini_set('memory_limit', '1024M');
        @set_time_limit(300);

        $groupLevel = $request->get('group_level', 'kabupaten');
        $province = $request->get('province');
        $kabupaten = $request->get('kabupaten');
        $kecamatan = $request->get('kecamatan');

        // Get data
        $query = Karyawans::where('status', 'active')
            ->with(['department', 'position']);

        if ($province) {
            $query->where('province', $province);
        }
        if ($kabupaten) {
            $query->where('kabupaten', $kabupaten);
        }
        if ($kecamatan) {
            $query->where('kecamatan', $kecamatan);
        }

        $employees = $query->orderBy('province')
            ->orderBy('kabupaten')
            ->orderBy('kecamatan')
            ->orderBy('desa')
            ->orderBy('name')
            ->get();

        $grouped = $this->groupEmployeesByLevel($employees, $groupLevel);

        $filename = 'Rekap_Karyawan_' . ucfirst($groupLevel) . '_' . now()->format('Y-m-d_His') . '.xlsx';

        try {
            return Excel::download(
                new \App\Exports\GeographicRekapExport([
                    'grouped_data' => $grouped,
                    'group_level' => $groupLevel,
                    'filters' => [
                        'province' => $province,
                        'kabupaten' => $kabupaten,
                        'kecamatan' => $kecamatan,
                    ]
                ]),
                $filename
            );
        } catch (\Throwable $e) {
            Log::error('Error in exportGeographicExcel: ' . $e->getMessage(), [
                'group_level' => $groupLevel,
                'province' => $province,
                'kabupaten' => $kabupaten,
                'kecamatan' => $kecamatan,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal export data geografis. Silakan coba lagi atau sempitkan filter wilayah.',
            ], 500);
        }
    }

    /**
     * Get employee detail for geographic location
     * Called when user clicks "Detail" button to see employees in a location
     */
    public function getGeographicLocationDetail(Request $request)
    {
        $groupLevel = $request->get('group_level', 'kabupaten');
        $location = $request->get('location'); // Full location string like "JAWA BARAT - INDRAMAYU"

        try {
            // Parse location based on groupLevel
            $filters = $this->parseLocationString($location, $groupLevel);

            // Query employees with those filters
            $query = Karyawans::where('status', 'active')
                ->with(['department', 'position']);

            if (isset($filters['province'])) {
                $query->where('province', $filters['province']);
            }
            if (isset($filters['kabupaten'])) {
                $query->where('kabupaten', $filters['kabupaten']);
            }
            if (isset($filters['kecamatan'])) {
                $query->where('kecamatan', $filters['kecamatan']);
            }
            if (isset($filters['desa'])) {
                $query->where('desa', $filters['desa']);
            }

            $employees = $query->orderBy('employee_code')->get();

            // Format response
            $data = $employees->map(function($e) {
                $joinDate = $e->join_date ?? null;
                $joinDateFormatted = $joinDate 
                    ? (is_string($joinDate) ? $joinDate : $joinDate->format('Y-m-d'))
                    : null;

                return [
                    'id' => $e->id,
                    'code' => $e->employee_code,
                    'name' => $e->name,
                    'department' => $e->department->name ?? '-',
                    'position' => $e->position->name ?? '-',
                    'join_date' => $joinDateFormatted,
                    'status' => $e->status,
                    'province' => $e->province ?? '-',
                    'kabupaten' => $e->kabupaten ?? '-',
                    'kecamatan' => $e->kecamatan ?? '-',
                    'desa' => $e->desa ?? '-',
                ];
            });

            return response()->json([
                'success' => true,
                'location' => $location,
                'group_level' => $groupLevel,
                'total_count' => $employees->count(),
                'employees' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getGeographicLocationDetail: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching location details: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Parse location string to extract individual location components
     * Examples:
     * - "JAWA BARAT" (provinsi level)
     * - "JAWA BARAT - INDRAMAYU" (kabupaten level)
     * - "JAWA BARAT - INDRAMAYU - LOSARANG" (kecamatan level)
     * - "JAWA BARAT - INDRAMAYU - LOSARANG - LOSARANG" (desa level)
     */
    private function parseLocationString($location, $groupLevel)
    {
        $parts = array_map('trim', explode(' - ', $location));

        $filters = [];

        if (count($parts) >= 1) {
            $filters['province'] = $parts[0];
        }
        if (count($parts) >= 2) {
            $filters['kabupaten'] = $parts[1];
        }
        if (count($parts) >= 3) {
            $filters['kecamatan'] = $parts[2];
        }
        if (count($parts) >= 4) {
            $filters['desa'] = $parts[3];
        }

        return $filters;
    }
}
