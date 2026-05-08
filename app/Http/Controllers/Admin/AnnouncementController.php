<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Karyawans;
use App\Models\Position;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnnouncementController extends Controller
{
    /**
     * Halaman manajemen pengumuman
     */
    public function index()
    {
        return view('admin.announcements.index');
    }

    /**
     * Daftar semua pengumuman (untuk DataTable)
     */
    public function list(Request $request)
    {
        $query = Announcement::with('creator')
            ->orderBy('created_at', 'desc');

        // Filter status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'expired') {
                $query->where('end_date', '<', now());
            }
        }

        // Filter type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $perPage     = $request->get('per_page', 10);
        $announcements = $query->withCount('reads')->paginate($perPage);

        // Tambahkan total_recipients ke setiap item
        $announcements->getCollection()->transform(function ($item) {
            $item->total_recipients = $item->countRecipients();
            return $item;
        });

        return response()->json([
            'success' => true,
            'data'    => $announcements,
        ]);
    }

    /**
     * Simpan pengumuman baru
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'          => 'required|string|max:255',
            'content'        => 'required|string',
            'type'           => 'required|in:info,warning,success,danger',
            'priority'       => 'required|in:low,normal,high,urgent',
            'filter_type'    => 'required|in:all,position,department,employee',
            'filter_values'  => 'required_unless:filter_type,all|array',
            'filter_values.*'=> 'integer',
            'is_active'      => 'boolean',
            'show_popup'     => 'boolean',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $announcement = Announcement::create([
                'title'         => $request->title,
                'content'       => $request->content,
                'type'          => $request->type,
                'priority'      => $request->priority,
                'filter_type'   => $request->filter_type,
                'filter_values' => $request->filter_values ?? [],
                'is_active'     => $request->boolean('is_active', true),
                'show_popup'    => $request->boolean('show_popup', false),
                'start_date'    => $request->start_date ?? null,
                'end_date'      => $request->end_date ?? null,
                'created_by'    => Auth::id(),
            ]);

            $recipientsCount = $announcement->countRecipients();

            return response()->json([
                'success' => true,
                'message' => "Pengumuman berhasil dibuat untuk {$recipientsCount} karyawan.",
                'data'    => $announcement->load('creator'),
            ]);
        } catch (\Exception $e) {
            Log::error('Announcement store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pengumuman: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detail satu pengumuman
     */
    public function show($id)
    {
        try {
            $announcement = Announcement::with('creator')->withCount('reads')->findOrFail($id);
            $announcement->total_recipients = $announcement->countRecipients();

            // Filter detail (nama jabatan/departemen/karyawan)
            $filterDetails = [];
            if ($announcement->filter_type === 'position') {
                $filterDetails = Position::whereIn('id', $announcement->filter_values ?? [])->pluck('name')->toArray();
            } elseif ($announcement->filter_type === 'department') {
                $filterDetails = Department::whereIn('id', $announcement->filter_values ?? [])->pluck('name')->toArray();
            } elseif ($announcement->filter_type === 'employee') {
                $filterDetails = Karyawans::whereIn('id', $announcement->filter_values ?? [])->pluck('name')->toArray();
            }

            // Siapa saja yang sudah baca
            $readers = AnnouncementRead::where('announcement_id', $id)
                ->with('employee:id,name,employee_code')
                ->get()
                ->map(fn($r) => [
                    'name'          => $r->employee->name ?? '-',
                    'employee_code' => $r->employee->employee_code ?? '-',
                    'read_at'       => $r->read_at?->format('d/m/Y H:i'),
                ]);

            return response()->json([
                'success' => true,
                'data'    => [
                    'announcement'  => $announcement,
                    'filter_details'=> $filterDetails,
                    'readers'       => $readers,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pengumuman tidak ditemukan',
            ], 404);
        }
    }

    /**
     * Update pengumuman
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title'          => 'required|string|max:255',
            'content'        => 'required|string',
            'type'           => 'required|in:info,warning,success,danger',
            'priority'       => 'required|in:low,normal,high,urgent',
            'filter_type'    => 'required|in:all,position,department,employee',
            'filter_values'  => 'required_unless:filter_type,all|array',
            'filter_values.*'=> 'integer',
            'is_active'      => 'boolean',
            'show_popup'     => 'boolean',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $announcement = Announcement::findOrFail($id);
            $announcement->update([
                'title'         => $request->title,
                'content'       => $request->content,
                'type'          => $request->type,
                'priority'      => $request->priority,
                'filter_type'   => $request->filter_type,
                'filter_values' => $request->filter_values ?? [],
                'is_active'     => $request->boolean('is_active', true),
                'show_popup'    => $request->boolean('show_popup', false),
                'start_date'    => $request->start_date ?? null,
                'end_date'      => $request->end_date ?? null,
            ]);

            // Reset reads jika filter berubah (opsional)
            if ($request->has('reset_reads') && $request->boolean('reset_reads')) {
                AnnouncementRead::where('announcement_id', $id)->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Pengumuman berhasil diperbarui.',
                'data'    => $announcement->load('creator'),
            ]);
        } catch (\Exception $e) {
            Log::error('Announcement update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui pengumuman: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle status aktif pengumuman
     */
    public function toggleActive($id)
    {
        try {
            $announcement = Announcement::findOrFail($id);
            $announcement->update(['is_active' => !$announcement->is_active]);

            $status = $announcement->is_active ? 'diaktifkan' : 'dinonaktifkan';
            return response()->json([
                'success'   => true,
                'message'   => "Pengumuman berhasil {$status}.",
                'is_active' => $announcement->is_active,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hapus pengumuman
     */
    public function destroy($id)
    {
        try {
            $announcement = Announcement::findOrFail($id);
            // Reads akan terhapus otomatis karena cascade
            $announcement->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pengumuman berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pengumuman: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview penerima berdasarkan filter
     */
    public function previewRecipients(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filter_type'    => 'required|in:all,position,department,employee',
            'filter_values'  => 'required_unless:filter_type,all|array',
            'filter_values.*'=> 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $query = Karyawans::with(['position:id,name', 'department:id,name'])
            ->where('status', 'active');

        switch ($request->filter_type) {
            case 'position':
                $query->whereIn('position_id', $request->filter_values ?? []);
                break;
            case 'department':
                $query->whereIn('department_id', $request->filter_values ?? []);
                break;
            case 'employee':
                $query->whereIn('id', $request->filter_values ?? []);
                break;
        }

        $employees = $query->get(['id', 'name', 'employee_code', 'position_id', 'department_id']);

        return response()->json([
            'success' => true,
            'data'    => [
                'count'     => $employees->count(),
                'employees' => $employees->map(fn($e) => [
                    'id'            => $e->id,
                    'name'          => $e->name,
                    'employee_code' => $e->employee_code,
                    'position'      => $e->position->name ?? '-',
                    'department'    => $e->department->name ?? '-',
                ]),
            ],
        ]);
    }

    /**
     * Ambil data positions untuk filter
     */
    public function getPositions()
    {
        $positions = Position::where('status', 'active')->orderBy('name')->get(['id', 'name']);
        return response()->json(['success' => true, 'data' => $positions]);
    }

    /**
     * Ambil data departments untuk filter
     */
    public function getDepartments()
    {
        $departments = Department::orderBy('name')->get(['id', 'name']);
        return response()->json(['success' => true, 'data' => $departments]);
    }

    /**
     * Ambil data karyawan aktif untuk filter
     */
    public function getEmployees()
    {
        $employees = Karyawans::with(['position:id,name', 'department:id,name'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'employee_code', 'position_id', 'department_id'])
            ->map(fn($e) => [
                'id'            => $e->id,
                'name'          => $e->name,
                'employee_code' => $e->employee_code,
                'position'      => $e->position->name ?? '-',
                'department'    => $e->department->name ?? '-',
            ]);

        return response()->json(['success' => true, 'data' => $employees]);
    }

    /**
     * Statistik pengumuman
     */
    public function stats()
    {
        $total   = Announcement::count();
        $active  = Announcement::active()->count();
        $expired = Announcement::where('end_date', '<', now())->count();
        $urgent  = Announcement::active()->where('priority', 'urgent')->count();

        return response()->json([
            'success' => true,
            'data'    => compact('total', 'active', 'expired', 'urgent'),
        ]);
    }

    /**
     * Export daftar pembaca pengumuman ke CSV
     */
    public function exportReaders($id): StreamedResponse
    {
        $announcement = Announcement::findOrFail($id);

        $readers = AnnouncementRead::where('announcement_id', $id)
            ->with('employee:id,name,employee_code,position_id,department_id')
            ->with('employee.position:id,name')
            ->with('employee.department:id,name')
            ->orderBy('read_at', 'asc')
            ->get();

        $filename = 'pembaca-pengumuman-' . $announcement->id . '-' . now()->format('Ymd-His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ];

        $callback = function () use ($announcement, $readers) {
            $out = fopen('php://output', 'w');

            // BOM untuk Excel agar UTF-8 terbaca dengan benar
            fwrite($out, "\xEF\xBB\xBF");

            // Info pengumuman
            fputcsv($out, ['Judul Pengumuman', $announcement->title]);
            fputcsv($out, ['Tanggal Dibuat', $announcement->created_at?->format('d/m/Y H:i')]);
            fputcsv($out, ['Total Pembaca', $readers->count()]);
            fputcsv($out, []);

            // Header kolom
            fputcsv($out, ['No', 'Kode Karyawan', 'Nama Karyawan', 'Jabatan', 'Departemen', 'Waktu Dibaca']);

            // Data pembaca
            $no = 1;
            foreach ($readers as $r) {
                fputcsv($out, [
                    $no++,
                    $r->employee->employee_code ?? '-',
                    $r->employee->name ?? '-',
                    $r->employee->position->name ?? '-',
                    $r->employee->department->name ?? '-',
                    $r->read_at?->format('d/m/Y H:i:s') ?? '-',
                ]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}
