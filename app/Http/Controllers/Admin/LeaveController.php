<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class LeaveController extends Controller
{
    /**
     * Display list of leave requests
     */
    public function index(Request $request)
    {
        // Security: Ensure only admin can access
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $query = Leave::with(['employee.department', 'employee.position'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Filter by leave type
        if ($request->has('leave_type') && $request->leave_type != '') {
            $query->where('leave_type', $request->leave_type);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->start_date != '') {
            $query->whereDate('start_date', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date != '') {
            $query->whereDate('end_date', '<=', $request->end_date);
        }

        $leaves = $query->paginate(20);

        // Statistics
        $stats = [
            'pending' => Leave::where('status', 'pending')->count(),
            'approved' => Leave::where('status', 'approved')->count(),
            'rejected' => Leave::where('status', 'rejected')->count(),
            'total' => Leave::count(),
        ];

        return view('admin.leave.index', compact('leaves', 'stats'));
    }

    /**
     * Show detail of leave request
     */
    public function show($id)
    {
        // Security check
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        try {
            $leave = Leave::with(['employee.department', 'employee.position'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $leave
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data cuti tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Approve leave request
     */
    public function approve($id)
    {
        // Security check
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        try {
            $leave = Leave::findOrFail($id);

            if ($leave->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya pengajuan dengan status pending yang dapat disetujui'
                ], 422);
            }

            $leave->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            // Load relations for WhatsApp notification
            $leave->load(['employee', 'approver']);

            // Send WhatsApp notification to employee
            try {
                $whatsappService = new WhatsAppService();
                $whatsappService->sendLeaveApprovedNotification($leave);
            } catch (\Exception $e) {
                Log::warning('Failed to send WhatsApp leave approved notification: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan cuti berhasil disetujui'
            ]);
        } catch (\Exception $e) {
            Log::error('Error approving leave: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyetujui cuti'
            ], 500);
        }
    }

    /**
     * Reject leave request
     */
    public function reject(Request $request, $id)
    {
        // Security check
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        try {
            $validated = $request->validate([
                'rejection_reason' => 'required|string|max:500',
            ]);

            $leave = Leave::findOrFail($id);

            if ($leave->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya pengajuan dengan status pending yang dapat ditolak'
                ], 422);
            }

            $leave->update([
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
            ]);

            // Load relations for WhatsApp notification
            $leave->load(['employee', 'approver']);

            // Send WhatsApp notification to employee
            try {
                $whatsappService = new WhatsAppService();
                $whatsappService->sendLeaveRejectedNotification($leave);
            } catch (\Exception $e) {
                Log::warning('Failed to send WhatsApp leave rejected notification: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan cuti berhasil ditolak'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error rejecting leave: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menolak cuti'
            ], 500);
        }
    }

    /**
     * Delete leave request (admin only)
     */
    public function destroy($id)
    {
        // Security check
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        try {
            $leave = Leave::findOrFail($id);

            // Delete attachment if exists
            if ($leave->attachment && Storage::disk('public')->exists($leave->attachment)) {
                Storage::disk('public')->delete($leave->attachment);
            }

            $leave->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data cuti berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting leave: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus cuti'
            ], 500);
        }
    }
}
