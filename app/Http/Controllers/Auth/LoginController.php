<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    /**
     * Determine whether the related employee is allowed to login.
     */
    private function hasActiveEmployeeStatus($user): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        $employee = $user->employee;

        // Admin/manager may not have an employee record; only enforce when relation exists.
        if (!$employee) {
            return true;
        }

        return $employee->status === 'active';
    }

    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->intended('/dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if (!$this->hasActiveEmployeeStatus($user)) {
                Auth::logout();

                throw ValidationException::withMessages([
                    'email' => 'Status karyawan Anda tidak aktif. Silakan hubungi admin.',
                ]);
            }

            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => 'Email atau password yang Anda masukkan salah.',
        ]);
    }

    /**
     * Redirect user to Google OAuth
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Gagal login dengan Google. Silakan coba lagi.',
            ]);
        }

        if (!$googleUser->email) {
            return redirect()->route('login')->withErrors([
                'email' => 'Email Google tidak tersedia. Silakan gunakan metode login lain.',
            ]);
        }

        $user = User::where('google_id', $googleUser->id)->first();

        if (!$user && $googleUser->email) {
            $user = User::where('email', $googleUser->email)->first();
        }

        if (!$user) {
            // User tidak ditemukan di database, tolak akses
            return redirect()->route('login')->withErrors([
                'email' => 'Email Anda (' . $googleUser->email . ') belum terdaftar di sistem. Silakan hubungi admin untuk didaftarkan.',
            ]);
        } else {
            $updates = ['google_id' => $googleUser->id];

            $employee = $user->employee;
            $hasUserPhoto = !empty($user->profile_photo);
            $hasEmployeePhoto = $employee && !empty($employee->profile_photo);

            if ($googleUser->avatar) {
                if (!$hasUserPhoto && !$hasEmployeePhoto) {
                    $updates['profile_photo'] = $googleUser->avatar;
                }

                if ($employee && !$hasEmployeePhoto) {
                    $employee->update(['profile_photo' => $googleUser->avatar]);
                }
            }

            $user->fill($updates)->save();
        }

        if ($user->status !== 'aktif') {
            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda tidak aktif. Silakan hubungi admin.',
            ]);
        }

        if (!$this->hasActiveEmployeeStatus($user)) {
            return redirect()->route('login')->withErrors([
                'email' => 'Status karyawan Anda tidak aktif. Silakan hubungi admin.',
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah berhasil logout.');
    }
}
