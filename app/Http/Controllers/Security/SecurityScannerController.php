<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Interview;
use App\Models\JoinCall;
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

        // Get today's join calls
        $todayJoinCalls = JoinCall::with(['department'])
            ->whereDate('join_call_date', Carbon::today())
            ->orderBy('join_call_time', 'asc')
            ->get();

        return view('security.scanner', compact('todayInterviews', 'todayJoinCalls'));
    }

    /**
     * Validate QR code token (API) â€” supports both Interview and JoinCall
     */
    public function validateToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        $token = $request->token;

        // --- Try Interview first ---
        $interview = Interview::where('qr_code_token', $token)->first();
        if ($interview) {
            if ($interview->isCheckedIn()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kandidat sudah melakukan check-in',
                    'data' => [
                        'type'             => 'interview',
                        'candidate_name'   => $interview->candidate_name,
                        'position'         => $interview->position->name,
                        'interview_time'   => $interview->interview_time ? $interview->interview_time->format('H:i') : '',
                        'checked_in_at'    => $interview->checked_in_at,
                        'already_checked_in' => true
                    ]
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'QR Code valid (Interview)',
                'data' => [
                    'type'           => 'interview',
                    'id'             => $interview->id,
                    'candidate_name' => $interview->candidate_name,
                    'phone'          => $interview->phone,
                    'position'       => $interview->position->name,
                    'event_date'     => Carbon::parse($interview->interview_date)->format('d/m/Y'),
                    'event_time'     => $interview->interview_time ? $interview->interview_time->format('H:i') : '',
                    'location'       => $interview->location,
                    'label'          => 'Waktu Interview',
                    'already_checked_in' => false
                ]
            ]);
        }

        // --- Try JoinCall ---
        $joinCall = JoinCall::with('department')->where('qr_code_token', $token)->first();
        if ($joinCall) {
            if ($joinCall->isCheckedIn()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kandidat sudah melakukan check-in',
                    'data' => [
                        'type'             => 'join_call',
                        'candidate_name'   => $joinCall->candidate_name,
                        'role_label'       => 'Departemen',
                        'role_value'       => $joinCall->department?->name ?? '-',
                        'event_time'       => $joinCall->join_call_time->format('H:i'),
                        'checked_in_at'    => $joinCall->checked_in_at,
                        'already_checked_in' => true
                    ]
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'QR Code valid (Panggilan Join)',
                'data' => [
                    'type'           => 'join_call',
                    'id'             => $joinCall->id,
                    'candidate_name' => $joinCall->candidate_name,
                    'phone'          => $joinCall->phone,
                    'role_label'     => 'Departemen',
                    'role_value'     => $joinCall->department?->name ?? '-',
                    'event_date'     => $joinCall->join_call_date->format('d/m/Y'),
                    'event_time'     => $joinCall->join_call_time->format('H:i'),
                    'location'       => $joinCall->location,
                    'label'          => 'Waktu Join',
                    'already_checked_in' => false
                ]
            ]);
        }

        // Not found
        return response()->json([
            'success' => false,
            'message' => 'QR Code tidak valid'
        ], 404);
    }

    /**
     * Check in candidate (API) â€” supports both Interview and JoinCall
     */
    public function checkIn(Request $request)
    {
        $request->validate([
            'type' => 'required|in:interview,join_call',
            'id'   => 'required|integer'
        ]);

        if ($request->type === 'interview') {
            $interview = Interview::find($request->id);
            if (!$interview) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
            }

            if ($interview->isCheckedIn()) {
                return response()->json(['success' => false, 'message' => 'Kandidat sudah melakukan check-in sebelumnya'], 400);
            }

            $interview->checked_in_at = Carbon::now();
            $interview->checked_in_by = Auth::id();
            $interview->status = 'confirmed';
            $interview->save();

            return response()->json([
                'success' => true,
                'message' => 'Check-in berhasil',
                'data' => [
                    'candidate_name' => $interview->candidate_name,
                    'checked_in_at'  => $interview->checked_in_at->format('d/m/Y H:i:s')
                ]
            ]);
        }

        if ($request->type === 'join_call') {
            $joinCall = JoinCall::find($request->id);
            if (!$joinCall) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
            }

            if ($joinCall->isCheckedIn()) {
                return response()->json(['success' => false, 'message' => 'Kandidat sudah melakukan check-in sebelumnya'], 400);
            }

            $joinCall->checked_in_at = Carbon::now();
            $joinCall->checked_in_by = Auth::user()->name ?? 'Security';
            $joinCall->status = 'confirmed';
            $joinCall->save();

            return response()->json([
                'success' => true,
                'message' => 'Check-in berhasil',
                'data' => [
                    'candidate_name' => $joinCall->candidate_name,
                    'checked_in_at'  => $joinCall->checked_in_at->format('d/m/Y H:i:s')
                ]
            ]);
        }
    }

    /**
     * Get today's check-in history (Interview + JoinCall)
     */
    public function history()
    {
        $interviews = Interview::with(['position'])
            ->whereDate('interview_date', Carbon::today())
            ->whereNotNull('checked_in_at')
            ->orderBy('checked_in_at', 'desc')
            ->get()
            ->map(function ($i) {
                return [
                    'type'           => 'interview',
                    'type_label'     => 'Interview',
                    'candidate_name' => $i->candidate_name,
                    'position'       => $i->position->name,
                    'event_time'     => $i->interview_time ? $i->interview_time->format('H:i') : '',
                    'checked_in_at'  => $i->checked_in_at->format('H:i:s')
                ];
            });

        $joinCalls = JoinCall::with(['department'])
            ->whereDate('join_call_date', Carbon::today())
            ->whereNotNull('checked_in_at')
            ->orderBy('checked_in_at', 'desc')
            ->get()
            ->map(function ($j) {
                return [
                    'type'           => 'join_call',
                    'type_label'     => 'Panggilan Join',
                    'candidate_name' => $j->candidate_name,
                    'role_label'     => 'Departemen',
                    'role_value'     => $j->department?->name ?? '-',
                    'event_time'     => $j->join_call_time->format('H:i'),
                    'checked_in_at'  => $j->checked_in_at->format('H:i:s')
                ];
            });

        $all = $interviews->merge($joinCalls)->sortByDesc('checked_in_at')->values();

        return response()->json([
            'success' => true,
            'data'    => $all
        ]);
    }
}
