<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PositionController extends Controller
{
    /**
     * Display the positions dashboard view
     */
    public function dashboard()
    {
        return view('admin.positions.index');
    }

    /**
     * Get paginated list of positions
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');

        $query = Position::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        }

        $positions = $query->orderBy('name', 'asc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Data jabatan berhasil dimuat',
            'data' => $positions
        ]);
    }

    /**
     * Store a new position
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:20|unique:positions,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive'
        ], [
            'code.required' => 'Kode jabatan harus diisi',
            'code.unique' => 'Kode jabatan sudah digunakan',
            'name.required' => 'Nama jabatan harus diisi',
            'status.required' => 'Status harus dipilih'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $position = Position::create($request->only(['code', 'name', 'description', 'status']));

            return response()->json([
                'success' => true,
                'message' => 'Jabatan berhasil ditambahkan',
                'data' => $position
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan jabatan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single position details
     */
    public function show($id)
    {
        try {
            $position = Position::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Detail jabatan berhasil dimuat',
                'data' => $position
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Jabatan tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Update position
     */
    public function update(Request $request, $id)
    {
        try {
            $position = Position::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'code' => 'required|string|max:20|unique:positions,code,' . $id,
                'name' => 'required|string|max:100',
                'description' => 'nullable|string',
                'status' => 'required|in:active,inactive'
            ], [
                'code.required' => 'Kode jabatan harus diisi',
                'code.unique' => 'Kode jabatan sudah digunakan',
                'name.required' => 'Nama jabatan harus diisi',
                'status.required' => 'Status harus dipilih'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $position->update($request->only(['code', 'name', 'description', 'status']));

            return response()->json([
                'success' => true,
                'message' => 'Jabatan berhasil diperbarui',
                'data' => $position
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui jabatan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete position
     */
    public function destroy($id)
    {
        try {
            $position = Position::findOrFail($id);

            // Check if position is being used by employees
            if ($position->employees()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jabatan tidak dapat dihapus karena masih digunakan oleh karyawan'
                ], 400);
            }

            $position->delete();

            return response()->json([
                'success' => true,
                'message' => 'Jabatan berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus jabatan: ' . $e->getMessage()
            ], 500);
        }
    }
}
