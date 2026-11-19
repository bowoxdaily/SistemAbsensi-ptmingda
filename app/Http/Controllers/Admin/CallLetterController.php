<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallLetter;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class CallLetterController extends Controller
{
    private function authorizeAccess(): void
    {
        abort_unless(Auth::check() && in_array(Auth::user()->role, ['admin', 'manager', 'viewer', 'superadmin'], true), 403);
    }

    public function index()
    {
        $this->authorizeAccess();
        return view('admin.call-letters.index');
    }

    public function employees(Request $request)
    {
        $this->authorizeAccess();
        $search = trim($request->query('q', ''));
        $employees = Employee::where('status', 'active')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('employee_code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")))
            ->orderBy('employee_code')
            ->paginate(20, ['id', 'employee_code', 'name']);

        return response()->json([
            'results' => $employees->getCollection()->map(fn ($employee) => [
                'id' => $employee->id,
                'text' => $employee->employee_code . ' - ' . $employee->name,
            ]),
            'pagination' => ['more' => $employees->hasMorePages()],
        ]);
    }

    public function list(Request $request)
    {
        $this->authorizeAccess();
        $query = CallLetter::with('employee')->latest('issue_date');
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn ($q) => $q->where('letter_number', 'like', "%{$search}%")
                ->orWhereHas('employee', fn ($employee) => $employee->where('name', 'like', "%{$search}%")));
        }
        return response()->json(['data' => $query->paginate(20)]);
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'letter_number' => 'required|string|max:100|unique:call_letters,letter_number',
            'issue_date' => 'required|date',
            'meeting_at' => 'required|date',
            'location' => 'required|string|max:255',
            'purpose' => 'required|string|max:2000',
            'description' => 'nullable|string|max:2000',
            'document' => 'required|file|mimes:pdf|max:5120',
        ]);
        $file = $request->file('document');
        $path = $file->storeAs(
            'call_letters',
            uniqid('call-letter-', true) . '.pdf',
            'local'
        );
        if (!$path) {
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan file surat.'], 500);
        }

        $data['document_path'] = $path;
        $data['issued_by'] = Auth::id();
        try {
            $letter = CallLetter::create($data);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return response()->json(['success' => true, 'data' => $letter], 201);
    }

    public function downloadDocument($id)
    {
        $this->authorizeAccess();
        $letter = CallLetter::findOrFail($id);
        abort_unless($letter->document_path && Storage::disk('local')->exists($letter->document_path), 404, 'File surat tidak ditemukan.');

        return Storage::disk('local')->download($letter->document_path, 'Surat-Resmi-' . $letter->letter_number . '.pdf');
    }

    public function print($id)
    {
        $this->authorizeAccess();
        return view('admin.call-letters.print', ['letter' => CallLetter::with(['employee.department', 'employee.position', 'issuer'])->findOrFail($id)]);
    }

    public function downloadPdf($id)
    {
        $this->authorizeAccess();
        $letter = CallLetter::with(['employee.department', 'employee.position', 'issuer'])->findOrFail($id);
        $url = route('admin.call-letters.print', $letter->id);
        $browsers = array_filter([
            env('BROWSER_PATH'),
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        ]);
        $browser = collect($browsers)->first(fn ($path) => is_file($path));
        abort_unless($browser, 503, 'Browser Chrome/Edge untuk membuat PDF tidak tersedia. Gunakan tombol Cetak / Simpan PDF.');

        $directory = storage_path('app/private/call-letters');
        if (!is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        $profile = $directory . DIRECTORY_SEPARATOR . 'chrome-' . uniqid();
        $path = $directory . DIRECTORY_SEPARATOR . 'surat-panggilan-' . $letter->id . '-' . uniqid() . '.pdf';
        $process = new Process([$browser, '--headless', '--disable-gpu', '--no-sandbox', '--no-first-run', '--user-data-dir=' . $profile, '--print-to-pdf=' . $path, $url]);
        $process->setTimeout(60);
        $process->run();
        if (!$process->isSuccessful() || !is_file($path)) {
            @unlink($path);
            abort(500, 'Gagal membuat PDF. Gunakan tombol Cetak / Simpan PDF.');
        }

        return response()->download($path, 'Surat-Panggilan-' . $letter->letter_number . '.pdf')->deleteFileAfterSend();
    }
}

