<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BroadcastMessage;
use App\Models\Karyawans;
use App\Models\Position;
use App\Models\Department;
use App\Jobs\SendBroadcastJob;
use App\Jobs\SendBroadcastEmailJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BroadcastController extends Controller
{
    /**
     * Display broadcast messages page
     */
    public function index()
    {
        return view('admin.broadcast.index');
    }

    /**
     * Get broadcast messages list
     */
    public function list(Request $request)
    {
        $query = BroadcastMessage::with('sender')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search') && $request->search != '') {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('message', 'like', '%' . $request->search . '%');
            });
        }

        $perPage = $request->get('per_page', 10);
        $broadcasts = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $broadcasts
        ]);
    }

    /**
     * Get positions for filter dropdown
     */
    public function getPositions()
    {
        $positions = Position::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => $positions
        ]);
    }

    /**
     * Get departments for filter dropdown
     */
    public function getDepartments()
    {
        $departments = Department::orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => $departments
        ]);
    }

    /**
     * Get employees for filter dropdown
     */
    public function getEmployees()
    {
        $employees = Karyawans::with(['position', 'department'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'employee_code', 'position_id', 'department_id'])
            ->map(function($emp) {
                return [
                    'id' => $emp->id,
                    'name' => $emp->name,
                    'employee_code' => $emp->employee_code,
                    'position' => $emp->position->name ?? '-',
                    'department' => $emp->department->name ?? '-',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $employees
        ]);
    }

    /**
     * Preview recipients based on filters
     */
    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filter_type' => 'required|in:all,position,department,employee,alpha_date',
            'filter_values' => 'required_unless:filter_type,all|array',
        ]);

        if ($request->filter_type !== 'alpha_date' && $request->filter_type !== 'all') {
            $validator->addRules(['filter_values.*' => 'integer']);
        } elseif ($request->filter_type === 'alpha_date') {
            $validator->addRules(['filter_values.*' => 'date']);
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $recipients = $this->getRecipients(
                $request->filter_type,
                $request->filter_values ?? []
            );

            $validPhone = $recipients->filter(fn($e) => !empty($e->phone));
            $validEmail = $recipients->filter(fn($e) => !empty($e->email));

            return response()->json([
                'success' => true,
                'data' => [
                    'total'         => $recipients->count(),
                    'valid_phone'   => $validPhone->count(),
                    'without_phone' => $recipients->count() - $validPhone->count(),
                    'valid_email'   => $validEmail->count(),
                    'without_email' => $recipients->count() - $validEmail->count(),
                    'recipients'    => $recipients->map(fn($emp) => [
                        'name'          => $emp->name,
                        'employee_code' => $emp->employee_code,
                        'phone'         => $emp->phone,
                        'email'         => $emp->email,
                        'position'      => $emp->position->name ?? '-',
                        'department'    => $emp->department->name ?? '-',
                    ])->values()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat preview: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send broadcast message
     */
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'             => 'required|string|max:255',
            'message'           => 'required|string',
            'channel'           => 'required|in:whatsapp,email,both',
            'filter_type'       => 'required|in:all,position,department,employee,alpha_date',
            'filter_values'     => 'required_unless:filter_type,all|array',
            'image'             => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'delay_per_message' => 'nullable|integer|in:3,5,10,15,30',
        ]);

        if ($request->filter_type !== 'alpha_date' && $request->filter_type !== 'all') {
            $validator->addRules(['filter_values.*' => 'integer']);
        } elseif ($request->filter_type === 'alpha_date') {
            $validator->addRules(['filter_values.*' => 'date']);
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // Handle image upload
            $imagePath = null;
            if ($request->hasFile('image')) {
                $file      = $request->file('image');
                $filename  = 'broadcast_' . time() . '.' . $file->getClientOriginalExtension();
                $imagePath = $file->storeAs('broadcast', $filename, 'public');
            }

            // Get recipients
            $recipients = $this->getRecipients(
                $request->filter_type,
                $request->filter_values ?? []
            );

            $channel  = $request->channel; // whatsapp | email | both
            $imageUrl = $imagePath ? asset('storage/' . $imagePath) : null;
            $delay    = (int) ($request->delay_per_message ?? 5);

            // Determine valid recipients based on channel
            $waRecipients    = ($channel === 'whatsapp' || $channel === 'both')
                ? $recipients->filter(fn($e) => !empty($e->phone))->values()
                : collect();
            $emailRecipients = ($channel === 'email' || $channel === 'both')
                ? $recipients->filter(fn($e) => !empty($e->email))->values()
                : collect();

            // For 'both', count unique recipients
            $totalJobs = $waRecipients->count() + $emailRecipients->count();

            if ($totalJobs === 0) {
                $hint = match($channel) {
                    'whatsapp' => 'Tidak ada penerima yang memiliki nomor WhatsApp.',
                    'email'    => 'Tidak ada penerima yang memiliki alamat email.',
                    'both'     => 'Tidak ada penerima yang memiliki nomor WhatsApp maupun email.',
                };
                return response()->json(['success' => false, 'message' => $hint], 422);
            }

            // Create broadcast record
            $broadcast = BroadcastMessage::create([
                'title'            => $request->title,
                'message'          => $request->message,
                'image'            => $imagePath,
                'channel'          => $channel,
                'filter_type'      => $request->filter_type,
                'filter_values'    => $request->filter_values ?? [],
                'total_recipients' => $totalJobs,
                'sent_count'       => 0,
                'failed_count'     => 0,
                'sent_by'          => Auth::id(),
                'status'           => 'sending',
                'sent_at'          => now(),
            ]);

            // Dispatch WhatsApp jobs
            foreach ($waRecipients as $index => $employee) {
                $personalizedMessage = "Kepada: *{$employee->name}*\n\n" . $request->message;
                SendBroadcastJob::dispatch(
                    $broadcast->id,
                    $employee->phone,
                    $personalizedMessage,
                    $imageUrl
                )->delay(now()->addSeconds($index * $delay));
            }

            // Dispatch Email jobs with pacing to avoid Lark frequency-limit bounces.
            foreach ($emailRecipients as $index => $employee) {
                SendBroadcastEmailJob::dispatch(
                    $broadcast->id,
                    $employee->email,
                    $employee->name,
                    $request->title,
                    $request->message,
                    $imageUrl
                )->delay(now()->addSeconds($index * 2));
            }

            $channelLabel = match($channel) {
                'whatsapp' => "WhatsApp ({$waRecipients->count()} penerima)",
                'email'    => "Email ({$emailRecipients->count()} penerima)",
                'both'     => "WhatsApp ({$waRecipients->count()}) + Email ({$emailRecipients->count()})",
            };

            Log::info("Broadcast #{$broadcast->id} queued: channel={$channel}, wa={$waRecipients->count()}, email={$emailRecipients->count()}");

            return response()->json([
                'success' => true,
                'message' => "Broadcast via {$channelLabel} berhasil dijadwalkan.",
                'data' => [
                    'broadcast_id'    => $broadcast->id,
                    'channel'         => $channel,
                    'total_wa'        => $waRecipients->count(),
                    'total_email'     => $emailRecipients->count(),
                    'delay'           => $delay,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Broadcast send error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim broadcast: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get broadcast detail
     */
    public function detail($id)
    {
        try {
            $broadcast = BroadcastMessage::with('sender')->findOrFail($id);

            // Get filter details
            $filterDetails = [];
            if ($broadcast->filter_type === 'position') {
                $filterDetails = Position::whereIn('id', $broadcast->filter_values ?? [])
                    ->pluck('name')
                    ->toArray();
            } elseif ($broadcast->filter_type === 'department') {
                $filterDetails = Department::whereIn('id', $broadcast->filter_values ?? [])
                    ->pluck('name')
                    ->toArray();
            } elseif ($broadcast->filter_type === 'employee') {
                $filterDetails = Karyawans::whereIn('id', $broadcast->filter_values ?? [])
                    ->pluck('name')
                    ->toArray();
            } elseif ($broadcast->filter_type === 'alpha_date') {
                $filterDetails = ["Tanggal: " . implode(', ', $broadcast->filter_values ?? [])];
            }

            // Get recipients list
            $recipients = $this->getRecipients(
                $broadcast->filter_type,
                $broadcast->filter_values ?? []
            );

            $recipientsList = $recipients->map(function($emp) {
                return [
                    'name' => $emp->name,
                    'employee_code' => $emp->employee_code,
                    'phone' => $emp->phone,
                    'position' => $emp->position->name ?? '-',
                    'department' => $emp->department->name ?? '-',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'broadcast' => $broadcast,
                    'filter_details' => $filterDetails,
                    'recipients' => $recipientsList,
                    'image_url' => $broadcast->image ? asset('storage/' . $broadcast->image) : null,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat detail: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete broadcast message
     */
    public function destroy($id)
    {
        try {
            $broadcast = BroadcastMessage::findOrFail($id);

            // Delete image file if exists
            if ($broadcast->image) {
                Storage::disk('public')->delete($broadcast->image);
            }

            $broadcast->delete();

            return response()->json([
                'success' => true,
                'message' => 'Broadcast berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus broadcast: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recipients based on filter
     * 
     * @param string $filterType
     * @param array $filterValues
     * @return \Illuminate\Support\Collection
     */
    private function getRecipients($filterType, $filterValues)
    {
        $query = Karyawans::with(['position', 'department'])
            ->where('status', 'active');

        switch ($filterType) {
            case 'position':
                $query->whereIn('position_id', $filterValues);
                break;
            
            case 'department':
                $query->whereIn('department_id', $filterValues);
                break;
            
            case 'employee':
                $query->whereIn('id', $filterValues);
                break;
            
            case 'alpha_date':
                $query->whereHas('attendances', function($q) use ($filterValues) {
                    $q->whereIn('attendance_date', $filterValues)
                      ->where('status', 'alpha');
                });
                break;
            
            case 'all':
            default:
                // No additional filter - all active employees
                break;
        }

        return $query->get();
    }
}
