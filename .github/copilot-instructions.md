# Sistem Absensi Karyawan - AI Coding Agent Instructions

## Project Overview

Laravel 11 employee attendance system with face detection, automated alpha generation, payroll management, and WhatsApp notifications. Uses Sneat Admin template (Bootstrap 5) with jQuery/AJAX for frontend interactions.

## Architecture & Key Components

### Data Model Relationships

- **User** (1:1) **Employee** (N:1) **Department/Position**
- **Employee** (1:N) **Attendance** - Core attendance tracking
- **Employee** (N:1) **WorkSchedule** - Defines working days/hours per shift
- **Employee** (1:N) **Leave** - Leave requests (izin, sakit, cuti)
- **Employee** (1:N) **Payroll** - Monthly salary calculations
- Table naming: Database uses `employees` table, but model is `Karyawans` (legacy naming preserved for compatibility)

### Route Architecture Pattern

**Separation of Concerns:**

- `routes/web.php` - View rendering only (returns Blade templates)
- `routes/api.php` - Data operations (CRUD via JSON API)
- Frontend makes AJAX calls to `/api/*` endpoints for all data manipulation
- Admin routes protected by `admin` middleware checking `users.role === 'admin'` or `'manager'`
- **Manager role has same access as Admin** - both use `/admin/*` routes

**Example Flow:**

```php
// web.php - Render page
Route::get('/admin/attendance', [AttendanceController::class, 'index']);

// api.php - Handle operations
Route::post('/api/attendance/check-in', [AttendanceController::class, 'checkIn']);
Route::get('/api/attendance/list', [AttendanceController::class, 'list']);
```

**Role Structure (from `users` table):**

- `karyawan` - Regular employee access (employee dashboard)
- `admin` - Full administrative access (admin dashboard)
- `manager` - Same as admin (shares admin dashboard and all features)

### Attendance Status Logic

**Status Values:** `hadir`, `terlambat`, `izin`, `sakit`, `alpha`, `cuti`

**Status Determination (in `AttendanceController::checkIn()`):**

1. Check if leave approved for date → `izin`/`sakit`/`cuti`
2. Compare `check_in` time with `WorkSchedule->start_time`:
    - Late by >15 min → `terlambat`
    - Otherwise → `hadir`
3. `late_minutes` calculated automatically

**Alpha Auto-Generation:**

- Command: `php artisan attendance:generate-absent {date?}`
- Runs: Hourly 08:00-23:59 on weekdays (see `routes/console.php`)
- Logic in `GenerateAbsentAttendance`:
    - Checks each active employee with `work_schedule_id`
    - Verifies if date is working day for their shift (uses `WorkSchedule->work_*` columns)
    - Skips if check-out time + 30min grace period hasn't passed
    - Creates `alpha` record only if no attendance/leave exists
    - Never overwrites existing attendance

### Retroactive Attendance Entry

**Key Feature:** Manual attendance supports date/time override (see `ABSENSI_MANUAL_UPDATE.md`)

- `face-detection.blade.php` has date picker to select any date
- `checkIn()`/`checkOut()` accept `date`, `check_in_time`, `check_out_time` parameters
- Use `/api/attendance/by-date/{employeeId}?date=YYYY-MM-DD` to check existing records
- Allow past date entry for corrections

### Date Handling & Timezone Issues

**CRITICAL: Avoid timezone conversion issues when handling dates in API responses**

**Problem:** Laravel's date casting (`'date'` in `$casts`) converts dates to Carbon instances, which serialize to JSON with timezone info (e.g., `2025-11-24T00:00:00.000000Z`). JavaScript's Date parsing may shift this by timezone offset, causing dates to appear 1 day earlier in forms.

**Solution Pattern:**

```php
// In API controller responses - explicitly format dates as Y-m-d strings
public function detail($id) {
    $attendance = Attendance::findOrFail($id);
    $data = $attendance->toArray();

    // Force date to string format without timezone
    if (isset($data['attendance_date'])) {
        $data['attendance_date'] = Carbon::parse($attendance->attendance_date)->format('Y-m-d');
    }

    return response()->json(['data' => $data]);
}
```

**JavaScript Parsing (in Blade views):**

```javascript
// Extract YYYY-MM-DD directly via regex (no Date object parsing)
const dateStr = String(data.attendance_date);
const match = dateStr.match(/(\d{4})-(\d{2})-(\d{2})/);
if (match) {
    attendanceDate = `${match[1]}-${match[2]}-${match[3]}`;
}

// Fallback: split string without Date constructor
const datePart = dateStr.split(/[T\s]/)[0]; // "2025-11-24"
if (/^\d{4}-\d{2}-\d{2}$/.test(datePart)) {
    attendanceDate = datePart;
}
```

**Key Rules:**

1. Always return dates from API as plain `Y-m-d` strings for form inputs
2. Never parse API dates with `new Date()` in JavaScript for date inputs
3. Use regex/string splitting to extract date components
4. For display purposes (not inputs), Carbon formatting in Blade is safe

## Development Workflows

### Running the Application

```powershell
# Development server
php artisan serve

# Scheduler (CRITICAL for alpha generation)
php artisan schedule:work  # Development
# Production: Set cron via dashboard at /admin/settings/cronjob
```

### Database Operations

```powershell
php artisan migrate          # Run migrations
php artisan migrate:fresh    # Reset database (caution!)
php artisan db:seed          # Seed data
```

### Common Tasks

- **Add migration:** `php artisan make:migration create_*_table`
- **Add controller:** `php artisan make:controller Admin\*Controller`
- **Clear cache:** `php artisan cache:clear; php artisan config:clear; php artisan route:clear`

### Testing Scheduler Locally

```powershell
# Test single command
php artisan attendance:generate-absent 2025-11-19

# Test specific date
php artisan attendance:generate-absent 2025-10-15

# Run scheduler once (executes all due tasks)
php artisan schedule:run
```

## Code Conventions

### Naming Patterns

- **Controllers:** Namespaced under `Admin\` or `Employee\` based on access level (Manager uses `Admin\` namespace)
- **Views:** `resources/views/{admin|employee}/{feature}/{action}.blade.php` (Manager uses admin views)
- **API Routes:** Prefix with feature: `/api/attendance/*`, `/api/karyawan/*`
- **Models:** Singular names, `Karyawans` is exception (maps to `employees` table via `$table` property)
- **User Roles:** `karyawan`, `admin`, `manager` - Manager has identical permissions to Admin

### Validation Pattern

Use Laravel's `Validator::make()` in controllers:

```php
$validator = Validator::make($request->all(), [
    'employee_id' => 'required|exists:employees,id',
    'date' => 'nullable|date',
    'status' => 'nullable|in:hadir,terlambat,izin,sakit,alpha,cuti',
]);

if ($validator->fails()) {
    return response()->json([
        'success' => false,
        'message' => 'Validasi gagal',
        'errors' => $validator->errors()
    ], 422);
}
```

### JSON Response Format

**Standardized structure across all API endpoints:**

```php
// Success
return response()->json([
    'success' => true,
    'message' => 'Operasi berhasil',
    'data' => $result
]);

// Error
return response()->json([
    'success' => false,
    'message' => 'Error message',
    'errors' => $validator->errors()  // Optional for validation errors
], 422);
```

### Frontend AJAX Pattern

```javascript
$.ajax({
    url: '/api/endpoint',
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    data: { ... },
    success: function(response) {
        if (response.success) {
            Swal.fire('Berhasil', response.message, 'success');
            // Refresh data table or redirect
        }
    },
    error: function(xhr) {
        Swal.fire('Error', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
    }
});
```

## External Integrations

### Excel Import/Export (Maatwebsite/Laravel-Excel)

- **Export classes:** `app/Exports/*Export.php` - Implement `FromCollection`, `WithHeadings`, `WithMapping`, `WithStyles`
- **Import classes:** `app/Imports/*Import.php` - Implement `ToModel`, `WithHeadingRow`, `WithValidation`
- **Usage:** `Excel::download(new AttendanceExport(...), 'filename.xlsx')`

### WhatsApp Notifications (WhatsAppService)

**Multi-Provider Support:**

- **Fonnte API:** Commercial service (https://api.fonnte.com/send)
- **Baileys:** Self-hosted WhatsApp Web API
- Configuration: `whatsapp_settings` table stores API keys/URLs
- Service: `app/Services/WhatsAppService.php` - Auto-detects provider via `WhatsAppSetting::getActive()`

**Implementation Pattern:**

```php
use App\Services\WhatsAppService;

$whatsapp = new WhatsAppService();
$whatsapp->send('628123456789', 'Message text', $imageUrl);
```

### Face Detection (Frontend Only)

- Uses browser `navigator.mediaDevices.getUserMedia()` for webcam access
- Captures Base64 image → sends to API as `photo` field
- No server-side AI/ML processing - just stores images in `storage/app/public/attendance/`

## Important Files

### Configuration

- `.env` - Database, app settings (copy from `.env.example`)
- `config/excel.php` - Excel export settings
- `bootstrap/providers.php` - Service provider registration

### Key Controllers

- `Admin/AttendanceController.php` - Attendance CRUD, check-in/out, reports, export
- `Admin/KaryawanController.php` - Employee management, Excel import/export with template
- `Admin/CronJobController.php` - Dashboard for cron setup with OS-specific commands
- `Admin/PayrollController.php` - Payroll calculation and export

### Scheduled Tasks

- `routes/console.php` - Defines all scheduled commands
- `app/Console/ScheduleRunMiddleware.php` - Tracks cron execution (updates cache/sentinel file)
- `app/Console/Commands/GenerateAbsentAttendance.php` - Alpha generation logic

### Views Layout

- `resources/views/layouts/app.blade.php` - Main layout (Sneat template)
- `resources/views/layouts/partials/{sidebar,navbar,footer}.blade.php` - UI components
- Blade directives: `@extends('layouts.app')`, `@section('content')`

## Common Pitfalls

1. **Model Name Mismatch:** Use `Karyawans` model even though table is `employees` - don't create `Employee` model unless intentional refactor
2. **CSRF Token:** Always include `X-CSRF-TOKEN` header in AJAX requests or use `@csrf` in forms
3. **Status Values:** Hardcoded enum values - changing requires migration. Current: `hadir|terlambat|izin|sakit|alpha|cuti`
4. **WorkSchedule Check:** Before marking alpha, verify employee has `work_schedule_id` and date is working day for their shift
5. **Date Formats:** Database stores `Y-m-d` for dates, `H:i:s` for times. Use Carbon for parsing
6. **API vs Web Routes:** Never put POST/PUT/DELETE in `web.php` - keep data operations in `api.php`
7. **Role Access:** Manager role has same access as Admin - middleware should check for `'admin'` OR `'manager'`, not just `'admin'`
8. **WorkSchedule Time Casting:** `WorkSchedule` model uses `datetime:H:i:s` cast for `start_time` and `end_time`, so they return **Carbon objects**, not strings. Never use `substr()` on these fields - use `->format('H:i')` instead:

    ```php
    // WRONG - will fail silently or error
    $startTimeStr = substr($schedule->start_time, 0, 5);

    // CORRECT - handle Carbon object
    if ($schedule->start_time instanceof Carbon) {
        $startTimeStr = $schedule->start_time->format('H:i');
    } else {
        preg_match('/(\d{1,2}):(\d{2})/', (string) $schedule->start_time, $m);
        $startTimeStr = $m ? $m[1] . ':' . $m[2] : '08:00';
    }
    ```

## Quick Reference Commands

```powershell
# Setup
composer install; npm install; php artisan key:generate; php artisan migrate

# Clear everything
php artisan optimize:clear

# Storage link (for uploaded files)
php artisan storage:link

# Test scheduler
php artisan schedule:work  # Keep running
php artisan schedule:list   # Show all scheduled tasks

# Check specific attendance generation
php artisan attendance:generate-absent 2025-11-19
```

## When Adding Features

1. **New API Endpoint:** Add route to `routes/api.php`, method in existing controller or create new under `Admin\`
2. **New Page:** Add GET route to `routes/web.php`, create Blade view extending `layouts.app`
3. **New Model:** Use singular name, add relationships, define `$fillable`, add to migration
4. **Modify Status/Enum:** Requires migration change + update validation rules in controller
5. **New Report/Export:** Create class in `app/Exports/`, implement required interfaces, add download route

---

**For more details:** See `README.md` (full setup guide), `ABSENSI_MANUAL_UPDATE.md` (retroactive entry feature), `composer.json` (dependencies).
