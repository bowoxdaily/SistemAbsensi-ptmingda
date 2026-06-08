<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Opl;
use Illuminate\Support\Facades\Log;

class OPLController extends Controller
{
    public function index()
    {
        return view('admin.opls.index');
    }

    public function list(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $query = Opl::orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ;
            });
        }

        $data = $query->paginate($perPage);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'attachment' => 'nullable|image|max:5120',
            'is_active' => 'boolean',
            'show_popup' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        try {
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('opls', 'public');
            }

            $opl = Opl::create([
                'title' => $request->input('title'),
                'attachment' => $attachmentPath,
                'is_active' => $request->boolean('is_active', true),
                'show_popup' => $request->boolean('show_popup', false),
                'created_by' => Auth::id(),
            ]);

            return response()->json(['success' => true, 'data' => $opl, 'message' => 'OPL berhasil ditambahkan']);
        } catch (\Exception $e) {
            Log::error('OPL store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan OPL: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $opl = Opl::findOrFail($id);
            return response()->json(['success' => true, 'data' => $opl]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'OPL tidak ditemukan'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'attachment' => 'nullable|image|max:5120',
            'is_active' => 'boolean',
            'show_popup' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        try {
            $opl = Opl::findOrFail($id);
            $attachmentPath = $opl->attachment;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('opls', 'public');
            }

            $opl->update([
                'title' => $request->input('title'),
                'attachment' => $attachmentPath,
                'is_active' => $request->boolean('is_active', true),
                'show_popup' => $request->boolean('show_popup', false),
            ]);

            return response()->json(['success' => true, 'data' => $opl, 'message' => 'OPL berhasil diperbarui']);
        } catch (\Exception $e) {
            Log::error('OPL update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui OPL: ' . $e->getMessage()], 500);
        }
    }

    public function toggleActive($id)
    {
        try {
            $opl = Opl::findOrFail($id);
            $opl->update(['is_active' => !$opl->is_active]);
            return response()->json(['success' => true, 'is_active' => $opl->is_active]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengubah status'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $opl = Opl::findOrFail($id);
            $opl->delete();
            return response()->json(['success' => true, 'message' => 'OPL berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus OPL'], 500);
        }
    }
}
