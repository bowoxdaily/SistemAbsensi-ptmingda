<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EmailWarmupService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmailWarmupController extends Controller
{
    protected EmailWarmupService $service;

    public function __construct()
    {
        $this->service = new EmailWarmupService();
    }

    /**
     * Show dashboard view
     */
    public function index()
    {
        return view('admin.email-warmup.index');
    }

    /**
     * Get warmup status
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->getStatus(),
        ]);
    }

    /**
     * Start warmup
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'total_days' => 'integer|min:1|max:90',
            'start_volume' => 'integer|min:1|max:100',
            'max_volume' => 'integer|min:10|max:5000',
            'increase_percentage' => 'numeric|min:0.1|max:50',
        ], [
            'total_days.required' => 'Total hari harus diisi',
            'start_volume.required' => 'Volume awal harus diisi',
        ]);

        $this->service->start(
            $validated['total_days'] ?? 30,
            $validated['start_volume'] ?? 10,
            $validated['max_volume'] ?? 500,
            $validated['increase_percentage'] ?? 15,
        );

        return response()->json([
            'success' => true,
            'message' => 'Email warmup dimulai',
            'data' => $this->service->getStatus(),
        ]);
    }

    /**
     * Pause warmup
     */
    public function pause(): JsonResponse
    {
        $this->service->pause();

        return response()->json([
            'success' => true,
            'message' => 'Email warmup dijeda',
            'data' => $this->service->getStatus(),
        ]);
    }

    /**
     * Resume warmup
     */
    public function resume(): JsonResponse
    {
        try {
            $this->service->resume();

            return response()->json([
                'success' => true,
                'message' => 'Email warmup dilanjutkan',
                'data' => $this->service->getStatus(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Stop warmup
     */
    public function stop(): JsonResponse
    {
        $this->service->stop();

        return response()->json([
            'success' => true,
            'message' => 'Email warmup dihentikan',
            'data' => $this->service->getStatus(),
        ]);
    }

    /**
     * Get recommendations
     */
    public function recommendations(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'recommendation' => $this->service->getRecommendation(),
            'data' => $this->service->getStatus(),
        ]);
    }

    /**
     * Get warmup logs
     */
    public function logs(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 50);
        $logs = \App\Models\EmailWarmupLog::latest('sent_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }
}
