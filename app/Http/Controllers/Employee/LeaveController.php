<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\Employee;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LeaveController extends Controller
{
    /**
     * Display leave history for employee
     */
    public function index()
    {
        // Security: Ensure user is logged in and is an employee (not admin)
        if (!Auth::check()) {
            abort(403, 'Unauthorized action.');
        }

        if (Auth::user()->role === 'admin') {
            return redirect()->route('admin.leave.index');
        }

        $employee = Employee::where('user_id', Auth::id())->firstOrFail();

        // Get all leave requests with pagination
        $leaves = Leave::where('employee_id', $employee->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Statistics
        $stats = [
            'pending' => Leave::where('employee_id', $employee->id)
                ->where('status', 'pending')
                ->count(),
            'approved' => Leave::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->count(),
            'rejected' => Leave::where('employee_id', $employee->id)
                ->where('status', 'rejected')
                ->count(),
            'total_days_approved' => Leave::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereYear('start_date', date('Y'))
                ->sum('total_days'),
        ];

        return view('employee.leave.index', compact('leaves', 'stats', 'employee'));
    }

    /**
     * Store a new leave request
     */
    public function store(Request $request)
    {
        // Security: Ensure user is logged in and is an employee (not admin)
        if (!Auth::check()) {
            abort(403, 'Unauthorized action.');
        }

        if (Auth::user()->role === 'admin') {
            return redirect()->route('admin.leave.index');
        }

        $employee = Employee::where('user_id', Auth::id())->firstOrFail();

        $validated = $request->validate([
            'leave_type' => 'required|in:cuti,izin,sakit',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048', // Max 2MB
        ]);

        // Calculate total days
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        // Handle file upload
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = 'leave_' . $employee->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $attachmentPath = $file->storeAs('leave_attachments', $filename, 'public');
        }

        // Create leave request
        $leave = Leave::create([
            'employee_id' => $employee->id,
            'leave_type' => $validated['leave_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'total_days' => $totalDays,
            'reason' => $validated['reason'],
            'attachment' => $attachmentPath,
            'status' => 'pending',
        ]);

        // Load employee relation for WhatsApp notification
        $leave->load('employee');

        // Send WhatsApp notification to admin
        try {
            $whatsappService = new WhatsAppService();
            $whatsappService->sendLeaveRequestNotification($leave);
        } catch (\Exception $e) {
            Log::warning('Failed to send WhatsApp leave request notification: ' . $e->getMessage());
        }

        // Return JSON for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan cuti berhasil disubmit dan menunggu persetujuan',
                'data' => $leave
            ], 201);
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Pengajuan cuti berhasil disubmit dan menunggu persetujuan');
    }

    /**
     * Cancel leave request (only pending status)
     */
    public function cancel($id)
    {
        // Security: Ensure user is logged in and is an employee (not admin)
        if (!Auth::check()) {
            abort(403, 'Unauthorized action.');
        }

        if (Auth::user()->role === 'admin') {
            return redirect()->route('admin.leave.index');
        }

        $employee = Employee::where('user_id', Auth::id())->firstOrFail();

        $leave = Leave::where('id', $id)
            ->where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->firstOrFail();

        // Delete attachment if exists
        if ($leave->attachment && Storage::disk('public')->exists($leave->attachment)) {
            Storage::disk('public')->delete($leave->attachment);
        }

        $leave->delete();

        // Return JSON for AJAX requests
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan cuti berhasil dibatalkan'
            ]);
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Pengajuan cuti berhasil dibatalkan');
    }
}
