<?php

use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\SubDepartmentController;
use App\Http\Controllers\Admin\KaryawanController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\OfficeSettingController;
use App\Http\Controllers\Admin\WorkScheduleController;
use App\Http\Controllers\Admin\CronJobController;
use App\Http\Controllers\Admin\PayrollController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes (with rate limiting to prevent brute force)
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1'); // Max 5 attempts per minute
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    // Dashboard (accessible by all authenticated users)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Admin Only Routes (protected by admin middleware)
    Route::middleware(['admin'])->group(function () {
        // Master Data
        Route::get('/admin/department', [DepartmentController::class, 'dashboard'])->name('admin.department.index');
        Route::get('/admin/sub-departments', [SubDepartmentController::class, 'index'])->name('admin.sub-departments.index');
        Route::get('/admin/karyawan', [KaryawanController::class, 'dashboard'])->name('admin.karyawan.index');
        Route::get('/admin/karyawan/export', [KaryawanController::class, 'export'])->name('admin.karyawan.export');
        Route::post('/admin/karyawan/import', [KaryawanController::class, 'import'])->name('admin.karyawan.import');
        Route::get('/admin/karyawan/template', [KaryawanController::class, 'downloadTemplate'])->name('admin.karyawan.template');
        Route::get('/admin/positions', [PositionController::class, 'dashboard'])->name('admin.positions.index');

        // Attendance Routes
        Route::get('/admin/attendance', [AttendanceController::class, 'index'])->name('admin.attendance.index');
        Route::get('/admin/attendance/face-detection', [AttendanceController::class, 'faceDetection'])->name('admin.attendance.face-detection');
        Route::get('/admin/attendance/manual', [AttendanceController::class, 'faceDetection'])->name('admin.attendance.manual');
        Route::get('/admin/attendance/report', [AttendanceController::class, 'report'])->name('admin.attendance.report');
        Route::get('/admin/attendance/export', [AttendanceController::class, 'export'])->name('admin.attendance.export');
        Route::delete('/admin/attendance/{id}', [AttendanceController::class, 'destroy'])->name('admin.attendance.destroy');

        // Office Settings (View only - API handles POST/PUT/DELETE)
        Route::get('/admin/settings/office', [OfficeSettingController::class, 'index'])->name('admin.settings.office');

        // Work Schedule Settings (View only - API handles POST/PUT/DELETE)
        Route::get('/admin/settings/work-schedule', [WorkScheduleController::class, 'index'])->name('admin.settings.work-schedule');

        // Cron Job Settings (View only - API handles POST)
        Route::get('/admin/settings/cronjob', [CronJobController::class, 'index'])->name('admin.settings.cronjob');

        // WhatsApp Settings (View only - API handles POST)
        Route::get('/admin/settings/whatsapp', [\App\Http\Controllers\Admin\WhatsAppSettingController::class, 'index'])->name('admin.settings.whatsapp');

        // Leave Management (View only - API handles POST/DELETE)
        Route::get('/admin/leave', [\App\Http\Controllers\Admin\LeaveController::class, 'index'])->name('admin.leave.index');

        // Payroll Management
        Route::get('/admin/payroll', [PayrollController::class, 'index'])->name('admin.payroll.index');
        Route::get('/admin/payroll/export', [PayrollController::class, 'export'])->name('admin.payroll.export');
    });

    // Employee Routes (View only - API handles POST/PUT/DELETE)
    Route::get('/employee/attendance', [\App\Http\Controllers\Employee\AttendanceController::class, 'index'])->name('employee.attendance.index');
    Route::get('/employee/attendance/history', [\App\Http\Controllers\Employee\AttendanceController::class, 'historyPage'])->name('employee.attendance.history');

    // Employee Leave (View only - API handles POST/DELETE)
    Route::get('/employee/leave', [\App\Http\Controllers\Employee\LeaveController::class, 'index'])->name('employee.leave.index');

    // Employee Payroll (View only)
    Route::get('/employee/payroll', [\App\Http\Controllers\Employee\PayrollController::class, 'index'])->name('employee.payroll.index');

    // Employee Profile (View only - API handles PUT)
    Route::get('/employee/profile', [\App\Http\Controllers\Employee\ProfileController::class, 'index'])->name('employee.profile.index');

    // Admin Profile (View only - API handles PUT)
    Route::middleware(['admin'])->group(function () {
        Route::get('/admin/profile', [\App\Http\Controllers\Admin\AdminProfileController::class, 'index'])->name('admin.profile.index');
    });



    // Master Data Routes (Commented - Controllers not yet created)
    // Route::prefix('master')->group(function () {
    //     Route::resource('karyawan', \App\Http\Controllers\KaryawanController::class);
    //     Route::resource('departemen', \App\Http\Controllers\DepartemenController::class);
    //     Route::resource('jabatan', \App\Http\Controllers\JabatanController::class);
    // });

    // Absensi Routes (Commented - Controllers not yet created)
    // Route::prefix('absensi')->group(function () {
    //     Route::get('/', [\App\Http\Controllers\AbsensiController::class, 'index'])->name('absensi.index');
    //     Route::post('/store', [\App\Http\Controllers\AbsensiController::class, 'store'])->name('absensi.store');
    //     Route::get('/rekap', [\App\Http\Controllers\RekapController::class, 'index'])->name('rekap.index');
    // });

    // Settings Routes (Commented - Controllers not yet created)
    // Route::prefix('settings')->group(function () {
    //     Route::get('/jam-kerja', [\App\Http\Controllers\JamKerjaController::class, 'index'])->name('jam-kerja.index');
    //     Route::resource('users', \App\Http\Controllers\UserController::class);
    //     Route::get('/', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
    // });

    // Profile Routes (Commented - Controllers not yet created)
    // Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    // Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
});
