<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\AttendanceEditRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Redirect berdasarkan role
        switch ($user->role) {
            case 'admin':
                return $this->adminDashboard();
            case 'manager':
                return $this->managerDashboard();
            case 'viewer':
                return $this->viewerDashboard();
            case 'security':
                return redirect()->route('security.scanner');
            case 'guest':
                return redirect()->route('guest.dashboard');
            case 'karyawan':
                return $this->karyawanDashboard();
            default:
                return $this->adminDashboard();
        }
    }

    /**
     * Dashboard untuk Admin
     * Melihat semua data karyawan dan absensi
     */
    private function adminDashboard()
    {
        $data = [
            'totalKaryawan' => Employee::where('status', 'active')->count(),
            'totalResign' => Employee::where('status', 'resign')->count(),
            'hadirHariIni' => Attendance::whereDate('attendance_date', today())
                ->whereIn('status', ['hadir', 'terlambat'])->count(),
            'tidakHadirHariIni' => Attendance::whereDate('attendance_date', today())
                ->where('status', 'alpha')->count(),
            'totalCutiPending' => Leave::where('status', 'pending')->count(),
            'totalEditRequestPending' => AttendanceEditRequest::where('status', 'pending')->count(),
            'absensiTerbaru' => Attendance::with(['employee.department', 'employee.position'])
                ->whereDate('attendance_date', today())
                ->latest()
                ->take(5)
                ->get(),
            'cutiPending' => Leave::with(['employee'])
                ->where('status', 'pending')
                ->latest()
                ->take(5)
                ->get(),
            'editRequestsPending' => AttendanceEditRequest::with(['attendance.employee'])
                ->where('status', 'pending')
                ->latest()
                ->take(5)
                ->get(),
            'statistikMingguIni' => $this->getWeeklyStats(),
            'weeklyWorkSummary' => $this->getWeeklyWorkSummary(),
        ];

        return view('dashboard.admin', $data);
    }

    /**
     * Dashboard untuk Manager
     * Manager memiliki akses yang sama dengan Admin
     */
    private function managerDashboard()
    {
        // Manager menggunakan dashboard admin dengan data yang sama
        return $this->adminDashboard();
    }

    /**
     * Dashboard untuk Viewer
     * Viewer hanya bisa melihat data absensi dan karyawan (read-only)
     */
    private function viewerDashboard()
    {
        $data = [
            'totalKaryawan'    => Employee::where('status', 'active')->count(),
            'totalResign'      => Employee::where('status', 'resign')->count(),
            'hadirHariIni'     => Attendance::whereDate('attendance_date', today())
                ->whereIn('status', ['hadir', 'terlambat'])->count(),
            'terlambatHariIni' => Attendance::whereDate('attendance_date', today())
                ->where('status', 'terlambat')->count(),
            'tidakHadirHariIni'=> Attendance::whereDate('attendance_date', today())
                ->where('status', 'alpha')->count(),
            'alphaHariIni'     => Attendance::whereDate('attendance_date', today())
                ->where('status', 'alpha')->count(),
            'izinHariIni'      => Attendance::whereDate('attendance_date', today())
                ->whereIn('status', ['izin', 'sakit', 'cuti'])->count(),
            'absensiTerbaru'   => Attendance::with(['employee.department', 'employee.position'])
                ->whereDate('attendance_date', today())
                ->latest()
                ->take(8)
                ->get(),
            'statistikMingguIni' => $this->getWeeklyStats(),
            'weeklyWorkSummary' => $this->getWeeklyWorkSummary(),
        ];

        return view('dashboard.viewer', $data);
    }

    /**
     * Halaman detail jam kerja mingguan.
     */
    public function weeklyWorkHours(Request $request)
    {
        $period = $this->resolveWorkPeriod($request);
        $employeesOverLimit = $this->getEmployeesOverLimit($period['start'], $period['end']);
        $weekAnchor = $period['anchor_date']->format('Y-m-d');

        return view('dashboard.weekly-hours', [
            'employeesOverLimit' => $employeesOverLimit,
            'overLimitCount' => $employeesOverLimit->count(),
            'period' => $period,
            'filters' => [
                'week_date' => $weekAnchor,
            ],
        ]);
    }

    /**
     * Dashboard untuk Karyawan
     * Melihat data absensi pribadi
     */
    private function karyawanDashboard()
    {
        // Ambil employee berdasarkan user_id
        $employee = Employee::where('user_id', Auth::id())->first();

        if (!$employee) {
            // Jika karyawan belum punya data employee, tampilkan dashboard kosong
            return view('dashboard.karyawan', [
                'message' => 'Data karyawan Anda belum tersedia. Silakan hubungi administrator.'
            ]);
        }

        $today = Carbon::today();
        $thisMonth = Carbon::now()->month;
        $thisYear = Carbon::now()->year;

        $data = [
            'employee' => $employee,
            'absensiHariIni' => Attendance::where('employee_id', $employee->id)
                ->whereDate('attendance_date', today())
                ->first(),
            'totalHadirBulanIni' => Attendance::where('employee_id', $employee->id)
                ->whereMonth('attendance_date', $thisMonth)
                ->whereYear('attendance_date', $thisYear)
                ->whereIn('status', ['hadir', 'terlambat'])
                ->count(),
            'totalTerlambatBulanIni' => Attendance::where('employee_id', $employee->id)
                ->whereMonth('attendance_date', $thisMonth)
                ->whereYear('attendance_date', $thisYear)
                ->where('status', 'terlambat')
                ->count(),
            'totalIzinBulanIni' => Attendance::where('employee_id', $employee->id)
                ->whereMonth('attendance_date', $thisMonth)
                ->whereYear('attendance_date', $thisYear)
                ->whereIn('status', ['izin', 'sakit', 'cuti'])
                ->count(),
            'riwayatAbsensi' => Attendance::where('employee_id', $employee->id)
                ->orderBy('attendance_date', 'desc')
                ->take(10)
                ->get(),
            'cutiTersedia' => 12, // Default 12 hari per tahun
            'cutiTerpakai' => Leave::where('employee_id', $employee->id)
                ->whereYear('start_date', $thisYear)
                ->where('status', 'approved')
                ->sum('total_days'),
            'cutiPending' => Leave::where('employee_id', $employee->id)
                ->where('status', 'pending')
                ->latest()
                ->get(),
            'statistikBulanIni' => $this->getMonthlyStatsForEmployee($employee->id),
        ];

        return view('dashboard.karyawan', $data);
    }

    /**
     * Get statistik mingguan untuk dashboard admin
     */
    private function getWeeklyStats()
    {
        $stats = [];
        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayName = $days[$date->dayOfWeek] ?? $date->format('l');

            $stats['labels'][] = $dayName;
            $stats['hadir'][] = Attendance::whereDate('attendance_date', $date)
                ->whereIn('status', ['hadir', 'terlambat'])->count();
            $stats['tidak_hadir'][] = Attendance::whereDate('attendance_date', $date)
                ->whereIn('status', ['alpha', 'izin', 'sakit'])->count();
        }

        return $stats;
    }

    /**
     * Hitung total jam kerja 7 hari terakhir dan bandingkan dengan limit 60 jam.
     */
    private function getWeeklyWorkSummary(): array
    {
        $weekStart = Carbon::today()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd = Carbon::today()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        return $this->getWorkSummaryForRange($weekStart, $weekEnd);
    }

    /**
     * Hitung total jam kerja untuk rentang tanggal apa pun dan bandingkan dengan limit 60 jam.
     */
    private function getWorkSummaryForRange(Carbon $startDate, Carbon $endDate): array
    {
        $rangeStart = $startDate->copy()->startOfDay();
        $rangeEnd = $endDate->copy()->endOfDay();

        $attendances = Attendance::query()
            ->whereBetween('attendance_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->whereNotNull('check_in')
            ->whereNotNull('check_out')
            ->whereIn('status', ['hadir', 'terlambat', 'lembur'])
            ->get();

        $totalMinutes = 0;

        foreach ($attendances as $attendance) {
            try {
                $attendanceDate = Carbon::parse($attendance->attendance_date);
                $checkInTime = Carbon::parse($attendanceDate->format('Y-m-d') . ' ' . (string) $attendance->check_in);
                $checkOutTime = Carbon::parse($attendanceDate->format('Y-m-d') . ' ' . (string) $attendance->check_out);

                if ($checkOutTime->greaterThan($checkInTime)) {
                    $totalMinutes += $this->calculateNetWorkMinutes($attendanceDate, $checkInTime, $checkOutTime);
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        $limitMinutes = 60 * 60;
        $remainingMinutes = max(0, $limitMinutes - $totalMinutes);

        return [
            'total_minutes' => $totalMinutes,
            'total_hours' => round($totalMinutes / 60, 2),
            'formatted_hours' => intdiv($totalMinutes, 60) . ' jam ' . str_pad((string) ($totalMinutes % 60), 2, '0', STR_PAD_LEFT) . ' menit',
            'limit_minutes' => $limitMinutes,
            'limit_hours' => 60,
            'remaining_minutes' => $remainingMinutes,
            'remaining_hours' => round($remainingMinutes / 60, 2),
            'is_over_limit' => $totalMinutes > $limitMinutes,
            'progress_percent' => min(100, (int) round(($totalMinutes / $limitMinutes) * 100)),
            'week_start' => $rangeStart,
            'week_end' => $rangeEnd,
        ];
    }

    /**
     * Tentukan periode laporan dari filter bulan atau tanggal.
     */
    private function resolveWorkPeriod(Request $request): array
    {
        $anchorDate = $request->filled('week_date')
            ? Carbon::parse($request->get('week_date'))
            : Carbon::today();

        $start = $anchorDate->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $end = $anchorDate->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        return [
            'mode' => 'week',
            'start' => $start,
            'end' => $end,
            'anchor_date' => $anchorDate->copy()->startOfDay(),
        ];
    }

    /**
     * List karyawan yang melebihi 60 jam pada rentang tertentu.
     */
    private function getEmployeesOverLimit(Carbon $startDate, Carbon $endDate)
    {
        $attendances = Attendance::with('employee')
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereNotNull('check_in')
            ->whereNotNull('check_out')
            ->whereIn('status', ['hadir', 'terlambat', 'lembur'])
            ->get();

        $grouped = $attendances->groupBy('employee_id');

        return $grouped->map(function ($items) use ($startDate, $endDate) {
            $employee = $items->first()->employee;
            $totalMinutes = $this->sumWorkMinutes($items);

            return [
                'employee' => $employee,
                'total_minutes' => $totalMinutes,
                'total_hours' => round($totalMinutes / 60, 2),
                'formatted_hours' => intdiv($totalMinutes, 60) . ' jam ' . str_pad((string) ($totalMinutes % 60), 2, '0', STR_PAD_LEFT) . ' menit',
                'is_over_limit' => $totalMinutes > 3600,
                'remaining_hours' => round(max(0, 3600 - $totalMinutes) / 60, 2),
                'week_start' => $startDate->copy()->startOfDay(),
                'week_end' => $endDate->copy()->endOfDay(),
            ];
        })->sortByDesc('total_minutes')->values();
    }

    /**
     * Hitung total menit kerja dari koleksi attendance.
     */
    private function sumWorkMinutes($attendances): int
    {
        $totalMinutes = 0;

        foreach ($attendances as $attendance) {
            try {
                $attendanceDate = Carbon::parse($attendance->attendance_date);
                $checkInTime = Carbon::parse($attendanceDate->format('Y-m-d') . ' ' . (string) $attendance->check_in);
                $checkOutTime = Carbon::parse($attendanceDate->format('Y-m-d') . ' ' . (string) $attendance->check_out);

                if ($checkOutTime->greaterThan($checkInTime)) {
                    $totalMinutes += $this->calculateNetWorkMinutes($attendanceDate, $checkInTime, $checkOutTime);
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $totalMinutes;
    }

    /**
     * Hitung menit kerja bersih dengan mengurangi waktu istirahat yang terpotong dalam interval kerja.
     */
    private function calculateNetWorkMinutes(Carbon $attendanceDate, Carbon $checkInTime, Carbon $checkOutTime): int
    {
        $totalMinutes = $checkInTime->diffInMinutes($checkOutTime);
        $breakMinutes = $this->calculateBreakMinutes($attendanceDate, $checkInTime, $checkOutTime);

        return max(0, $totalMinutes - $breakMinutes);
    }

    /**
     * Hitung durasi istirahat yang overlap dengan jam kerja.
     */
    private function calculateBreakMinutes(Carbon $attendanceDate, Carbon $checkInTime, Carbon $checkOutTime): int
    {
        $breakStart = $attendanceDate->copy()->setTime(12, 0, 0);
        $breakEnd = $attendanceDate->copy()->setTime(13, 0, 0);

        if ($attendanceDate->isFriday()) {
            $breakStart = $attendanceDate->copy()->setTime(11, 30, 0);
        }

        if ($checkOutTime->lessThanOrEqualTo($breakStart) || $checkInTime->greaterThanOrEqualTo($breakEnd)) {
            return 0;
        }

        $overlapStart = $checkInTime->greaterThan($breakStart) ? $checkInTime->copy() : $breakStart;
        $overlapEnd = $checkOutTime->lessThan($breakEnd) ? $checkOutTime->copy() : $breakEnd;

        if ($overlapEnd->lessThanOrEqualTo($overlapStart)) {
            return 0;
        }

        return $overlapStart->diffInMinutes($overlapEnd);
    }

    /**
     * Get statistik bulanan untuk karyawan
     */
    private function getMonthlyStatsForEmployee($employeeId)
    {
        $stats = [];
        $thisMonth = Carbon::now()->month;
        $thisYear = Carbon::now()->year;

        // Get jumlah hari dalam bulan ini
        $daysInMonth = Carbon::now()->daysInMonth;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($thisYear, $thisMonth, $day);

            if ($date->isFuture()) break;

            $stats['labels'][] = $day;

            $attendance = Attendance::where('employee_id', $employeeId)
                ->whereDate('attendance_date', $date)
                ->first();

            $stats['status'][] = $attendance ? $attendance->status : 'tidak_ada_data';
        }

        return $stats;
    }
}
