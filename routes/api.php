<?php

use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\AttendanceEditRequestController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\SubDepartmentController;
use App\Http\Controllers\Admin\KaryawanController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\InterviewController;
use App\Http\Controllers\Admin\JoinCallController;
use App\Http\Controllers\Admin\BroadcastController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\EmployeeCalendarController;
use App\Http\Controllers\Admin\WarningLetterController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExternalAttendanceController;
use App\Http\Controllers\Api\ExternalKaryawanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ─── External API Authentication ─────────────────────────────────────────────
// Use these endpoints to get/revoke a Bearer token for external app access.
// POST /api/auth/login        → { email, password } → returns { token }
// POST /api/auth/google       → { google_token } → returns { token }
// POST /api/auth/logout       → revoke current token
// POST /api/auth/logout-all   → revoke all tokens of current user
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/google', [AuthController::class, 'googleLogin'])->middleware('throttle:10,1');
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    });
});

// ─── External API v1 (Bearer Token) ──────────────────────────────────────────
// Accessible by external applications via Bearer token.
// Roles allowed: admin, manager, viewer (karyawan role is blocked).
// Sensitive PII/financial fields are hidden from all responses.
//
// GET /api/v1/karyawan           → list with pagination
//   Params: per_page, search, department_id, position_id, status, page
// GET /api/v1/karyawan/all       → list all (no pagination)
//   Params: search, department_id, position_id, status
// GET /api/v1/karyawan/{id}      → single record
// GET /api/v1/attendance         → list attendance with pagination
//   Params: per_page, employee_id, employee_code, status, date_from, date_to, page
// GET /api/v1/attendance/summary → summary by status
//   Params: date_from, date_to, employee_id
// GET /api/v1/attendance/employee/{employeeId} → attendance by employee
//   Params: per_page, date_from, date_to, status, page
// GET /api/v1/attendance/{id}    → single attendance record
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('/karyawan',      [ExternalKaryawanController::class, 'index']);
    Route::get('/karyawan/all',  [ExternalKaryawanController::class, 'all']);
    Route::get('/karyawan/{id}', [ExternalKaryawanController::class, 'show']);

    Route::get('/attendance', [ExternalAttendanceController::class, 'index']);
    Route::get('/attendance/summary', [ExternalAttendanceController::class, 'summary']);
    Route::get('/attendance/employee/{employeeId}', [ExternalAttendanceController::class, 'byEmployee']);
    Route::get('/attendance/{id}', [ExternalAttendanceController::class, 'show']);
});

// ─── Public API v1 (No Authentication) ──────────────────────────────────────
// Publicly accessible endpoints without bearer token.
// GET /api/v1/photo/{filename}   → get profile photo (public)
Route::prefix('v1')->group(function () {
    Route::get('/photo/{filename}', [ExternalKaryawanController::class, 'getProfilePhoto']);
});

// ─── Mobile App API v1 (Karyawan / Employee App) ─────────────────────────────
// Accessible by Mobile Application via Bearer token (from /api/auth/login).
Route::middleware('auth:sanctum')->prefix('mobile/v1')->group(function () {
    // Profil Karyawan
    Route::get('/profile', [\App\Http\Controllers\Employee\AttendanceController::class, 'getCurrentEmployee']);
    Route::post('/profile/photo', [\App\Http\Controllers\Employee\ProfileController::class, 'updatePhoto']);
    Route::put('/profile/password', [\App\Http\Controllers\Employee\ProfileController::class, 'updatePassword']);
    
    // Absensi
    Route::get('/attendance/today', [\App\Http\Controllers\Employee\AttendanceController::class, 'getTodayAttendance']);
    Route::get('/attendance/history', [\App\Http\Controllers\Employee\AttendanceController::class, 'history']);
    Route::get('/attendance/summary', [\App\Http\Controllers\Employee\AttendanceController::class, 'summary']);
    Route::get('/attendance/{id}/detail', [\App\Http\Controllers\Employee\AttendanceController::class, 'detail']);

    // Payslip / Slip Gaji
    Route::get('/payslip', [\App\Http\Controllers\Employee\PayrollController::class, 'list']);
    Route::get('/payslip/download', [\App\Http\Controllers\Employee\PayrollController::class, 'downloadPdf']);

    // Pengumuman / Announcements
    Route::get('/announcements', [\App\Http\Controllers\Employee\AnnouncementController::class, 'index']);
    Route::get('/announcements/unread-count', [\App\Http\Controllers\Employee\AnnouncementController::class, 'unreadCount']);
    Route::get('/announcements/popups', [\App\Http\Controllers\Employee\AnnouncementController::class, 'getPopups']);
    Route::post('/announcements/{id}/mark-read', [\App\Http\Controllers\Employee\AnnouncementController::class, 'markRead']);
    Route::post('/announcements/mark-all-read', [\App\Http\Controllers\Employee\AnnouncementController::class, 'markAllRead']);

    // Cuti / Leave
    Route::get('/leave', [\App\Http\Controllers\Employee\LeaveController::class, 'index']);
    Route::post('/leave', [\App\Http\Controllers\Employee\LeaveController::class, 'store']);
    Route::delete('/leave/{id}', [\App\Http\Controllers\Employee\LeaveController::class, 'cancel']);

    // Surat Peringatan / Warning Letters
    Route::get('/warning-letters', [\App\Http\Controllers\Employee\WarningLetterController::class, 'list']);
    Route::get('/warning-letters/statistics', [\App\Http\Controllers\Employee\WarningLetterController::class, 'statistics']);
    Route::get('/warning-letters/{id}', [\App\Http\Controllers\Employee\WarningLetterController::class, 'show']);
    Route::get('/warning-letters/{id}/download', [\App\Http\Controllers\Employee\WarningLetterController::class, 'downloadDocument']);
});

Route::middleware(['web', 'auth', 'admin'])->prefix('departments')->group(function () {
    Route::get('/', [DepartmentController::class, 'index']);
    Route::post('/', [DepartmentController::class, 'store']);
    Route::get('/{id}', [DepartmentController::class, 'show']);
    Route::put('/{id}', [DepartmentController::class, 'update']);
    Route::delete('/{id}', [DepartmentController::class, 'destroy']);
});

Route::middleware(['web', 'auth', 'admin'])->prefix('sub-departments')->group(function () {
    Route::get('/', [SubDepartmentController::class, 'list']);
    Route::post('/', [SubDepartmentController::class, 'store']);
    Route::get('/by-department/{departmentId}', [SubDepartmentController::class, 'getByDepartment']);
    Route::get('/{id}', [SubDepartmentController::class, 'show']);
    Route::put('/{id}', [SubDepartmentController::class, 'update']);
    Route::delete('/{id}', [SubDepartmentController::class, 'destroy']);
});

// Karyawan - Read endpoints (accessible by all authenticated roles)
Route::middleware(['web', 'auth'])->prefix('karyawan')->group(function () {
    Route::get('/master-data', [KaryawanController::class, 'getMasterData']);
    Route::get('/status-report', [KaryawanController::class, 'statusReport']);
    Route::get('/', [KaryawanController::class, 'index']);
    Route::get('/{id}', [KaryawanController::class, 'show']);
});

// Karyawan - Write endpoints (admin/manager only, viewer blocked)
Route::middleware(['web', 'auth', 'admin'])->prefix('karyawan')->group(function () {
    Route::post('/', [KaryawanController::class, 'store']);
    Route::put('/{id}', [KaryawanController::class, 'update']);
    Route::delete('/{id}', [KaryawanController::class, 'destroy']);
    Route::post('/parse-address', [KaryawanController::class, 'parseAddress']);
});

// Karyawan Career History - admin/manager only
Route::middleware(['web', 'auth', 'admin'])->prefix('karyawan')->group(function () {
    Route::put('/{id}/career-history/{careerId}', [KaryawanController::class, 'updateCareerHistory']);
    Route::delete('/{id}/career-history/{careerId}', [KaryawanController::class, 'destroyCareerHistory']);
});

Route::middleware(['web', 'auth', 'admin'])->prefix('positions')->group(function () {
    Route::get('/', [PositionController::class, 'index']);
    Route::post('/', [PositionController::class, 'store']);
    Route::get('/{id}', [PositionController::class, 'show']);
    Route::put('/{id}', [PositionController::class, 'update']);
    Route::delete('/{id}', [PositionController::class, 'destroy']);
});

Route::middleware(['web', 'auth'])->prefix('attendance')->group(function () {
    Route::get('/', [AttendanceController::class, 'list']);
    Route::get('/today/{employeeId}', [AttendanceController::class, 'getTodayAttendance']);
    Route::get('/by-date/{employeeId}', [AttendanceController::class, 'getAttendanceByDate']);
    Route::get('/check-existing', [AttendanceController::class, 'checkExistingByDate']);
    Route::post('/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/check-out', [AttendanceController::class, 'checkOut']);
    Route::post('/verify-face', [AttendanceController::class, 'verifyFace']);
    Route::get('/summary', [AttendanceController::class, 'summary']);
    Route::post('/manual', [AttendanceController::class, 'manualEntry']);
});

// Admin Attendance API - Read (viewer accessible)
Route::middleware(['web', 'auth', 'viewer'])->prefix('admin/attendance')->group(function () {
    Route::get('/{id}/detail', [AttendanceController::class, 'detail']);
});

// Admin Attendance API - Write (admin/manager only)
Route::middleware(['web', 'auth', 'admin'])->prefix('admin/attendance')->group(function () {
    Route::post('/bulk-checkout', [AttendanceController::class, 'bulkCheckOut']);
    Route::put('/{id}', [AttendanceController::class, 'update']);
    Route::delete('/{id}', [AttendanceController::class, 'destroy']);
    Route::post('/bulk-delete', [AttendanceController::class, 'bulkDelete']);
});

// OPL Admin API
Route::middleware(['web', 'auth', 'admin'])->prefix('admin/opls')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\OPLController::class, 'list']);
    Route::post('/', [\App\Http\Controllers\Admin\OPLController::class, 'store']);
    Route::get('/{id}', [\App\Http\Controllers\Admin\OPLController::class, 'show']);
    Route::put('/{id}', [\App\Http\Controllers\Admin\OPLController::class, 'update']);
    Route::post('/{id}/toggle-active', [\App\Http\Controllers\Admin\OPLController::class, 'toggleActive']);
    Route::delete('/{id}', [\App\Http\Controllers\Admin\OPLController::class, 'destroy']);
});

// Employee OPL API (web)
Route::middleware(['web', 'auth'])->prefix('employee/opls')->group(function () {
    Route::get('/', [\App\Http\Controllers\Employee\OplController::class, 'list']);
    Route::get('/popups', [\App\Http\Controllers\Employee\OplController::class, 'getPopups']);
});

// Mobile endpoints for OPL popups
Route::middleware('auth:sanctum')->prefix('mobile/v1')->group(function () {
    Route::get('/opls', [\App\Http\Controllers\Employee\OplController::class, 'list']);
    Route::get('/opls/popups', [\App\Http\Controllers\Employee\OplController::class, 'getPopups']);
});

// Attendance Edit Requests – submit & read (admin & manager)
Route::middleware(['web', 'auth', 'admin'])->prefix('admin/attendance-edit-requests')->group(function () {
    Route::get('/', [AttendanceEditRequestController::class, 'list']);
    Route::get('/pending-count', [AttendanceEditRequestController::class, 'pendingCount']);
    Route::get('/stats', [AttendanceEditRequestController::class, 'stats']);
    Route::get('/{id}/detail', [AttendanceEditRequestController::class, 'detail']);
    Route::post('/', [AttendanceEditRequestController::class, 'store']);
});

// Attendance Edit Requests – approve/reject (manager only)
Route::middleware(['web', 'auth', 'manager'])->prefix('admin/attendance-edit-requests')->group(function () {
    Route::put('/{id}/approve', [AttendanceEditRequestController::class, 'approve']);
    Route::put('/{id}/reject', [AttendanceEditRequestController::class, 'reject']);
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

    // Employee Payslip API (from external HRIS)
    Route::get('/payslip', [\App\Http\Controllers\Employee\PayrollController::class, 'list']);
    Route::get('/payslip/download', [\App\Http\Controllers\Employee\PayrollController::class, 'downloadPdf']);
});

// Employee Attendance Edit Requests – view own requests (read-only)
Route::middleware(['web', 'auth'])->prefix('employee/attendance-edit-requests')->group(function () {
    Route::get('/list', [\App\Http\Controllers\Employee\AttendanceEditRequestController::class, 'list']);
    Route::get('/stats', [\App\Http\Controllers\Employee\AttendanceEditRequestController::class, 'stats']);
    Route::get('/pending-count', [\App\Http\Controllers\Employee\AttendanceEditRequestController::class, 'pendingCount']);
    Route::get('/detail/{id}', [\App\Http\Controllers\Employee\AttendanceEditRequestController::class, 'detail']);
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

    // HRIS Payslip Integration (Admin)
    Route::get('/hris/payslip', [PayrollController::class, 'hrisPayslipList']);
    Route::get('/hris/payslip/download', [PayrollController::class, 'hrisPayslipDownload']);
    Route::post('/hris/test-connection', [PayrollController::class, 'hrisTestConnection']);
    Route::get('/hris/status', [PayrollController::class, 'hrisStatus']);
});

// Account Management API (Superadmin only)
Route::middleware(['web', 'auth'])->prefix('admin/account-management')->group(function () {
    Route::get('/list', [\App\Http\Controllers\Admin\AccountManagementController::class, 'list']);
    Route::get('/options', [\App\Http\Controllers\Admin\AccountManagementController::class, 'getOptions']);
    Route::get('/stats', [\App\Http\Controllers\Admin\AccountManagementController::class, 'stats']);
    Route::get('/detail/{id}', [\App\Http\Controllers\Admin\AccountManagementController::class, 'detail']);
    Route::post('/store', [\App\Http\Controllers\Admin\AccountManagementController::class, 'store']);
    Route::put('/update/{id}', [\App\Http\Controllers\Admin\AccountManagementController::class, 'update']);
    Route::delete('/destroy/{id}', [\App\Http\Controllers\Admin\AccountManagementController::class, 'destroy']);
    Route::put('/change-password/{id}', [\App\Http\Controllers\Admin\AccountManagementController::class, 'changePassword']);
});

// Office Settings API
Route::middleware(['web', 'auth', 'admin'])->prefix('settings/office')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\OfficeSettingController::class, 'show']);
    Route::post('/', [\App\Http\Controllers\Admin\OfficeSettingController::class, 'update']);
});

// Work Schedule API
Route::middleware(['web', 'auth', 'admin'])->prefix('settings/work-schedule')->group(function () {
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
    Route::get('/kirim/templates', [\App\Http\Controllers\Admin\WhatsAppSettingController::class, 'listKirimTemplates']);
    Route::post('/kirim/templates', [\App\Http\Controllers\Admin\WhatsAppSettingController::class, 'createKirimTemplate']);
    Route::get('/kirim/templates/{name}', [\App\Http\Controllers\Admin\WhatsAppSettingController::class, 'getKirimTemplateByName']);
    Route::post('/kirim/templates/sync', [\App\Http\Controllers\Admin\WhatsAppSettingController::class, 'syncKirimTemplates']);
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

// Warning Letters Management API (admin, manager, viewer - full access)
Route::middleware(['web', 'auth', 'viewer'])->prefix('admin/warning-letters')->group(function () {
    Route::get('/', [WarningLetterController::class, 'list']);
    Route::get('/statistics', [WarningLetterController::class, 'statistics']);
    Route::post('/generate-number', [WarningLetterController::class, 'generateNumber']);
    Route::post('/check-employee-sp', [WarningLetterController::class, 'checkEmployeeSP']);
    Route::post('/', [WarningLetterController::class, 'store']);
    Route::get('/{id}', [WarningLetterController::class, 'show']);
    Route::put('/{id}', [WarningLetterController::class, 'update']);
    Route::delete('/{id}', [WarningLetterController::class, 'destroy']);
    Route::post('/{id}/cancel', [WarningLetterController::class, 'cancel']);
    Route::post('/{id}/upload-document', [WarningLetterController::class, 'uploadDocument']);
    Route::post('/{id}/send-notification', [WarningLetterController::class, 'sendNotification']);
    Route::post('/bulk-send-notification', [WarningLetterController::class, 'bulkSendNotification']);
    Route::get('/{id}/download', [WarningLetterController::class, 'downloadDocument']);
});

// Employee Warning Letters API (Read-only)
Route::middleware(['web', 'auth'])->prefix('employee/warning-letters')->group(function () {
    Route::get('/', [\App\Http\Controllers\Employee\WarningLetterController::class, 'list']);
    Route::get('/statistics', [\App\Http\Controllers\Employee\WarningLetterController::class, 'statistics']);
    Route::get('/{id}', [\App\Http\Controllers\Employee\WarningLetterController::class, 'show']);
    Route::get('/{id}/download', [\App\Http\Controllers\Employee\WarningLetterController::class, 'downloadDocument']);
});

// Interview Management API (Admin)
Route::middleware(['web', 'auth', 'admin'])->prefix('admin/interviews')->group(function () {
    Route::post('/', [InterviewController::class, 'store']);
    Route::get('/{id}', [InterviewController::class, 'show']);
    Route::put('/{id}', [InterviewController::class, 'update']);
    Route::delete('/{id}', [InterviewController::class, 'destroy']);
    Route::post('/{id}/send-notification', [InterviewController::class, 'sendNotification']);
    Route::post('/bulk-send-notification', [InterviewController::class, 'bulkSendNotification']);
    Route::post('/bulk-delete', [InterviewController::class, 'bulkDelete']);
    
    // Message Templates
    Route::get('/templates/list', [InterviewController::class, 'getTemplates']);
    Route::post('/templates/save', [InterviewController::class, 'saveTemplate']);
    Route::put('/templates/{id}', [InterviewController::class, 'updateTemplate']);
    Route::delete('/templates/{id}', [InterviewController::class, 'deleteTemplate']);
    
    // Import/Export
    Route::get('/template/download', [InterviewController::class, 'downloadTemplate']);
    Route::post('/import', [InterviewController::class, 'import']);
});

// Join Call Management API (Admin)
Route::middleware(['web', 'auth', 'admin'])->prefix('admin/join-calls')->group(function () {
    // Specific routes MUST come before wildcard /{id} routes
    Route::post('/bulk-send-notification', [JoinCallController::class, 'bulkSendNotification']);
    Route::post('/bulk-delete', [JoinCallController::class, 'bulkDelete']);

    // Message Templates
    Route::get('/templates/list', [JoinCallController::class, 'getTemplates']);
    Route::post('/templates/save', [JoinCallController::class, 'saveTemplate']);
    Route::put('/templates/{id}', [JoinCallController::class, 'updateTemplate']);
    Route::delete('/templates/{id}', [JoinCallController::class, 'deleteTemplate']);

    // Import/Export
    Route::get('/template/download', [JoinCallController::class, 'downloadTemplate']);
    Route::post('/import', [JoinCallController::class, 'import']);

    // CRUD (wildcard routes last)
    Route::post('/', [JoinCallController::class, 'store']);
    Route::get('/{id}', [JoinCallController::class, 'show']);
    Route::put('/{id}', [JoinCallController::class, 'update']);
    Route::delete('/{id}', [JoinCallController::class, 'destroy']);
    Route::post('/{id}/send-notification', [JoinCallController::class, 'sendNotification']);
});

// Security Scanner API Routes
Route::middleware(['web', 'auth', 'security'])->prefix('security')->group(function () {
    Route::post('/validate-token', [\App\Http\Controllers\Security\SecurityScannerController::class, 'validateToken']);
    Route::post('/checkin', [\App\Http\Controllers\Security\SecurityScannerController::class, 'checkIn']);
    Route::get('/history', [\App\Http\Controllers\Security\SecurityScannerController::class, 'history']);
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



// Guest Monitoring API Routes (public - no login required)
Route::prefix('guest')->group(function () {
    Route::get('/stats', [\App\Http\Controllers\Guest\GuestMonitoringController::class, 'stats']);
    Route::get('/karyawan', [\App\Http\Controllers\Guest\GuestMonitoringController::class, 'karyawanList']);
    Route::get('/absensi', [\App\Http\Controllers\Guest\GuestMonitoringController::class, 'absensiList']);
    Route::get('/interview', [\App\Http\Controllers\Guest\GuestMonitoringController::class, 'interviewList']);
    Route::get('/master-data', [\App\Http\Controllers\Guest\GuestMonitoringController::class, 'masterData']);
});

// Import/Export API
Route::middleware(['web', 'auth', 'admin'])->prefix('karyawan')->group(function () {
    Route::post('/import', [\App\Http\Controllers\Admin\KaryawanController::class, 'import']);
});

// Fingerspot Webhook API (No Auth - uses device token via X-Fingerspot-Token header)
Route::prefix('fingerspot')->group(function () {
    Route::post('/webhook', [\App\Http\Controllers\Api\FingerspotWebhookController::class, 'handleWebhook']);
    Route::post('/test-photo-url', [\App\Http\Controllers\Api\FingerspotWebhookController::class, 'testPhotoUrl']);
});

// Fingerspot debug/test endpoints (admin only)
Route::middleware(['web', 'auth', 'admin'])->prefix('fingerspot')->group(function () {
    Route::get('/test', [\App\Http\Controllers\Api\FingerspotWebhookController::class, 'test']);
    Route::get('/debug-logs', [\App\Http\Controllers\Api\FingerspotWebhookController::class, 'debugLogs']);
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

// Broadcast Messages API (Admin)
Route::middleware(['web', 'auth', 'admin'])->prefix('admin/broadcast')->group(function () {
    Route::get('/', [BroadcastController::class, 'list']);
    Route::get('/positions', [BroadcastController::class, 'getPositions']);
    Route::get('/departments', [BroadcastController::class, 'getDepartments']);
    Route::get('/employees', [BroadcastController::class, 'getEmployees']);
    Route::post('/preview', [BroadcastController::class, 'preview']);
    Route::post('/send', [BroadcastController::class, 'send']);
    Route::get('/{id}', [BroadcastController::class, 'detail']);
    Route::delete('/{id}', [BroadcastController::class, 'destroy']);
});

// Rekapitulasi Absensi API (Viewer accessible - read only)
Route::middleware(['web', 'auth', 'viewer'])->prefix('admin/rekapitulasi')->group(function () {
    Route::get('/data', [\App\Http\Controllers\Admin\RekapitulasiController::class, 'getData']);
    Route::get('/filter-options', [\App\Http\Controllers\Admin\RekapitulasiController::class, 'getFilterOptions']);
    Route::get('/geographic-data', [\App\Http\Controllers\Admin\RekapitulasiController::class, 'getGeographicData']);
    Route::get('/geographic-chart-data', [\App\Http\Controllers\Admin\RekapitulasiController::class, 'getGeographicChartData']);
    Route::get('/geographic-location-detail', [\App\Http\Controllers\Admin\RekapitulasiController::class, 'getGeographicLocationDetail']);
    Route::get('/geographic-export-excel', [\App\Http\Controllers\Admin\RekapitulasiController::class, 'exportGeographicExcel']);
});

// Calendar Agenda & Birthday API (Viewer accessible - read only)
Route::middleware(['web', 'auth', 'viewer'])->prefix('admin/calendar')->group(function () {
    Route::get('/events', [EmployeeCalendarController::class, 'events']);
});

// ─── Pengumuman In-App (Admin) ────────────────────────────────────────────────
Route::middleware(['web', 'auth', 'admin'])->prefix('admin/announcements')->group(function () {
    Route::get('/', [AnnouncementController::class, 'list']);
    Route::get('/stats', [AnnouncementController::class, 'stats']);
    Route::get('/positions', [AnnouncementController::class, 'getPositions']);
    Route::get('/departments', [AnnouncementController::class, 'getDepartments']);
    Route::get('/employees', [AnnouncementController::class, 'getEmployees']);
    Route::post('/preview-recipients', [AnnouncementController::class, 'previewRecipients']);
    Route::post('/', [AnnouncementController::class, 'store']);
    Route::get('/{id}', [AnnouncementController::class, 'show']);
    Route::put('/{id}', [AnnouncementController::class, 'update']);
    Route::post('/{id}/toggle-active', [AnnouncementController::class, 'toggleActive']);
    Route::get('/{id}/export-readers', [AnnouncementController::class, 'exportReaders']);
    Route::delete('/{id}', [AnnouncementController::class, 'destroy']);
});

// ─── Pengumuman In-App (Employee) ────────────────────────────────────────────
Route::middleware(['web', 'auth'])->prefix('employee/announcements')->group(function () {
    Route::get('/', [\App\Http\Controllers\Employee\AnnouncementController::class, 'index']);
    Route::get('/unread-count', [\App\Http\Controllers\Employee\AnnouncementController::class, 'unreadCount']);
    Route::get('/popups', [\App\Http\Controllers\Employee\AnnouncementController::class, 'getPopups']);
    Route::post('/{id}/mark-read', [\App\Http\Controllers\Employee\AnnouncementController::class, 'markRead']);
    Route::post('/mark-all-read', [\App\Http\Controllers\Employee\AnnouncementController::class, 'markAllRead']);
});

// WhatsApp Webhook (Fonnte/Gateway) - Dinonaktifkan sementara sesuai permintaan
// Route::post('/whatsapp/webhook', [\App\Http\Controllers\Api\WhatsAppWebhookController::class, 'handle']);
