<?php

namespace App\Http\Controllers;

use App\Models\Interview;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InterviewScanController extends Controller
{
    /**
     * Show QR scan page (public access)
     */
    public function scan($token)
    {
        $interview = Interview::with('position')
            ->where('qr_code_token', $token)
            ->first();

        if (!$interview) {
            return view('interview.scan-error', [
                'message' => 'QR Code tidak valid atau sudah tidak aktif'
            ]);
        }

        // Check if interview date is today or in the future
        if ($interview->interview_date->lt(Carbon::today())) {
            return view('interview.scan-error', [
                'message' => 'Jadwal interview sudah lewat',
                'interview' => $interview
            ]);
        }

        return view('interview.scan', compact('interview'));
    }

    /**
     * Confirm check-in (API)
     */
    public function checkIn(Request $request, $token)
    {
        $interview = Interview::where('qr_code_token', $token)->first();

        if (!$interview) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code tidak valid'
            ], 404);
        }

        if ($interview->isCheckedIn()) {
            return response()->json([
                'success' => false,
                'message' => 'Kandidat sudah melakukan check-in sebelumnya pada ' . $interview->checked_in_at->format('d/m/Y H:i')
            ], 400);
        }

        // Update check-in status
        $interview->update([
            'checked_in_at' => now(),
            'checked_in_by' => $request->input('security_name', 'Security'),
            'status' => 'confirmed'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Check-in berhasil! Kandidat dapat menuju ke HRD.',
            'data' => [
                'checked_in_at' => $interview->checked_in_at->format('d/m/Y H:i'),
                'checked_in_by' => $interview->checked_in_by
            ]
        ]);
    }

    /**
     * Get interview details (API for validation)
     */
    public function getDetails($token)
    {
        $interview = Interview::with('position')
            ->where('qr_code_token', $token)
            ->first();

        if (!$interview) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code tidak valid'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $interview->id,
                'candidate_name' => $interview->candidate_name,
                'position' => $interview->position->name,
                'interview_date' => $interview->interview_date->format('d/m/Y'),
                'interview_time' => $interview->interview_time->format('H:i'),
                'location' => $interview->location,
                'status' => $interview->status,
                'is_checked_in' => $interview->isCheckedIn(),
                'checked_in_at' => $interview->checked_in_at ? $interview->checked_in_at->format('d/m/Y H:i') : null,
                'checked_in_by' => $interview->checked_in_by
            ]
        ]);
    }
}
