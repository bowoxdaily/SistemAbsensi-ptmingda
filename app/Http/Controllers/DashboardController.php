<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Attendance;
use App\Models\Leave;
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
            'totalDepartemen' => Department::count(),
            'hadirHariIni' => Attendance::whereDate('attendance_date', today())
                ->whereIn('status', ['hadir', 'terlambat'])->count(),
            'tidakHadirHariIni' => Attendance::whereDate('attendance_date', today())
                ->where('status', 'alpha')->count(),
            'totalCutiPending' => Leave::where('status', 'pending')->count(),
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
            'statistikMingguIni' => $this->getWeeklyStats(),
        ];

        return view('dashboard.admin', $data);
    }

    /**
     * Dashboard untuk Manager
     * Melihat data departemen yang dikelola
     */
    private function managerDashboard()
    {
        // Ambil employee berdasarkan user_id manager
        $employee = Employee::where('user_id', Auth::id())->first();

        if (!$employee) {
            // Jika manager belum punya data employee, tampilkan dashboard kosong
            return view('dashboard.manager', [
                'message' => 'Data karyawan Anda belum tersedia. Silakan hubungi administrator.'
            ]);
        }

        // Ambil karyawan di departemen yang sama atau yang di-supervisi
        $teamMembers = Employee::where('department_id', $employee->department_id)
            ->orWhere('supervisor_id', $employee->id)
            ->where('status', 'active')
            ->get();

        $teamMemberIds = $teamMembers->pluck('id');

        $data = [
            'employee' => $employee,
            'totalTeamMembers' => $teamMembers->count(),
            'hadirHariIni' => Attendance::whereIn('employee_id', $teamMemberIds)
                ->whereDate('attendance_date', today())
                ->whereIn('status', ['hadir', 'terlambat'])->count(),
            'tidakHadirHariIni' => Attendance::whereIn('employee_id', $teamMemberIds)
                ->whereDate('attendance_date', today())
                ->where('status', 'alpha')->count(),
            'cutiPending' => Leave::whereIn('employee_id', $teamMemberIds)
                ->where('status', 'pending')
                ->count(),
            'absensiTeam' => Attendance::with(['employee.position'])
                ->whereIn('employee_id', $teamMemberIds)
                ->whereDate('attendance_date', today())
                ->latest()
                ->get(),
            'cutiPendingList' => Leave::with(['employee'])
                ->whereIn('employee_id', $teamMemberIds)
                ->where('status', 'pending')
                ->latest()
                ->take(5)
                ->get(),
            'teamMembers' => $teamMembers,
        ];

        return view('dashboard.manager', $data);
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
