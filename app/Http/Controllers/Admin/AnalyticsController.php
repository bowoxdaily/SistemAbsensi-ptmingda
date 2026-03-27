<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * Controller untuk analytics dan dashboard insights
 * 
 * Endpoints:
 * GET /api/analytics/overview
 * GET /api/analytics/trend
 * GET /api/analytics/by-department
 * GET /api/analytics/overtime
 * GET /api/analytics/top-late-employees
 * GET /api/analytics/top-absent-employees
 * GET /api/analytics/attendance-rate
 * GET /api/analytics/heatmap
 * GET /api/analytics/supervisor-performance
 * GET /admin/analytics (view page)
 */
class AnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Show analytics dashboard page
     * GET /admin/analytics
     */
    public function index()
    {
        return view('admin.analytics.dashboard');
    }

    /**
     * Get attendance overview API
     * GET /api/analytics/overview
     * 
     * Params:
     *   - start_date (YYYY-MM-DD, default: 1st of current month)
     *   - end_date (YYYY-MM-DD, default: today)
     */
    public function overview(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        try {
            $data = $this->analyticsService->getAttendanceOverview($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get attendance trend (line chart data)
     * GET /api/analytics/trend
     * 
     * Params:
     *   - start_date (YYYY-MM-DD)
     *   - end_date (YYYY-MM-DD)
     *   - status (hadir|terlambat|izin|sakit|alpha|cuti, optional)
     */
    public function trend(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $statusFilter = $request->get('status');

        try {
            $data = $this->analyticsService->getAttendanceTrend($startDate, $endDate, $statusFilter);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get attendance by department
     * GET /api/analytics/by-department
     */
    public function byDepartment(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        try {
            $data = $this->analyticsService->getAttendanceByDepartment($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get overtime statistics
     * GET /api/analytics/overtime
     */
    public function overtime(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        try {
            $data = $this->analyticsService->getOvertimeStats($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get top late employees (ranking)
     * GET /api/analytics/top-late-employees
     */
    public function topLateEmployees(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $limit = $request->get('limit', 10);

        try {
            $data = $this->analyticsService->getTopLateEmployees($startDate, $endDate, $limit);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get top absent employees (alpha count ranking)
     * GET /api/analytics/top-absent-employees
     */
    public function topAbsentEmployees(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $limit = $request->get('limit', 10);

        try {
            $data = $this->analyticsService->getTopAbsentEmployees($startDate, $endDate, $limit);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get attendance rate percentages
     * GET /api/analytics/attendance-rate
     */
    public function attendanceRate(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        try {
            $data = $this->analyticsService->getAttendanceRate($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get attendance heatmap (by day and hour)
     * GET /api/analytics/heatmap
     */
    public function heatmap(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->subDay(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        try {
            $data = $this->analyticsService->getAttendanceHeatmap($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get supervisor performance metrics
     * GET /api/analytics/supervisor-performance
     */
    public function supervisorPerformance(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        try {
            $data = $this->analyticsService->getSupervisorPerformance($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 400);
        }
    }
}
