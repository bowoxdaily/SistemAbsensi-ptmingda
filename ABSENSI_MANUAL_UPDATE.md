# Update Absensi Manual - Fitur Pilih Tanggal

## 📋 **PERUBAHAN YANG DILAKUKAN**

### 1. **Frontend (face-detection.blade.php)**

#### Tambahan UI:

-   ✅ **Date Picker**: Input tanggal untuk memilih tanggal absensi
-   ✅ **Button "Hari Ini"**: Quick action untuk set tanggal hari ini
-   ✅ **Date Change Handler**: Auto-refresh data saat tanggal berubah

#### JavaScript Updates:

-   ✅ `selectedDate` variable untuk tracking tanggal yang dipilih
-   ✅ `setDefaultDate()` untuk set default tanggal hari ini
-   ✅ `checkAttendanceByDate()` menggantikan `checkTodayAttendance()`
-   ✅ API call ke `/api/attendance/by-date/{employeeId}?date={date}`
-   ✅ Kirim parameter `date` saat check-in dan check-out

### 2. **Backend (AttendanceController.php)**

#### New Method:

```php
public function getAttendanceByDate($employeeId, Request $request)
```

-   Mengambil data absensi berdasarkan employee ID dan tanggal
-   Support query parameter `?date=YYYY-MM-DD`
-   Default ke tanggal hari ini jika tidak ada parameter

#### Updated Methods:

**checkIn():**

-   ✅ Tambah parameter `date` (nullable|date)
-   ✅ Tambah parameter `check_in_time` (nullable|date_format:H:i)
-   ✅ Tambah parameter `notes` (nullable|string|max:500)
-   ✅ Support input retroaktif (tanggal masa lalu)
-   ✅ Validasi berdasarkan tanggal yang dipilih

**checkOut():**

-   ✅ Tambah parameter `date` (nullable|date)
-   ✅ Tambah parameter `check_out_time` (nullable|date_format:H:i)
-   ✅ Tambah parameter `notes` (nullable|string|max:500)
-   ✅ Support input retroaktif
-   ✅ Validasi berdasarkan tanggal yang dipilih

### 3. **Routes (api.php)**

```php
Route::get('/by-date/{employeeId}', [AttendanceController::class, 'getAttendanceByDate']);
```

## 🎯 **CARA PENGGUNAAN**

### 1. **Akses Halaman**

```
/admin/attendance/face-detection
```

### 2. **Workflow Baru:**

1. **Pilih Karyawan** dari dropdown
2. **Pilih Tanggal** absensi (default: hari ini)
3. System akan load data absensi untuk tanggal tersebut
4. Jika belum check-in → Tampil form check-in
5. Jika sudah check-in → Tampil form check-out
6. Jika sudah lengkap → Tampil status saja

### 3. **Fitur Tambahan:**

-   ✅ **Input Retroaktif**: Bisa input absensi tanggal lalu
-   ✅ **Koreksi Data**: Bisa ubah tanggal untuk koreksi
-   ✅ **Custom Time**: Bisa set jam manual atau pakai "Jam Sekarang"
-   ✅ **Notes**: Tambah catatan pada check-in/check-out
-   ✅ **Real-time Update**: Data refresh otomatis setelah submit

## 📊 **API ENDPOINTS**

### 1. Get Attendance by Date

```http
GET /api/attendance/by-date/{employeeId}?date=2025-11-15
```

**Response:**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "employee_id": 5,
        "attendance_date": "2025-11-15",
        "check_in": "08:00:00",
        "check_out": "17:00:00",
        "status": "present",
        "late_minutes": 0
    },
    "date": "2025-11-15"
}
```

### 2. Check In with Date

```http
POST /api/attendance/check-in
Content-Type: application/json

{
  "employee_id": 5,
  "date": "2025-11-15",
  "check_in_time": "08:30",
  "notes": "Terlambat karena macet"
}
```

### 3. Check Out with Date

```http
POST /api/attendance/check-out
Content-Type: application/json

{
  "employee_id": 5,
  "date": "2025-11-15",
  "check_out_time": "17:00",
  "notes": "Selesai tepat waktu"
}
```

## ⚙️ **VALIDASI & BUSINESS LOGIC**

### Check-In:

-   ✅ Tidak boleh check-in 2x di tanggal yang sama
-   ✅ Calculate late_minutes berdasarkan work schedule
-   ✅ Status otomatis: 'present' atau 'late'
-   ✅ Support custom date & time

### Check-Out:

-   ✅ Harus sudah check-in dulu
-   ✅ Tidak boleh check-out 2x di tanggal yang sama
-   ✅ Notes bisa digabung dengan notes check-in (separator: ' | ')

## 🔒 **SECURITY & VALIDATION**

### Frontend:

-   Input type="date" untuk format konsisten
-   Input type="time" untuk jam
-   Select2 untuk searchable dropdown
-   SweetAlert untuk konfirmasi

### Backend:

-   Validasi date format (Y-m-d)
-   Validasi time format (H:i)
-   Exists validation untuk employee_id
-   Max length 500 chars untuk notes
-   Try-catch error handling

## 🎨 **UI/UX IMPROVEMENTS**

1. **Date Picker dengan Quick Action:**

    ```html
    <div class="input-group">
        <input type="date" class="form-control" id="attendanceDate" />
        <button class="btn btn-outline-primary" id="setTodayDate">
            <i class="bx bx-calendar"></i> Hari Ini
        </button>
    </div>
    ```

2. **Form Text Helper:**

    ```
    "Pilih tanggal untuk input atau koreksi absensi"
    ```

3. **Updated Instructions:**
    - Tambah step "Pilih tanggal absensi"
    - Update warning message

## 🧪 **TESTING CHECKLIST**

-   [x] Load employees dropdown
-   [x] Set default date to today
-   [x] Change date and check attendance
-   [x] Check-in with custom date
-   [x] Check-in with custom time
-   [x] Check-out with custom date
-   [x] Check-out with custom time
-   [x] Add notes on check-in
-   [x] Add notes on check-out
-   [x] Prevent double check-in
-   [x] Prevent double check-out
-   [x] Prevent check-out before check-in
-   [x] Retroactive input (past dates)
-   [x] Future date prevention (optional)

## 📝 **NOTES FOR DEVELOPERS**

1. **Date Format Consistency:**

    - Frontend: `YYYY-MM-DD` (HTML5 date input)
    - Backend: Carbon parse, store as date
    - API: ISO 8601 format

2. **Timezone Handling:**

    - Gunakan Carbon dengan timezone app
    - Default: Asia/Jakarta (sesuai config/app.php)

3. **Future Enhancement Ideas:**
    - Bulk import absensi dari Excel
    - Calendar view untuk lihat absensi bulanan
    - Approval workflow untuk koreksi
    - Audit log untuk tracking perubahan
    - Limit retroactive input (misal: max 7 hari ke belakang)

## 🚀 **DEPLOYMENT**

Tidak perlu migrasi database karena menggunakan field yang sudah ada:

-   `attendance_date` (sudah ada)
-   `check_in` (sudah ada)
-   `check_out` (sudah ada)
-   `notes` (sudah ada)

Yang perlu:

1. ✅ Update file blade
2. ✅ Update controller
3. ✅ Update routes
4. ✅ Clear cache: `php artisan route:clear`
5. ✅ Test di browser

## 🎉 **HASIL AKHIR**

Fitur absensi manual sekarang lebih fleksibel:

-   ✅ Bisa pilih tanggal absensi
-   ✅ Bisa input retroaktif untuk koreksi
-   ✅ Bisa set jam custom
-   ✅ Bisa tambah catatan
-   ✅ UI/UX lebih user-friendly
-   ✅ Validasi lebih ketat
-   ✅ Error handling lebih baik

Perfect untuk:

-   Admin input absensi manual
-   Koreksi data absensi
-   Input absensi yang tertinggal
-   Backup jika sistem utama bermasalah
