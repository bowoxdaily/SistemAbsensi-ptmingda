<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OfficeSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OfficeSettingController extends Controller
{
    /**
     * Display office setting page
     */
    public function index()
    {
        $setting = OfficeSetting::get();
        return view('admin.settings.office', compact('setting'));
    }

    /**
     * Update office setting
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'office_name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_meters' => 'required|integer|min:10|max:5000',
            'enforce_location' => 'required|boolean',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $setting = OfficeSetting::get();
            $setting->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Pengaturan lokasi kantor berhasil diperbarui',
                'data' => $setting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui pengaturan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current office setting
     */
    public function show()
    {
        $setting = OfficeSetting::get();
        return response()->json([
            'success' => true,
            'data' => $setting
        ]);
    }
}
