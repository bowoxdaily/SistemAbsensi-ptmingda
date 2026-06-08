<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Opl;
use App\Models\OplRead;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class OplController extends Controller
{
    // Halaman list OPL untuk karyawan (web)
    public function index()
    {
        return view('employee.opls.index');
    }

    // API: daftar OPL untuk aplikasi / web
    public function list(Request $request)
    {
        // Return simple list for web/mobile consumption
        $perPage = (int) $request->get('per_page', 50);
        $query = Opl::where('is_active', true)->orderBy('created_at', 'desc');
        if ($perPage > 0) {
            $data = $query->limit($perPage)->get();
        } else {
            $data = $query->get();
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    // API: popups untuk dashboard (OPL yang berflag show_popup)
    public function getPopups()
    {
        // Get current employee
        $employee = \App\Models\Karyawans::where('user_id', Auth::id())->first();
        if (!$employee) return response()->json(['success' => true, 'data' => []]);

        // Show only the latest OPL (by created_at) and only if the employee hasn't read it today
        $latest = Opl::where('is_active', true)->where('show_popup', true)->orderBy('created_at', 'desc')->first();
        if (!$latest) return response()->json(['success' => true, 'data' => []]);

        $today = Carbon::now()->startOfDay();
        $read = OplRead::where('opl_id', $latest->id)
            ->where('employee_id', $employee->id)
            ->whereDate('read_at', '>=', $today)
            ->exists();

        if ($read) {
            return response()->json(['success' => true, 'data' => []]);
        }

        return response()->json(['success' => true, 'data' => [$latest]]);
    }

    public function markRead($id)
    {
        $employee = \App\Models\Karyawans::where('user_id', Auth::id())->first();
        if (!$employee) return response()->json(['success' => false, 'message' => 'Employee not found'], 404);

        $opl = Opl::find($id);
        if (!$opl) return response()->json(['success' => false, 'message' => 'OPL not found'], 404);

        // Overwrite (upsert) read record with current timestamp to avoid log accumulation
        OplRead::updateOrCreate(
            ['opl_id' => $opl->id, 'employee_id' => $employee->id],
            ['read_at' => Carbon::now()]
        );

        return response()->json(['success' => true]);
    }
}
