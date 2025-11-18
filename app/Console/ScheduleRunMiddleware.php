<?php

namespace App\Console;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ScheduleRunMiddleware
{
    /**
     * Update tracking setiap kali schedule:run dipanggil
     */
    public static function updateTracking()
    {
        try {
            $now = now();

            // Update cache
            Cache::put('cron_last_run', $now, $now->addDays(7));

            // Update sentinel file
            $sentinelFile = storage_path('framework/schedule-sentinel');

            // Create directory if not exists
            $dir = dirname($sentinelFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Touch file to update modification time
            touch($sentinelFile);

            // Also write timestamp to file for debugging
            file_put_contents($sentinelFile, $now->toDateTimeString());
        } catch (\Exception $e) {
            // Silent fail - don't break scheduler if tracking fails
            Log::warning('Failed to update schedule tracking: ' . $e->getMessage());
        }
    }
}
