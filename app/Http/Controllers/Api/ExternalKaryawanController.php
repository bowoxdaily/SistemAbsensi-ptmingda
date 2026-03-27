<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Karyawans;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * External API controller for karyawan data.
 *
 * Requires Bearer token auth (Sanctum). Accessible by roles: admin, manager, viewer.
 * Returns all fields including NIK and other identifiers.
 */
class ExternalKaryawanController extends Controller
{

    /**
     * List karyawan with pagination and filters.
     *
     * GET /api/v1/karyawan
     * Params:
     *   - per_page       (int, default 25)
     *   - search         (string) — name, employee_code, or email
     *   - department_id  (int)
     *   - position_id    (int)
     *   - status         (string: active|inactive|resign|mangkir|gagal_probation)
     *   - page           (int, default 1)
     */
    public function index(Request $request)
    {
        if ($denied = $this->denyIfNotAllowed($request)) {
            return $denied;
        }

        $perPage      = min((int) $request->get('per_page', 25), 100); // cap at 100
        $search       = $request->get('search', '');
        $departmentId = $request->get('department_id');
        $positionId   = $request->get('position_id');
        $status       = $request->get('status');

        $paginated = Karyawans::with(['department', 'subDepartment', 'position'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%")
                       ->orWhere('employee_code', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->when($positionId,   fn($q) => $q->where('position_id', $positionId))
            ->when($status,       fn($q) => $q->where('status', $status))
            ->orderBy('employee_code')
            ->paginate($perPage);

        $items = collect($paginated->items())->map(fn($k) => $this->sanitize($k));

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

    /**
     * List all karyawan without pagination.
     *
     * GET /api/v1/karyawan/all
     * Params:
     *   - search         (string) — name, employee_code, or email
     *   - department_id  (int)
     *   - position_id    (int)
     *   - status         (string: active|inactive|resign|mangkir|gagal_probation)
     */
    public function all(Request $request)
    {
        if ($denied = $this->denyIfNotAllowed($request)) {
            return $denied;
        }

        $search       = $request->get('search', '');
        $departmentId = $request->get('department_id');
        $positionId   = $request->get('position_id');
        $status       = $request->get('status');

        $items = Karyawans::with(['department', 'subDepartment', 'position'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%")
                       ->orWhere('employee_code', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->when($positionId,   fn($q) => $q->where('position_id', $positionId))
            ->when($status,       fn($q) => $q->where('status', $status))
            ->orderBy('employee_code')
            ->get()
            ->map(fn($k) => $this->sanitize($k))
            ->values();

        return response()->json([
            'success' => true,
            'data'    => $items,
        ]);
    }

    /**
     * Get a single karyawan record.
     *
     * GET /api/v1/karyawan/{id}
     */
    public function show(Request $request, $id)
    {
        if ($denied = $this->denyIfNotAllowed($request)) {
            return $denied;
        }

        $karyawan = Karyawans::with(['department', 'subDepartment', 'position', 'workSchedule'])->find($id);

        if (!$karyawan) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->sanitize($karyawan),
        ]);
    }

    /**
     * Get profile photo (PUBLIC - no authentication needed).
     *
     * GET /api/v1/karyawan/photo/{filename}
     * Returns the profile photo file directly or 404 if not found.
     */
    public function getProfilePhoto($filename)
    {
        // Security: only allow alphanumeric, dash, dot, underscore
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $filename)) {
            abort(404, 'Invalid filename');
        }

        $path = 'profile_photos/' . $filename;

        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Photo not found');
        }

        return Storage::disk('public')->download($path);
    }

    /**
     * Return a 403 JSON response if the user is not admin/manager/viewer,
     * or null if access is allowed.
     */
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

    /**
     * Remove sensitive fields from a Karyawans model instance.
     * Returns all fields including NIK and identifiers, and converts profile_photo to full public URL.
     */
    private function sanitize(Karyawans $karyawan): array
    {
        // Convert to array dan kemudian manipulasi
        $data = $karyawan->toArray();

        // Hapus field sensitif
        unset($data['ktp']);

        // Selalu tambahkan profile_photo_url
        $profilePhoto = $data['profile_photo'] ?? null;
        if (!empty($profilePhoto)) {
            $data['profile_photo_url'] = Storage::disk('public')->url($profilePhoto);
        } else {
            $data['profile_photo_url'] = null;
        }

        return $data;
    }
}
