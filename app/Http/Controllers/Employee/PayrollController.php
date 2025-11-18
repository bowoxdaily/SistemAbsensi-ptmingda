<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollController extends Controller
{
    /**
     * Display payroll history page for employee
     */
    public function index()
    {
        return view('employee.payroll.index');
    }

    /**
     * Get payroll history data for employee (AJAX)
     */
    public function list(Request $request)
    {
        try {
            $employee = Auth::user()->employee;

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data karyawan tidak ditemukan'
                ], 404);
            }

            $search = $request->get('search', '');
            $period = $request->get('period');
            $status = $request->get('status');
            $perPage = $request->get('per_page', 10);

            $query = Payroll::with(['employee'])
                ->where('employee_id', $employee->id)
                ->when($search, function ($q, $search) {
                    return $q->where('payroll_code', 'like', "%{$search}%");
                })
                ->when($period, function ($q, $period) {
                    return $q->where('period_month', $period);
                })
                ->when($status, function ($q, $status) {
                    return $q->where('status', $status);
                })
                ->orderBy('period_month', 'desc')
                ->orderBy('created_at', 'desc');

            $payrolls = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $payrolls
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show payroll detail
     */
    public function show($id)
    {
        try {
            $employee = Auth::user()->employee;

            $payroll = Payroll::with(['employee.department', 'employee.position'])
                ->where('id', $id)
                ->where('employee_id', $employee->id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $payroll
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data payroll tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Get payroll statistics for employee
     */
    public function statistics()
    {
        try {
            $employee = Auth::user()->employee;

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data karyawan tidak ditemukan'
                ], 404);
            }

            // Get current year
            $currentYear = date('Y');

            // Total payroll received
            $totalReceived = Payroll::where('employee_id', $employee->id)
                ->where('status', '!=', 'draft')
                ->count();

            // Total earnings this year (period_month format: YYYY-MM)
            $totalEarningsThisYear = Payroll::where('employee_id', $employee->id)
                ->where('status', '!=', 'draft')
                ->where('period_month', 'like', $currentYear . '%')
                ->sum('net_salary');

            // Latest payroll
            $latestPayroll = Payroll::where('employee_id', $employee->id)
                ->where('status', '!=', 'draft')
                ->orderBy('period_month', 'desc')
                ->first();

            // Monthly earnings for chart (last 6 months)
            $monthlyEarnings = Payroll::where('employee_id', $employee->id)
                ->where('status', '!=', 'draft')
                ->where('period_month', '>=', date('Y-m', strtotime('-6 months')))
                ->orderBy('period_month', 'asc')
                ->get()
                ->map(function ($payroll) {
                    return [
                        'period' => $payroll->period_month,
                        'period_formatted' => $payroll->formatted_period,
                        'net_salary' => $payroll->net_salary,
                        'total_earnings' => $payroll->total_earnings,
                        'total_deductions' => $payroll->total_deductions,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'total_received' => $totalReceived,
                    'total_earnings_this_year' => $totalEarningsThisYear,
                    'latest_payroll' => $latestPayroll,
                    'monthly_earnings' => $monthlyEarnings,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat statistik: ' . $e->getMessage()
            ], 500);
        }
    }
}
