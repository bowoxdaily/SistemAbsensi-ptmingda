<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Interview;
use App\Models\Karyawans;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

    /**
     * [FIX 2026-08-08] Konsolidasi 14+ COUNT queries terpisah → 3 GROUP BY queries + cache.
     * Endpoint ini public (no auth), jadi tanpa cache bisa di-hit bot/crawler setiap detik.
     * Sebelumnya: 8 COUNT(employees) + 6 COUNT(attendances) + 6 COUNT(interviews) = 20 queries/request.
     * Sesudah: 3 GROUP BY queries, hasil di-cache 60 detik.
     */
    public function stats()
    {
        $today    = today();
        $todayStr = $today->toDateString();

        // ── Karyawan stats: 1 GROUP BY query ──────────────────────────────
        $karyawanCounts = \Illuminate\Support\Facades\Cache::remember(
            'guest_karyawan_stats',
            60,
            function () {
                return Karyawans::select('status', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
                    ->groupBy('status')
                    ->pluck('total', 'status');
            }
        );

        $karyawanStats = [
            'total'           => $karyawanCounts->sum(),
            'active'          => (int) $karyawanCounts->get('active', 0),
            'resign'          => (int) $karyawanCounts->get('resign', 0),
            'inactive'        => (int) $karyawanCounts->get('inactive', 0),
            'mangkir'         => (int) $karyawanCounts->get('mangkir', 0),
            'gagal_probation' => (int) $karyawanCounts->get('gagal_probation', 0),
            'pending'         => (int) $karyawanCounts->get('pending', 0),
            'phk'             => (int) $karyawanCounts->get('phk', 0),
        ];

        // ── Absensi stats hari ini: 1 GROUP BY query, cache 60 detik ──────
        $absensiCounts = \Illuminate\Support\Facades\Cache::remember(
            'guest_absensi_stats_' . $todayStr,
            60,
            function () use ($todayStr) {
                return Attendance::whereDate('attendance_date', $todayStr)
                    ->select('status', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
                    ->groupBy('status')
                    ->pluck('total', 'status');
            }
        );

        $absensiStats = [
            'hadir'     => (int) $absensiCounts->get('hadir', 0),
            'terlambat' => (int) $absensiCounts->get('terlambat', 0),
            'alpha'     => (int) $absensiCounts->get('alpha', 0),
            'izin'      => (int) $absensiCounts->get('izin', 0),
            'sakit'     => (int) $absensiCounts->get('sakit', 0),
            'cuti'      => (int) $absensiCounts->get('cuti', 0),
        ];

        // ── Interview stats: 1 GROUP BY query, cache 5 menit ──────────────
        $interviewData = \Illuminate\Support\Facades\Cache::remember(
            'guest_interview_stats_' . $todayStr,
            300,
            function () use ($todayStr) {
                $counts = Interview::select('status', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
                    ->groupBy('status')
                    ->pluck('total', 'status');

                $hariIni = Interview::whereDate('interview_date', $todayStr)->count();

                return ['counts' => $counts, 'hari_ini' => $hariIni];
            }
        );

        $interviewCounts = $interviewData['counts'];
        $interviewStats  = [
            'total'     => $interviewCounts->sum(),
            'scheduled' => (int) $interviewCounts->get('scheduled', 0),
            'confirmed' => (int) $interviewCounts->get('confirmed', 0),
            'completed' => (int) $interviewCounts->get('completed', 0),
            'cancelled' => (int) $interviewCounts->get('cancelled', 0),
            'hari_ini'  => (int) $interviewData['hari_ini'],
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

        // Hide sensitive PII and financial data from the public endpoint
        $items = collect($paginated->items())->map(fn($k) => $k->makeHidden([
            'fingerspot_pin', 'nik', 'ktp', 'kartu_keluarga', 'tax_npwp',
            'bpjs_kesehatan', 'bpjs_ketenagakerjaan', 'bank', 'nomor_rekening',
            'salary_base', 'nama_ibu_kandung', 'user_id',
        ])->toArray());

        return response()->json([
            'success' => true,
            'data'    => $items,
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
                ->orWhere('email', 'like', "%{$search}%")
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
