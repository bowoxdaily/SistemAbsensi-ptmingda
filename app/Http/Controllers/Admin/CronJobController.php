<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CronJobController extends Controller
{
    /**
     * Display cron job settings page
     */
    public function index()
    {
        return view('admin.settings.cronjob');
    }

    /**
     * Test specific command
     */
    public function testCommand(Request $request)
    {
        try {
            $command = $request->input('command');

            // Validate command
            $allowedCommands = [
                'attendance:generate-absent',
                'schedule:run',
                'schedule:list'
            ];

            if (!in_array($command, $allowedCommands)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Command tidak diizinkan'
                ], 403);
            }

            // Run command
            Artisan::call($command);
            $output = Artisan::output();

            // Update last run time
            Cache::put('cron_last_run', now(), now()->addDays(7));

            return response()->json([
                'success' => true,
                'message' => 'Command berhasil dijalankan',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run scheduler manually
     */
    public function runScheduler(Request $request)
    {
        try {
            Artisan::call('schedule:run');
            $output = Artisan::output();

            // Update last run time
            Cache::put('cron_last_run', now(), now()->addDays(7));

            return response()->json([
                'success' => true,
                'message' => 'Scheduler berhasil dijalankan',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get schedule list
     */
    public function getScheduleList()
    {
        try {
            Artisan::call('schedule:list');
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check cron status
     */
    public function checkStatus()
    {
        try {
            // Use both cache and file-based tracking for better reliability
            $lastRun = Cache::get('cron_last_run');

            // Also check sentinel file (created by scheduler)
            $sentinelFile = storage_path('framework/schedule-sentinel');
            $fileLastRun = null;

            if (file_exists($sentinelFile)) {
                $fileLastRun = Carbon::createFromTimestamp(filemtime($sentinelFile));

                // Use the most recent time between cache and file
                if (!$lastRun || ($fileLastRun && $fileLastRun->gt(Carbon::parse($lastRun)))) {
                    $lastRun = $fileLastRun->toDateTimeString();
                    // Update cache with file time
                    Cache::put('cron_last_run', $lastRun, now()->addDays(7));
                }
            }

            // Check if cron is running (last run within 2 minutes)
            $isRunning = false;
            $message = 'Cron belum pernah dijalankan atau tidak aktif';
            $diffInMinutes = null;

            if ($lastRun) {
                $lastRunTime = Carbon::parse($lastRun);
                $diffInMinutes = $lastRunTime->diffInMinutes(now());

                if ($diffInMinutes <= 2) {
                    $isRunning = true;
                    $message = 'Cron sedang aktif dan berjalan normal';
                } else if ($diffInMinutes <= 5) {
                    $isRunning = false;
                    $message = 'Cron mungkin bermasalah. Last run: ' . $lastRunTime->diffForHumans(null, true) . ' ago';
                } else {
                    $isRunning = false;
                    $message = 'Cron tidak aktif. Last run: ' . $lastRunTime->diffForHumans(null, true) . ' ago';
                }
            }

            // Calculate next run (every minute)
            $nextRun = null;
            if ($lastRun) {
                $nextRun = Carbon::parse($lastRun)->addMinute()->format('Y-m-d H:i:s');
            }

            return response()->json([
                'success' => true,
                'is_running' => $isRunning,
                'last_run' => $lastRun ? Carbon::parse($lastRun)->format('Y-m-d H:i:s') : null,
                'last_run_human' => $lastRun ? Carbon::parse($lastRun)->diffForHumans(null, true) . ' ago' : null,
                'next_run' => $nextRun,
                'minutes_ago' => $diffInMinutes,
                'message' => $message,
                'sentinel_exists' => file_exists($sentinelFile),
                'cache_exists' => Cache::has('cron_last_run')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cron command for different OS
     */
    public function getCronCommand(Request $request)
    {
        $basePath = base_path();
        $phpPath = PHP_BINARY;
        $os = $request->input('os', 'linux');

        $commands = [
            'linux' => "* * * * * cd {$basePath} && {$phpPath} artisan schedule:run >> /dev/null 2>&1",
            'windows' => "schtasks /create /tn \"Laravel Scheduler\" /tr \"cd {$basePath} && {$phpPath} artisan schedule:run\" /sc minute /mo 1",
            'direct' => "{$phpPath} {$basePath}/artisan schedule:run"
        ];

        return response()->json([
            'success' => true,
            'command' => $commands[$os] ?? $commands['linux'],
            'php_path' => $phpPath,
            'base_path' => $basePath,
            'os' => PHP_OS
        ]);
    }
}
