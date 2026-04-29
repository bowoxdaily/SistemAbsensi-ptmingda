<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    /**
     * Ambil data employee dari user yang login (konsisten dengan controller lain)
     */
    private function getEmployee()
    {
        return Employee::where('user_id', Auth::id())->first();
    }

    /**
     * Ambil daftar pengumuman aktif untuk karyawan yang login
     */
    public function index(Request $request)
    {
        $employee = $this->getEmployee();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Data karyawan tidak ditemukan.',
            ], 404);
        }

        // Ambil semua pengumuman aktif
        $announcements = Announcement::active()->byPriority()->get();

        // Filter berdasarkan target karyawan
        $filtered = $announcements->filter(fn($a) => $a->isForEmployee($employee));

        // Tandai mana yang sudah dibaca
        $readIds = AnnouncementRead::where('employee_id', $employee->id)
            ->pluck('announcement_id')
            ->toArray();

        $result = $filtered->map(function ($a) use ($readIds) {
            return [
                'id'             => $a->id,
                'title'          => $a->title,
                'content'        => $a->content,
                'type'           => $a->type,
                'type_label'     => $a->type_label,
                'type_badge'     => $a->type_badge,
                'type_icon'      => $a->type_icon,
                'priority'       => $a->priority,
                'priority_label' => $a->priority_label,
                'priority_badge' => $a->priority_badge,
                'show_popup'     => $a->show_popup,
                'is_read'        => in_array($a->id, $readIds),
                'created_at'     => $a->created_at->format('d/m/Y'),
                'end_date'       => $a->end_date?->format('d/m/Y H:i'),
            ];
        })->values();

        $unread = $result->where('is_read', false)->count();

        return response()->json([
            'success'      => true,
            'data'         => $result,
            'unread_count' => $unread,
        ]);
    }

    /**
     * Tandai satu pengumuman sudah dibaca
     */
    public function markRead($id)
    {
        $employee = $this->getEmployee();

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Karyawan tidak ditemukan.'], 404);
        }

        $announcement = Announcement::find($id);
        if (!$announcement) {
            return response()->json(['success' => false, 'message' => 'Pengumuman tidak ditemukan.'], 404);
        }

        // Pastikan pengumuman ini memang untuk karyawan ini
        if (!$announcement->isForEmployee($employee)) {
            return response()->json(['success' => false, 'message' => 'Tidak diizinkan.'], 403);
        }

        AnnouncementRead::firstOrCreate(
            ['announcement_id' => $id, 'employee_id' => $employee->id],
            ['read_at' => now()]
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengumuman ditandai sudah dibaca.',
        ]);
    }

    /**
     * Tandai semua pengumuman sudah dibaca
     */
    public function markAllRead()
    {
        $employee = $this->getEmployee();

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Karyawan tidak ditemukan.'], 404);
        }

        $announcements = Announcement::active()->byPriority()->get();

        foreach ($announcements as $a) {
            if ($a->isForEmployee($employee)) {
                AnnouncementRead::firstOrCreate(
                    ['announcement_id' => $a->id, 'employee_id' => $employee->id],
                    ['read_at' => now()]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Semua pengumuman ditandai sudah dibaca.',
        ]);
    }

    /**
     * Ambil pengumuman yang harus tampil sebagai popup (belum dibaca & show_popup = true)
     */
    public function getPopups()
    {
        $employee = $this->getEmployee();

        if (!$employee) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $readIds = AnnouncementRead::where('employee_id', $employee->id)
            ->pluck('announcement_id')
            ->toArray();

        $popups = Announcement::active()
            ->where('show_popup', true)
            ->byPriority()
            ->get()
            ->filter(fn($a) => $a->isForEmployee($employee) && !in_array($a->id, $readIds))
            ->map(fn($a) => [
                'id'             => $a->id,
                'title'          => $a->title,
                'content'        => $a->content,
                'type'           => $a->type,
                'type_label'     => $a->type_label,
                'type_icon'      => $a->type_icon,
                'priority'       => $a->priority,
                'priority_label' => $a->priority_label,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data'    => $popups,
        ]);
    }

    /**
     * Jumlah pengumuman belum dibaca (untuk badge navbar)
     */
    public function unreadCount()
    {
        $employee = $this->getEmployee();

        if (!$employee) {
            return response()->json(['success' => true, 'count' => 0]);
        }

        $readIds = AnnouncementRead::where('employee_id', $employee->id)
            ->pluck('announcement_id')
            ->toArray();

        $count = Announcement::active()
            ->get()
            ->filter(fn($a) => $a->isForEmployee($employee) && !in_array($a->id, $readIds))
            ->count();

        return response()->json([
            'success' => true,
            'count'   => $count,
        ]);
    }
}
