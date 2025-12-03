<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function dashboard()
    {
        $departments = Department::withCount('employees')->latest()->paginate(10);
        return view('admin.departments.index', compact('departments'));
    }

    public function index(Request $request)
    {
        $query = Department::withCount('employees');

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }

        // Check if pagination is needed
        if ($request->has('paginate') && $request->paginate === 'false') {
            // Return all results without pagination (for Select2)
            $departments = $query->orderBy('name', 'asc')->get();
            return response()->json([
                'success' => true,
                'data' => $departments
            ]);
        }

        // Default paginated results
        $perPage = $request->get('per_page', 10);
        $departments = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $departments
        ]);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:departments,name',
            'description' => 'nullable|string|max:1000'
        ], [
            'name.required' => 'Nama departemen wajib diisi',
            'name.unique' => 'Nama departemen sudah ada',
            'name.max' => 'Nama departemen maksimal 255 karakter',
            'description.max' => 'Deskripsi maksimal 1000 karakter'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $department = Department::create($request->only(['name', 'description']));

        return response()->json([
            'success' => true,
            'message' => 'Departemen berhasil ditambahkan',
            'data' => $department->loadCount('employees')
        ], 201);
    }

    /**
     * Get single department
     */
    public function show($id)
    {
        $department = Department::withCount('employees')->find($id);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Departemen tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $department
        ]);
    }

    /**
     * Update department
     */
    public function update(Request $request, $id)
    {
        $department = Department::find($id);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Departemen tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:departments,name,' . $id,
            'description' => 'nullable|string|max:1000'
        ], [
            'name.required' => 'Nama departemen wajib diisi',
            'name.unique' => 'Nama departemen sudah ada',
            'name.max' => 'Nama departemen maksimal 255 karakter',
            'description.max' => 'Deskripsi maksimal 1000 karakter'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $department->update($request->only(['name', 'description']));

        return response()->json([
            'success' => true,
            'message' => 'Departemen berhasil diperbarui',
            'data' => $department->loadCount('employees')
        ]);
    }

    /**
     * Delete department
     */
    public function destroy($id)
    {
        $department = Department::withCount('employees')->find($id);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Departemen tidak ditemukan'
            ], 404);
        }

        // Check if department has employees
        if ($department->employees_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Departemen tidak dapat dihapus karena masih memiliki ' . $department->employees_count . ' karyawan'
            ], 400);
        }

        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'Departemen berhasil dihapus'
        ]);
    }
}
