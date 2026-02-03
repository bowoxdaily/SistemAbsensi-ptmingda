<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\FingerspotWebhookController;
use App\Models\FingerspotSetting;
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
    protected $signature = 'fingerspot:sync {--setting-id= : Specific setting ID to sync}';

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

            try {
                $request = Request::create('/api/fingerspot/fetch', 'POST', [
                    'api_url' => $setting->api_url
                ]);

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
