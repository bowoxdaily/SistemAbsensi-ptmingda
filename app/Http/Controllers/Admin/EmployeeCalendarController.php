<?php

namespace App\Http\Controllers\Admin;

use App\Exports\BirthdayEmployeeExport;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeCalendarController extends Controller
{
    public function index()
    {
        return view('admin.calendar.index');
    }

    public function events(Request $request)
    {
        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);

        if ($year < 2000 || $year > 2100) {
            $year = now()->year;
        }

        if ($month < 1 || $month > 12) {
            $month = now()->month;
        }

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();

        $daysInSelectedMonth = $startDate->daysInMonth;

        $birthdayEvents = Employee::query()
            ->with(['subDepartment:id,name'])
            ->where('status', 'active')
            ->whereNotNull('birth_date')
            ->whereMonth('birth_date', $month)
            ->orderByRaw('DAY(birth_date) ASC')
            ->get()
            ->map(function (Employee $employee) use ($year, $month, $daysInSelectedMonth) {
                $originalBirthDate = Carbon::parse($employee->birth_date);
                $day = min((int) $originalBirthDate->day, $daysInSelectedMonth);
                $eventDate = Carbon::create($year, $month, $day)->format('Y-m-d');

                return [
                    'date' => $eventDate,
                    'title' => 'Ulang Tahun: ' . $employee->name,
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                    'sub_department' => optional($employee->subDepartment)->name,
                    'age' => max(0, $year - (int) $originalBirthDate->year),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Data kalender ulang tahun berhasil dimuat',
            'data' => [
                'year' => $year,
                'month' => $month,
                'birthday_events' => $birthdayEvents,
            ],
        ]);
    }

    public function exportBirthday(Request $request)
    {
        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);

        if ($year < 2000 || $year > 2100) {
            $year = now()->year;
        }

        if ($month < 1 || $month > 12) {
            $month = now()->month;
        }

        $filename = sprintf(
            'Karyawan_Ulang_Tahun_%04d-%02d_%s.xlsx',
            $year,
            $month,
            now()->format('His')
        );

        return Excel::download(new BirthdayEmployeeExport($year, $month), $filename);
    }
}
