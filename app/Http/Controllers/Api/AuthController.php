<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
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
     * Revoke the current access token (logout).
     *
     * POST /api/auth/logout
     * Header: Authorization: Bearer <token>
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

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

    /**
     * TEMPORARY: Diagnose Authorization header delivery on production.
     * GET /api/auth/header-diag
     *
     * Delete this method and its route once the issue is resolved.
     */
    public function headerDiag(Request $request)
    {
        $candidates = [
            'HTTP_AUTHORIZATION',
            'REDIRECT_HTTP_AUTHORIZATION',
            'REDIRECT_REDIRECT_HTTP_AUTHORIZATION',
            'HTTP_HTTP_AUTHORIZATION',
            'HTTP_X_AUTHORIZATION',
            'HTTP_X_API_TOKEN',
        ];

        $serverValues = [];
        foreach ($candidates as $key) {
            $serverValues[$key] = isset($_SERVER[$key]) ? substr($_SERVER[$key], 0, 30) . '...' : null;
        }

        $allHeaders = [];
        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $k => $v) {
                $allHeaders[strtolower($k)] = strlen($v) > 30 ? substr($v, 0, 30) . '...' : $v;
            }
        }

        return response()->json([
            'laravel_sees_auth_header'   => $request->headers->has('Authorization'),
            'laravel_bearer_token'       => $request->bearerToken() ? substr($request->bearerToken(), 0, 10) . '...' : null,
            'laravel_sees_x_auth'        => $request->headers->has('X-Authorization'),
            'laravel_sees_x_api_token'   => $request->headers->has('X-Api-Token'),
            '_SERVER_candidates'         => $serverValues,
            'getallheaders'              => $allHeaders,
            'php_sapi'                   => PHP_SAPI,
            'workaround_instruction'     => 'If laravel_sees_auth_header=false, use X-Authorization: Bearer TOKEN header instead',
        ]);
    }
}
