<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\OvertimeExport;
use App\Jobs\GenerateOvertimeExportJob;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\ExportFile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class OvertimeController extends Controller
{
    private const ASYNC_THRESHOLD = 5000;

    public function index(Request $request)
    {
        $from = $request->get('date_from', now()->toDateString());
        $to = $request->get('date_to', now()->toDateString());
        $category = $request->get('category');
        $search = trim($request->get('search', ''));
        $employeeId = $request->filled('employee_id') ? (int) $request->get('employee_id') : null;

        $overtimes = Attendance::with('employee')
            ->whereBetween('attendance_date', [$from, $to])
            ->whereNotNull('check_out')
            ->whereNotNull('overtime_category')
            ->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId))
            ->when($search, function ($query) use ($search) {
                $query->whereHas('employee', function ($employeeQuery) use ($search) {
                    $employeeQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            })
            ->when($category, fn ($query) => $query->where('overtime_category', $category))
            ->orderByDesc('attendance_date')
            ->orderBy('overtime_category')
            ->paginate(25)
            ->withQueryString();

        $employees = Employee::orderBy('employee_code', 'asc')->get(['id', 'employee_code', 'name']);

        // Check latest pending/processing export for this user
        $pendingExport = ExportFile::where('user_id', auth()->id())
            ->where('type', 'overtime')
            ->whereIn('status', ['pending', 'processing'])
            ->latest()
            ->first();

        $doneExport = ExportFile::where('user_id', auth()->id())
            ->where('type', 'overtime')
            ->where('status', 'done')
            ->latest()
            ->first();

        return view('admin.overtime.index', compact(
            'overtimes', 'from', 'to', 'category', 'search', 'employeeId', 'employees',
            'pendingExport', 'doneExport'
        ));
    }

    public function export(Request $request)
    {
        $from = $request->get('date_from', now()->startOfMonth()->toDateString());
        $to = $request->get('date_to', now()->toDateString());
        $category = $request->get('category');
        $search = trim($request->get('search', ''));
        $employeeId = $request->filled('employee_id') ? (int) $request->get('employee_id') : null;

        $filename = 'overtime_' . $from . '_' . $to;
        if ($employeeId) {
            $employee = Employee::find($employeeId);
            if ($employee) {
                $filename .= '_' . Str::slug($employee->name);
            }
        }
        $filename .= '_' . substr(md5(auth()->id() . microtime()), 0, 8) . '.xlsx';

        $rowCount = OvertimeExport::countRows($from, $to, $category ?: null, $search ?: null, $employeeId);

        // Cleanup stale exports older than 24 hours
        ExportFile::where('user_id', auth()->id())
            ->where('type', 'overtime')
            ->where('created_at', '<', now()->subDay())
            ->each(function (ExportFile $old) {
                if ($old->path && Storage::disk($old->disk)->exists($old->path)) {
                    Storage::disk($old->disk)->delete($old->path);
                }
                $old->delete();
            });

        // Sync download for small datasets
        if ($rowCount < self::ASYNC_THRESHOLD) {
            return Excel::download(
                new OvertimeExport($from, $to, $category ?: null, $search ?: null, $employeeId),
                $filename
            );
        }

        // Async: prevent double-submit
        $existing = ExportFile::where('user_id', auth()->id())
            ->where('type', 'overtime')
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        if ($existing) {
            return redirect()->back()->with('export_queued', 'Export sebelumnya masih diproses. Silakan tunggu hingga selesai.');
        }

        // Async: create record + dispatch job
        $exportFile = ExportFile::create([
            'user_id' => auth()->id(),
            'type' => 'overtime',
            'filename' => $filename,
            'status' => 'pending',
        ]);

        GenerateOvertimeExportJob::dispatch(
            $exportFile->id,
            $from,
            $to,
            $category ?: null,
            $search ?: null,
            $employeeId,
        );

        return redirect()->back()->with('export_queued', 'Export sedang diproses di background. Silakan tunggu, tombol download akan muncul otomatis.');
    }

    public function exportStatus()
    {
        $latest = ExportFile::where('user_id', auth()->id())
            ->where('type', 'overtime')
            ->latest()
            ->first();

        if (!$latest) {
            return response()->json(['status' => 'none']);
        }

        return response()->json([
            'status' => $latest->status,
            'id' => $latest->id,
            'filename' => $latest->filename,
            'error' => $latest->error,
        ]);
    }

    public function exportDownload(ExportFile $exportFile)
    {
        if ($exportFile->user_id !== auth()->id()) {
            abort(403);
        }

        if (!$exportFile->isDone() || !$exportFile->path) {
            abort(404, 'File belum siap atau tidak ditemukan.');
        }

        if (!Storage::disk($exportFile->disk)->exists($exportFile->path)) {
            abort(404, 'File tidak ditemukan di storage.');
        }

        // Clean up: delete record after download (file deleted via stream callback)
        $path = Storage::disk($exportFile->disk)->path($exportFile->path);
        $filename = $exportFile->filename;

        $exportFile->delete();

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }
}
