<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;

class OvertimeController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->get('date_from', now()->toDateString());
        $to = $request->get('date_to', now()->toDateString());
        $category = $request->get('category');
        $search = trim($request->get('search', ''));

        $overtimes = Attendance::with('employee')
            ->whereBetween('attendance_date', [$from, $to])
            ->whereNotNull('check_out')
            ->whereNotNull('overtime_category')
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

        return view('admin.overtime.index', compact('overtimes', 'from', 'to', 'category', 'search'));
    }
}