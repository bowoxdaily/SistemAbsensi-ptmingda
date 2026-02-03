<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\FingerspotLog;
use App\Models\FingerspotSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FingerspotWebhookController extends Controller
{
    /**
     * Handle incoming webhook from Fingerspot
     *
     * Expected payload format from Fingerspot:
     * {
     *   "trans_id": "123456",
     *   "cloud_id": "C001",
     *   "attlog": [
     *     {
     *       "pin": "123",
     *       "datetime": "2026-02-03 08:00:00",
     *       "status_scan": "0",
     *       "verify_mode": "1"
     *     }
     *   ]
     * }
     *
     * Or single attlog:
     * {
     *   "pin": "123",
     *   "datetime": "2026-02-03 08:00:00",
     *   "status_scan": "0",
     *   "verify_mode": "1",
     *   "sn": "DEVICE001"
     * }
     */
    public function handleWebhook(Request $request)
    {
        // Get token from header or query parameter
        $token = $request->header('X-Fingerspot-Token')
            ?? $request->header('Authorization')
            ?? $request->query('token');

        // Clean up Bearer prefix if present
        if ($token && str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
        }

        // Validate token
        $setting = FingerspotSetting::findByToken($token);

        if (!$setting) {
            Log::warning('Fingerspot webhook: Invalid token', [
                'token' => $token ? substr($token, 0, 10) . '...' : null,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive token'
            ], 401);
        }

        // Get raw data
        $rawData = $request->all();

        Log::info('Fingerspot webhook received', [
            'setting_id' => $setting->id,
            'ip' => $request->ip(),
            'data_keys' => array_keys($rawData),
        ]);

        try {
            // Handle different payload formats
            $attlogs = $this->extractAttlogs($rawData);

            if (empty($attlogs)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No attlog data found'
                ], 400);
            }

            $results = [];
            $processed = 0;
            $failed = 0;

            foreach ($attlogs as $attlog) {
                try {
                    $result = $this->processAttlog($attlog, $setting, $rawData);
                    $results[] = $result;

                    if ($result['status'] === 'success') {
                        $processed++;
                    } else {
                        $failed++;
                    }
                } catch (\Exception $e) {
                    Log::error('Fingerspot: Error processing attlog', [
                        'attlog' => $attlog,
                        'error' => $e->getMessage(),
                    ]);
                    $failed++;
                    $results[] = [
                        'status' => 'error',
                        'message' => $e->getMessage(),
                        'attlog' => $attlog,
                    ];
                }
            }

            // Update last sync time
            $setting->updateLastSync();

            return response()->json([
                'success' => true,
                'message' => "Processed {$processed} records, {$failed} failed",
                'data' => [
                    'processed' => $processed,
                    'failed' => $failed,
                    'total' => count($attlogs),
                    'results' => $results,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Fingerspot webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract attlogs from various payload formats
     */
    protected function extractAttlogs(array $rawData): array
    {
        // Format 1: Fingerspot Cloud format - single attlog with nested data
        // { "type": "attlog", "cloud_id": "xxx", "data": { "pin": "123", "scan": "...", ... } }
        if (isset($rawData['type']) && $rawData['type'] === 'attlog' && isset($rawData['data'])) {
            $attlog = $rawData['data'];
            $attlog['cloud_id'] = $rawData['cloud_id'] ?? null;
            return [$attlog];
        }

        // Format 2: Array of attlogs in 'attlog' key
        if (isset($rawData['attlog']) && is_array($rawData['attlog'])) {
            return $rawData['attlog'];
        }

        // Format 3: Single attlog object with 'pin' key directly
        if (isset($rawData['pin'])) {
            return [$rawData];
        }

        // Format 4: Array of attlogs directly
        if (isset($rawData[0]) && isset($rawData[0]['pin'])) {
            return $rawData;
        }

        // Format 5: 'data' array containing attlogs or single attlog
        if (isset($rawData['data']) && is_array($rawData['data'])) {
            // Nested data with pin (Fingerspot format)
            if (isset($rawData['data']['pin'])) {
                $attlog = $rawData['data'];
                $attlog['cloud_id'] = $rawData['cloud_id'] ?? null;
                return [$attlog];
            }
            if (isset($rawData['data']['attlog'])) {
                return $rawData['data']['attlog'];
            }
            if (isset($rawData['data'][0]['pin'])) {
                return $rawData['data'];
            }
        }

        return [];
    }

    /**
     * Find employee by Fingerspot PIN
     * Supports matching:
     * 1. fingerspot_pin field directly
     * 2. employee_code field directly (exact match)
     * 3. employee_code with prefix patterns (EMP003, MIF-0233, emp0233)
     */
    protected function findEmployeeByPin(string $pin): ?Employee
    {
        // First try direct match with fingerspot_pin or employee_code
        $employee = Employee::where('fingerspot_pin', $pin)
            ->orWhere('employee_code', $pin)
            ->first();

        if ($employee) {
            return $employee;
        }

        // Try matching with employee_code prefix patterns
        // Fingerspot PIN only supports numbers, but employee_code may have prefix like "EMP003" or "MIF-0233"
        return Employee::where(function ($query) use ($pin) {
            // Match EMP + pin (case insensitive), e.g., EMP003 matches PIN 003
            $query->whereRaw('LOWER(employee_code) = ?', ['emp' . $pin])
                // Match MIF- + pin (case insensitive)
                ->orWhereRaw('LOWER(employee_code) = ?', ['mif-' . $pin])
                // Match just the numeric part at the end of employee_code using regex
                // This handles any alphabetic/symbol prefix like "ABC-", "DEPT_", etc.
                ->orWhereRaw("REGEXP_REPLACE(employee_code, '^[A-Za-z_-]+', '') = ?", [$pin]);
        })->first();
    }

    /**
     * Process single attlog entry
     */
    protected function processAttlog(array $attlog, FingerspotSetting $setting, array $rawData): array
    {
        // Extract required fields - support multiple field names
        $pin = $attlog['pin'] ?? $attlog['PIN'] ?? null;
        // Fingerspot Cloud uses 'scan', others may use 'datetime' or 'date_time'
        $datetime = $attlog['scan'] ?? $attlog['datetime'] ?? $attlog['scan_date'] ?? $attlog['date_time'] ?? null;
        $statusScan = $attlog['status_scan'] ?? $attlog['status'] ?? null;
        $verifyMode = $attlog['verify_mode'] ?? $attlog['verify'] ?? null;
        $photoUrl = $attlog['photo_url'] ?? $attlog['photo'] ?? null;
        $cloudId = $attlog['cloud_id'] ?? $rawData['cloud_id'] ?? null;
        $sn = $attlog['sn'] ?? $attlog['SN'] ?? $rawData['sn'] ?? $cloudId ?? $setting->sn ?? null;

        if (!$pin || !$datetime) {
            return [
                'status' => 'failed',
                'message' => 'Missing required fields (pin or datetime/scan)',
                'attlog' => $attlog,
            ];
        }

        // Parse datetime
        try {
            $scanTime = Carbon::parse($datetime);
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'message' => 'Invalid datetime format: ' . $datetime,
                'attlog' => $attlog,
            ];
        }

        // Find employee by PIN (supports fingerspot_pin, employee_code, and employee_no with prefix)
        $employee = $this->findEmployeeByPin($pin);

        // Create log entry
        $log = FingerspotLog::create([
            'pin' => $pin,
            'employee_id' => $employee?->id,
            'scan_time' => $scanTime,
            'status_scan' => $statusScan,
            'verify_mode' => $verifyMode,
            'sn' => $sn,
            'photo_url' => $photoUrl,
            'process_status' => 'pending',
            'raw_data' => $attlog,
        ]);

        if (!$employee) {
            $log->markAsFailed("Employee not found for PIN: {$pin}");
            return [
                'status' => 'failed',
                'message' => "Employee not found for PIN: {$pin}",
                'log_id' => $log->id,
            ];
        }

        // Process attendance
        return $this->processAttendance($log, $employee, $scanTime, $setting, $photoUrl);
    }

    /**
     * Process attendance from scan log
     */
    protected function processAttendance(
        FingerspotLog $log,
        Employee $employee,
        Carbon $scanTime,
        FingerspotSetting $setting,
        ?string $photoUrl = null
    ): array {
        $attendanceDate = $scanTime->toDateString();

        // Get employee's work schedule for time-based rules
        $schedule = $employee->workSchedule;
        $workStartTime = null;
        $workEndTime = null;

        if ($schedule) {
            try {
                $startTimeStr = substr($schedule->start_time, 0, 5); // HH:MM
                $endTimeStr = substr($schedule->end_time, 0, 5); // HH:MM
                $workStartTime = Carbon::createFromFormat('Y-m-d H:i', $attendanceDate . ' ' . $startTimeStr);
                $workEndTime = Carbon::createFromFormat('Y-m-d H:i', $attendanceDate . ' ' . $endTimeStr);

                // Handle overnight shifts (end time is next day)
                if ($workEndTime->lt($workStartTime)) {
                    $workEndTime->addDay();
                }
            } catch (\Exception $e) {
                Log::warning('Fingerspot: Could not parse work schedule times', [
                    'employee_id' => $employee->id,
                    'schedule' => $schedule->toArray(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Check for existing attendance on this date
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $attendanceDate)
            ->first();

        DB::beginTransaction();
        try {
            if (!$attendance) {
                // No attendance yet - this is a potential check-in
                // Rule: Accept scans before or around work start time as check-in
                $attendance = $this->createCheckIn($employee, $scanTime, $attendanceDate, $photoUrl);

                $log->markAsSuccess($attendance->id, 'Check-in recorded');

                DB::commit();
                return [
                    'status' => 'success',
                    'action' => 'check_in',
                    'attendance_id' => $attendance->id,
                    'employee' => $employee->name,
                    'time' => $scanTime->format('Y-m-d H:i:s'),
                ];
            }

            // Existing attendance - determine if this scan should update check-out
            // Rule: Only update check-out if scan is AFTER work end time
            // Note: check_in is cast to datetime by Eloquent, so we need to format it back to time string
            $checkInTimeStr = $attendance->check_in instanceof \Carbon\Carbon
                ? $attendance->check_in->format('H:i:s')
                : $attendance->check_in;
            $checkInTime = Carbon::parse($attendanceDate . ' ' . $checkInTimeStr);

            // Check if scan is valid for check-out (after work end time)
            $isAfterWorkEnd = true; // Default to true if no schedule
            if ($workEndTime) {
                $isAfterWorkEnd = $scanTime->gte($workEndTime);
            }

            if ($setting->scan_mode === 'first_last') {
                // First/Last mode with work schedule rules
                if ($isAfterWorkEnd) {
                    // Scan is after work end time - valid for check-out
                    $checkOutTimeStr = $attendance->check_out instanceof \Carbon\Carbon
                        ? $attendance->check_out->format('H:i:s')
                        : $attendance->check_out;
                    if (!$attendance->check_out || $scanTime->gt(Carbon::parse($attendanceDate . ' ' . $checkOutTimeStr))) {
                        // Update check-out time
                        $updateData = ['check_out' => $scanTime->format('H:i:s')];
                        if ($photoUrl) {
                            $updateData['photo_out'] = $photoUrl;
                        }
                        $attendance->update($updateData);

                        $log->markAsSuccess($attendance->id, 'Check-out updated (after work end)');

                        DB::commit();
                        return [
                            'status' => 'success',
                            'action' => 'check_out',
                            'attendance_id' => $attendance->id,
                            'employee' => $employee->name,
                            'time' => $scanTime->format('Y-m-d H:i:s'),
                        ];
                    } else {
                        // Scan time is before existing check-out
                        $log->markAsSkipped('Scan time before existing check-out');

                        DB::commit();
                        return [
                            'status' => 'skipped',
                            'message' => 'Scan time before existing check-out',
                            'attendance_id' => $attendance->id,
                        ];
                    }
                } else {
                    // Scan is during work hours (before work end time) - skip for check-out
                    $log->markAsSkipped('Scan during work hours - not valid for check-out');

                    DB::commit();
                    return [
                        'status' => 'skipped',
                        'message' => 'Scan during work hours (before ' . ($workEndTime ? $workEndTime->format('H:i') : 'work end') . ')',
                        'attendance_id' => $attendance->id,
                    ];
                }
            } else {
                // "all" mode - log all scans, but still respect work schedule for check-out
                if ($isAfterWorkEnd && $scanTime->gt($checkInTime)) {
                    $updateData = ['check_out' => $scanTime->format('H:i:s')];
                    if ($photoUrl) {
                        $updateData['photo_out'] = $photoUrl;
                    }
                    $attendance->update($updateData);

                    $log->markAsSuccess($attendance->id, 'Check-out updated (all mode, after work end)');

                    DB::commit();
                    return [
                        'status' => 'success',
                        'action' => 'logged',
                        'attendance_id' => $attendance->id,
                    ];
                } else {
                    // Scan during work hours or before check-in
                    $log->markAsSkipped('Scan during work hours or before check-in');

                    DB::commit();
                    return [
                        'status' => 'skipped',
                        'message' => 'Scan during work hours or before check-in',
                        'attendance_id' => $attendance->id,
                    ];
                }
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $log->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Create check-in attendance record
     */
    protected function createCheckIn(Employee $employee, Carbon $scanTime, string $attendanceDate, ?string $photoUrl = null): Attendance
    {
        $checkInTime = $scanTime->format('H:i:s');

        // Calculate late minutes if employee has work schedule
        $lateMinutes = 0;
        $status = 'hadir';

        $schedule = $employee->workSchedule;

        if ($schedule) {
            try {
                $startTime = substr($schedule->start_time, 0, 5); // HH:MM
                $scheduledTime = Carbon::createFromFormat('Y-m-d H:i', $attendanceDate . ' ' . $startTime);

                if ($scanTime->gt($scheduledTime)) {
                    $lateMinutes = $scheduledTime->diffInMinutes($scanTime);
                    $status = 'terlambat';
                }
            } catch (\Exception $e) {
                Log::warning('Fingerspot: Could not calculate late minutes', [
                    'employee_id' => $employee->id,
                    'schedule' => $schedule->toArray(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return Attendance::create([
            'employee_id' => $employee->id,
            'attendance_date' => $attendanceDate,
            'check_in' => $checkInTime,
            'photo_in' => $photoUrl,
            'status' => $status,
            'late_minutes' => $lateMinutes,
            'notes' => 'Via Fingerspot',
        ]);
    }

    /**
     * Test endpoint to verify webhook is working
     */
    public function test(Request $request)
    {
        $token = $request->header('X-Fingerspot-Token')
            ?? $request->header('Authorization')
            ?? $request->query('token');

        if ($token && str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
        }

        $setting = FingerspotSetting::findByToken($token);

        return response()->json([
            'success' => (bool) $setting,
            'message' => $setting ? 'Connection OK' : 'Invalid token',
            'server_time' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Get webhook logs (for admin)
     */
    public function logs(Request $request)
    {
        $perPage = $request->get('per_page', 50);
        $status = $request->get('status');
        $pin = $request->get('pin');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = FingerspotLog::with('employee')
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('process_status', $status);
        }

        if ($pin) {
            $query->where('pin', 'like', "%{$pin}%");
        }

        if ($dateFrom) {
            $query->whereDate('scan_time', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('scan_time', '<=', $dateTo);
        }

        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Reprocess failed logs
     */
    public function reprocess(Request $request)
    {
        $logIds = $request->input('log_ids', []);

        $setting = FingerspotSetting::getActive();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'No active Fingerspot setting found'
            ], 400);
        }

        $logs = FingerspotLog::whereIn('id', $logIds)
            ->whereIn('process_status', ['failed', 'pending'])
            ->get();

        $processed = 0;
        $failed = 0;

        foreach ($logs as $log) {
            try {
                // Find employee by PIN (supports fingerspot_pin, employee_code, and employee_no with prefix)
                $employee = $this->findEmployeeByPin($log->pin);

                if (!$employee) {
                    $log->markAsFailed("Employee not found for PIN: {$log->pin}");
                    $failed++;
                    continue;
                }

                $log->update(['employee_id' => $employee->id, 'process_status' => 'pending']);

                $result = $this->processAttendance($log, $employee, Carbon::parse($log->scan_time), $setting);

                if (in_array($result['status'], ['success', 'skipped'])) {
                    $processed++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $log->markAsFailed($e->getMessage());
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Reprocessed {$processed} records, {$failed} failed",
            'data' => [
                'processed' => $processed,
                'failed' => $failed,
            ]
        ]);
    }

    /**
     * Reprocess all pending and failed logs
     */
    public function reprocessAll(Request $request)
    {
        $setting = FingerspotSetting::getActive();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'No active Fingerspot setting found'
            ], 400);
        }

        // Get all pending and failed logs that have employee_id
        $logs = FingerspotLog::whereIn('process_status', ['failed', 'pending'])
            ->whereNotNull('employee_id')
            ->orderBy('scan_time', 'asc')
            ->limit(1000) // Process max 1000 at a time to avoid timeout
            ->get();

        $total = $logs->count();
        $processed = 0;
        $failed = 0;

        foreach ($logs as $log) {
            try {
                $employee = Employee::find($log->employee_id);

                if (!$employee) {
                    // Try to find by PIN again
                    $employee = $this->findEmployeeByPin($log->pin);
                    if ($employee) {
                        $log->update(['employee_id' => $employee->id]);
                    }
                }

                if (!$employee) {
                    $log->markAsFailed("Employee not found for PIN: {$log->pin}");
                    $failed++;
                    continue;
                }

                $result = $this->processAttendance($log, $employee, Carbon::parse($log->scan_time), $setting, $log->photo_url);

                if (in_array($result['status'], ['success', 'skipped'])) {
                    $processed++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $log->markAsFailed($e->getMessage());
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Reprocessed {$processed} of {$total} records, {$failed} failed",
            'data' => [
                'total' => $total,
                'processed' => $processed,
                'failed' => $failed,
            ]
        ]);
    }

    /**
     * Fetch and sync data from external Fingerspot API
     * This pulls data from an external endpoint instead of waiting for webhook push
     */
    public function fetchFromApi(Request $request)
    {
        $setting = FingerspotSetting::getActive();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'No active Fingerspot setting found. Please configure Fingerspot first.'
            ], 400);
        }

        // Get API URL from request or setting
        $apiUrl = $request->input('api_url', $setting->api_url ?? 'https://api.mingda.my.id/get_webhook.php');

        if (!$apiUrl) {
            return response()->json([
                'success' => false,
                'message' => 'API URL not configured'
            ], 400);
        }

        try {
            // Fetch data from external API
            $client = new \GuzzleHttp\Client([
                'timeout' => 30,
                'verify' => false, // Skip SSL verification if needed
            ]);

            $response = $client->get($apiUrl);
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            if (!$data || !isset($data['success']) || !$data['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch data from API or invalid response',
                    'raw_response' => substr($body, 0, 500),
                ], 400);
            }

            $attlogs = $data['data'] ?? [];

            if (empty($attlogs)) {
                return response()->json([
                    'success' => true,
                    'message' => 'No attlog data to process',
                    'data' => ['total' => 0, 'processed' => 0, 'failed' => 0, 'skipped' => 0]
                ]);
            }

            // IMPORTANT: Sort attlogs by scan time (oldest first) to ensure proper check-in/check-out order
            // Without sorting, newer scans may be processed first, incorrectly becoming check-ins
            usort($attlogs, function ($a, $b) {
                $getScanTime = function ($record) {
                    // Handle string records
                    if (is_string($record)) {
                        $record = json_decode($record, true) ?? [];
                    }
                    // Extract from nested structure
                    $data = $record['original_data']['data'] ?? $record['original_data'] ?? $record['data'] ?? $record;
                    if (is_string($data)) {
                        $data = json_decode($data, true) ?? [];
                    }
                    return $data['scan'] ?? $data['datetime'] ?? $data['scan_date'] ?? '';
                };

                return strcmp($getScanTime($a), $getScanTime($b));
            });

            Log::info('Fingerspot: Fetched data from API', [
                'url' => $apiUrl,
                'total_records' => count($attlogs),
                'sample_record' => $attlogs[0] ?? null,
            ]);

            $processed = 0;
            $failed = 0;
            $skipped = 0;
            $results = [];

            foreach ($attlogs as $record) {
                try {
                    // Handle case where record might be a string (JSON encoded)
                    if (is_string($record)) {
                        $record = json_decode($record, true);
                        if (!$record) {
                            $failed++;
                            continue;
                        }
                    }

                    // Extract attlog from the record format
                    // Format: { "cloud_id": "xxx", "type": "attlog", "original_data": { "type": "attlog", "cloud_id": "xxx", "data": {...} } }
                    $originalData = $record['original_data'] ?? $record;

                    // Handle if original_data is also a string
                    if (is_string($originalData)) {
                        $originalData = json_decode($originalData, true) ?? [];
                    }

                    $attlogData = $originalData['data'] ?? $originalData;

                    // Handle if data is also a string
                    if (is_string($attlogData)) {
                        $attlogData = json_decode($attlogData, true) ?? [];
                    }

                    // Add cloud_id if available
                    if (isset($originalData['cloud_id'])) {
                        $attlogData['cloud_id'] = $originalData['cloud_id'];
                    }

                    // Check if this scan already exists in our logs (avoid duplicates)
                    $pin = $attlogData['pin'] ?? null;
                    $scanTime = $attlogData['scan'] ?? $attlogData['datetime'] ?? null;

                    if ($pin && $scanTime) {
                        $existingLog = FingerspotLog::where('pin', $pin)
                            ->where('scan_time', Carbon::parse($scanTime))
                            ->first();

                        if ($existingLog) {
                            $skipped++;
                            continue; // Skip duplicate
                        }
                    }

                    $result = $this->processAttlog($attlogData, $setting, $originalData);
                    $results[] = $result;

                    if ($result['status'] === 'success') {
                        $processed++;
                    } elseif ($result['status'] === 'skipped') {
                        $skipped++;
                    } else {
                        $failed++;
                    }
                } catch (\Exception $e) {
                    Log::error('Fingerspot: Error processing fetched attlog', [
                        'record' => $record,
                        'error' => $e->getMessage(),
                    ]);
                    $failed++;
                }
            }

            // Update last sync time
            $setting->updateLastSync();

            return response()->json([
                'success' => true,
                'message' => "Sync completed. Processed: {$processed}, Failed: {$failed}, Skipped (duplicates): {$skipped}",
                'data' => [
                    'total' => count($attlogs),
                    'processed' => $processed,
                    'failed' => $failed,
                    'skipped' => $skipped,
                ]
            ]);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('Fingerspot: API request failed', [
                'url' => $apiUrl,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to API: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error('Fingerspot: Fetch error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
