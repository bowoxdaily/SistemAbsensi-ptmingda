<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Karyawans;
use App\Models\Department;
use App\Models\EmployeeCareer;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Exports\KaryawanExport;
use App\Exports\KaryawanTemplateExport;
use App\Imports\KaryawanImport;
use App\Models\Attendance;
use Maatwebsite\Excel\Facades\Excel;

class KaryawanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function dashboard()
    {
        return view('admin.karyawan.index');
    }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search', '');
        $departmentId = $request->get('department_id');
        $subDepartmentId = $request->get('sub_department_id');
        $positionId = $request->get('position_id');
        $status = $request->get('status');
        $workScheduleId = $request->get('work_schedule_id');

        $karyawans = Karyawans::with(['department', 'subDepartment', 'position', 'workSchedule'])
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('employee_code', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($departmentId, function ($query, $departmentId) {
                return $query->where('department_id', $departmentId);
            })
            ->when($subDepartmentId, function ($query, $subDepartmentId) {
                return $query->where('sub_department_id', $subDepartmentId);
            })
            ->when($positionId, function ($query, $positionId) {
                return $query->where('position_id', $positionId);
            })
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($workScheduleId, function ($query, $workScheduleId) {
                return $query->where('work_schedule_id', $workScheduleId);
            })
            ->orderBy('employee_code', 'asc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $karyawans
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_code' => 'required|string|max:20|unique:employees,employee_code',
            'nik' => 'nullable|string|max:20',
            'name' => 'required|string|max:100',
            'gender' => 'required|in:L,P',
            'birth_place' => 'required|string|max:50',
            'birth_date' => 'required|date',
            'marital_status' => 'required|in:Belum Menikah,Menikah,Duda,Janda',
            'tanggungan_anak' => 'nullable|integer|min:0',
            'agama' => 'nullable|string|max:50',
            'bangsa' => 'nullable|string|max:50',
            'status_kependudukan' => 'nullable|string|max:20',
            'nama_ibu_kandung' => 'nullable|string|max:100',
            'ktp' => 'nullable|string|max:20',
            'kartu_keluarga' => 'nullable|string|max:20',
            'department_id' => 'required|exists:departments,id',
            'sub_department_id' => 'nullable|exists:sub_departments,id',
            'position_id' => 'required|exists:positions,id',
            'lulusan_sekolah' => 'nullable|string|max:100',
            'join_date' => 'required|date',
            'employment_status' => 'required|in:Tetap,Kontrak,Probation,Mangkir,Gagal Probation',
            'serikat' => 'required|in:Serikat GARTEKS,Non Serikat',
            'work_schedule_id' => 'required|exists:work_schedules,id',
            'tanggal_resign' => 'nullable|date',
            'tanggal_phk' => 'nullable|date',
            'tanggal_mangkir' => 'nullable|date',
            'tanggal_gagal_probation' => 'nullable|date',
            'tanggal_pending' => 'nullable|date',
            'termination_recommendation' => 'nullable|in:can_rehire,considered,not_recommended,blacklist',
            'bank' => 'nullable|string|max:50',
            'nomor_rekening' => 'nullable|string|max:50',
            'tax_npwp' => 'nullable|string|max:20',
            'bpjs_kesehatan' => 'nullable|string|max:20',
            'bpjs_ketenagakerjaan' => 'nullable|string|max:20',
            'address' => 'required|string',
            'city' => 'required|string|max:50',
            'province' => 'required|string|max:50',
            'kabupaten' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'desa' => 'nullable|string|max:100',
            'postal_code' => 'required|string|max:10',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:100|unique:employees,email|unique:users,email',
            'emergency_contact_name' => 'required|string|max:100',
            'emergency_contact_phone' => 'required|string|max:20',
            'status' => 'required|in:active,inactive,resign,mangkir,gagal_probation,pending,phk',
        ], [
            'employee_code.required' => 'Kode karyawan wajib diisi',
            'employee_code.unique' => 'Kode karyawan sudah ada',
            'name.required' => 'Nama karyawan wajib diisi',
            'gender.required' => 'Jenis kelamin wajib dipilih',
            'birth_place.required' => 'Tempat lahir wajib diisi',
            'birth_date.required' => 'Tanggal lahir wajib diisi',
            'marital_status.required' => 'Status perkawinan wajib dipilih',
            'department_id.required' => 'Departemen wajib dipilih',
            'position_id.required' => 'Posisi wajib dipilih',
            'join_date.required' => 'Tanggal bergabung wajib diisi',
            'employment_status.required' => 'Status kerja wajib dipilih',
            'serikat.required' => 'Status serikat wajib dipilih',
            'work_schedule_id.required' => 'Jadwal kerja wajib dipilih',
            'work_schedule_id.exists' => 'Jadwal kerja tidak valid',
            'email.required' => 'Email wajib diisi',
            'email.unique' => 'Email sudah terdaftar',
            'phone.required' => 'Nomor HP wajib diisi',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create user account
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make('password123'), // Default password
            ]);

            // Create employee
            $data = $request->all();
            $data['user_id'] = $user->id;
            
            // Normalize geographic fields to UPPERCASE
            $data = $this->normalizeGeographicData($data);

            $karyawan = Karyawans::create($data);

            DB::commit();

            // Send WhatsApp welcome notification
            try {
                $waService = app(\App\Services\WhatsAppService::class);
                $waService->sendWelcomeNotification($karyawan, 'password123');
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send welcome WA: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Karyawan berhasil ditambahkan',
                'data' => $karyawan->load(['department', 'position', 'workSchedule'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan karyawan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single karyawan
     */
    public function show($id)
    {
        $isHr = in_array(optional(Auth::user())->role, ['manager', 'admin', 'superadmin'], true);

        $relations = ['department', 'subDepartment', 'position', 'supervisor', 'workSchedule', 'warningLetters'];
        if ($isHr) {
            $relations[] = 'careerHistories.previousPosition';
            $relations[] = 'careerHistories.newPosition';
        }

        $karyawan = Karyawans::with($relations)->find($id);

        if (!$karyawan) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan tidak ditemukan'
            ], 404);
        }

        // Convert to array (dates are already Y-m-d strings since model doesn't cast)
        $data = $karyawan->toArray();

        $attendanceSummary = Attendance::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->where('employee_id', $karyawan->id)
            ->whereIn('status', ['hadir', 'alpha', 'sakit', 'izin'])
            ->groupBy('status')
            ->pluck('total', 'status');

        $data['attendance_summary'] = [
            'hadir' => (int) ($attendanceSummary['hadir'] ?? 0),
            'alpha' => (int) ($attendanceSummary['alpha'] ?? 0),
            'sakit' => (int) ($attendanceSummary['sakit'] ?? 0),
            'izin' => (int) ($attendanceSummary['izin'] ?? 0),
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Update karyawan
     */
    public function update(Request $request, $id)
    {
        $karyawan = Karyawans::find($id);

        if (!$karyawan) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'employee_code' => 'required|string|max:20|unique:employees,employee_code,' . $id,
            'nik' => 'nullable|string|max:20',
            'name' => 'required|string|max:100',
            'gender' => 'required|in:L,P',
            'birth_place' => 'required|string|max:50',
            'birth_date' => 'required|date',
            'marital_status' => 'required|in:Belum Menikah,Menikah,Duda,Janda',
            'tanggungan_anak' => 'nullable|integer|min:0',
            'agama' => 'nullable|string|max:50',
            'bangsa' => 'nullable|string|max:50',
            'status_kependudukan' => 'nullable|string|max:20',
            'nama_ibu_kandung' => 'nullable|string|max:100',
            'ktp' => 'nullable|string|max:20',
            'kartu_keluarga' => 'nullable|string|max:20',
            'department_id' => 'required|exists:departments,id',
            'sub_department_id' => 'nullable|exists:sub_departments,id',
            'position_id' => 'required|exists:positions,id',
            'lulusan_sekolah' => 'nullable|string|max:100',
            'join_date' => 'required|date',
            'employment_status' => 'required|in:Tetap,Kontrak,Probation,Mangkir,Gagal Probation',
            'serikat' => 'required|in:Serikat GARTEKS,Non Serikat',
            'work_schedule_id' => 'required|exists:work_schedules,id',
            'tanggal_resign' => 'nullable|date',
            'tanggal_phk' => 'nullable|date',
            'tanggal_mangkir' => 'nullable|date',
            'tanggal_gagal_probation' => 'nullable|date',
            'tanggal_pending' => 'nullable|date',
            'termination_recommendation' => 'nullable|in:can_rehire,considered,not_recommended,blacklist',
            'bank' => 'nullable|string|max:50',
            'nomor_rekening' => 'nullable|string|max:50',
            'tax_npwp' => 'nullable|string|max:20',
            'bpjs_kesehatan' => 'nullable|string|max:20',
            'bpjs_ketenagakerjaan' => 'nullable|string|max:20',
            'address' => 'required|string',
            'city' => 'required|string|max:50',
            'province' => 'required|string|max:50',
            'postal_code' => 'required|string|max:10',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:100|unique:employees,email,' . $id . '|unique:users,email,' . ($karyawan->user_id ?? 'NULL'),
            'emergency_contact_name' => 'required|string|max:100',
            'emergency_contact_phone' => 'required|string|max:20',
            'status' => 'required|in:active,inactive,resign,mangkir,gagal_probation,pending,phk',
        ], [
            'employee_code.required' => 'Kode karyawan wajib diisi',
            'employee_code.unique' => 'Kode karyawan sudah ada',
            'name.required' => 'Nama karyawan wajib diisi',
            'gender.required' => 'Jenis kelamin wajib dipilih',
            'birth_place.required' => 'Tempat lahir wajib diisi',
            'birth_date.required' => 'Tanggal lahir wajib diisi',
            'marital_status.required' => 'Status perkawinan wajib dipilih',
            'department_id.required' => 'Departemen wajib dipilih',
            'position_id.required' => 'Posisi wajib dipilih',
            'join_date.required' => 'Tanggal bergabung wajib diisi',
            'employment_status.required' => 'Status kerja wajib dipilih',
            'serikat.required' => 'Status serikat wajib dipilih',
            'work_schedule_id.required' => 'Jadwal kerja wajib dipilih',
            'work_schedule_id.exists' => 'Jadwal kerja tidak valid',
            'email.required' => 'Email wajib diisi',
            'email.unique' => 'Email sudah terdaftar',
            'phone.required' => 'Nomor HP wajib diisi',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $previousPositionId = $karyawan->position_id;
        $nextPositionId = (int) $request->position_id;

        DB::beginTransaction();
        try {
            // Normalize geographic fields to UPPERCASE before update
            $data = $this->normalizeGeographicData($request->all());
            $karyawan->update($data);

            // Update user if email changed
            if ($karyawan->user && $karyawan->user->email !== $request->email) {
                $karyawan->user->update([
                    'name' => $request->name,
                    'email' => $request->email,
                ]);
            }

            if ((int) $previousPositionId !== $nextPositionId) {
                $oldPosition = Position::find($previousPositionId);
                $newPosition = Position::find($nextPositionId);

                EmployeeCareer::create([
                    'employee_id' => $karyawan->id,
                    'previous_position_id' => $previousPositionId,
                    'new_position_id' => $nextPositionId,
                    'effective_date' => now()->toDateString(),
                    'movement_type' => $this->resolveMovementType($oldPosition, $newPosition),
                    'notes' => 'Perubahan posisi otomatis dari edit data karyawan.',
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Karyawan berhasil diperbarui',
                'data' => $karyawan->load(['department', 'position', 'workSchedule'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui karyawan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update one career history record (HR only)
     */
    public function updateCareerHistory(Request $request, $id, $careerId)
    {
        if (!in_array(optional(Auth::user())->role, ['manager', 'admin', 'superadmin'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin/manager yang dapat mengubah riwayat karir.'
            ], 403);
        }

        $karyawan = Karyawans::find($id);
        if (!$karyawan) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan tidak ditemukan'
            ], 404);
        }

        $career = EmployeeCareer::where('employee_id', $id)->find($careerId);
        if (!$career) {
            return response()->json([
                'success' => false,
                'message' => 'Riwayat karir tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'effective_date' => 'required|date',
            'movement_type' => 'required|in:promosi,mutasi,demosi',
            'previous_position_id' => 'nullable|exists:positions,id',
            'new_position_id' => 'nullable|exists:positions,id',
            'notes' => 'nullable|string|max:1000',
        ], [
            'effective_date.required' => 'Tanggal efektif wajib diisi',
            'movement_type.required' => 'Jenis perpindahan wajib dipilih',
            'movement_type.in' => 'Jenis perpindahan tidak valid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldPosition = $request->previous_position_id ? Position::find($request->previous_position_id) : null;
        $newPosition = $request->new_position_id ? Position::find($request->new_position_id) : null;
        $resolvedMovementType = $this->resolveMovementType($oldPosition, $newPosition);

        $career->update([
            'effective_date' => $request->effective_date,
            'movement_type' => $resolvedMovementType,
            'previous_position_id' => $request->previous_position_id,
            'new_position_id' => $request->new_position_id,
            'notes' => $request->notes,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Riwayat karir berhasil diperbarui',
            'data' => $career->load(['previousPosition', 'newPosition'])->toArray()
        ]);
    }

    /**
     * Delete one career history record (admin/manager only)
     */
    public function destroyCareerHistory($id, $careerId)
    {
        if (!in_array(optional(Auth::user())->role, ['manager', 'admin', 'superadmin'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin/manager yang dapat menghapus riwayat karir.'
            ], 403);
        }

        $karyawan = Karyawans::find($id);
        if (!$karyawan) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan tidak ditemukan'
            ], 404);
        }

        $career = EmployeeCareer::where('employee_id', $id)->find($careerId);
        if (!$career) {
            return response()->json([
                'success' => false,
                'message' => 'Riwayat karir tidak ditemukan'
            ], 404);
        }

        $career->delete();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat karir berhasil dihapus'
        ]);
    }

    /**
     * Delete karyawan
     */
    public function destroy($id)
    {
        $karyawan = Karyawans::find($id);

        if (!$karyawan) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan tidak ditemukan'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Delete user account
            if ($karyawan->user) {
                $karyawan->user->delete();
            }

            $karyawan->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Karyawan berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus karyawan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get master data for dropdowns
     */
    public function getMasterData()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'departments' => Department::orderBy('name')->get(),
                'positions' => Position::where('status', 'active')->orderBy('name')->orderByRaw('ISNULL(level) ASC')->orderBy('level')->get()->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'code' => $p->code,
                        'name' => $p->name,
                        'level' => $p->level,
                        'display_name' => $p->display_name,
                    ];
                }),
                'work_schedules' => WorkSchedule::where('is_active', true)->orderBy('name')->get(),
                // Return all sub departments (filtering will be done on frontend)
                'sub_departments' => \App\Models\SubDepartment::with('department')->orderBy('name')->get(),
                'supervisors' => Karyawans::where('status', 'active')
                    ->orderBy('name')
                    ->get(['id', 'name', 'employee_code'])
            ]
        ]);
    }

    /**
     * Export karyawan to Excel
     */
    public function export(Request $request)
    {
        $search = $request->get('search');
        $departmentId = $request->get('department_id');
        $subDepartmentId = $request->get('sub_department_id');
        $positionId = $request->get('position_id');
        $status = $request->get('status');
        $workScheduleId = $request->get('work_schedule_id');

        $filters = [
            'search' => $search,
            'department_id' => $departmentId,
            'sub_department_id' => $subDepartmentId,
            'position_id' => $positionId,
            'status' => $status,
            'work_schedule_id' => $workScheduleId,
        ];

        $filename = 'Data_Karyawan';
        if ($search || $departmentId || $subDepartmentId || $positionId || $status || $workScheduleId) {
            $filename .= '_Filtered';
        }
        $filename .= '_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(new KaryawanExport($filters), $filename);
    }

    /**
     * Import karyawan from Excel
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls|max:2048'
        ], [
            'file.required' => 'File Excel harus dipilih',
            'file.mimes' => 'File harus berformat Excel (.xlsx atau .xls)',
            'file.max' => 'Ukuran file maksimal 2MB'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $import = new KaryawanImport();
            Excel::import($import, $request->file('file'));

            $failures = $import->failures();
            $errors = $import->errors();

            if (count($failures) > 0 || count($errors) > 0) {
                $errorMessages = [];

                foreach ($failures as $failure) {
                    $errorMessages[] = "Baris {$failure->row()}: " . implode(', ', $failure->errors());
                }

                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Import selesai dengan error',
                    'errors' => $errorMessages
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data karyawan berhasil diimport'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download template Excel
     */
    public function downloadTemplate()
    {
        return Excel::download(new KaryawanTemplateExport, 'Template_Import_Karyawan.xlsx');
    }

    /**
     * Display status karyawan report page (web)
     */
    public function statusReportPage()
    {
        return view('admin.karyawan.status-report');
    }

    /**
     * API: Get employee status data with filters
     */
    public function statusReport(Request $request)
    {
        $perPage    = $request->get('per_page', 25);
        $search     = $request->get('search', '');
        $status     = $request->get('status');
        $departmentId = $request->get('department_id');
        $joinFrom   = $request->get('join_from');
        $joinTo     = $request->get('join_to');

        $query = Karyawans::with(['department', 'subDepartment', 'position'])
            ->when($search, fn($q) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")
                   ->orWhere('employee_code', 'like', "%{$search}%")
                   ->orWhere('nik', 'like', "%{$search}%");
            }))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->when($joinFrom, fn($q) => $q->whereDate('join_date', '>=', $joinFrom))
            ->when($joinTo, fn($q) => $q->whereDate('join_date', '<=', $joinTo));

        // Summary counts (apply same filters except status)
        $summaryQuery = Karyawans::when($search, fn($q) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")
                   ->orWhere('employee_code', 'like', "%{$search}%")
                   ->orWhere('nik', 'like', "%{$search}%");
            }))
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->when($joinFrom, fn($q) => $q->whereDate('join_date', '>=', $joinFrom))
            ->when($joinTo, fn($q) => $q->whereDate('join_date', '<=', $joinTo));

        $summary = [
            'total'            => (clone $summaryQuery)->count(),
            'active'           => (clone $summaryQuery)->where('status', 'active')->count(),
            'inactive'         => (clone $summaryQuery)->where('status', 'inactive')->count(),
            'resign'           => (clone $summaryQuery)->where('status', 'resign')->count(),
            'mangkir'          => (clone $summaryQuery)->where('status', 'mangkir')->count(),
            'gagal_probation'  => (clone $summaryQuery)->where('status', 'gagal_probation')->count(),
            'pending'          => (clone $summaryQuery)->where('status', 'pending')->count(),
            'phk'              => (clone $summaryQuery)->where('status', 'phk')->count(),
        ];

        if ($perPage === 'all') {
            $data = $query->orderBy('employee_code')->get();
            return response()->json([
                'success' => true,
                'summary' => $summary,
                'data'    => $data,
                'meta'    => ['total' => $data->count(), 'per_page' => 'all', 'current_page' => 1, 'last_page' => 1],
            ]);
        }

        $paginated = $query->orderBy('employee_code')->paginate((int) $perPage);

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'data'    => $paginated->items(),
            'meta'    => [
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'from'         => $paginated->firstItem(),
                'to'           => $paginated->lastItem(),
            ],
        ]);
    }

    /**
     * Export status report to Excel
     */
    public function statusReportExport(Request $request)
    {
        $filters = [
            'search'      => $request->get('search'),
            'status'      => $request->get('status'),
            'department_id' => $request->get('department_id'),
            'join_from'   => $request->get('join_from'),
            'join_to'     => $request->get('join_to'),
        ];

        $filename = 'Status_Karyawan_' . date('Y-m-d_His') . '.xlsx';
        return Excel::download(new \App\Exports\StatusKaryawanExport($filters), $filename);
    }

    /**
     * Parse address to extract geographic data
     * API endpoint: POST /api/admin/karyawan/parse-address
     */
    public function parseAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|string|max:500',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $parsed = $this->extractGeographicData(
                address: $request->input('address'),
                city: $request->input('city'),
                province: $request->input('province')
            );

            return response()->json([
                'success' => true,
                'data' => $parsed
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal parse alamat: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract geographic data from employee fields
     * Helper method used by parseAddress() and MigrateGeographicData command
     */
    public function extractGeographicData(string $address, ?string $city = null, ?string $province = null): array
    {
        $addressUpper = strtoupper($address);

        // Kabupaten biasanya sama dengan city - normalize to UPPERCASE
        $kabupaten = $city ? strtoupper(trim($city)) : null;

        // Try to extract kecamatan and desa from address
        $addressParts = $this->parseAddressString($addressUpper);

        return [
            'kabupaten' => $kabupaten,
            'kecamatan' => $addressParts['kecamatan'] ?? null,
            'desa' => $addressParts['desa'] ?? null,
        ];
    }

    /**
     * Parse address string to extract kecamatan and desa
     * Pattern references:
     * - "DESA LOSARANG RT 010 RW 003 KEL/DESA LOSARANG KEC LOSARANG INDRAMAYU JAWA BARAT"
     * - "JL. PENDIDIKAN NO. 123 KELURAHAN CITARUM KECAMATAN BANDUNG TENGAH"
     */
    private function parseAddressString(string $address): array
    {
        $result = [
            'desa' => null,
            'kecamatan' => null,
        ];

        // Clean address
        $address = trim($address);

        // Pattern 1: Look for "DESA" or "KEL" or "KELURAHAN" 
        if (preg_match('/(?:DESA|KEL|KELURAHAN)\s+([A-Z\s]+?)(?:\s+RT|\s+RW|\s+KEC|$)/i', $address, $matches)) {
            $result['desa'] = trim($matches[1]);
        }

        // Pattern 2: Look for "KEC" or "KECAMATAN" followed by name
        // This pattern looks for KEC followed by word(s) that are not city/province names
        if (preg_match('/\s+KEC\s+([A-Z\s]+?)(?:\s+INDRAMAYU|\s+BANDUNG|\s+JAWA|\s+SUMATERA|\s+SULAWESI|\s+KALIMANTAN|\s+BALI|\s+NTT|\s+MALUKU|$)/i', $address, $matches)) {
            $potentialKec = trim($matches[1]);
            // Filter out noise
            if (!preg_match('/^\d+$|^RT$|^RW$/i', $potentialKec)) {
                $result['kecamatan'] = $potentialKec;
            }
        }

        // Pattern 3: Try alternative "KECAMATAN" keyword
        if (!$result['kecamatan']) {
            if (preg_match('/\s+KECAMATAN\s+([A-Z\s]+?)(?:\s+KOTA|\s+KAB|\s+JAWA|$)/i', $address, $matches)) {
                $result['kecamatan'] = trim($matches[1]);
            }
        }

        return $result;
    }

    /**
     * Normalize geographic fields to UPPERCASE
     * Called before saving employee data to ensure consistency
     */
    private function normalizeGeographicData(array $data): array
    {
        if (isset($data['province'])) {
            $data['province'] = $data['province'] ? strtoupper(trim($data['province'])) : null;
        }
        if (isset($data['city'])) {
            $data['city'] = $data['city'] ? strtoupper(trim($data['city'])) : null;
        }
        if (isset($data['kabupaten'])) {
            $data['kabupaten'] = $data['kabupaten'] ? strtoupper(trim($data['kabupaten'])) : null;
        }
        if (isset($data['kecamatan'])) {
            $data['kecamatan'] = $data['kecamatan'] ? strtoupper(trim($data['kecamatan'])) : null;
        }
        if (isset($data['desa'])) {
            $data['desa'] = $data['desa'] ? strtoupper(trim($data['desa'])) : null;
        }

        return $data;
    }

    /**
     * Resolve movement type from position level comparison.
     */
    private function resolveMovementType(?Position $oldPosition, ?Position $newPosition): string
    {
        if (!$oldPosition || !$newPosition) {
            return 'mutasi';
        }

        $oldRank = $this->getPositionRank($oldPosition);
        $newRank = $this->getPositionRank($newPosition);

        if ($oldRank !== null && $newRank !== null) {
            if ($newRank > $oldRank) {
                return 'promosi';
            }

            if ($newRank < $oldRank) {
                return 'demosi';
            }
        }

        return 'mutasi';
    }

    /**
     * Resolve position rank from numeric level or fixed hierarchy name fallback.
     * Hierarchy: Operator -> Staff/Pengawas -> Supervisor -> Kabag -> Assisten Manager -> Manager
     */
    private function getPositionRank(?Position $position): ?int
    {
        if (!$position) {
            return null;
        }

        $name = strtolower(trim((string) $position->name));
        $name = preg_replace('/\s+/', ' ', $name);

        if (preg_match('/\boperator\b/', $name)) {
            return 1;
        }

        if (preg_match('/\bstaff\b/', $name) || preg_match('/\bpengawas\b/', $name)) {
            return 2;
        }

        if (preg_match('/\bsupervisor\b/', $name)) {
            return 3;
        }

        if (preg_match('/\bkabag\b/', $name) || str_contains($name, 'kepala bagian')) {
            return 4;
        }

        if (str_contains($name, 'assisten manager') || str_contains($name, 'asisten manager') || str_contains($name, 'assistant manager')) {
            return 5;
        }

        if (preg_match('/\bmanager\b/', $name)) {
            return 6;
        }

        // Fallback to numeric level when position name does not match custom hierarchy.
        if (is_numeric($position->level)) {
            return (int) $position->level;
        }

        return null;
    }
}

