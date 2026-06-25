<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AccountManagementController extends Controller
{
    /**
     * Hanya superadmin yang bisa akses
     */
    public function __construct()
    {
        if (!Auth::check() || Auth::user()->role !== 'superadmin') {
            abort(403, 'Unauthorized. Hanya superadmin yang dapat mengakses management akun.');
        }
    }

    /**
     * Display list of accounts
     */
    public function index()
    {
        return view('admin.account-management.index');
    }

    /**
     * Get list of accounts (API)
     * Superadmin can only manage admin, security, viewer accounts (not other superadmin)
     */
    public function list(Request $request)
    {
        $query = User::query();
        
        // Exclude superadmin and karyawan accounts (can only see own superadmin)
        $query->where(function ($q) {
            $q->where('role', '!=', 'superadmin')
              ->where('role', '!=', 'karyawan')
              ->orWhere('id', Auth::id());
        });

        // Filter by role
        if ($role = $request->get('role')) {
            $query->where('role', $role);
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Search by name or email
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        $accounts = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        // Hide sensitive data
        $accounts->getCollection()->transform(function ($user) {
            $user->makeHidden(['password', 'remember_token']);
            return $user;
        });

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    /**
     * Get detail of one account
     * Superadmin can only view admin, security, viewer accounts (except own)
     */
    public function detail($id)
    {
        $user = User::findOrFail($id);
        
        // Superadmin can only view other superadmin accounts if it's themselves
        if ($user->role === 'superadmin' && $id != Auth::id()) {
            abort(403, 'Tidak dapat melihat detail akun superadmin lain');
        }
        
        // Karyawan accounts cannot be managed
        if ($user->role === 'karyawan') {
            abort(403, 'Akun karyawan tidak dapat dikelola melalui interface ini');
        }
        
        $user->makeHidden(['password', 'remember_token']);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Get available roles and statuses
     * Superadmin can only manage admin, security, viewer roles
     */
    public function getOptions()
    {
        return response()->json([
            'success' => true,
            'roles' => ['admin', 'security', 'viewer'],
            'statuses' => ['aktif', 'nonaktif'],
        ]);
    }

    /**
     * Create new account
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,security,viewer',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'status' => $request->status,
            ]);

            $user->makeHidden(['password', 'remember_token']);

            return response()->json([
                'success' => true,
                'message' => 'Akun berhasil dibuat',
                'data' => $user,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat akun: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update account
     * Superadmin can only manage admin, security, viewer accounts
     */
    public function update(Request $request, $id)
    {
        // Superadmin tidak boleh edit dirinya sendiri untuk role/status (mencegah lockout)
        if ($id == Auth::id() && ($request->has('role') || $request->has('status'))) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat mengubah role/status akun Anda sendiri',
            ], 422);
        }

        $user = User::findOrFail($id);
        
        // Superadmin cannot manage other superadmin accounts
        if ($user->role === 'superadmin' && $id != Auth::id()) {
            abort(403, 'Tidak dapat mengelola akun superadmin lain');
        }
        
        // Karyawan accounts cannot be managed
        if ($user->role === 'karyawan') {
            abort(403, 'Akun karyawan tidak dapat dikelola melalui interface ini');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'nullable|in:admin,security,viewer',
            'status' => 'nullable|in:aktif,nonaktif',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            if ($request->has('name')) $user->name = $request->name;
            if ($request->has('email')) $user->email = $request->email;
            if ($request->has('password')) $user->password = Hash::make($request->password);
            if ($request->has('role')) $user->role = $request->role;
            if ($request->has('status')) $user->status = $request->status;

            $user->save();
            $user->makeHidden(['password', 'remember_token']);

            return response()->json([
                'success' => true,
                'message' => 'Akun berhasil diperbarui',
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui akun: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete account
     * Superadmin cannot delete other superadmin accounts
     */
    public function destroy($id)
    {
        // Superadmin tidak boleh delete dirinya sendiri
        if ($id == Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus akun Anda sendiri',
            ], 422);
        }

        $user = User::findOrFail($id);
        
        // Superadmin cannot delete other superadmin accounts
        if ($user->role === 'superadmin') {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus akun superadmin',
            ], 422);
        }
        
        // Karyawan accounts cannot be deleted
        if ($user->role === 'karyawan') {
            return response()->json([
                'success' => false,
                'message' => 'Akun karyawan tidak dapat dihapus melalui interface ini',
            ], 422);
        }

        // Check for dependent records (attendance_edit_requests, etc)
        $dependentRecords = \DB::table('attendance_edit_requests')
            ->where('requested_by', $id)
            ->count();
        
        if ($dependentRecords > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus akun ini karena masih memiliki ' . $dependentRecords . ' permintaan edit absensi yang terkait. Hubungi administrator untuk detail lebih lanjut.',
            ], 422);
        }

        try {
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Akun berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus akun: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change password
     */
    public function changePassword(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::findOrFail($id);
            
            // Karyawan accounts cannot be managed
            if ($user->role === 'karyawan') {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun karyawan tidak dapat dikelola melalui interface ini',
                ], 403);
            }
            
            $user->update(['password' => Hash::make($request->password)]);

            return response()->json([
                'success' => true,
                'message' => 'Password berhasil diubah',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah password: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get account statistics
     */
    public function stats()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total' => User::count(),
                'aktif' => User::where('status', 'aktif')->count(),
                'nonaktif' => User::where('status', 'nonaktif')->count(),
                'by_role' => [
                    'superadmin' => User::where('role', 'superadmin')->count(),
                    'admin' => User::where('role', 'admin')->count(),
                    'manager' => User::where('role', 'manager')->count(),
                    'viewer' => User::where('role', 'viewer')->count(),
                    'security' => User::where('role', 'security')->count(),
                    'guest' => User::where('role', 'guest')->count(),
                    'karyawan' => User::where('role', 'karyawan')->count(),
                ],
            ],
        ]);
    }
}
