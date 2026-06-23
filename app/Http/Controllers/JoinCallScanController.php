<?php

namespace App\Http\Controllers;

use App\Models\JoinCall;
use Illuminate\Http\Request;
use Carbon\Carbon;

class JoinCallScanController extends Controller
{
    /**
     * Show QR scan page (public access)
     */
    public function scan($token)
    {
        $joinCall = JoinCall::with('department')
            ->where('qr_code_token', $token)
            ->first();

        if (!$joinCall) {
            return view('join_call.scan-error', [
                'message' => 'QR Code tidak valid atau sudah tidak aktif'
            ]);
        }

        // Check if join date is today or in the future
        if ($joinCall->join_call_date->lt(Carbon::today())) {
            return view('join_call.scan-error', [
                'message' => 'Jadwal panggilan join sudah lewat',
                'joinCall' => $joinCall
            ]);
        }

        return view('join_call.scan', compact('joinCall'));
    }

    /**
     * Confirm check-in (API)
     */
    public function checkIn(Request $request, $token)
    {
        $joinCall = JoinCall::where('qr_code_token', $token)->first();

        if (!$joinCall) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code tidak valid'
            ], 404);
        }

        if ($joinCall->isCheckedIn()) {
            return response()->json([
                'success' => false,
                'message' => 'Kandidat sudah melakukan check-in sebelumnya pada ' . $joinCall->checked_in_at->format('d/m/Y H:i')
            ], 400);
        }

        // Update check-in status
        $joinCall->update([
            'checked_in_at' => now(),
            'checked_in_by' => $request->input('security_name', 'Security'),
            'status' => 'confirmed'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Check-in berhasil! Kandidat dapat menuju ke HRD.',
            'data' => [
                'checked_in_at' => $joinCall->checked_in_at->format('d/m/Y H:i'),
                'checked_in_by' => $joinCall->checked_in_by
            ]
        ]);
    }

    /**
     * Get join call details (API for validation)
     */
    public function getDetails($token)
    {
        $joinCall = JoinCall::with('department')
            ->where('qr_code_token', $token)
            ->first();

        if (!$joinCall) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code tidak valid'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $joinCall->id,
                'candidate_name' => $joinCall->candidate_name,
                'department' => $joinCall->department?->name,
                'join_call_date' => $joinCall->join_call_date->format('d/m/Y'),
                'join_call_time' => $joinCall->join_call_time->format('H:i'),
                'location' => $joinCall->location,
                'status' => $joinCall->status,
                'is_checked_in' => $joinCall->isCheckedIn(),
                'checked_in_at' => $joinCall->checked_in_at ? $joinCall->checked_in_at->format('d/m/Y H:i') : null,
                'checked_in_by' => $joinCall->checked_in_by
            ]
        ]);
    }
}
