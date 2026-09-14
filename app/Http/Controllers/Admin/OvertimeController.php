<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\OvertimeExport;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class OvertimeController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->get('date_from', now()->toDateString());
        $to = $request->get('date_to', now()->toDateString());
        $category = $request->get('category');
        $search = trim($request->get('search', ''));
        $employeeId = $request->filled('employee_id') ? (int) $request->get('employee_id') : null;

        $overtimes = Attendance::with('employee')
            ->whereBetween('attendance_date', [$from, $to])
            ->whereNotNull('check_out')
            ->whereNotNull('overtime_category')
            ->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId))
            ->when($search, function ($query) use ($search) {
                $query->whereHas('employee', function ($employeeQuery) use ($search) {
                    $employeeQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            })
            ->when($category, fn ($query) => $query->where('overtime_category', $category))
            ->orderByDesc('attendance_date')
            ->orderBy('overtime_category')
            ->paginate(25)
            ->withQueryString();

        $employees = Employee::orderBy('employee_code', 'asc')->get(['id', 'employee_code', 'name']);

        return view('admin.overtime.index', compact('overtimes', 'from', 'to', 'category', 'search', 'employeeId', 'employees'));
    }

    public function export(Request $request)
    {
        $from = $request->get('date_from', now()->startOfMonth()->toDateString());
        $to = $request->get('date_to', now()->toDateString());
        $category = $request->get('category');
        $search = trim($request->get('search', ''));
        $employeeId = $request->filled('employee_id') ? (int) $request->get('employee_id') : null;

        $filename = 'overtime_' . $from . '_' . $to;
        if ($employeeId) {
            $employee = Employee::find($employeeId);
            if ($employee) {
                $filename .= '_' . Str::slug($employee->name);
            }
        }
        $filename .= '.xlsx';

        return Excel::download(
            new OvertimeExport($from, $to, $category ?: null, $search ?: null, $employeeId),
            $filename
        );
    }
}
