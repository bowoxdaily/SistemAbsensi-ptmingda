# 🔍 LAPORAN AUDIT: MySQL Overload Saat Selesai Jam Kerja
> **Tanggal Audit:** 2026-08-06  
> **Auditor:** Sistem Audit Otomatis  
> **Project:** SistemAbsensi PT Mingda  
> **Status:** ⚠️ ADA MASALAH AKTIF + REKOMENDASI KRITIS

---

## 📋 RINGKASAN EKSEKUTIF

Project ini memiliki **arsitektur yang sudah cukup baik** dengan banyak optimasi yang telah diterapkan sebelumnya. Namun ditemukan **5 masalah aktif** yang menyebabkan MySQL overload khususnya saat jam selesai kerja (sekitar **16:30–18:30 WIB**), ditambah **3 risiko tersembunyi** yang perlu segera ditangani.

---

## ⏰ PETA BEBAN MYSQL PER JAM

```
Jam     Beban        Sumber
──────────────────────────────────────────────────────────
06:00   🟡 Sedang    fingerspot:sync (start)
07:00   🔴 TINGGI    generate-absent setiap 10 menit + user mulai absen
07:10   🔴 TINGGI    generate-absent sweep + fingerspot:sync DIHENTIKAN (OK ✓)
08:00   🔴 TINGGI    peak check-in, fingerspot berhenti sync (OK ✓)
09:00   🟢 Normal    fingerspot:sync resume + generate-absent lanjut
17:00   🔴 TINGGI    ★ PEAK CHECK-OUT — fingerspot:sync MASIH JALAN!
17:30   🔴 TINGGI    ★ generate-absent final sweep (semua karyawan)
18:00   🔴 TINGGI    fingerspot:sync masih jalan
18:30   🔴 TINGGI    ★ recalculate-overtime dijalankan (714 records!)
21:00   🟢 Normal    fingerspot:sync berhenti
23:xx   🟢 Normal    (sudah tidak ada schedule berat)
02:00   🟢 Low       recalculate-overtime final (off-peak ✓)
```

**⚠️ Window Paling Kritis: 17:00–18:30 WIB**  
Tiga proses berat bertabrakan: fingerspot sync + generate-absent + recalculate-overtime.

---

## 🔴 TEMUAN MASALAH AKTIF

### MASALAH #1: `fingerspot:sync` Masih Jalan Saat Peak Checkout (17:00–18:00)
**Tingkat Keparahan: KRITIS 🔴**

**Lokasi:** `routes/console.php` baris 41–45

```php
// JADWAL SAAT INI — bermasalah!
Schedule::command('fingerspot:sync')
    ->cron('*/5 6,9-16,18-21 * * *')  // skip jam 7,8,17 saja
```

**Masalah:**  
Jadwal skip jam **7, 8, dan 17**, tetapi **jam 16 (16:00–16:59) dan jam 18 (18:01–18:59) tetap aktif**.  
Jam 16:30–16:59 adalah **jam checkout paling ramai** (karyawan pulang massal dari fingerspot).  
Fingerspot sync setiap 5 menit = **12 kali/jam** saat peak checkout.

**Dampak:**
- Fingerspot sync berjalan bersamaan dengan ribuan INSERT attendance dari checkout
- Terjadi **table lock contention** di tabel `attendances` dan `fingerspot_logs`
- Query timeout atau slowdown untuk user yang sedang absen pulang

**Solusi:**
```php
// PERBAIKAN: Skip jam 16, 17 (peak checkout)
Schedule::command('fingerspot:sync')
    ->cron('*/5 6,9-15,19-21 * * *')  // skip jam 7,8,16,17
```

---

### MASALAH #2: `summary()` di AttendanceController — 6 Queries Terpisah (N+1 Style)
**Tingkat Keparahan: TINGGI 🔴**

**Lokasi:** `app/Http/Controllers/Admin/AttendanceController.php` baris 523–551

```php
// ❌ MASALAH: 6 query count() terpisah untuk 1 endpoint
$summary = [
    'total'     => $query->count(),          // Query 1
    'hadir'     => $query->clone()->where('status', 'hadir')->count(),      // Query 2
    'terlambat' => $query->clone()->where('status', 'terlambat')->count(),  // Query 3
    'izin'      => $query->clone()->where('status', 'izin')->count(),       // Query 4
    'sakit'     => $query->clone()->where('status', 'sakit')->count(),      // Query 5
    'alpha'     => $query->clone()->where('status', 'alpha')->count(),      // Query 6
    'cuti'      => $query->clone()->where('status', 'cuti')->count(),       // Query 7
];
```

**Dampak:** 7 query ke MySQL untuk 1 request. Jika dipanggil 50 user = **350 queries bersamaan**.

**Solusi:**
```php
// ✅ PERBAIKAN: 1 query groupBy menggantikan 7 query terpisah
$statusCounts = $query->clone()
    ->select('status', DB::raw('COUNT(*) as total'))
    ->groupBy('status')
    ->pluck('total', 'status');

$summary = [
    'total'     => $statusCounts->sum(),
    'hadir'     => (int) $statusCounts->get('hadir', 0),
    'terlambat' => (int) $statusCounts->get('terlambat', 0),
    'izin'      => (int) $statusCounts->get('izin', 0),
    'sakit'     => (int) $statusCounts->get('sakit', 0),
    'alpha'     => (int) $statusCounts->get('alpha', 0),
    'cuti'      => (int) $statusCounts->get('cuti', 0),
];
```

---

### MASALAH #3: `report()` — Multiple Count Queries Terpisah
**Tingkat Keparahan: TINGGI 🔴**

**Lokasi:** `app/Http/Controllers/Admin/AttendanceController.php` baris 736–740

```php
// ❌ MASALAH: 5 query terpisah
$totalAttendance = $query->count();
$hadirCount      = (clone $query)->where('status', 'hadir')->count();
$terlambatCount  = (clone $query)->where('status', 'terlambat')->count();
$izinCount       = (clone $query)->where('status', 'izin')->count();
$alphaCount      = (clone $query)->where('status', 'alpha')->count();
```

Ditambah `whereHas('employee')` dengan subquery tanpa optimasi JOIN = **query sangat berat** untuk range 1 bulan.

**Solusi:**
```php
// ✅ PERBAIKAN: 1 query groupBy
$statusCounts = (clone $query)
    ->select('status', DB::raw('COUNT(*) as total'))
    ->groupBy('status')
    ->pluck('total', 'status');

$totalAttendance = $statusCounts->sum();
$hadirCount      = (int) $statusCounts->get('hadir', 0);
$terlambatCount  = (int) $statusCounts->get('terlambat', 0);
$izinCount       = (int) $statusCounts->get('izin', 0);
$alphaCount      = (int) $statusCounts->get('alpha', 0);
```

---

### MASALAH #4: `AttendanceObserver` — Race Condition & Excessive DB Calls Saat Bulk Checkout
**Tingkat Keparahan: TINGGI 🔴**

**Lokasi:** `app/Observers/AttendanceObserver.php`

```php
// Setiap kali attendance di-save, observer ini dipanggil:
public function updated(Attendance $attendance): void
{
    // firstOrCreate = 1 SELECT + kemungkinan 1 INSERT
    $summary = AttendanceMonthlySummary::firstOrCreate([...]);
    // UPDATE summary
    $summary->save();
}
```

**Dampak saat `bulkCheckOut`:**  
`bulkCheckOut()` memanggil `$attendance->save()` **per karyawan dalam loop**  
→ Observer dipanggil N kali  
→ N × (1 SELECT + 1 UPDATE) pada `attendance_monthly_summaries`  
→ Untuk 100 karyawan = **200 extra queries** saat peak checkout

**Contoh Masalah di bulkCheckOut (baris 1039–1065):**
```php
foreach ($attendances as $attendance) {
    // ...
    $attendance->save(); // ← Observer terpanggil setiap iterasi!
}
```

**Solusi:**
```php
// Option 1: Nonaktifkan observer sementara untuk bulk operation
Attendance::withoutObserver(AttendanceObserver::class, function () use ($attendances) {
    foreach ($attendances as $attendance) {
        $attendance->save();
    }
});
// Lalu update summary sekali setelah bulk selesai

// Option 2: Gunakan DB::update() langsung untuk bulk checkout
// (menghindari observer sama sekali, update summary manual)
```

---

### MASALAH #5: `OvertimeCalculator::getWeeklyUsedOvertimeMinutes()` — Query N+1 Tersembunyi
**Tingkat Keparahan: SEDANG 🟡**

**Lokasi:** `app/Services/OvertimeCalculator.php` baris 72–89

```php
// ❌ Dipanggil jika $weeklyUsedMinutes = null (fallback)
private function getWeeklyUsedOvertimeMinutes(object $attendance): int
{
    // Query ini SEHARUSNYA tidak dipanggil di RecalculateOvertimeCommand
    // karena weeklyUsage di-track di PHP array.
    // Tapi jika ada code path lain yang memanggil calculate() tanpa weeklyUsed,
    // ini akan fire 1 query PER attendance record!
    return (int) Attendance::query()
        ->where('employee_id', $attendance->employee_id)
        ->whereBetween('attendance_date', [...])
        ->sum('overtime_minutes');
}
```

**Skenario Bahaya:**  
Jika ada controller atau job yang memanggil `OvertimeCalculator::calculate()` tanpa parameter `$weeklyUsedMinutes`, maka terjadi **N+1 query** (1 query per attendance record).

**Solusi:**
```php
// Tambahkan log warning saat fallback ini digunakan
private function getWeeklyUsedOvertimeMinutes(object $attendance): int
{
    \Log::warning('OvertimeCalculator: fallback getWeeklyUsedOvertimeMinutes called', [
        'employee_id' => $attendance->employee_id,
        'caller' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3),
    ]);
    // ... existing code
}
```

---

## 🟡 RISIKO TERSEMBUNYI

### RISIKO A: `queue:work` Bersaing dengan Proses Berat di Jam 18:30
**Lokasi:** `routes/console.php` baris 69–73

```php
// queue:work jalan setiap menit, termasuk jam 18:30
Schedule::command('queue:work', ['--stop-when-empty', '--max-time=50', '--sleep=3'])
    ->everyMinute()
```

Jam 18:30 = `recalculate-overtime` (714 records) + `generate-absent final` + queue:work yang mungkin memproses email broadcast.  
**3 proses berat bersamaan** pada DB yang sama.

**Mitigasi:** Pertimbangkan `--queue=low-priority` dan batasi resource queue worker saat jam puncak.

---

### RISIKO B: `whereHas` dengan Subquery pada Filter Department/SubDepartment
**Lokasi:** `AttendanceController::index()` baris 47–58

```php
// Setiap filter department menghasilkan subquery IN atau EXISTS
$query->whereHas('employee', function ($q) use ($department) {
    $q->where('department_id', $department);
});
```

Untuk data besar, `whereHas` menghasilkan query `WHERE EXISTS (SELECT ...)` yang lebih lambat dibanding JOIN langsung.

**Solusi:**
```php
// Gunakan JOIN langsung
$query->join('employees', 'attendances.employee_id', '=', 'employees.id')
      ->where('employees.department_id', $department);
```

---

### RISIKO C: Memory Leak pada Export dengan `per_page=all`
**Lokasi:** `AttendanceController::index()` baris 97–105

```php
if ($perPage === 'all') {
    $allData = $orderedQuery->get(); // ← Load SEMUA record ke memory!
}
```

Jika ada 10.000+ attendance records dalam range tanggal yang besar, ini akan menyebabkan PHP memory exhausted dan query timeout.

**Solusi:** Batasi maksimum records untuk `per_page=all` atau gunakan `chunk()`:
```php
if ($perPage === 'all') {
    // Tambahkan batas maksimum
    $allData = $orderedQuery->limit(5000)->get();
    // Atau redirect ke export Excel untuk data besar
}
```

---

## ✅ HAL-HAL YANG SUDAH BAIK (JANGAN DIUBAH)

| # | Fitur | Status | Keterangan |
|---|-------|--------|------------|
| 1 | Bulk UPDATE overtime | ✅ Excellent | 491 queries → 1 query (99.8% reduction) |
| 2 | Chunk processing (CHUNK_SIZE=200) | ✅ Excellent | Memory-efficient, locks minimal |
| 3 | Index `att_overtime_query_idx` | ✅ Good | Composite index sudah tepat |
| 4 | Index `att_emp_date_idx` | ✅ Good | Query per-karyawan per-tanggal tercover |
| 5 | Index `att_emp_status_idx` | ✅ Good | Summary status per karyawan |
| 6 | Index `emp_status_idx`, `emp_dept_status_idx` | ✅ Good | Filter employees sudah ter-index |
| 7 | Jadwal overtime pindah ke jam 02:00 | ✅ Good | Off-peak hours |
| 8 | Fingerspot skip jam 7, 8, 17 | ✅ Good | Tapi perlu tambah jam 16 |
| 9 | `withoutOverlapping()` semua schedule | ✅ Excellent | Cegah double-run |
| 10 | `AttendanceMonthlySummary` untuk rekap | ✅ Excellent | Eliminasi full-scan saat rekap |
| 11 | RekapitulasiController gunakan Monthly Summary | ✅ Excellent | 1 query aggregasi vs N×full-scan |
| 12 | Stats di index() pakai groupBy | ✅ Good | Sudah dioptimasi (5→1 query) |
| 13 | bulkCheckOut pakai batch query | ✅ Good | Sudah pakai batch fingerspot log query |

---

## 🛠️ REKOMENDASI TOOLS MONITORING MYSQL OVERLOAD

Karena masalah utama adalah **MySQL overload saat jam selesai kerja**, berikut tools yang direkomendasikan:

### 🥇 PILIHAN UTAMA: **Percona Monitoring and Management (PMM)**
```
Tool    : Percona PMM (Free & Open Source)
URL     : https://www.percona.com/software/database-tools/percona-monitoring-and-management
Install : Docker (paling mudah)
```

**Kenapa PMM?**
- ✅ **Query Analytics (QAN)** — Langsung tahu query mana yang paling lambat/paling sering
- ✅ **Real-time dashboard** MySQL metrics (QPS, connections, InnoDB buffer hit rate)
- ✅ **Alerting** — Kirim notifikasi jika MySQL load melebihi threshold
- ✅ **Gratis dan self-hosted** — Data tidak keluar dari server
- ✅ **History** — Bisa lihat korelasi antara jam 18:30 dan spike query

**Setup dengan Docker:**
```bash
# Pull & run PMM Server
docker pull percona/pmm-server:latest
docker run --detach \
  --publish 80:80 \
  --publish 443:443 \
  --name pmm-server \
  percona/pmm-server:latest

# Install PMM Client di server MySQL
# (Ikuti panduan: https://docs.percona.com/percona-monitoring-and-management/setting-up/client/mysql.html)
```

---

### 🥈 ALTERNATIF 1: **Laravel Telescope** (Untuk Dev/Staging)
```
Tool    : Laravel Telescope
Install : composer require laravel/telescope --dev
```

**Kapan pakai:** Cocok untuk **development/staging**, bukan production (bisa overhead).

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Akses di: `http://your-app.test/telescope`

**Fitur yang berguna untuk kasus ini:**
- Lihat semua query yang di-fire per request
- Monitor queue jobs
- Lihat scheduled commands

---

### 🥉 ALTERNATIF 2: **MySQL Slow Query Log** (Sudah Built-in, GRATIS)
```
Konfigurasi di my.ini / my.cnf (cPanel: phpMyAdmin → Variables)
```

```ini
[mysqld]
slow_query_log = 1
long_query_time = 1          ; Log query yang > 1 detik
slow_query_log_file = /var/log/mysql/slow-query.log
log_queries_not_using_indexes = 1  ; Log query tanpa index
```

**Cara baca dengan mysqldumpslow:**
```bash
# Top 10 slowest queries
mysqldumpslow -s t -t 10 /var/log/mysql/slow-query.log

# Queries yang tidak pakai index
mysqldumpslow -s l /var/log/mysql/slow-query.log
```

---

### 📊 ALTERNATIF 3: **New Relic / Datadog APM** (Berbayar, Recommended untuk Production)
```
Harga : New Relic Free tier tersedia, Datadog mulai $15/host/bulan
```

**Keunggulan:**
- APM (Application Performance Monitoring) end-to-end
- Korelasi antara slow HTTP request → slow query MySQL
- Alert otomatis via email/Slack/PagerDuty
- Dashboard siap pakai tanpa konfigurasi

---

### 🔧 TOOL TAMBAHAN UNTUK ANALISIS SATU KALI

**Query untuk monitoring real-time (jalankan saat jam 17:00–18:30):**

```sql
-- 1. Lihat query yang sedang berjalan
SELECT 
    id, USER, HOST, DB, COMMAND, TIME, STATE,
    SUBSTRING(INFO, 1, 200) as QUERY
FROM information_schema.PROCESSLIST
WHERE COMMAND != 'Sleep'
ORDER BY TIME DESC;

-- 2. Cek query yang paling banyak lock
SELECT * FROM information_schema.INNODB_LOCKS;
SELECT * FROM information_schema.INNODB_LOCK_WAITS;

-- 3. Cek penggunaan index di tabel attendances
SHOW INDEX FROM attendances;

-- 4. EXPLAIN query overtime recalculation
EXPLAIN SELECT * FROM attendances
WHERE status IN ('hadir', 'terlambat')
  AND attendance_date >= CURDATE()
  AND check_out IS NOT NULL;
-- Pastikan: key = 'att_overtime_query_idx'

-- 5. Lihat status InnoDB buffer pool
SHOW STATUS LIKE 'Innodb_buffer_pool%';
-- Target: Innodb_buffer_pool_read_requests >> Innodb_buffer_pool_reads
-- (artinya data banyak dari cache, bukan dari disk)

-- 6. Monitor query per detik
SHOW STATUS LIKE 'Questions';
SHOW STATUS LIKE 'Slow_queries';
SHOW STATUS LIKE 'Connections';
SHOW STATUS LIKE 'Threads_connected';
```

---

## 📋 PRIORITAS PERBAIKAN

| # | Masalah | Dampak | Kesulitan | Prioritas |
|---|---------|--------|-----------|-----------|
| 1 | Fingerspot sync masih jalan jam 16:xx | Tinggi | Mudah (1 baris) | 🔴 SEGERA |
| 2 | summary() — 7 query terpisah | Tinggi | Mudah | 🔴 SEGERA |
| 3 | report() — 5 query terpisah | Tinggi | Mudah | 🔴 SEGERA |
| 4 | Observer N+1 saat bulkCheckOut | Tinggi | Sedang | 🟡 MINGGU INI |
| 5 | OvertimeCalculator fallback N+1 | Sedang | Mudah (log) | 🟡 MINGGU INI |
| 6 | whereHas → JOIN di filter department | Sedang | Sedang | 🟢 BULAN INI |
| 7 | per_page=all tanpa batas | Sedang | Mudah | 🟢 BULAN INI |
| 8 | Setup Monitoring (PMM/Telescope) | Preventif | Sedang | 🟢 BULAN INI |

---

## 🚀 QUICK WINS (Bisa Langsung Dilakukan Sekarang)

### Fix #1: Perbaiki Jadwal Fingerspot (1 menit)

**File:** `routes/console.php`

```php
// BEFORE (bermasalah — jam 16 tetap jalan saat peak checkout):
->cron('*/5 6,9-16,18-21 * * *')

// AFTER (lebih aman — skip jam 16 dan 17):
->cron('*/5 6,9-15,19-21 * * *')
```

### Fix #2: Perbaiki summary() (5 menit)

**File:** `app/Http/Controllers/Admin/AttendanceController.php`

Ganti method `summary()` baris 523–551 dengan versi 1-query groupBy.

### Fix #3: Aktifkan MySQL Slow Query Log (2 menit)

Tambahkan ke `php.ini` atau `my.ini`:
```ini
slow_query_log = 1
long_query_time = 1
```

---

## 📝 KESIMPULAN

**Root cause utama MySQL overload saat selesai jam kerja:**

1. **Jam 16:30–18:30** adalah window paling padat: checkout massal karyawan + fingerspot sync + generate-absent + recalculate-overtime semua berjalan bersamaan
2. **Fingerspot sync masih aktif jam 16** menyebabkan lock contention saat checkout peak
3. **Multiple count queries** pada endpoint summary/report membebani MySQL di jam sibuk
4. **Observer N+1** pada bulk checkout menambah beban saat proses massal

**Status keseluruhan project:** Arsitektur sudah 70% dioptimasi dengan baik. Perbaikan yang tersisa relatif kecil tapi dampaknya signifikan untuk mengatasi overload di jam kritis.

---

*Generated: 2026-08-06 | SistemAbsensi PT Mingda*
