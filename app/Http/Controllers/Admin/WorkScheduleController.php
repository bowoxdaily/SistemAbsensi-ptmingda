<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WorkScheduleController extends Controller
{
    /**
     * Display the work schedule settings page
     */
    public function index()
    {
        $schedules = WorkSchedule::orderBy('is_active', 'desc')
            ->orderBy('start_time', 'asc')
            ->get();

        return view('admin.settings.work-schedule', compact('schedules'));
    }

    /**
     * Store a new work schedule
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'late_tolerance' => 'required|integer|min:0|max:120',
        ], [
            'name.required' => 'Nama jadwal harus diisi',
            'start_time.required' => 'Jam mulai harus diisi',
            'start_time.date_format' => 'Format jam mulai tidak valid (HH:MM)',
            'end_time.required' => 'Jam selesai harus diisi',
            'end_time.date_format' => 'Format jam selesai tidak valid (HH:MM)',
            'end_time.after' => 'Jam selesai harus lebih besar dari jam mulai',
            'late_tolerance.required' => 'Toleransi keterlambatan harus diisi',
            'late_tolerance.integer' => 'Toleransi keterlambatan harus berupa angka',
            'late_tolerance.min' => 'Toleransi keterlambatan minimal 0 menit',
            'late_tolerance.max' => 'Toleransi keterlambatan maksimal 120 menit',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $schedule = WorkSchedule::create([
                'name' => $request->name,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'late_tolerance' => $request->late_tolerance,
                'is_active' => $request->has('is_active') && $request->is_active ? true : false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Jadwal kerja berhasil ditambahkan',
                'data' => $schedule
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing work schedule
     */
    public function update(Request $request, $id)
    {
        $schedule = WorkSchedule::find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal kerja tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'late_tolerance' => 'required|integer|min:0|max:120',
        ], [
            'name.required' => 'Nama jadwal harus diisi',
            'start_time.required' => 'Jam mulai harus diisi',
            'start_time.date_format' => 'Format jam mulai tidak valid (HH:MM)',
            'end_time.required' => 'Jam selesai harus diisi',
            'end_time.date_format' => 'Format jam selesai tidak valid (HH:MM)',
            'end_time.after' => 'Jam selesai harus lebih besar dari jam mulai',
            'late_tolerance.required' => 'Toleransi keterlambatan harus diisi',
            'late_tolerance.integer' => 'Toleransi keterlambatan harus berupa angka',
            'late_tolerance.min' => 'Toleransi keterlambatan minimal 0 menit',
            'late_tolerance.max' => 'Toleransi keterlambatan maksimal 120 menit',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $schedule->update([
                'name' => $request->name,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'late_tolerance' => $request->late_tolerance,
                'is_active' => $request->has('is_active') && $request->is_active ? true : false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Jadwal kerja berhasil diperbarui',
                'data' => $schedule
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a work schedule
     */
    public function destroy($id)
    {
        try {
            $schedule = WorkSchedule::find($id);

            if (!$schedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jadwal kerja tidak ditemukan'
                ], 404);
            }

            $schedule->delete();

            return response()->json([
                'success' => true,
                'message' => 'Jadwal kerja berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle active status of a work schedule
     */
    public function toggleStatus($id)
    {
        try {
            $schedule = WorkSchedule::find($id);

            if (!$schedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jadwal kerja tidak ditemukan'
                ], 404);
            }

            $schedule->is_active = !$schedule->is_active;
            $schedule->save();

            return response()->json([
                'success' => true,
                'message' => 'Status jadwal berhasil diubah',
                'data' => $schedule
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a single work schedule
     */
    public function show($id)
    {
        try {
            $schedule = WorkSchedule::find($id);

            if (!$schedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jadwal kerja tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $schedule
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
