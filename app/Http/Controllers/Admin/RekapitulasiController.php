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
        [$startDate, $endDate] = $this->resolveDateRange($periodType, $month, $quarter, $year, $rangeFromMonth, $rangeFromYear, $rangeToMonth, $rangeToYear);

        [$rekapitulasi, $totalStats] = $this->buildRekapData($employees, $startDate, $endDate);

        $summary = [
            'total_karyawan'   => $employees->count(),
            'total_hadir'      => $totalStats['hadir'],
            'total_terlambat'  => $totalStats['terlambat'],
            'total_izin'       => $totalStats['izin'],
            'total_sakit'      => $totalStats['sakit'],
            'total_cuti'       => $totalStats['cuti'],
            'total_alpha'      => $totalStats['alpha'],
            'avg_attendance'   => $employees->count() > 0
                ? round(collect($rekapitulasi)->avg('percentage'), 1)
                : 0,
        ];

        $periodName = $this->formatPeriodName($periodType, $quarter, $year, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data'    => $rekapitulasi,
            'summary' => $summary,
            'period'  => [
                'type'        => $periodType,
                'month'       => $month,
                'quarter'     => $quarter,
                'year'        => $year,
                'period_name' => $periodName,
                'start_date'  => $startDate->format('Y-m-d'),
                'end_date'    => $endDate->format('Y-m-d'),
            ]
        ]);
    }

    /**
     * Resolve start/end date dari periode yang dipilih.
     */
    private function resolveDateRange(
        string $periodType,
        $month, $quarter, $year,
        $rangeFromMonth, $rangeFromYear,
        $rangeToMonth, $rangeToYear
    ): array {
        if ($periodType === 'quarterly') {
            $quarterStartMonth = ($quarter - 1) * 3 + 1;
            $startDate = Carbon::createFromDate($year, $quarterStartMonth, 1)->startOfMonth();
            $endDate   = $startDate->copy()->addMonths(2)->endOfMonth();
        } elseif ($periodType === 'range') {
            $startDate = Carbon::createFromDate($rangeFromYear, $rangeFromMonth, 1)->startOfMonth();
            $endDate   = Carbon::createFromDate($rangeToYear, $rangeToMonth, 1)->endOfMonth();
        } else {
            $startDate = Carbon::createFromDate($year, $month, 1);
            $endDate   = $startDate->copy()->endOfMonth();
        }

        return [$startDate, $endDate];
    }

    /**
     * Format nama periode untuk tampilan.
     */
    private function formatPeriodName(string $periodType, $quarter, $year, $startDate, $endDate): string
    {
        if ($periodType === 'quarterly') {
            return 'Kuartal ' . $quarter . ' ' . $year . ' (' .
                $startDate->translatedFormat('F') . ' - ' .
                $endDate->translatedFormat('F Y') . ')';
        } elseif ($periodType === 'range') {
            return $startDate->translatedFormat('F Y') . ' - ' . $endDate->translatedFormat('F Y');
        }
        return $startDate->translatedFormat('F Y');
    }

    /**
     * Bangun data rekapitulasi dari kumpulan karyawan dan rentang tanggal.
     *
     * Menggunakan 1 bulk query attendance untuk semua karyawan (anti N+1),
     * lalu groupBy di PHP untuk hitung per-karyawan.
     */
    private function buildRekapData($employees, $startDate, $endDate): array
    {
        $employeeIds = $employees->pluck('id');

        // 1 query untuk semua attendance (bukan N query per karyawan)
        $allAttendances = Attendance::whereIn('employee_id', $employeeIds)
            ->whereBetween('attendance_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->select('employee_id', 'status')
            ->get()
            ->groupBy('employee_id');

        $rekapitulasi = [];
        $totalStats = [
            'hadir' => 0, 'terlambat' => 0,
            'izin'  => 0, 'sakit'     => 0,
            'cuti'  => 0, 'alpha'     => 0,
        ];

        foreach ($employees as $employee) {
            $attendances = $allAttendances->get($employee->id, collect());

            $stats = [
                'hadir'     => $attendances->where('status', 'hadir')->count(),
                'terlambat' => $attendances->where('status', 'terlambat')->count(),
                'izin'      => $attendances->where('status', 'izin')->count(),
                'sakit'     => $attendances->where('status', 'sakit')->count(),
                'cuti'      => $attendances->where('status', 'cuti')->count(),
                'alpha'     => $attendances->where('status', 'alpha')->count(),
            ];

            $workingDays  = $this->calculateWorkingDays($employee, $startDate, $endDate);
            $totalPresent = $stats['hadir'] + $stats['terlambat'];
            $percentage   = $workingDays > 0 ? round(($totalPresent / $workingDays) * 100, 1) : 0;

            $rekapitulasi[] = [
                'employee_id'   => $employee->id,
                'employee_code' => $employee->employee_code,
                'name'          => $employee->name,
                'department'    => $employee->department->name ?? '-',
                'position'      => $employee->position->name ?? '-',
                'hadir'         => $stats['hadir'],
                'terlambat'     => $stats['terlambat'],
                'izin'          => $stats['izin'],
                'sakit'         => $stats['sakit'],
                'cuti'          => $stats['cuti'],
                'alpha'         => $stats['alpha'],
                'total_present' => $totalPresent,
                'working_days'  => $workingDays,
                'percentage'    => $percentage,
            ];

            foreach ($stats as $key => $value) {
                $totalStats[$key] += $value;
            }
        }

        return [$rekapitulasi, $totalStats];
    }

    /**
     * Calculate working days for employee based on work schedule
     */
    private function calculateWorkingDays($employee, $startDate, $endDate)
    {
        if (!$employee->workSchedule) {
            return $this->countWeekdays($startDate, $endDate);
        }

        $schedule    = $employee->workSchedule;
        $workingDays = 0;
        $current     = $startDate->copy();

        while ($current <= $endDate) {
            $dayOfWeek  = strtolower($current->format('l'));
            $workColumn = 'work_' . $dayOfWeek;

            if (isset($schedule->$workColumn) && $schedule->$workColumn) {
                $workingDays++;
            }

            $current->addDay();
        }

        // Fallback jika schedule ada tapi tidak ada hari kerja terkonfigurasi
        if ($workingDays === 0) {
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
        $periodType     = $request->get('period_type', 'monthly');
        $month          = $request->get('month', now()->month);
        $quarter        = $request->get('quarter', now()->quarter);
        $year           = $request->get('year', now()->year);
        $rangeFromMonth = $request->get('range_from_month');
        $rangeFromYear  = $request->get('range_from_year');
        $rangeToMonth   = $request->get('range_to_month');
        $rangeToYear    = $request->get('range_to_year');
        $departmentId   = $request->get('department_id');
        $positionId     = $request->get('position_id');
        $employeeId     = $request->get('employee_id');
        $joinDateFrom   = $request->get('join_date_from');
        $joinDateTo     = $request->get('join_date_to');

        $employees = Karyawans::where('status', 'active')
            ->with(['department', 'position', 'workSchedule'])
            ->when($employeeId,    fn($q) => $q->where('id', $employeeId))
            ->when($departmentId,  fn($q) => $q->where('department_id', $departmentId))
            ->when($positionId,    fn($q) => $q->where('position_id', $positionId))
            ->when($joinDateFrom,  fn($q) => $q->whereDate('join_date', '>=', $joinDateFrom))
            ->when($joinDateTo,    fn($q) => $q->whereDate('join_date', '<=', $joinDateTo))
            ->orderBy('employee_code')
            ->get();

        [$startDate, $endDate] = $this->resolveDateRange(
            $periodType, $month, $quarter, $year,
            $rangeFromMonth, $rangeFromYear, $rangeToMonth, $rangeToYear
        );

        // Reuse buildRekapData — tidak ada duplikasi N+1 query
        [$rekapitulasi] = $this->buildRekapData($employees, $startDate, $endDate);

        $periodName = $this->formatPeriodName($periodType, $quarter, $year, $startDate, $endDate);

        $data = [
            'rekapitulasi' => $rekapitulasi,
            'period'       => $periodName,
            'generated_at' => now()->translatedFormat('d F Y H:i'),
        ];

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
        $positionScope = $request->get('position_scope', 'operator_staff');
        
        try {
            // Get all active employees with geographic filtering
            $query = Karyawans::where('status', 'active')
                ->with(['department', 'position']);

            $this->applyPositionScopeFilter($query, $positionScope);

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
                'position_scope' => $positionScope,
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
     * Menggunakan 1 query distinct multi-kolom (bukan 4 query terpisah)
     */
    private function getAvailableLocations()
    {
        $rows = Karyawans::where('status', 'active')
            ->select('province', 'kabupaten', 'kecamatan', 'desa')
            ->whereNotNull('province')
            ->distinct()
            ->get();

        return [
            'provinces'  => $rows->pluck('province')->filter()->unique()->sort()->values(),
            'kabupatens' => $rows->pluck('kabupaten')->filter()->unique()->sort()->values(),
            'kecamatans' => $rows->pluck('kecamatan')->filter()->unique()->sort()->values(),
            'desas'      => $rows->pluck('desa')->filter()->unique()->sort()->values(),
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
        $positionScope = $request->get('position_scope', 'operator_staff');

        // Get data
        $query = Karyawans::where('status', 'active')
            ->with(['department', 'position']);

        $this->applyPositionScopeFilter($query, $positionScope);

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
                        'position_scope' => $positionScope,
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
                'position_scope' => $positionScope,
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
        $positionScope = $request->get('position_scope', 'operator_staff');

        try {
            // Parse location based on groupLevel
            $filters = $this->parseLocationString($location, $groupLevel);

            // Query employees with those filters
            $query = Karyawans::where('status', 'active')
                ->with(['department', 'position']);

            $this->applyPositionScopeFilter($query, $positionScope);

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
                'position_scope' => $positionScope,
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

    /**
     * Get data for 3 doughnut charts:
     * 1. All active employees by location
     * 2. Indramayu (Kabupaten INDRAMAYU)
     * 3. Losarang (Kecamatan LOSARANG in Indramayu)
     */
    public function getGeographicChartData(Request $request)
    {
        $positionScope = $request->get('position_scope', 'operator_staff');

        try {
            // Chart 1: All active employees by province
            $allEmployeesByProvinceQuery = Karyawans::where('status', 'active')
                ->whereNotNull('province')
                ->selectRaw('province, COUNT(*) as count')
                ->groupBy('province')
                ->orderBy('count', 'desc');

            $this->applyPositionScopeFilter($allEmployeesByProvinceQuery, $positionScope);
            $allEmployeesByProvince = $allEmployeesByProvinceQuery->get();

            // Chart 2: Indramayu - by kecamatan (district)
            $indramayuByDistrictQuery = Karyawans::where('status', 'active')
                ->where('kabupaten', 'INDRAMAYU')
                ->whereNotNull('kecamatan')
                ->selectRaw('kecamatan, COUNT(*) as count')
                ->groupBy('kecamatan')
                ->orderBy('count', 'desc');

            $this->applyPositionScopeFilter($indramayuByDistrictQuery, $positionScope);
            $indramayuByDistrict = $indramayuByDistrictQuery->get();

            // Chart 3: Losarang - by desa (village)
            $losarangByVillageQuery = Karyawans::where('status', 'active')
                ->where('kabupaten', 'INDRAMAYU')
                ->whereRaw("UPPER(TRIM(kecamatan)) REGEXP '^LOSARANG(\\\\s+KAB\\\\.?)?$'")
                ->selectRaw('desa, COUNT(*) as count')
                ->groupBy('desa')
                ->orderBy('count', 'desc');

            $this->applyPositionScopeFilter($losarangByVillageQuery, $positionScope);
            $losarangByVillage = $losarangByVillageQuery->get();

            // Format data for charts
            $chart1Data = [
                'labels' => $allEmployeesByProvince->pluck('province')->toArray(),
                'data' => $allEmployeesByProvince->pluck('count')->toArray(),
            ];

            $chart2Data = $this->collapseGeographicSeries($indramayuByDistrict, 'kecamatan', true);

            $chart3Data = $this->collapseGeographicSeries($losarangByVillage, 'desa', false);

            return response()->json([
                'success' => true,
                'position_scope' => $positionScope,
                'chart1' => [
                    'title' => 'Statistik Karyawan Aktif per Provinsi',
                    'labels' => $chart1Data['labels'],
                    'data' => $chart1Data['data'],
                    'total' => array_sum($chart1Data['data']),
                ],
                'chart2' => [
                    'title' => 'Statistik Karyawan Indramayu per Kecamatan',
                    'labels' => $chart2Data['labels'],
                    'data' => $chart2Data['data'],
                    'total' => array_sum($chart2Data['data']),
                ],
                'chart3' => [
                    'title' => 'Statistik Karyawan Losarang per Desa',
                    'labels' => $chart3Data['labels'],
                    'data' => $chart3Data['data'],
                    'total' => array_sum($chart3Data['data']),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getGeographicChartData: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching chart data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Collapse duplicate geographic labels caused by spacing or legacy suffixes.
     */
    private function collapseGeographicSeries($rows, string $field, bool $stripKabSuffix = false): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $label = $this->normalizeGeographicLabel($row->{$field} ?? '', $stripKabSuffix);
            if ($label === '') {
                continue;
            }

            $grouped[$label] = ($grouped[$label] ?? 0) + (int) $row->count;
        }

        arsort($grouped);

        return [
            'labels' => array_keys($grouped),
            'data' => array_values($grouped),
        ];
    }

    /**
     * Normalize a geographic label for display and grouping.
     */
    private function normalizeGeographicLabel(string $value, bool $stripKabSuffix = false): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        $value = mb_strtoupper($value);

        if ($value === '') {
            return 'TIDAK DIISI';
        }

        if ($stripKabSuffix) {
            $value = preg_replace('/\s+KAB\.?$/u', '', $value) ?? $value;
        }

        return trim($value);
    }

    /**
     * Apply position-based filtering. Default focus: Operator and Staff roles only.
     */
    private function applyPositionScopeFilter($query, ?string $positionScope): void
    {
        if ($positionScope === 'all') {
            return;
        }

        $query->whereHas('position', function ($positionQuery) {
            $positionQuery->where(function ($q) {
                $q->whereRaw('UPPER(name) LIKE ?', ['%OPERATOR%'])
                    ->orWhereRaw('UPPER(name) LIKE ?', ['%STAFF%']);
            });
        });
    }
}
