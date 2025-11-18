<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\Employee;
use App\Models\Attendance;
use App\Services\WhatsAppService;
use App\Exports\PayrollExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PayrollController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Display payroll index page
     */
    public function index()
    {
        return view('admin.payroll.index');
    }

    /**
     * Get paginated payroll list (API)
     */
    public function list(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search', '');
        $period = $request->get('period');
        $status = $request->get('status');

        $payrolls = Payroll::with(['employee.department', 'employee.position'])
            ->when($search, function ($query, $search) {
                return $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                })->orWhere('payroll_code', 'like', "%{$search}%");
            })
            ->when($period, function ($query, $period) {
                return $query->where('period_month', $period);
            })
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->orderBy('period_month', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $payrolls
        ]);
    }

    /**
     * Store new payroll
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'period_month' => 'required|date_format:Y-m',
            'payment_date' => 'required|date',
            'basic_salary' => 'required|numeric|min:0',
            'allowance_transport' => 'nullable|numeric|min:0',
            'allowance_meal' => 'nullable|numeric|min:0',
            'allowance_position' => 'nullable|numeric|min:0',
            'allowance_others' => 'nullable|numeric|min:0',
            'overtime_pay' => 'nullable|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'deduction_late' => 'nullable|numeric|min:0',
            'deduction_absent' => 'nullable|numeric|min:0',
            'deduction_loan' => 'nullable|numeric|min:0',
            'deduction_bpjs' => 'nullable|numeric|min:0',
            'deduction_tax' => 'nullable|numeric|min:0',
            'deduction_others' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'payment_proof_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'employee_id.required' => 'Karyawan wajib dipilih',
            'period_month.required' => 'Periode gaji wajib diisi',
            'payment_date.required' => 'Tanggal pembayaran wajib diisi',
            'basic_salary.required' => 'Gaji pokok wajib diisi',
            'payment_proof_file.mimes' => 'Format file harus jpg, jpeg, png, atau pdf',
            'payment_proof_file.max' => 'Ukuran file maksimal 5MB',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if payroll already exists for this employee and period
        $exists = Payroll::where('employee_id', $request->employee_id)
            ->where('period_month', $request->period_month)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Slip gaji untuk karyawan ini di periode tersebut sudah ada'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Generate payroll code
            $payrollCode = Payroll::generatePayrollCode($request->period_month);

            // Calculate totals
            $totalEarnings = ($request->basic_salary ?? 0)
                + ($request->allowance_transport ?? 0)
                + ($request->allowance_meal ?? 0)
                + ($request->allowance_position ?? 0)
                + ($request->allowance_others ?? 0)
                + ($request->overtime_pay ?? 0)
                + ($request->bonus ?? 0);

            $totalDeductions = ($request->deduction_late ?? 0)
                + ($request->deduction_absent ?? 0)
                + ($request->deduction_loan ?? 0)
                + ($request->deduction_bpjs ?? 0)
                + ($request->deduction_tax ?? 0)
                + ($request->deduction_others ?? 0);

            $netSalary = $totalEarnings - $totalDeductions;

            // Get attendance summary for the period
            $attendanceSummary = $this->getAttendanceSummary($request->employee_id, $request->period_month);

            // Handle file upload if provided
            $paymentProofPath = null;
            $paidAt = null;
            $status = 'draft';

            if ($request->hasFile('payment_proof_file')) {
                $file = $request->file('payment_proof_file');
                $filename = 'payment_proof_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $paymentProofPath = $file->storeAs('payment_proofs', $filename, 'public');
                $paidAt = now();
                $status = 'paid'; // Automatically set to paid if proof is uploaded
            }

            $payroll = Payroll::create([
                'employee_id' => $request->employee_id,
                'payroll_code' => $payrollCode,
                'period_month' => $request->period_month,
                'payment_date' => $request->payment_date,
                'basic_salary' => $request->basic_salary,
                'allowance_transport' => $request->allowance_transport ?? 0,
                'allowance_meal' => $request->allowance_meal ?? 0,
                'allowance_position' => $request->allowance_position ?? 0,
                'allowance_others' => $request->allowance_others ?? 0,
                'overtime_pay' => $request->overtime_pay ?? 0,
                'bonus' => $request->bonus ?? 0,
                'deduction_late' => $request->deduction_late ?? 0,
                'deduction_absent' => $request->deduction_absent ?? 0,
                'deduction_loan' => $request->deduction_loan ?? 0,
                'deduction_bpjs' => $request->deduction_bpjs ?? 0,
                'deduction_tax' => $request->deduction_tax ?? 0,
                'deduction_others' => $request->deduction_others ?? 0,
                'total_earnings' => $totalEarnings,
                'total_deductions' => $totalDeductions,
                'net_salary' => $netSalary,
                'total_days_present' => $attendanceSummary['present'],
                'total_days_late' => $attendanceSummary['late'],
                'total_days_absent' => $attendanceSummary['absent'],
                'total_days_leave' => $attendanceSummary['leave'],
                'status' => $status,
                'payment_proof' => $paymentProofPath,
                'paid_at' => $paidAt,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Slip gaji berhasil dibuat',
                'data' => $payroll->load('employee')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show payroll detail
     */
    public function show($id)
    {
        $payroll = Payroll::with(['employee.department', 'employee.position', 'creator'])->find($id);

        if (!$payroll) {
            return response()->json([
                'success' => false,
                'message' => 'Slip gaji tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $payroll
        ]);
    }

    /**
     * Update payroll
     */
    public function update(Request $request, $id)
    {
        $payroll = Payroll::find($id);

        if (!$payroll) {
            return response()->json([
                'success' => false,
                'message' => 'Slip gaji tidak ditemukan'
            ], 404);
        }

        // Cannot edit if already sent
        if ($payroll->status === 'sent' || $payroll->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Slip gaji yang sudah dikirim tidak dapat diubah'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'payment_date' => 'required|date',
            'basic_salary' => 'required|numeric|min:0',
            'allowance_transport' => 'nullable|numeric|min:0',
            'allowance_meal' => 'nullable|numeric|min:0',
            'allowance_position' => 'nullable|numeric|min:0',
            'allowance_others' => 'nullable|numeric|min:0',
            'overtime_pay' => 'nullable|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'deduction_late' => 'nullable|numeric|min:0',
            'deduction_absent' => 'nullable|numeric|min:0',
            'deduction_loan' => 'nullable|numeric|min:0',
            'deduction_bpjs' => 'nullable|numeric|min:0',
            'deduction_tax' => 'nullable|numeric|min:0',
            'deduction_others' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
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
            // Recalculate totals
            $totalEarnings = ($request->basic_salary ?? 0)
                + ($request->allowance_transport ?? 0)
                + ($request->allowance_meal ?? 0)
                + ($request->allowance_position ?? 0)
                + ($request->allowance_others ?? 0)
                + ($request->overtime_pay ?? 0)
                + ($request->bonus ?? 0);

            $totalDeductions = ($request->deduction_late ?? 0)
                + ($request->deduction_absent ?? 0)
                + ($request->deduction_loan ?? 0)
                + ($request->deduction_bpjs ?? 0)
                + ($request->deduction_tax ?? 0)
                + ($request->deduction_others ?? 0);

            $netSalary = $totalEarnings - $totalDeductions;

            $payroll->update([
                'payment_date' => $request->payment_date,
                'basic_salary' => $request->basic_salary,
                'allowance_transport' => $request->allowance_transport ?? 0,
                'allowance_meal' => $request->allowance_meal ?? 0,
                'allowance_position' => $request->allowance_position ?? 0,
                'allowance_others' => $request->allowance_others ?? 0,
                'overtime_pay' => $request->overtime_pay ?? 0,
                'bonus' => $request->bonus ?? 0,
                'deduction_late' => $request->deduction_late ?? 0,
                'deduction_absent' => $request->deduction_absent ?? 0,
                'deduction_loan' => $request->deduction_loan ?? 0,
                'deduction_bpjs' => $request->deduction_bpjs ?? 0,
                'deduction_tax' => $request->deduction_tax ?? 0,
                'deduction_others' => $request->deduction_others ?? 0,
                'total_earnings' => $totalEarnings,
                'total_deductions' => $totalDeductions,
                'net_salary' => $netSalary,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Slip gaji berhasil diperbarui',
                'data' => $payroll->load('employee')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete payroll
     */
    public function destroy($id)
    {
        $payroll = Payroll::find($id);

        if (!$payroll) {
            return response()->json([
                'success' => false,
                'message' => 'Slip gaji tidak ditemukan'
            ], 404);
        }

        // Cannot delete if already sent
        if ($payroll->status === 'sent' || $payroll->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Slip gaji yang sudah dikirim tidak dapat dihapus'
            ], 422);
        }

        $payroll->delete();

        return response()->json([
            'success' => true,
            'message' => 'Slip gaji berhasil dihapus'
        ]);
    }

    /**
     * Send payroll notification via WhatsApp
     */
    public function sendNotification($id)
    {
        $payroll = Payroll::with('employee')->find($id);

        if (!$payroll) {
            return response()->json([
                'success' => false,
                'message' => 'Slip gaji tidak ditemukan'
            ], 404);
        }

        if ($payroll->status === 'sent' || $payroll->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Slip gaji sudah pernah dikirim'
            ], 422);
        }

        try {
            // Send WhatsApp notification
            $this->whatsappService->sendPayrollNotification($payroll);

            // Update status
            $payroll->update([
                'status' => 'sent',
                'sent_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Slip gaji berhasil dikirim ke WhatsApp karyawan'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim notifikasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance summary for a period
     */
    private function getAttendanceSummary($employeeId, $periodMonth)
    {
        $startDate = Carbon::parse($periodMonth . '-01')->startOfMonth();
        $endDate = Carbon::parse($periodMonth . '-01')->endOfMonth();

        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->get();

        return [
            'present' => $attendances->where('status', 'hadir')->count(),
            'late' => $attendances->where('status', 'terlambat')->count(),
            'absent' => $attendances->where('status', 'alpha')->count(),
            'leave' => $attendances->where('status', 'izin')->count() + $attendances->where('status', 'sakit')->count() + $attendances->where('status', 'cuti')->count(),
        ];
    }

    /**
     * Get employees list for dropdown
     */
    public function getEmployees()
    {
        $employees = Employee::where('status', 'active')
            ->select('id', 'employee_code', 'name', 'salary_base')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $employees
        ]);
    }

    /**
     * Export payroll to Excel
     */
    public function export(Request $request)
    {
        $filename = 'Payroll_' . date('Y-m-d_His') . '.xlsx';
        return Excel::download(new PayrollExport($request), $filename);
    }

    /**
     * Upload payment proof
     */
    public function uploadProof(Request $request, $id)
    {
        $payroll = Payroll::find($id);

        if (!$payroll) {
            return response()->json([
                'success' => false,
                'message' => 'Slip gaji tidak ditemukan'
            ], 404);
        }

        // Only allow upload for sent payrolls
        if ($payroll->status !== 'sent') {
            return response()->json([
                'success' => false,
                'message' => 'Bukti transfer hanya dapat diunggah untuk slip gaji yang sudah dikirim'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB max
        ], [
            'payment_proof.required' => 'File bukti transfer wajib diunggah',
            'payment_proof.file' => 'File tidak valid',
            'payment_proof.mimes' => 'Format file harus jpg, jpeg, png, atau pdf',
            'payment_proof.max' => 'Ukuran file maksimal 5MB',
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
            // Delete old payment proof if exists
            if ($payroll->payment_proof && Storage::disk('public')->exists($payroll->payment_proof)) {
                Storage::disk('public')->delete($payroll->payment_proof);
            }

            // Store new file
            $file = $request->file('payment_proof');
            $filename = 'payment_proof_' . $payroll->payroll_code . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('payment_proofs', $filename, 'public');

            // Update payroll
            $payroll->update([
                'payment_proof' => $path,
                'paid_at' => now(),
                'status' => 'paid'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bukti transfer berhasil diunggah',
                'data' => $payroll->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete payment proof
     */
    public function deleteProof($id)
    {
        $payroll = Payroll::find($id);

        if (!$payroll) {
            return response()->json([
                'success' => false,
                'message' => 'Slip gaji tidak ditemukan'
            ], 404);
        }

        if (!$payroll->payment_proof) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada bukti transfer untuk dihapus'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Delete file
            if (Storage::disk('public')->exists($payroll->payment_proof)) {
                Storage::disk('public')->delete($payroll->payment_proof);
            }

            // Update payroll
            $payroll->update([
                'payment_proof' => null,
                'paid_at' => null,
                'status' => 'sent'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bukti transfer berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
