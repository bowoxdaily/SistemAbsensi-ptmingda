<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FingerspotSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FingerspotSettingController extends Controller
{
    /**
     * Display Fingerspot settings page
     */
    public function index(Request $request)
    {
        // If AJAX request, return JSON
        if ($request->ajax() || $request->wantsJson()) {
            $settings = FingerspotSetting::orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $settings,
            ]);
        }

        // Return view for web request
        return view('admin.settings.fingerspot');
    }

    /**
     * Store new Fingerspot setting
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sn' => 'nullable|string|max:100',
            'api_url' => 'nullable|url|max:500',
            'is_active' => 'boolean',
            'auto_checkout' => 'boolean',
            'auto_checkout_hours' => 'integer|min:1|max:24',
            'scan_mode' => 'in:first_last,all',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $setting = FingerspotSetting::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Fingerspot setting berhasil ditambahkan',
                'data' => $setting,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan setting: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update Fingerspot setting
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'sn' => 'nullable|string|max:100',
            'api_url' => 'nullable|url|max:500',
            'is_active' => 'boolean',
            'auto_checkout' => 'boolean',
            'auto_checkout_hours' => 'integer|min:1|max:24',
            'scan_mode' => 'in:first_last,all',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $setting = FingerspotSetting::findOrFail($id);
            $setting->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Fingerspot setting berhasil diperbarui',
                'data' => $setting,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui setting: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Fingerspot setting
     */
    public function destroy($id)
    {
        try {
            $setting = FingerspotSetting::findOrFail($id);
            $setting->delete();

            return response()->json([
                'success' => true,
                'message' => 'Fingerspot setting berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus setting: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Regenerate webhook token
     */
    public function regenerateToken($id)
    {
        try {
            $setting = FingerspotSetting::findOrFail($id);
            $newToken = $setting->regenerateToken();

            return response()->json([
                'success' => true,
                'message' => 'Token berhasil di-regenerate',
                'data' => [
                    'webhook_token' => $newToken,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal regenerate token: ' . $e->getMessage()
            ], 500);
        }
    }
}
