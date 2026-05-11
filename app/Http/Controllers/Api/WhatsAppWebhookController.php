<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Karyawans;
use App\Models\Attendance;

class WhatsAppWebhookController extends Controller
{
    /**
     * Handle incoming webhook from WhatsApp Gateway (Fonnte)
     */
    public function handle(Request $request)
    {
        try {
            // Fonnte sends data via POST. Common fields: sender, message
            $sender = $request->input('sender');
            $message = $request->input('message') ?? $request->input('text');
            
            Log::info('WhatsApp Webhook Received:', [
                'sender' => $sender,
                'message' => $message,
                'payload' => $request->all()
            ]);

            if (!$sender || !$message) {
                return response()->json(['status' => false, 'message' => 'Missing sender or message']);
            }

            // Normalize sender number (e.g. 628... to 08... or vice versa)
            // Karyawan phones could be stored as 08... or 628...
            // Let's try to find the employee
            $cleanPhone = preg_replace('/[^0-9]/', '', $sender);
            
            // Generate possible phone formats
            $phoneFormats = [
                $cleanPhone, // exactly as received
            ];
            
            if (str_starts_with($cleanPhone, '62')) {
                $phoneFormats[] = '0' . substr($cleanPhone, 2); // 628... -> 08...
                $phoneFormats[] = '+' . $cleanPhone; // +628...
            } elseif (str_starts_with($cleanPhone, '0')) {
                $phoneFormats[] = '62' . substr($cleanPhone, 1); // 08... -> 628...
                $phoneFormats[] = '+62' . substr($cleanPhone, 1); // 08... -> +628...
            }

            $employee = Karyawans::whereIn('phone', $phoneFormats)->first();

            if (!$employee) {
                Log::info('WhatsApp Webhook: Employee not found for phone ' . $sender);
                return response()->json(['status' => true, 'message' => 'Employee not found']);
            }

            // Find the most recent 'alpha' attendance for this employee (e.g. within last 3 days)
            $recentAlpha = Attendance::where('employee_id', $employee->id)
                ->where('status', 'alpha')
                ->where('attendance_date', '>=', now()->subDays(3)->format('Y-m-d'))
                ->orderBy('attendance_date', 'desc')
                ->first();

            if ($recentAlpha) {
                // Check if there's already a pending request
                $existingRequest = \App\Models\AttendanceEditRequest::where('attendance_id', $recentAlpha->id)
                    ->where('status', 'pending')
                    ->first();

                if (!$existingRequest) {
                    $adminId = \App\Models\User::where('role', 'admin')->first()->id ?? 1;
                    $requestingUserId = $employee->user_id ?? $adminId;

                    // Deteksi otomatis status dari isi pesan
                    $lowerMsg = strtolower($message);
                    $newStatus = 'hadir'; // Default
                    if (str_contains($lowerMsg, 'sakit')) $newStatus = 'sakit';
                    elseif (str_contains($lowerMsg, 'izin')) $newStatus = 'izin';
                    elseif (str_contains($lowerMsg, 'cuti')) $newStatus = 'cuti';

                    // Berikan jam default jika hadir/lupa absen
                    $defaultCheckIn = null;
                    $defaultCheckOut = null;
                    if ($newStatus === 'hadir') {
                        $schedule = $employee->workSchedule;
                        if ($schedule) {
                            $defaultCheckIn = is_string($schedule->start_time) ? $schedule->start_time : ($schedule->start_time ? $schedule->start_time->format('H:i:s') : '08:00:00');
                            $defaultCheckOut = is_string($schedule->end_time) ? $schedule->end_time : ($schedule->end_time ? $schedule->end_time->format('H:i:s') : '17:00:00');
                        } else {
                            $defaultCheckIn = '08:00:00';
                            $defaultCheckOut = '17:00:00';
                        }
                    }

                    \App\Models\AttendanceEditRequest::create([
                        'attendance_id' => $recentAlpha->id,
                        'requested_by' => $requestingUserId,
                        'old_attendance_date' => $recentAlpha->attendance_date,
                        'old_check_in' => $recentAlpha->check_in,
                        'old_check_out' => $recentAlpha->check_out,
                        'old_status' => $recentAlpha->status,
                        'new_attendance_date' => $recentAlpha->attendance_date,
                        'new_check_in' => $defaultCheckIn,
                        'new_check_out' => $defaultCheckOut,
                        'new_status' => $newStatus,
                        'reason' => "[Klarifikasi WA " . now()->format('H:i') . "]: " . $message,
                        'status' => 'pending'
                    ]);
                    
                    Log::info('WhatsApp Webhook: Alpha clarification recorded as Edit Request for ' . $employee->name);
                    
                    // Reply back to the employee automatically
                    $replyMsg = "Terima kasih, klarifikasi Anda terkait absen tanggal " . $recentAlpha->attendance_date->format('d/m/Y') . " telah kami terima.\n\nData saat ini berstatus *Menunggu Persetujuan (Pending)* dan akan segera direview oleh Manager/HRD.";
                    $whatsappService = new \App\Services\WhatsAppService();
                    $whatsappService->send($sender, $replyMsg);
                } else {
                    $replyMsg = "Klarifikasi Anda sebelumnya terkait absen tanggal " . $recentAlpha->attendance_date->format('d/m/Y') . " masih dalam antrean persetujuan HRD. Harap menunggu.";
                    $whatsappService = new \App\Services\WhatsAppService();
                    $whatsappService->send($sender, $replyMsg);
                }
            }

            return response()->json([
                'status' => true, 
                'message' => 'Webhook processed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('WhatsApp Webhook Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
