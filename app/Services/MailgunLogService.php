<?php

namespace App\Services;

use App\Models\EmailSmtpSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailgunLogService
{
    private const BASE_URL = 'https://api.mailgun.net/v3';
    private const EU_BASE_URL = 'https://api.eu.mailgun.net/v3';

    /**
     * Get Mailgun config for a given context from DB.
     * Returns ['api_key' => ..., 'domain' => ..., 'region' => 'us'|'eu'] or null.
     */
    public function getConfig(string $context): ?array
    {
        $record = EmailSmtpSetting::query()->where('context', $context)->first();

        if (!$record) {
            return null;
        }

        $apiKey = $record->getMailgunApiKeyDecrypted();
        $domain = $record->mailgun_domain;

        if (empty($apiKey) || empty($domain)) {
            return null;
        }

        return [
            'api_key' => $apiKey,
            'domain'  => $domain,
        ];
    }

    /**
     * Get all configured Mailgun contexts (those with api_key + domain set).
     * Returns array keyed by context: ['notifications' => [...], 'interview' => [...]]
     */
    public function getAllConfigs(): array
    {
        $configs = [];

        foreach (EmailSmtpSettingService::contexts() as $context) {
            $config = $this->getConfig($context);
            if ($config !== null) {
                $configs[$context] = $config;
            }
        }

        return $configs;
    }

    /**
     * Fetch events from Mailgun Events API.
     *
     * @param  string      $context   'notifications' or 'interview'
     * @param  array       $filters   Optional: event, recipient, begin, end, limit, cursor, cursor_direction
     * @return array{success: bool, data: array, next_cursor: string|null, prev_cursor: string|null, error: string|null}
     */
    public function fetchEvents(string $context, array $filters = []): array
    {
        $config = $this->getConfig($context);

        if (!$config) {
            return [
                'success'      => false,
                'data'         => [],
                'next_cursor'  => null,
                'prev_cursor'  => null,
                'error'        => 'Konfigurasi Mailgun (API Key & Domain) belum diatur untuk konteks ini.',
            ];
        }

        $apiKey = $config['api_key'];
        $domain = $config['domain'];

        // Determine base URL from domain (EU domains contain .eu.mailgun)
        $baseUrl = (str_contains($domain, 'eu.mailgun') || str_contains($apiKey, 'key-eu'))
            ? self::EU_BASE_URL
            : self::BASE_URL;

        $params = [
            'limit' => min((int) ($filters['limit'] ?? 25), 300),
        ];

        // Cursor-based pagination (takes priority over other filters)
        if (!empty($filters['cursor'])) {
            $params['p']    = $filters['cursor'];
            $params['page'] = $filters['cursor_direction'] ?? 'next';
        } else {
            if (!empty($filters['event'])) {
                $params['event'] = $filters['event'];
            }
            if (!empty($filters['recipient'])) {
                $params['recipient'] = $filters['recipient'];
            }
            if (!empty($filters['begin'])) {
                $params['begin'] = $filters['begin'];
            }
            if (!empty($filters['end'])) {
                $params['end'] = $filters['end'];
            }
            if (!empty($filters['subject'])) {
                $params['subject'] = $filters['subject'];
            }
        }

        try {
            $response = Http::withBasicAuth('api', $apiKey)
                ->timeout(15)
                ->get("{$baseUrl}/{$domain}/events", $params);

            if ($response->failed()) {
                $body = $response->json();
                $errorMsg = $body['message'] ?? "HTTP {$response->status()}";
                Log::warning("Mailgun Events API error [{$context}]: {$errorMsg}");

                return [
                    'success'      => false,
                    'data'         => [],
                    'next_cursor'  => null,
                    'prev_cursor'  => null,
                    'error'        => "Mailgun API error: {$errorMsg}",
                ];
            }

            $body   = $response->json();
            $items  = $body['items'] ?? [];
            $paging = $body['paging'] ?? [];

            // Extract opaque cursor tokens from Mailgun paging URLs
            $nextCursor = $this->extractCursor($paging['next'] ?? null);
            $prevCursor = $this->extractCursor($paging['previous'] ?? null);

            // Normalize event items for frontend display
            $normalized = array_map([$this, 'normalizeEvent'], $items);

            return [
                'success'      => true,
                'data'         => $normalized,
                'next_cursor'  => $nextCursor,
                'prev_cursor'  => $prevCursor,
                'error'        => null,
            ];
        } catch (\Exception $e) {
            Log::error("Mailgun Events fetch exception [{$context}]: " . $e->getMessage());

            return [
                'success'      => false,
                'data'         => [],
                'next_cursor'  => null,
                'prev_cursor'  => null,
                'error'        => 'Gagal menghubungi Mailgun API: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch events from all configured contexts and merge results.
     */
    public function fetchAllContextsEvents(array $filters = []): array
    {
        $configs = $this->getAllConfigs();

        if (empty($configs)) {
            return [
                'success' => false,
                'data'    => [],
                'paging'  => [],
                'error'   => 'Tidak ada konteks dengan konfigurasi Mailgun yang lengkap.',
            ];
        }

        $allItems = [];
        $errors   = [];

        foreach ($configs as $context => $config) {
            $result = $this->fetchEvents($context, $filters);
            if ($result['success']) {
                foreach ($result['data'] as $item) {
                    $item['_context'] = $context;
                    $allItems[]       = $item;
                }
            } else {
                $errors[] = "[{$context}] {$result['error']}";
            }
        }

        // Sort merged results by timestamp descending
        usort($allItems, fn($a, $b) => ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0));

        return [
            'success' => count($allItems) > 0 || empty($errors),
            'data'    => $allItems,
            'paging'  => [],
            'error'   => !empty($errors) ? implode('; ', $errors) : null,
            'next_cursor' => null,
            'prev_cursor' => null,
        ];
    }

    /**
     * Test Mailgun API connectivity for a context.
     */
    public function testConnection(string $context): array
    {
        $config = $this->getConfig($context);

        if (!$config) {
            return ['success' => false, 'message' => 'API Key dan Domain Mailgun belum diatur.'];
        }

        $apiKey  = $config['api_key'];
        $domain  = $config['domain'];
        $baseUrl = (str_contains($domain, 'eu.mailgun') || str_contains($apiKey, 'key-eu'))
            ? self::EU_BASE_URL
            : self::BASE_URL;

        try {
            $response = Http::withBasicAuth('api', $apiKey)
                ->timeout(10)
                ->get("{$baseUrl}/domains/{$domain}");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => 'Koneksi Mailgun berhasil. Domain: ' . ($data['domain']['name'] ?? $domain),
                ];
            }

            return [
                'success' => false,
                'message' => 'Mailgun API menolak koneksi. Cek API Key dan Domain Anda. HTTP ' . $response->status(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Gagal terhubung ke Mailgun: ' . $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Extract the opaque cursor token from a Mailgun paging URL.
     * Returns the value of the `p` query parameter, or null if not present.
     */
    private function extractCursor(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        $parsed = parse_url($url);
        if (empty($parsed['query'])) {
            return null;
        }

        parse_str($parsed['query'], $queryParams);

        // `p` is Mailgun's cursor token; `page` indicates direction (next/previous)
        if (empty($queryParams['p'])) {
            return null;
        }

        // Return JSON-encoded cursor: {p, page}
        return json_encode([
            'p'    => $queryParams['p'],
            'page' => $queryParams['page'] ?? 'next',
        ]);
    }

    private function normalizeEvent(array $event): array
    {
        return [
            'id'          => $event['id'] ?? ($event['message']['headers']['message-id'] ?? null),
            'event'       => $event['event'] ?? 'unknown',
            'timestamp'   => $event['timestamp'] ?? null,
            'datetime'    => isset($event['timestamp'])
                ? date('Y-m-d H:i:s', (int) $event['timestamp'])
                : null,
            'recipient'   => $event['recipient'] ?? ($event['envelope']['targets'] ?? null),
            'sender'      => $event['envelope']['sender'] ?? null,
            'subject'     => $event['message']['headers']['subject'] ?? null,
            'message_id'  => $event['message']['headers']['message-id'] ?? null,
            'severity'    => $event['severity'] ?? null,
            'reason'      => $event['reason'] ?? null,
            'description' => $event['delivery-status']['description'] ?? ($event['delivery-status']['message'] ?? null),
            'ip'          => $event['ip'] ?? null,
            'country'     => $event['geolocation']['country'] ?? null,
            'user_agent'  => $event['client-info']['user-agent'] ?? null,
            'tags'        => $event['tags'] ?? [],
        ];
    }
}
