<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExternalAttendanceController extends Controller
{
    /**
     * GET /api/v1/attendance
     *
     * Params:
     * - per_page (int, default 25, max 100)
     * - employee_id (int)
     * - employee_code (string)
     * - status (string)
     * - date_from (Y-m-d)
     * - date_to (Y-m-d)
     */
    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->denyIfNotAllowed($request)) {
            return $denied;
        }

        $perPage = min(max((int) $request->get('per_page', 25), 1), 100);
        $employeeId = $request->get('employee_id');
        $employeeCode = $request->get('employee_code');
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = Attendance::with(['employee.department', 'employee.position'])
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($employeeCode, function ($q) use ($employeeCode) {
                $q->whereHas('employee', function ($e) use ($employeeCode) {
                    $e->where('employee_code', $employeeCode);
                });
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($dateFrom, fn ($q) => $q->whereDate('attendance_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('attendance_date', '<=', $dateTo))
            ->orderBy('attendance_date', 'desc')
            ->orderBy('id', 'desc');

        $paginated = $query->paginate($perPage);

        $data = collect($paginated->items())
            ->map(fn (Attendance $item) => $this->serializeAttendance($item))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
            'filters' => [
                'employee_id' => $employeeId,
                'employee_code' => $employeeCode,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    /**
     * GET /api/v1/attendance/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->denyIfNotAllowed($request)) {
            return $denied;
        }

        $attendance = Attendance::with(['employee.department', 'employee.position'])->find($id);

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Data absensi tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->serializeAttendance($attendance),
        ]);
    }

    /**
     * GET /api/v1/attendance/employee/{employeeId}
     */
    public function byEmployee(Request $request, int $employeeId): JsonResponse
    {
        if ($denied = $this->denyIfNotAllowed($request)) {
            return $denied;
        }

        $perPage = min(max((int) $request->get('per_page', 31), 1), 100);
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = Attendance::with(['employee.department', 'employee.position'])
            ->where('employee_id', $employeeId)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($dateFrom, fn ($q) => $q->whereDate('attendance_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('attendance_date', '<=', $dateTo))
            ->orderBy('attendance_date', 'desc')
            ->orderBy('id', 'desc');

        $paginated = $query->paginate($perPage);

        $data = collect($paginated->items())
            ->map(fn (Attendance $item) => $this->serializeAttendance($item))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
            'filters' => [
                'employee_id' => $employeeId,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    /**
     * GET /api/v1/attendance/summary
     */
    public function summary(Request $request): JsonResponse
    {
        if ($denied = $this->denyIfNotAllowed($request)) {
            return $denied;
        }

        $employeeId = $request->get('employee_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $baseQuery = Attendance::query()
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($dateFrom, fn ($q) => $q->whereDate('attendance_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('attendance_date', '<=', $dateTo));

        $total = (clone $baseQuery)->count();
        $groupedStatus = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'by_status' => [
                    'hadir' => (int) ($groupedStatus['hadir'] ?? 0),
                    'terlambat' => (int) ($groupedStatus['terlambat'] ?? 0),
                    'izin' => (int) ($groupedStatus['izin'] ?? 0),
                    'sakit' => (int) ($groupedStatus['sakit'] ?? 0),
                    'alpha' => (int) ($groupedStatus['alpha'] ?? 0),
                    'cuti' => (int) ($groupedStatus['cuti'] ?? 0),
                    'libur' => (int) ($groupedStatus['libur'] ?? 0),
                    'cuti_bersama' => (int) ($groupedStatus['cuti_bersama'] ?? 0),
                    'lembur' => (int) ($groupedStatus['lembur'] ?? 0),
                    'off' => (int) ($groupedStatus['off'] ?? 0),
                    'cuti_khusus' => (int) ($groupedStatus['cuti_khusus'] ?? 0),
                ],
            ],
            'filters' => [
                'employee_id' => $employeeId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    private function denyIfNotAllowed(Request $request): ?JsonResponse
    {
        $role = $request->user()?->role;

        if (!in_array($role, ['admin', 'manager', 'viewer'])) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Endpoint ini hanya untuk admin/manager/viewer.',
            ], 403);
        }

        return null;
    }

    private function serializeAttendance(Attendance $attendance): array
    {
        $data = $attendance->toArray();

        // Force plain date string to avoid timezone shift in external clients.
        $data['attendance_date'] = $attendance->attendance_date
            ? Carbon::parse($attendance->attendance_date)->format('Y-m-d')
            : null;

        // Explicit time formatting for stable cross-platform parsing.
        $data['check_in'] = $attendance->check_in
            ? Carbon::parse($attendance->check_in)->format('H:i:s')
            : null;

        $data['check_out'] = $attendance->check_out
            ? Carbon::parse($attendance->check_out)->format('H:i:s')
            : null;

        if ($attendance->relationLoaded('employee') && $attendance->employee) {
            $data['employee'] = [
                'id' => $attendance->employee->id,
                'employee_code' => $attendance->employee->employee_code,
                'name' => $attendance->employee->name,
                'status' => $attendance->employee->status,
                'department' => $attendance->employee->department->name ?? null,
                'position' => $attendance->employee->position->name ?? null,
            ];
        }

        return $data;
    }
}
