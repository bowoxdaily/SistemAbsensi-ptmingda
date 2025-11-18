<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubDepartment;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubDepartmentController extends Controller
{
    /**
     * Display listing (Blade view)
     */
    public function index()
    {
        return view('admin.sub-departments.index');
    }

    /**
     * Get paginated list (API)
     */
    public function list(Request $request)
    {
        $query = SubDepartment::with(['department', 'employees'])
            ->withCount('employees');

        // Filter by department
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Search
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $perPage = $request->get('per_page', 10);
        $subDepartments = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $subDepartments
        ]);
    }

    /**
     * Get single sub department (API)
     */
    public function show($id)
    {
        $subDepartment = SubDepartment::with(['department', 'employees'])
            ->withCount('employees')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $subDepartment
        ]);
    }

    /**
     * Store new sub department (API)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ], [
            'department_id.required' => 'Departemen harus dipilih',
            'department_id.exists' => 'Departemen tidak valid',
            'name.required' => 'Nama sub departemen harus diisi',
            'name.max' => 'Nama sub departemen maksimal 255 karakter',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $subDepartment = SubDepartment::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Sub departemen berhasil ditambahkan',
            'data' => $subDepartment->load('department')
        ], 201);
    }

    /**
     * Update sub department (API)
     */
    public function update(Request $request, $id)
    {
        $subDepartment = SubDepartment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ], [
            'department_id.required' => 'Departemen harus dipilih',
            'department_id.exists' => 'Departemen tidak valid',
            'name.required' => 'Nama sub departemen harus diisi',
            'name.max' => 'Nama sub departemen maksimal 255 karakter',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $subDepartment->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Sub departemen berhasil diperbarui',
            'data' => $subDepartment->load('department')
        ]);
    }

    /**
     * Delete sub department (API)
     */
    public function destroy($id)
    {
        $subDepartment = SubDepartment::withCount('employees')->findOrFail($id);

        // Check if has employees
        if ($subDepartment->employees_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Sub departemen tidak dapat dihapus karena masih memiliki ' . $subDepartment->employees_count . ' karyawan'
            ], 422);
        }

        $subDepartment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sub departemen berhasil dihapus'
        ]);
    }

    /**
     * Get sub departments by department (API - untuk cascade dropdown)
     */
    public function getByDepartment($departmentId)
    {
        $subDepartments = SubDepartment::where('department_id', $departmentId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subDepartments
        ]);
    }
}
