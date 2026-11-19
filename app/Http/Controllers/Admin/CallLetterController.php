<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallLetter;
use App\Models\Employee;
use App\Jobs\SendCallLetterEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

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
            'employee_id' => ['required', 'exists:employees,id,status,active'],
            'letter_number' => 'required|string|max:100|unique:call_letters,letter_number',
            'issue_date' => 'required|date',
            'meeting_at' => 'required|date',
            'location' => 'required|string|max:255',
            'purpose' => 'required|string|max:2000',
            'description' => 'nullable|string|max:2000',
            'document' => 'required|file|mimes:pdf|max:5120',
        ]);
        $employee = Employee::findOrFail($data['employee_id']);
        $email = trim((string) $employee->email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['success' => false, 'message' => 'Email karyawan tidak tersedia atau tidak valid.'], 422);
        }

        $disk = 'r2';
        $path = $request->file('document')->storeAs(
            'call_letters',
            uniqid('call-letter-', true) . '.pdf',
            $disk
        );
        if (!$path) {
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan file surat.'], 500);
        }

        $data['document_path'] = $path;
        $data['issued_by'] = Auth::id();
        $data['email_status'] = 'pending';
        try {
            $letter = CallLetter::create($data);
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($path);
            throw $exception;
        }

        try {
            SendCallLetterEmail::dispatch($letter->id);
        } catch (\Throwable $exception) {
            $letter->forceFill(['email_status' => 'failed', 'email_error' => mb_substr($exception->getMessage(), 0, 2000)])->save();
            Log::error('Call letter email could not be queued', ['call_letter_id' => $letter->id, 'error' => $exception->getMessage()]);

            return response()->json(['success' => true, 'message' => 'Surat tersimpan, tetapi email gagal masuk antrean.', 'data' => $letter], 201);
        }

        return response()->json(['success' => true, 'message' => 'Surat tersimpan. Email sedang diproses di background.', 'data' => $letter], 201);
    }

    public function detail($id)
    {
        $this->authorizeAccess();

        return response()->json(['data' => CallLetter::with('employee')->findOrFail($id)]);
    }

    public function destroy($id)
    {
        $this->authorizeAccess();
        $letter = CallLetter::findOrFail($id);
        $path = $letter->document_path;

        if ($path && !Storage::disk('r2')->delete($path)) {
            return response()->json(['success' => false, 'message' => 'PDF gagal dihapus dari R2. Surat tidak dihapus.'], 500);
        }
        $letter->delete();

        return response()->json(['success' => true, 'message' => 'Surat panggilan berhasil dihapus.']);
    }

    public function sendNotification($id)
    {
        $this->authorizeAccess();
        $letter = CallLetter::with('employee')->findOrFail($id);
        $email = trim((string) ($letter->employee->email ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $letter->forceFill(['email_status' => 'failed', 'email_error' => 'Email karyawan tidak tersedia atau tidak valid.'])->save();

            return response()->json(['success' => false, 'message' => 'Email karyawan tidak tersedia atau tidak valid.'], 422);
        }

        abort_unless($letter->document_path && Storage::disk('r2')->exists($letter->document_path), 404, 'File surat tidak ditemukan di R2.');
        if ($letter->email_status === 'pending') {
            return response()->json(['success' => false, 'message' => 'Email sedang dalam antrean. Tunggu hingga proses selesai.'], 409);
        }
        $letter->forceFill(['email_status' => 'pending', 'email_error' => null])->save();

        try {
            SendCallLetterEmail::dispatch($letter->id);
        } catch (\Throwable $exception) {
            $letter->forceFill(['email_status' => 'failed', 'email_error' => mb_substr($exception->getMessage(), 0, 2000)])->save();
            Log::error('Call letter email could not be queued', ['call_letter_id' => $letter->id, 'error' => $exception->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Email gagal masuk antrean.'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Email masuk antrean dan akan dikirim ke ' . $letter->employee->name]);
    }

    public function downloadDocument($id)
    {
        $this->authorizeAccess();
        $letter = CallLetter::findOrFail($id);
        $disk = 'r2';
        abort_unless($letter->document_path && Storage::disk($disk)->exists($letter->document_path), 404, 'File surat tidak ditemukan.');

        $filename = 'Surat-Resmi-' . preg_replace('/[\\\\\/]+/', '-', $letter->letter_number) . '.pdf';

        return Storage::disk($disk)->download($letter->document_path, $filename);
    }
}

