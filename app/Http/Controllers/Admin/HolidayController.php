<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class HolidayController extends Controller
{
    /**
     * Display a listing of holidays
     */
    public function index(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month');

        $query = Holiday::orderBy('date', 'asc');

        if ($month) {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();
            $query->whereBetween('date', [$startDate, $endDate]);
        } else {
            $query->whereYear('date', $year);
        }

        $holidays = $query->get();

        return response()->json([
            'success' => true,
            'data' => $holidays->map(function ($holiday) {
                return [
                    'id' => $holiday->id,
                    'date' => $holiday->date->format('Y-m-d'),
                    'name' => $holiday->name,
                    'type' => $holiday->type,
                    'type_label' => $holiday->type_label,
                    'description' => $holiday->description,
                    'is_active' => $holiday->is_active,
                    'date_formatted' => $holiday->date->translatedFormat('d F Y'),
                    'day_name' => $holiday->date->translatedFormat('l'),
                ];
            })
        ]);
    }

    /**
     * Store a newly created holiday
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|unique:holidays,date',
            'name' => 'required|string|max:255',
            'type' => 'required|in:nasional,cuti_bersama,custom',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $holiday = Holiday::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Hari libur berhasil ditambahkan',
                'data' => $holiday
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan hari libur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified holiday
     */
    public function show($id)
    {
        $holiday = Holiday::find($id);

        if (!$holiday) {
            return response()->json([
                'success' => false,
                'message' => 'Hari libur tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $holiday
        ]);
    }

    /**
     * Update the specified holiday
     */
    public function update(Request $request, $id)
    {
        $holiday = Holiday::find($id);

        if (!$holiday) {
            return response()->json([
                'success' => false,
                'message' => 'Hari libur tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'date' => 'required|date|unique:holidays,date,' . $id,
            'name' => 'required|string|max:255',
            'type' => 'required|in:nasional,cuti_bersama,custom',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $holiday->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Hari libur berhasil diperbarui',
                'data' => $holiday
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui hari libur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified holiday
     */
    public function destroy($id)
    {
        $holiday = Holiday::find($id);

        if (!$holiday) {
            return response()->json([
                'success' => false,
                'message' => 'Hari libur tidak ditemukan'
            ], 404);
        }

        try {
            $holiday->delete();

            return response()->json([
                'success' => true,
                'message' => 'Hari libur berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus hari libur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle holiday active status
     */
    public function toggleActive($id)
    {
        $holiday = Holiday::find($id);

        if (!$holiday) {
            return response()->json([
                'success' => false,
                'message' => 'Hari libur tidak ditemukan'
            ], 404);
        }

        try {
            $holiday->is_active = !$holiday->is_active;
            $holiday->save();

            return response()->json([
                'success' => true,
                'message' => 'Status hari libur berhasil diubah',
                'data' => $holiday
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get calendar data for a specific month
     */
    public function calendar(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));

        $holidays = Holiday::getHolidaysByMonth($year, $month);

        return response()->json([
            'success' => true,
            'data' => $holidays->map(function ($holiday) {
                return [
                    'date' => $holiday->date->format('Y-m-d'),
                    'title' => $holiday->name,
                    'type' => $holiday->type,
                ];
            })
        ]);
    }

    /**
     * Import holidays from predefined list (e.g., Indonesian national holidays)
     */
    public function import(Request $request)
    {
        $year = $request->get('year', date('Y'));

        // Contoh data hari libur nasional Indonesia
        $nationalHolidays = $this->getIndonesianHolidays($year);

        $imported = 0;
        $skipped = 0;

        foreach ($nationalHolidays as $holiday) {
            $exists = Holiday::where('date', $holiday['date'])->exists();

            if (!$exists) {
                Holiday::create($holiday);
                $imported++;
            } else {
                $skipped++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Import selesai: {$imported} ditambahkan, {$skipped} dilewati",
            'data' => [
                'imported' => $imported,
                'skipped' => $skipped
            ]
        ]);
    }

    /**
     * Get Indonesian national holidays for a specific year
     * You can update this data annually
     */
    private function getIndonesianHolidays($year)
    {
        // Data hari libur nasional 2026 (contoh)
        // Update sesuai dengan kalender resmi pemerintah
        if ($year == 2026) {
            return [
                ['date' => '2026-01-01', 'name' => 'Tahun Baru Masehi', 'type' => 'nasional', 'is_active' => true],
                ['date' => '2026-02-17', 'name' => 'Tahun Baru Imlek 2577', 'type' => 'nasional', 'is_active' => true],
                ['date' => '2026-03-11', 'name' => 'Isra Mi\'raj Nabi Muhammad SAW', 'type' => 'nasional', 'is_active' => true],
                ['date' => '2026-03-22', 'name' => 'Hari Suci Nyepi (Tahun Baru Saka 1948)', 'type' => 'nasional', 'is_active' => true],
                ['date' => '2026-04-03', 'name' => 'Wafat Isa Almasih', 'type' => 'nasional', 'is_active' => true],
                ['date' => '2026-05-01', 'name' => 'Hari Buruh Internasional', 'type' => 'nasional', 'is_active' => true],
                ['date' => '2026-05-07', 'name' => 'Kenaikan Isa Almasih', 'type' => 'nasional', 'is_active' => true],
                ['date' => '2026-06-01', 'name' => 'Hari Lahir Pancasila', 'type' => 'nasional', 'is_active' => true],
                ['date' => '2026-08-17', 'name' => 'Hari Kemerdekaan RI', 'type' => 'nasional', 'is_active' => true],
                ['date' => '2026-12-25', 'name' => 'Hari Raya Natal', 'type' => 'nasional', 'is_active' => true],
            ];
        }

        // Add more years as needed
        return [];
    }
}

