<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Interview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SecurityScannerController extends Controller
{
    /**
     * Display security scanner page
     */
    public function index()
    {
        // Get today's interviews
        $todayInterviews = Interview::with(['position'])
            ->whereDate('interview_date', Carbon::today())
            ->orderBy('interview_time', 'asc')
            ->get();

        return view('security.scanner', compact('todayInterviews'));
    }

    /**
     * Validate QR code token (API)
     */
    public function validateToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        $interview = Interview::where('qr_code_token', $request->token)->first();

        if (!$interview) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code tidak valid'
            ], 404);
        }

        // Check if already checked in
        if ($interview->isCheckedIn()) {
            return response()->json([
                'success' => false,
                'message' => 'Kandidat sudah melakukan check-in',
                'data' => [
                    'candidate_name' => $interview->candidate_name,
                    'position' => $interview->position->name,
                    'interview_time' => $interview->interview_time ? $interview->interview_time->format('H:i') : '',
                    'checked_in_at' => $interview->checked_in_at,
                    'already_checked_in' => true
                ]
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'QR Code valid',
            'data' => [
                'id' => $interview->id,
                'candidate_name' => $interview->candidate_name,
                'phone' => $interview->phone,
                'position' => $interview->position->name,
                'interview_date' => Carbon::parse($interview->interview_date)->format('Y-m-d'),
                'interview_time' => $interview->interview_time ? $interview->interview_time->format('H:i') : '',
                'location' => $interview->location,
                'already_checked_in' => false
            ]
        ]);
    }

    /**
     * Check in candidate (API)
     */
    public function checkIn(Request $request)
    {
        $request->validate([
            'interview_id' => 'required|exists:interviews,id'
        ]);

        $interview = Interview::findOrFail($request->interview_id);

        // Check if already checked in
        if ($interview->isCheckedIn()) {
            return response()->json([
                'success' => false,
                'message' => 'Kandidat sudah melakukan check-in sebelumnya'
            ], 400);
        }

        // Update check-in status
        $interview->checked_in_at = Carbon::now();
        $interview->checked_in_by = Auth::id();
        $interview->save();

        return response()->json([
            'success' => true,
            'message' => 'Check-in berhasil',
            'data' => [
                'candidate_name' => $interview->candidate_name,
                'checked_in_at' => $interview->checked_in_at->format('d/m/Y H:i:s')
            ]
        ]);
    }

    /**
     * Get today's check-in history
     */
    public function history()
    {
        $checkedIn = Interview::with(['position'])
            ->whereDate('interview_date', Carbon::today())
            ->whereNotNull('checked_in_at')
            ->orderBy('checked_in_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $checkedIn->map(function($interview) {
                return [
                    'candidate_name' => $interview->candidate_name,
                    'position' => $interview->position->name,
                    'interview_time' => $interview->interview_time ? $interview->interview_time->format('H:i') : '',
                    'checked_in_at' => $interview->checked_in_at->format('H:i:s')
                ];
            })
        ]);
    }
}
