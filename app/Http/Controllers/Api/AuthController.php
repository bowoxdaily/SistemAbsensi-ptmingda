<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
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
     * Login and issue a Sanctum API token.
     *
     * POST /api/auth/login
     * Body: { "email": "...", "password": "...", "token_name": "optional name" }
     *
     * Returns: Bearer token to be used in Authorization header for subsequent requests.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'      => 'required|email',
            'password'   => 'required|string',
            'token_name' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah',
            ], 401);
        }

        $user      = Auth::user();

        if (!$this->hasActiveEmployeeStatus($user)) {
            Auth::logout();

            return response()->json([
                'success' => false,
                'message' => 'Status karyawan tidak aktif. Silakan hubungi admin.',
            ], 403);
        }

        $tokenName = $request->input('token_name', 'api-token');
        $token     = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data'    => [
                'token'      => $token,
                'token_type' => 'Bearer',
                'user'       => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ],
            ],
        ]);
    }

    /**
     * Login with Google access token and issue a Sanctum API token.
     *
     * POST /api/auth/google
     * Body: { "google_token": "...", "token_name": "optional name" }
     */
    public function googleLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'google_token' => 'required|string',
            'token_name'   => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $googleResponse = Http::withToken($request->input('google_token'))
                ->get('https://www.googleapis.com/oauth2/v3/userinfo');

            if (!$googleResponse->successful()) {
                throw new \RuntimeException('Google userinfo request failed');
            }

            $googleUser = (object) $googleResponse->json();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token Google tidak valid atau sudah kedaluwarsa.',
            ], 401);
        }

        if (!$googleUser || empty($googleUser->email) || empty($googleUser->sub)) {
            return response()->json([
                'success' => false,
                'message' => 'Email Google tidak tersedia. Silakan gunakan metode login lain.',
            ], 422);
        }

        $user = User::query()->where('google_id', $googleUser->sub)->first();

        if (!$user) {
            $user = User::query()->where('email', $googleUser->email)->first();
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Google Anda belum terdaftar di sistem. Silakan hubungi admin untuk didaftarkan.',
            ], 404);
        }

        if ($user->status !== 'aktif') {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak aktif. Silakan hubungi admin.',
            ], 403);
        }

        if (!$this->hasActiveEmployeeStatus($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Status karyawan tidak aktif. Silakan hubungi admin.',
            ], 403);
        }

        $updates = ['google_id' => $googleUser->sub];

        $employee = $user->employee;
        $hasUserPhoto = !empty($user->profile_photo);
        $hasEmployeePhoto = $employee && !empty($employee->profile_photo);

        if (!empty($googleUser->picture)) {
            if (!$hasUserPhoto && !$hasEmployeePhoto) {
                $updates['profile_photo'] = $googleUser->picture;
            }

            if ($employee && !$hasEmployeePhoto) {
                $employee->update(['profile_photo' => $googleUser->picture]);
            }
        }

        $user->fill($updates)->save();

        $tokenName = $request->input('token_name', 'mobile-google');
        $token = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login dengan Google berhasil',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ],
        ]);
    }

    /**
     * Revoke the current access token (logout).
     *
     * POST /api/auth/logout
     * Header: Authorization: Bearer <token>
     */
    public function logout(Request $request)
    {
        $currentToken = $request->user()?->currentAccessToken();

        if ($currentToken) {
            PersonalAccessToken::query()->whereKey($currentToken->id)->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil. Token telah dicabut.',
        ]);
    }

    /**
     * Revoke ALL tokens for the current user.
     *
     * POST /api/auth/logout-all
     * Header: Authorization: Bearer <token>
     */
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Semua token telah dicabut.',
        ]);
    }
}
