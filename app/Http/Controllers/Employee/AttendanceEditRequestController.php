<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AttendanceEditRequest;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceEditRequestController extends Controller
{
    /**
     * Display list of employee's own attendance edit requests (read-only)
     */
    public function index()
    {
        // Get current employee
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        return view('employee.attendance.edit-requests', compact('employee'));
    }

    /**
     * Get employee's own attendance edit requests (API, untuk DataTable AJAX)
     * Read-only endpoint - no approve/reject actions
     */
    public function list(Request $request)
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        // Only show requests for this employee's attendances
        $query = AttendanceEditRequest::with([
            'attendance.employee.department',
            'attendance.employee.subDepartment',
            'requester',
            'reviewer',
        ])
        ->whereHas('attendance', function ($q) use ($employee) {
            $q->where('employee_id', $employee->id);
        });

        // Filter by status (if provided)
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $requests = $query->latest()->paginate($request->get('per_page', 15));

        // Explicitly format date fields to avoid timezone shift in JS
        $requests->getCollection()->transform(function ($item) {
            $item->old_attendance_date = $item->old_attendance_date
                ? Carbon::parse($item->old_attendance_date)->format('Y-m-d')
                : null;
            $item->new_attendance_date = $item->new_attendance_date
                ? Carbon::parse($item->new_attendance_date)->format('Y-m-d')
                : null;
            return $item;
        });

        return response()->json([
            'success' => true,
            'data'    => $requests,
        ]);
    }

    /**
     * Get statistics for employee's attendance edit requests
     */
    public function stats()
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'data'    => [
                    'pending'  => 0,
                    'approved' => 0,
                    'rejected' => 0,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'pending'  => AttendanceEditRequest::whereHas('attendance', function ($q) use ($employee) {
                    $q->where('employee_id', $employee->id);
                })->where('status', 'pending')->count(),
                'approved' => AttendanceEditRequest::whereHas('attendance', function ($q) use ($employee) {
                    $q->where('employee_id', $employee->id);
                })->where('status', 'approved')->count(),
                'rejected' => AttendanceEditRequest::whereHas('attendance', function ($q) use ($employee) {
                    $q->where('employee_id', $employee->id);
                })->where('status', 'rejected')->count(),
            ],
        ]);
    }

    /**
     * Get count of pending requests (for badge)
     */
    public function pendingCount()
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json(['count' => 0]);
        }

        $count = AttendanceEditRequest::whereHas('attendance', function ($q) use ($employee) {
            $q->where('employee_id', $employee->id);
        })->where('status', 'pending')->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Get detail of one request (read-only)
     */
    public function detail($id)
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $editRequest = AttendanceEditRequest::with([
            'attendance.employee.department',
            'attendance.employee.subDepartment',
            'requester',
            'reviewer',
        ])
        ->whereHas('attendance', function ($q) use ($employee) {
            $q->where('employee_id', $employee->id);
        })
        ->findOrFail($id);

        // Format date fields
        $data = $editRequest->toArray();
        foreach (['old_attendance_date', 'new_attendance_date'] as $field) {
            if (isset($data[$field]) && $data[$field]) {
                $data[$field] = Carbon::parse($editRequest->$field)->format('Y-m-d');
            }
        }

        return response()->json(['success' => true, 'data' => $data]);
    }
}
