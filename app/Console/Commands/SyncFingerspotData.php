<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\FingerspotWebhookController;
use App\Models\FingerspotSetting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SyncFingerspotData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fingerspot:sync
                            {--setting-id= : Specific setting ID to sync}
                            {--sync-date= : Sync data for specific date (Y-m-d)}
                            {--days-back=0 : Sync from N days back (0=today, 1=yesterday)}
                            {--no-date-filter : Disable date filter and fetch all data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync attendance data from Fingerspot API (Pull Mode)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $settingId = $this->option('setting-id');
        $syncDateOption = $this->option('sync-date');
        $daysBackOption = (int) $this->option('days-back');
        $disableDateFilter = (bool) $this->option('no-date-filter');

        // Default behavior for scheduled runs: sync only current date to avoid heavy full-history fetches.
        // For backfill/debug, user can pass --sync-date, --days-back, or --no-date-filter.
        $syncDate = null;
        if (!$disableDateFilter) {
            if (!empty($syncDateOption)) {
                try {
                    $syncDate = Carbon::parse($syncDateOption)->format('Y-m-d');
                } catch (\Exception $e) {
                    $this->error("Invalid --sync-date format. Use Y-m-d, contoh: 2026-07-18");
                    return 1;
                }
            } else {
                $daysBack = max(0, $daysBackOption);
                $syncDate = now()->subDays($daysBack)->format('Y-m-d');
            }
        }

        // Get settings with auto_sync enabled and api_url configured
        $query = FingerspotSetting::where('is_active', true)
            ->where('auto_sync', true)
            ->whereNotNull('api_url')
            ->where('api_url', '!=', '');

        if ($settingId) {
            $query->where('id', $settingId);
        }

        $settings = $query->get();

        if ($settings->isEmpty()) {
            $this->info('No active Fingerspot settings with auto sync enabled.');
            return 0;
        }

        $controller = new FingerspotWebhookController();

        foreach ($settings as $setting) {
            $this->info("Syncing from: {$setting->name} ({$setting->api_url})");
            if ($syncDate) {
                $this->info("  Date filter: {$syncDate}");
            } else {
                $this->warn('  Date filter: DISABLED (full data fetch)');
            }

            try {
                $payload = ['api_url' => $setting->api_url];
                if ($syncDate) {
                    $payload['sync_date'] = $syncDate;
                }

                $request = Request::create('/api/fingerspot/fetch', 'POST', $payload);

                $response = $controller->fetchFromApi($request);
                $result = json_decode($response->getContent(), true);

                if ($result['success']) {
                    $data = $result['data'];
                    $this->info("  ✓ Total: {$data['total']}, Processed: {$data['processed']}, Failed: {$data['failed']}, Skipped: {$data['skipped']}");

                    Log::info('Fingerspot auto sync completed', [
                        'setting_id' => $setting->id,
                        'setting_name' => $setting->name,
                        'total' => $data['total'],
                        'processed' => $data['processed'],
                        'failed' => $data['failed'],
                        'skipped' => $data['skipped'],
                    ]);
                } else {
                    $this->error("  ✗ Failed: " . ($result['message'] ?? 'Unknown error'));
                    Log::error('Fingerspot auto sync failed', [
                        'setting_id' => $setting->id,
                        'message' => $result['message'] ?? 'Unknown error',
                    ]);
                }
            } catch (\Exception $e) {
                $this->error("  ✗ Error: " . $e->getMessage());
                Log::error('Fingerspot auto sync exception', [
                    'setting_id' => $setting->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info('Fingerspot sync completed.');
        return 0;
    }
}
