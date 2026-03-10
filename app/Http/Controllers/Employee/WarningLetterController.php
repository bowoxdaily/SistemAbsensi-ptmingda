<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\WarningLetter;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class WarningLetterController extends Controller
{
    /**
     * Display list of employee's own warning letters (web view)
     */
    public function index(Request $request)
    {
        // Security: Ensure authenticated and only karyawan role
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        if (in_array(Auth::user()->role, ['admin', 'manager', 'viewer'])) {
            return redirect()->route('admin.warning-letters.index');
        }
        if (Auth::user()->role !== 'karyawan') {
            return redirect()->route('dashboard');
        }

        return view('employee.warning-letters.index');
    }

    /**
     * Get list of employee's own warning letters (API)
     */
    public function list(Request $request)
    {
        // Security check - karyawan only
        if (!Auth::check() || Auth::user()->role !== 'karyawan') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        try {
            // Get current employee
            $employee = Employee::where('user_id', Auth::id())->firstOrFail();

            $query = WarningLetter::with(['issuer'])
                ->where('employee_id', $employee->id)
                ->orderBy('issue_date', 'desc');

            // Filter by SP type
            if ($request->has('sp_type') && $request->sp_type != '') {
                $query->where('sp_type', $request->sp_type);
            }

            // Filter by status
            if ($request->has('status') && $request->status != '') {
                $query->where('status', $request->status);
            }

            // Filter by year
            if ($request->has('year') && $request->year != '') {
                $query->whereYear('issue_date', $request->year);
            }

            $warningLetters = $query->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $warningLetters
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching employee warning letters', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data SP'
            ], 500);
        }
    }

    /**
     * Get statistics for employee dashboard (API)
     */
    public function statistics()
    {
        // Security check - karyawan only
        if (!Auth::check() || Auth::user()->role !== 'karyawan') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        try {
            // Get current employee
            $employee = Employee::where('user_id', Auth::id())->firstOrFail();

            $stats = [
                'aktif' => WarningLetter::where('employee_id', $employee->id)
                    ->where('status', 'aktif')
                    ->count(),
                'total' => WarningLetter::where('employee_id', $employee->id)->count(),
                'selesai' => WarningLetter::where('employee_id', $employee->id)
                    ->where('status', 'selesai')
                    ->count(),
                'sp1' => WarningLetter::where('employee_id', $employee->id)
                    ->where('sp_type', 'SP1')
                    ->count(),
                'sp2' => WarningLetter::where('employee_id', $employee->id)
                    ->where('sp_type', 'SP2')
                    ->count(),
                'sp3' => WarningLetter::where('employee_id', $employee->id)
                    ->where('sp_type', 'SP3')
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching employee SP statistics', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik SP'
            ], 500);
        }
    }

    /**
     * Show detail of employee's own warning letter (API)
     */
    public function show($id)
    {
        // Security check - karyawan only
        if (!Auth::check() || Auth::user()->role !== 'karyawan') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        try {
            // Get current employee
            $employee = Employee::where('user_id', Auth::id())->firstOrFail();

            // Find SP and verify ownership
            $sp = WarningLetter::with(['issuer'])
                ->where('id', $id)
                ->where('employee_id', $employee->id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $sp
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data SP tidak ditemukan atau Anda tidak memiliki akses'
            ], 404);
        }
    }

    /**
     * Download employee's own SP document
     */
    public function downloadDocument($id)
    {
        // Security check - karyawan only
        if (!Auth::check() || Auth::user()->role !== 'karyawan') {
            abort(403, 'Unauthorized action.');
        }

        try {
            // Get current employee
            $employee = Employee::where('user_id', Auth::id())->firstOrFail();

            // Find SP and verify ownership
            $sp = WarningLetter::where('id', $id)
                ->where('employee_id', $employee->id)
                ->firstOrFail();

            if (!$sp->document_path || !Storage::disk('public')->exists($sp->document_path)) {
                abort(404, 'Dokumen tidak ditemukan');
            }

            $filePath = storage_path('app/public/' . $sp->document_path);
            $fileName = basename($sp->document_path);

            return response()->download($filePath, $fileName);
        } catch (\Exception $e) {
            Log::error('Error downloading SP document by employee', [
                'sp_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            abort(500, 'Gagal mengunduh dokumen');
        }
    }
}
