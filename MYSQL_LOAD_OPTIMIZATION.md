# Analisis & Optimasi Beban MySQL dari Cron Jobs

## 🔴 Identifikasi Masalah

### Cron Job yang Berpotensi Memberatkan MySQL

#### 1. **OVERTIME RECALCULATION** (Prioritas Tertinggi)
- **Jadwal:** Setiap hari jam 23:00
- **Lokasi:** `routes/console.php` line 51-56
- **Command:** `attendance:recalculate-overtime`
- **Beban:**
  - Query seluruh attendance dengan status `hadir`/`terlambat` yang sudah check-out
  - Eager loading: `employee.workSchedule` → 2-3 JOIN operations
  - Loop untuk setiap record → kalkulasi overtime
  - Multiple UPDATE queries untuk record yang berubah

**Estimasi Load:**
```
100 karyawan/hari × (1 SELECT + 1 UPDATE) = ~200 queries
200 karyawan/hari × (1 SELECT + 1 UPDATE) = ~400 queries
500 karyawan/hari × (1 SELECT + 1 UPDATE) = ~1000 queries
```

#### 2. **FINGERSPOT SYNC** (Beban Sedang)
- **Jadwal:** Setiap 5 menit
- **Beban:** Tergantung jumlah data dari API Fingerspot
- **Impact:** Jika ada ratusan scan per 5 menit → bisa membebani

#### 3. **ALPHA GENERATION** (Beban Ringan)
- **Jadwal:** Setiap menit (hari kerja)
- **Beban:** Relatif ringan, hanya cek karyawan aktif dengan schedule

---

## ✅ Solusi yang Sudah Diimplementasikan

### 1. **Ubah Jadwal Overtime ke Hari Kerja Saja**
✅ **Status:** COMPLETED

**Perubahan di `routes/console.php`:**
```php
Schedule::command('attendance:recalculate-overtime', ['--from' => now()->format('Y-m-d')])
    ->dailyAt('23:00')
    ->weekdays() // ✅ BARU: Hanya Senin-Jumat
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/overtime-recalculate.log'));
```

**Impact:** 
- Mengurangi eksekusi dari 365 hari/tahun → 260 hari/tahun (28% reduction)
- Tidak perlu process di Sabtu/Minggu (biasanya tidak ada attendance)

---

### 2. **Tambah Database Index untuk Query Overtime**
✅ **Status:** MIGRATION READY

**File:** `database/migrations/2026_07_10_100000_add_overtime_query_index_to_attendances.php`

**Index baru:**
```php
$table->index(['status', 'attendance_date', 'check_out'], 'att_overtime_query_idx');
```

**Alasan urutan kolom:**
1. `status` → Filter pertama (IN clause: hadir/terlambat)
2. `attendance_date` → Range query (>=)
3. `check_out` → NOT NULL check

**Cara apply:**
```powershell
php artisan migrate
```

**Expected Performance Improvement:** 2-5x faster query execution

---

## 🎯 Rekomendasi Tambahan

### Rekomendasi A: Ubah Waktu Eksekusi ke Beban Rendah

**Current:** 23:00 (masih prime time untuk beberapa user)

**Opsi:**
```php
// Opsi 1: Dini hari (RECOMMENDED)
->dailyAt('02:00')  // Beban paling rendah
->weekdays()

// Opsi 2: Pagi sebelum jam kerja
->dailyAt('05:00')  // Sebelum karyawan mulai absen
->weekdays()

// Opsi 3: Malam lebih larut
->dailyAt('01:00')
->weekdays()
```

**Impact:** Menghindari konflik dengan user yang masih online

---

### Rekomendasi B: Tambah Monitoring & Alerting

#### 1. **Log File Monitoring**

Log file sudah dikonfigurasi di:
```
storage/logs/overtime-recalculate.log
storage/logs/fingerspot-sync.log
```

**Cara monitor:**
```powershell
# Windows PowerShell
Get-Content storage\logs\overtime-recalculate.log -Tail 50

# Linux/Mac
tail -f storage/logs/overtime-recalculate.log
```

#### 2. **MySQL Slow Query Log**

Aktifkan di `my.cnf` / `my.ini`:
```ini
[mysqld]
slow_query_log = 1
long_query_time = 2
slow_query_log_file = /var/log/mysql/slow-query.log
```

Query yang lebih dari 2 detik akan tercatat.

#### 3. **Laravel Telescope** (Optional)

Install untuk monitoring real-time:
```powershell
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Access di: `http://your-app.test/telescope`

---

### Rekomendasi C: Optimasi Query di RecalculateOvertimeCommand

**Current Implementation:**
```php
// app/Console/Commands/RecalculateOvertimeCommand.php
$attendances = Attendance::with(['employee.workSchedule'])
    ->whereNotNull('check_out')
    ->whereIn('status', ['hadir', 'terlambat'])
    ->whereDate('attendance_date', '>=', $from)
    ->get();

foreach ($attendances as $attendance) {
    // ... calculation ...
    $attendance->save(); // ❌ Individual UPDATE
}
```

**Optimasi dengan Bulk Update:**
```php
// Collect IDs dan nilai yang perlu diupdate
$updates = [];
foreach ($attendances as $attendance) {
    $overtimeMinutes = ... // calculation
    
    if ($attendance->overtime_minutes != $overtimeMinutes) {
        $updates[] = [
            'id' => $attendance->id,
            'overtime_minutes' => $overtimeMinutes
        ];
    }
}

// ✅ Bulk update (lebih efisien)
if (!empty($updates)) {
    $cases = [];
    $ids = [];
    
    foreach ($updates as $update) {
        $cases[] = "WHEN {$update['id']} THEN {$update['overtime_minutes']}";
        $ids[] = $update['id'];
    }
    
    $casesStr = implode(' ', $cases);
    $idsStr = implode(',', $ids);
    
    DB::update("UPDATE attendances SET overtime_minutes = CASE id $casesStr END WHERE id IN ($idsStr)");
}
```

**Note:** Implementasi ini opsional, hanya jika beban masih tinggi setelah index ditambahkan.

---

## 📊 Monitoring Queries

File SQL untuk monitoring sudah dibuat di:
**`docs/monitoring_mysql_load.sql`**

Query yang tersedia:
1. Hitung attendance yang perlu diproses per hari
2. Rata-rata beban harian
3. Check existing indexes
4. Monitor real-time running queries

**Cara pakai:**
```sql
-- Jalankan di phpMyAdmin / MySQL Workbench
source docs/monitoring_mysql_load.sql;
```

---

## 🧪 Testing

### Test Recalculation Manual

```powershell
# Dry run (preview tanpa save)
php artisan attendance:recalculate-overtime --from=2026-07-09 --dry-run

# Execute untuk tanggal tertentu
php artisan attendance:recalculate-overtime --from=2026-07-09 --to=2026-07-09

# Execute untuk semua bulan ini
php artisan attendance:recalculate-overtime --from=2026-07-01
```

### Test Scheduler

```powershell
# List semua scheduled tasks
php artisan schedule:list

# Run scheduler once (test semua due tasks)
php artisan schedule:run

# Run scheduler terus menerus (development)
php artisan schedule:work
```

---

## ✅ Checklist Implementasi

- [x] ✅ Ubah jadwal overtime recalculation ke weekdays only
- [x] ✅ Buat migration untuk index baru
- [ ] 🔲 Apply migration: `php artisan migrate`
- [ ] 🔲 Test query performance sebelum & sesudah index
- [ ] 🔲 Monitor log file `overtime-recalculate.log` selama 1 minggu
- [ ] 🔲 Evaluate: Apakah perlu pindah jam eksekusi (23:00 → 02:00)?
- [ ] 🔲 Evaluate: Apakah perlu implementasi bulk update?
- [ ] 🔲 Setup MySQL slow query log (optional)
- [ ] 🔲 Setup Laravel Telescope untuk monitoring (optional)

---

## 📈 Expected Results

Setelah implementasi optimasi:

**Before:**
- Eksekusi: 365 hari/tahun
- Query time: ~2-5 detik untuk 100 karyawan (tanpa index yang optimal)
- Total queries: ~200-400 per eksekusi

**After:**
- Eksekusi: 260 hari/tahun (28% reduction)
- Query time: ~0.5-1 detik untuk 100 karyawan (dengan index)
- Impact: 60-80% reduction in database load

---

## 🔗 Related Files

- `routes/console.php` - Scheduled tasks configuration
- `app/Console/Commands/RecalculateOvertimeCommand.php` - Overtime calculation logic
- `app/Console/Commands/SyncFingerspotData.php` - Fingerspot sync logic
- `app/Console/Commands/GenerateAbsentAttendance.php` - Alpha generation logic
- `database/migrations/2026_07_10_100000_add_overtime_query_index_to_attendances.php` - New index migration
- `docs/monitoring_mysql_load.sql` - Monitoring queries

---

**Dibuat:** 2026-07-10  
**Last Updated:** 2026-07-10  
**Status:** Ready for Implementation
