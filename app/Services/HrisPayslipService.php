<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class HrisPayslipService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.hris.base_url', ''), '/');
        $this->apiKey = config('services.hris.api_key', '');
        $this->timeout = (int) config('services.hris.timeout', 30);
    }

    /**
     * Check if HRIS integration is configured and enabled.
     */
    public function isConfigured(): bool
    {
        return !empty($this->baseUrl) && !empty($this->apiKey);
    }

    /**
     * Get list of payslips for an employee from HRIS.
     *
     * @param string $employeeId  The employee_id used in the HRIS system (e.g. employee_code)
     * @return array{success: bool, data?: array, message?: string}
     */
    public function getPayslipList(string $employeeId): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Integrasi HRIS belum dikonfigurasi. Hubungi administrator.',
            ];
        }

        try {
            $url = $this->baseUrl . '/api/payslip/list';

            Log::info('HRIS Payslip: Fetching payslip list', [
                'url' => $url,
                'employee_id' => $employeeId,
            ]);

            $response = Http::withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->timeout($this->timeout)
                ->get($url, [
                    'employee_id' => $employeeId,
                ]);

            if ($response->successful()) {
                $responseJson = $response->json();
                $actualData = $responseJson['data'] ?? [];

                Log::info('HRIS Payslip: List fetched successfully', [
                    'employee_id' => $employeeId,
                    'count' => is_array($actualData['payslips'] ?? null) ? count($actualData['payslips']) : 0,
                ]);

                return [
                    'success' => $responseJson['success'] ?? true,
                    'data' => $actualData,
                ];
            }

            Log::warning('HRIS Payslip: API returned error', [
                'employee_id' => $employeeId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'Gagal mengambil data payslip dari HRIS (HTTP ' . $response->status() . ')',
            ];
        } catch (\Exception $e) {
            Log::error('HRIS Payslip: Exception fetching list', [
                'employee_id' => $employeeId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan koneksi ke HRIS: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Download payslip PDF for an employee from HRIS.
     *
     * @param string $employeeId  The employee_id used in the HRIS system
     * @param string $month       The month in YYYY-MM format
     * @return array{success: bool, content?: string, content_type?: string, filename?: string, message?: string}
     */
    public function downloadPayslipPdf(string $employeeId, string $month): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Integrasi HRIS belum dikonfigurasi. Hubungi administrator.',
            ];
        }

        try {
            $url = $this->baseUrl . '/api/payslip/download';

            Log::info('HRIS Payslip: Downloading PDF', [
                'url' => $url,
                'employee_id' => $employeeId,
                'month' => $month,
            ]);

            $response = Http::withHeaders([
                    'X-API-Key' => $this->apiKey,
                ])
                ->timeout($this->timeout)
                ->get($url, [
                    'employee_id' => $employeeId,
                    'month' => $month,
                ]);

            if ($response->successful()) {
                $contentType = $response->header('Content-Type');

                // Check if it's actually a PDF response
                if (str_contains($contentType, 'application/pdf')) {
                    $filename = 'payslip_' . $employeeId . '_' . $month . '.pdf';

                    // Try to get filename from Content-Disposition header
                    $disposition = $response->header('Content-Disposition');
                    if ($disposition && preg_match('/filename="?([^";\s]+)"?/', $disposition, $matches)) {
                        $filename = $matches[1];
                    }

                    Log::info('HRIS Payslip: PDF downloaded successfully', [
                        'employee_id' => $employeeId,
                        'month' => $month,
                        'filename' => $filename,
                        'size' => strlen($response->body()),
                    ]);

                    return [
                        'success' => true,
                        'content' => $response->body(),
                        'content_type' => $contentType,
                        'filename' => $filename,
                    ];
                }

                // API returned JSON error instead of PDF
                $errorData = $response->json();
                return [
                    'success' => false,
                    'message' => $errorData['message'] ?? 'Payslip PDF tidak tersedia untuk periode ini',
                ];
            }

            Log::warning('HRIS Payslip: PDF download failed', [
                'employee_id' => $employeeId,
                'month' => $month,
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'message' => 'Gagal mengunduh payslip PDF dari HRIS (HTTP ' . $response->status() . ')',
            ];
        } catch (\Exception $e) {
            Log::error('HRIS Payslip: Exception downloading PDF', [
                'employee_id' => $employeeId,
                'month' => $month,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan koneksi ke HRIS: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check HRIS connection by making a test request.
     *
     * @param string $testEmployeeId  Optional employee ID to test with
     * @return array{success: bool, message: string}
     */
    public function testConnection(string $testEmployeeId = ''): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'HRIS Base URL atau API Key belum dikonfigurasi.',
            ];
        }

        try {
            $url = $this->baseUrl . '/api/payslip/list';
            $params = [];
            if ($testEmployeeId) {
                $params['employee_id'] = $testEmployeeId;
            }

            $response = Http::withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->timeout(10)
                ->get($url, $params);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Koneksi ke HRIS berhasil! (HTTP ' . $response->status() . ')',
                ];
            }

            return [
                'success' => false,
                'message' => 'Koneksi gagal. HRIS mengembalikan HTTP ' . $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Tidak dapat terhubung ke HRIS: ' . $e->getMessage(),
            ];
        }
    }
}
