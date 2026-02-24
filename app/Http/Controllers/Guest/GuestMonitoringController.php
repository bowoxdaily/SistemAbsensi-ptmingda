<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Interview;
use App\Models\Karyawans;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\Request;
use Carbon\Carbon;

class GuestMonitoringController extends Controller
{
    /* ─── Web Pages ─────────────────────────────────── */

    public function dashboard()
    {
        return view('guest.dashboard');
    }

    public function karyawanPage()
    {
        return view('guest.karyawan');
    }

    public function absensiPage()
    {
        return view('guest.absensi');
    }

    public function interviewPage()
    {
        return view('guest.interview');
    }

    /* ─── API: Dashboard Stats ───────────────────────── */

    public function stats()
    {
        $today = today();

        $karyawanStats = [
            'total'           => Karyawans::count(),
            'active'          => Karyawans::where('status', 'active')->count(),
            'resign'          => Karyawans::where('status', 'resign')->count(),
            'inactive'        => Karyawans::where('status', 'inactive')->count(),
            'mangkir'         => Karyawans::where('status', 'mangkir')->count(),
            'gagal_probation' => Karyawans::where('status', 'gagal_probation')->count(),
        ];

        $absensiStats = [
            'hadir'    => Attendance::whereDate('attendance_date', $today)->where('status', 'hadir')->count(),
            'terlambat' => Attendance::whereDate('attendance_date', $today)->where('status', 'terlambat')->count(),
            'alpha'    => Attendance::whereDate('attendance_date', $today)->where('status', 'alpha')->count(),
            'izin'     => Attendance::whereDate('attendance_date', $today)->where('status', 'izin')->count(),
            'sakit'    => Attendance::whereDate('attendance_date', $today)->where('status', 'sakit')->count(),
            'cuti'     => Attendance::whereDate('attendance_date', $today)->where('status', 'cuti')->count(),
        ];

        $interviewStats = [
            'total'     => Interview::count(),
            'scheduled' => Interview::where('status', 'scheduled')->count(),
            'confirmed' => Interview::where('status', 'confirmed')->count(),
            'completed' => Interview::where('status', 'completed')->count(),
            'cancelled' => Interview::where('status', 'cancelled')->count(),
            'hari_ini'  => Interview::whereDate('interview_date', $today)->count(),
        ];

        $recentAttendance = Attendance::with(['employee.department', 'employee.position'])
            ->whereDate('attendance_date', $today)
            ->latest('check_in')
            ->take(8)
            ->get()
            ->map(fn($a) => [
                'name'        => $a->employee->name ?? '-',
                'code'        => $a->employee->employee_code ?? '-',
                'department'  => $a->employee->department->name ?? '-',
                'status'      => $a->status,
                'check_in'    => $a->check_in ? Carbon::parse($a->check_in)->format('H:i') : '-',
                'check_out'   => $a->check_out ? Carbon::parse($a->check_out)->format('H:i') : '-',
            ]);

        $upcomingInterviews = Interview::with('position')
            ->whereDate('interview_date', '>=', $today)
            ->where('status', '!=', 'cancelled')
            ->orderBy('interview_date')
            ->orderBy('interview_time')
            ->take(6)
            ->get()
            ->map(fn($i) => [
                'candidate_name' => $i->candidate_name,
                'position'       => $i->position->name ?? '-',
                'interview_date' => Carbon::parse($i->interview_date)->format('Y-m-d'),
                'interview_time' => $i->interview_time ? Carbon::parse($i->interview_time)->format('H:i') : '-',
                'status'         => $i->status,
                'location'       => $i->location,
            ]);

        return response()->json([
            'success'             => true,
            'karyawan'            => $karyawanStats,
            'absensi'             => $absensiStats,
            'interview'           => $interviewStats,
            'recent_attendance'   => $recentAttendance,
            'upcoming_interviews' => $upcomingInterviews,
        ]);
    }

    /* ─── API: Karyawan (read-only) ───────────────────── */

    public function karyawanList(Request $request)
    {
        $perPage    = $request->get('per_page', 25);
        $search     = $request->get('search', '');
        $status     = $request->get('status');
        $deptId     = $request->get('department_id');

        $query = Karyawans::with(['department', 'position'])
            ->when($search, fn($q) => $q->where(fn($q2) => $q2
                ->where('name', 'like', "%{$search}%")
                ->orWhere('employee_code', 'like', "%{$search}%")))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($deptId, fn($q) => $q->where('department_id', $deptId))
            ->orderBy('employee_code');

        $paginated = $query->paginate((int) $perPage);

        return response()->json([
            'success' => true,
            'data'    => $paginated->items(),
            'meta'    => [
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'from'         => $paginated->firstItem(),
                'to'           => $paginated->lastItem(),
            ],
        ]);
    }

    /* ─── API: Absensi (read-only) ────────────────────── */

    public function absensiList(Request $request)
    {
        $perPage  = $request->get('per_page', 25);
        $search   = $request->get('search', '');
        $dateFrom = $request->get('date_from', today()->toDateString());
        $dateTo   = $request->get('date_to', today()->toDateString());
        $status   = $request->get('status', '');
        $deptId   = $request->get('department_id');

        $paginated = Attendance::with(['employee.department', 'employee.position'])
            ->when($search, fn($q) => $q->whereHas('employee', fn($e) => $e
                ->where('name', 'like', "%{$search}%")
                ->orWhere('employee_code', 'like', "%{$search}%")))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($deptId, fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $deptId)))
            ->whereBetween('attendance_date', [$dateFrom, $dateTo])
            ->orderBy('attendance_date', 'desc')
            ->orderBy('check_in', 'desc')
            ->paginate((int) $perPage);

        $data = $paginated->items();
        // Format dates/times
        foreach ($data as &$a) {
            $a['attendance_date'] = Carbon::parse($a['attendance_date'])->format('Y-m-d');
            $a['check_in_fmt']  = $a['check_in']  ? Carbon::parse($a['check_in'])->format('H:i')  : '-';
            $a['check_out_fmt'] = $a['check_out'] ? Carbon::parse($a['check_out'])->format('H:i') : '-';
        }

        return response()->json([
            'success' => true,
            'data'    => $data,
            'meta'    => [
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'from'         => $paginated->firstItem(),
                'to'           => $paginated->lastItem(),
            ],
        ]);
    }

    /* ─── API: Interview (read-only) ──────────────────── */

    public function interviewList(Request $request)
    {
        $perPage = $request->get('per_page', 25);
        $search  = $request->get('search', '');
        $status  = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');

        $paginated = Interview::with('position')
            ->when($search, fn($q) => $q->where(fn($q2) => $q2
                ->where('candidate_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($dateFrom, fn($q) => $q->whereDate('interview_date', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('interview_date', '<=', $dateTo))
            ->orderBy('interview_date', 'desc')
            ->orderBy('interview_time', 'desc')
            ->paginate((int) $perPage);

        return response()->json([
            'success' => true,
            'data'    => $paginated->items(),
            'meta'    => [
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'from'         => $paginated->firstItem(),
                'to'           => $paginated->lastItem(),
            ],
        ]);
    }

    /* ─── API: Master Data (departments) ─────────────── */

    public function masterData()
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'departments' => Department::orderBy('name')->get(['id', 'name']),
                'positions'   => Position::orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }
}
