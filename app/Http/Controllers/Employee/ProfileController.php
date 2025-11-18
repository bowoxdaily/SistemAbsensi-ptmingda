<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProfileController extends Controller
{
    /**
     * Display the profile page
     */
    public function index()
    {
        // Security: Ensure user is logged in
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Redirect admin to admin profile
        if (Auth::user()->role === 'admin') {
            return redirect()->route('admin.profile.index');
        }

        $employee = Employee::with(['department', 'position'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Get current month statistics
        $stats = [
            'hadir' => Attendance::where('employee_id', $employee->id)
                ->whereMonth('attendance_date', now()->month)
                ->whereYear('attendance_date', now()->year)
                ->where('status', 'hadir')
                ->count(),
            'terlambat' => Attendance::where('employee_id', $employee->id)
                ->whereMonth('attendance_date', now()->month)
                ->whereYear('attendance_date', now()->year)
                ->where('status', 'terlambat')
                ->count(),
            'alpha' => Attendance::where('employee_id', $employee->id)
                ->whereMonth('attendance_date', now()->month)
                ->whereYear('attendance_date', now()->year)
                ->where('status', 'alpha')
                ->count(),
        ];

        return view('employee.profile.index', compact('employee', 'stats'));
    }

    /**
     * Update profile information
     */
    public function update(Request $request)
    {
        // Security: Ensure user is logged in and is an employee
        if (!Auth::check()) {
            abort(403, 'Unauthorized action.');
        }

        if (Auth::user()->role === 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $employee = Employee::where('user_id', Auth::id())->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:employees,email,' . $employee->id,
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:L,P',
            'address' => 'nullable|string|max:500',
        ]);

        // Remove NIK from update - only admin can change NIK
        $employee->update($validated);

        // Update user name if changed
        if ($employee->user) {
            $employee->user->update(['name' => $validated['name']]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Profil berhasil diperbarui',
                'data' => [
                    'employee' => $employee->fresh()
                ]
            ]);
        }

        return redirect()
            ->route('employee.profile.index')
            ->with('success', 'Profil berhasil diperbarui');
    }

    /**
     * Update profile photo
     */
    public function updatePhoto(Request $request)
    {
        // Security: Ensure user is logged in and is an employee
        if (!Auth::check()) {
            abort(403, 'Unauthorized action.');
        }

        if (Auth::user()->role === 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $employee = Employee::where('user_id', Auth::id())->firstOrFail();

        $request->validate([
            'profile_photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240', // Max 10MB
        ]);

        // Delete old photo if exists
        if ($employee->profile_photo && Storage::disk('public')->exists($employee->profile_photo)) {
            Storage::disk('public')->delete($employee->profile_photo);
        }

        // Process and store new photo
        $file = $request->file('profile_photo');
        $filename = 'profile_' . $employee->id . '_' . time() . '.webp';

        // Create image manager instance
        $manager = new ImageManager(new Driver());

        // Read and process image
        $image = $manager->read($file);

        // Resize to max 500x500 (maintain aspect ratio)
        $image->scale(width: 500, height: 500);

        // Convert to WebP with 85% quality and save
        $path = 'profile_photos/' . $filename;
        $webpImage = $image->toWebp(quality: 85);

        Storage::disk('public')->put($path, (string) $webpImage);

        $employee->update(['profile_photo' => $path]);

        // Return JSON for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Foto profil berhasil diperbarui',
                'photo_url' => asset('storage/' . $path)
            ]);
        }

        return redirect()
            ->route('employee.profile.index')
            ->with('success', 'Foto profil berhasil diperbarui');
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        // Security: Ensure user is logged in and is an employee
        if (!Auth::check()) {
            abort(403, 'Unauthorized action.');
        }

        if (Auth::user()->role === 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $employee = Employee::where('user_id', Auth::id())->firstOrFail();

        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // Check current password
        if (!Hash::check($request->current_password, $employee->user->password)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password lama tidak sesuai',
                    'errors' => [
                        'current_password' => ['Password lama tidak sesuai']
                    ]
                ], 422);
            }

            return back()
                ->withErrors(['current_password' => 'Password lama tidak sesuai'])
                ->with('error', 'Password lama tidak sesuai');
        }

        // Update password
        $employee->user->update([
            'password' => Hash::make($request->password)
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Password berhasil diubah!'
            ]);
        }

        return redirect()
            ->route('employee.profile.index')
            ->with('success', 'Password berhasil diubah! Silakan login kembali dengan password baru.');
    }
}
