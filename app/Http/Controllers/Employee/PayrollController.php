<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\HrisPayslipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollController extends Controller
{
    protected $hrisService;

    public function __construct(HrisPayslipService $hrisService)
    {
        $this->hrisService = $hrisService;
    }

    /**
     * Display payroll/payslip page for employee
     */
    public function index()
    {
        return view('employee.payroll.index');
    }

    /**
     * Get payslip list from external HRIS system.
     * Uses the employee's employee_code as the HRIS employee_id.
     */
    public function list()
    {
        try {
            $employee = Auth::user()->employee;

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data karyawan tidak ditemukan'
                ], 404);
            }

            if (!$this->hrisService->isConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Integrasi HRIS belum dikonfigurasi. Hubungi administrator.'
                ], 503);
            }

            $hrisEmployeeId = $employee->employee_code;

            if (empty($hrisEmployeeId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode karyawan belum diatur, tidak dapat mengambil data payslip'
                ], 422);
            }

            $result = $this->hrisService->getPayslipList($hrisEmployeeId);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data payslip: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download payslip PDF from external HRIS system.
     */
    public function downloadPdf(Request $request)
    {
        try {
            $employee = Auth::user()->employee;

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data karyawan tidak ditemukan'
                ], 404);
            }

            $month = $request->get('month');
            if (!$month || !preg_match('/^\d{4}-\d{2}$/', $month)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter month wajib diisi (format: YYYY-MM)'
                ], 422);
            }

            if (!$this->hrisService->isConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Integrasi HRIS belum dikonfigurasi'
                ], 503);
            }

            $hrisEmployeeId = $employee->employee_code;

            if (empty($hrisEmployeeId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode karyawan belum diatur'
                ], 422);
            }

            $result = $this->hrisService->downloadPayslipPdf($hrisEmployeeId, $month);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 404);
            }

            return response($result['content'])
                ->header('Content-Type', $result['content_type'])
                ->header('Content-Disposition', 'attachment; filename="' . $result['filename'] . '"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunduh payslip: ' . $e->getMessage()
            ], 500);
        }
    }
}
