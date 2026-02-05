<?php

use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\SubDepartmentController;
use App\Http\Controllers\Admin\KaryawanController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\PayrollController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('departments')->group(function () {
    Route::get('/', [DepartmentController::class, 'index']);
    Route::post('/', [DepartmentController::class, 'store']);
    Route::get('/{id}', [DepartmentController::class, 'show']);
    Route::put('/{id}', [DepartmentController::class, 'update']);
    Route::delete('/{id}', [DepartmentController::class, 'destroy']);
});

Route::prefix('sub-departments')->group(function () {
    Route::get('/', [SubDepartmentController::class, 'list']);
    Route::post('/', [SubDepartmentController::class, 'store']);
    Route::get('/by-department/{departmentId}', [SubDepartmentController::class, 'getByDepartment']);
    Route::get('/{id}', [SubDepartmentController::class, 'show']);
    Route::put('/{id}', [SubDepartmentController::class, 'update']);
    Route::delete('/{id}', [SubDepartmentController::class, 'destroy']);
});

Route::prefix('karyawan')->group(function () {
    Route::get('/master-data', [KaryawanController::class, 'getMasterData']);
    Route::get('/', [KaryawanController::class, 'index']);
    Route::post('/', [KaryawanController::class, 'store']);
    Route::get('/{id}', [KaryawanController::class, 'show']);
    Route::put('/{id}', [KaryawanController::class, 'update']);
    Route::delete('/{id}', [KaryawanController::class, 'destroy']);
});

Route::prefix('positions')->group(function () {
    Route::get('/', [PositionController::class, 'index']);
    Route::post('/', [PositionController::class, 'store']);
    Route::get('/{id}', [PositionController::class, 'show']);
    Route::put('/{id}', [PositionController::class, 'update']);
    Route::delete('/{id}', [PositionController::class, 'destroy']);
});

Route::prefix('attendance')->group(function () {
    Route::get('/', [AttendanceController::class, 'list']);
    Route::get('/today/{employeeId}', [AttendanceController::class, 'getTodayAttendance']);
    Route::get('/by-date/{employeeId}', [AttendanceController::class, 'getAttendanceByDate']);
    Route::post('/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/check-out', [AttendanceController::class, 'checkOut']);
    Route::post('/verify-face', [AttendanceController::class, 'verifyFace']);
    Route::get('/summary', [AttendanceController::class, 'summary']);
    Route::post('/manual', [AttendanceController::class, 'manualEntry']);
});

// Admin Attendance API
Route::middleware(['web', 'auth', 'admin'])->prefix('admin/attendance')->group(function () {
    Route::get('/{id}/detail', [AttendanceController::class, 'detail']);
    Route::put('/{id}', [AttendanceController::class, 'update']);
    Route::delete('/{id}', [AttendanceController::class, 'destroy']);
    Route::post('/bulk-delete', [AttendanceController::class, 'bulkDelete']);
});

// Employee Routes (for logged-in employees)
// Use web middleware to access session-based auth
Route::middleware('web')->prefix('employee')->group(function () {
    Route::get('/current', [\App\Http\Controllers\Employee\AttendanceController::class, 'getCurrentEmployee']);
    Route::get('/attendance/today', [\App\Http\Controllers\Employee\AttendanceController::class, 'getTodayAttendance']);
    Route::post('/attendance/check-in', [\App\Http\Controllers\Employee\AttendanceController::class, 'checkIn']);
    Route::post('/attendance/check-out', [\App\Http\Controllers\Employee\AttendanceController::class, 'checkOut']);
    Route::get('/attendance/history', [\App\Http\Controllers\Employee\AttendanceController::class, 'history']);
    Route::get('/attendance/summary', [\App\Http\Controllers\Employee\AttendanceController::class, 'summary']);
    Route::get('/attendance/{id}/detail', [\App\Http\Controllers\Employee\AttendanceController::class, 'detail']);

    // Employee Payroll API
    Route::get('/payroll', [\App\Http\Controllers\Employee\PayrollController::class, 'list']);
    Route::get('/payroll/statistics', [\App\Http\Controllers\Employee\PayrollController::class, 'statistics']);
    Route::get('/payroll/{id}', [\App\Http\Controllers\Employee\PayrollController::class, 'show']);
});

// Payroll API Routes (Manager only)
Route::middleware(['web', 'auth', 'manager'])->prefix('payroll')->group(function () {
    Route::get('/', [PayrollController::class, 'list']);
    Route::post('/', [PayrollController::class, 'store']);
    Route::get('/employees', [PayrollController::class, 'getEmployees']);
    Route::get('/attendance-summary', [PayrollController::class, 'getAttendanceSummary']);
    Route::get('/{id}', [PayrollController::class, 'show']);
    Route::put('/{id}', [PayrollController::class, 'update']);
    Route::delete('/{id}', [PayrollController::class, 'destroy']);
    Route::post('/{id}/send', [PayrollController::class, 'sendNotification']);
    Route::post('/{id}/upload-proof', [PayrollController::class, 'uploadProof']);
    Route::delete('/{id}/delete-proof', [PayrollController::class, 'deleteProof']);
});

// Office Settings API
Route::prefix('settings/office')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\OfficeSettingController::class, 'show']);
    Route::post('/', [\App\Http\Controllers\Admin\OfficeSettingController::class, 'update']);
});

// Work Schedule API
Route::prefix('settings/work-schedule')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\WorkScheduleController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Admin\WorkScheduleController::class, 'store']);
    Route::get('/{id}', [\App\Http\Controllers\Admin\WorkScheduleController::class, 'show']);
    Route::put('/{id}', [\App\Http\Controllers\Admin\WorkScheduleController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\Admin\WorkScheduleController::class, 'destroy']);
    Route::post('/{id}/toggle', [\App\Http\Controllers\Admin\WorkScheduleController::class, 'toggleStatus']);
});

// WhatsApp Settings API
Route::middleware(['web', 'auth', 'admin'])->prefix('settings/whatsapp')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\WhatsAppSettingController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Admin\WhatsAppSettingController::class, 'update']);
    Route::post('/test-connection', [\App\Http\Controllers\Admin\WhatsAppSettingController::class, 'testConnection']);
    Route::post('/send-test', [\App\Http\Controllers\Admin\WhatsAppSettingController::class, 'sendTest']);
    Route::post('/reset-templates', [\App\Http\Controllers\Admin\WhatsAppSettingController::class, 'resetTemplates']);
});

// Leave Management API (Admin)
Route::middleware(['web', 'auth', 'admin'])->prefix('leave')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\LeaveController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\Admin\LeaveController::class, 'show']);
    Route::post('/{id}/approve', [\App\Http\Controllers\Admin\LeaveController::class, 'approve']);
    Route::post('/{id}/reject', [\App\Http\Controllers\Admin\LeaveController::class, 'reject']);
    Route::delete('/{id}', [\App\Http\Controllers\Admin\LeaveController::class, 'destroy']);
});

// Employee Leave API
Route::middleware(['web', 'auth'])->prefix('employee/leave')->group(function () {
    Route::get('/', [\App\Http\Controllers\Employee\LeaveController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Employee\LeaveController::class, 'store']);
    Route::delete('/{id}', [\App\Http\Controllers\Employee\LeaveController::class, 'cancel']);
});

// Employee Profile API
Route::middleware(['web', 'auth'])->prefix('employee/profile')->group(function () {
    Route::put('/', [\App\Http\Controllers\Employee\ProfileController::class, 'update']);
    Route::post('/photo', [\App\Http\Controllers\Employee\ProfileController::class, 'updatePhoto']);
    Route::put('/password', [\App\Http\Controllers\Employee\ProfileController::class, 'updatePassword']);
});

// Admin Profile API
Route::middleware(['web', 'auth'])->prefix('admin/profile')->group(function () {
    Route::put('/', [\App\Http\Controllers\Admin\AdminProfileController::class, 'update']);
    Route::post('/photo', [\App\Http\Controllers\Admin\AdminProfileController::class, 'updatePhoto']);
    Route::put('/password', [\App\Http\Controllers\Admin\AdminProfileController::class, 'updatePassword']);
});

// Cron Job API
Route::middleware(['web', 'auth', 'admin'])->prefix('settings/cronjob')->group(function () {
    Route::get('/list', [\App\Http\Controllers\Admin\CronJobController::class, 'getScheduleList']);
    Route::get('/status', [\App\Http\Controllers\Admin\CronJobController::class, 'checkStatus']);
    Route::get('/command', [\App\Http\Controllers\Admin\CronJobController::class, 'getCronCommand']);
    Route::post('/test', [\App\Http\Controllers\Admin\CronJobController::class, 'testCommand']);
    Route::post('/run', [\App\Http\Controllers\Admin\CronJobController::class, 'runScheduler']);
});

// Holiday Management API
Route::middleware(['web', 'auth', 'admin'])->prefix('settings/holidays')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\HolidayController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Admin\HolidayController::class, 'store']);
    Route::get('/calendar', [\App\Http\Controllers\Admin\HolidayController::class, 'calendar']);
    Route::post('/import', [\App\Http\Controllers\Admin\HolidayController::class, 'import']);
    Route::get('/{id}', [\App\Http\Controllers\Admin\HolidayController::class, 'show']);
    Route::put('/{id}', [\App\Http\Controllers\Admin\HolidayController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\Admin\HolidayController::class, 'destroy']);
    Route::post('/{id}/toggle', [\App\Http\Controllers\Admin\HolidayController::class, 'toggleActive']);
});

// Import/Export API
Route::prefix('karyawan')->group(function () {
    Route::post('/import', [\App\Http\Controllers\Admin\KaryawanController::class, 'import']);
});

// Fingerspot Webhook API (No Auth - uses token)
Route::prefix('fingerspot')->group(function () {
    Route::post('/webhook', [\App\Http\Controllers\Api\FingerspotWebhookController::class, 'handleWebhook']);
    Route::get('/test', [\App\Http\Controllers\Api\FingerspotWebhookController::class, 'test']);
    Route::get('/debug-logs', [\App\Http\Controllers\Api\FingerspotWebhookController::class, 'debugLogs']);
    Route::post('/test-photo-url', [\App\Http\Controllers\Api\FingerspotWebhookController::class, 'testPhotoUrl']);
});

// Fingerspot Admin API (Requires Auth)
Route::middleware(['web', 'auth', 'admin'])->prefix('settings/fingerspot')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\FingerspotSettingController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Admin\FingerspotSettingController::class, 'store']);
    Route::put('/{id}', [\App\Http\Controllers\Admin\FingerspotSettingController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\Admin\FingerspotSettingController::class, 'destroy']);
    Route::post('/{id}/regenerate-token', [\App\Http\Controllers\Admin\FingerspotSettingController::class, 'regenerateToken']);
    Route::get('/logs', [\App\Http\Controllers\Api\FingerspotWebhookController::class, 'logs']);
    Route::post('/logs/reprocess', [\App\Http\Controllers\Api\FingerspotWebhookController::class, 'reprocess']);
    Route::post('/logs/reprocess-all', [\App\Http\Controllers\Api\FingerspotWebhookController::class, 'reprocessAll']);
    Route::post('/fetch', [\App\Http\Controllers\Api\FingerspotWebhookController::class, 'fetchFromApi']);
});
