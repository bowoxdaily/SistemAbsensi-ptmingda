# MySQL Load Optimization — Audit Report
> **Tanggal Audit:** 2026-08-06 21:00 WIB  
> **Auditor:** Cline AI  
> **Trigger:** MySQL overload saat jam selesai kerja (±17:00–19:00 WIB)

---

## 🔍 Root Cause Analysis

Setiap hari kerja pada rentang **17:00–19:00 WIB**, terjadi **MySQL spike** karena beberapa proses berjalan bersamaan:

| Waktu | Proses | Beban DB |
|-------|--------|----------|
| 17:30 | `attendance:generate-absent` (final sweep) | Berat — UPDATE massal |
| 18:30 | `attendance:recalculate-overtime` (lama) | Berat — N query per karyawan |
| setiap 5 menit | `fingerspot:sync` jam 16–18 | Sedang — INSERT + UPDATE |
| setiap menit | `queue:work` | Polling DB terus |
| Request user | `summary()` — 7 COUNT query | 7 round-trips per request |
| Request user | `report()` — 5 COUNT query | 5 round-trips per request |
| bulkCheckOut | `$attendance->save()` per loop | N×Observer queries |

**Efek gabungan:** Semua proses ini tabrakan di jam yang sama → MySQL connection pool habis → response lambat / timeout.

---

## ✅ Perbaikan yang Diterapkan

### Fix #1 — Jadwal Schedule (routes/console.php)
**Masalah:** `fingerspot:sync` jalan tiap 5 menit termasuk jam 16:00–18:59 (peak checkout).  
**Masalah 2:** `recalculate-overtime` dijadwalkan jam 18:30, tabrakan dengan generate-absent 17:30.

```php
// SEBELUM
->cron('*/5 6,9-15,17-21 * * *')   // sync jalan jam 17 & 18 = peak!
->dailyAt('18:30')                   // overtime jam 18:30 = tabrakan

// SESUDAH  
->cron('*/5 6,9-15,19-21 * * *')   // skip jam 7,8,16,17,18 (peak)
->dailyAt('19:00')                   // geser 30 menit ke off-peak
```

**Dampak:** Fingerspot sync tidak bersaing dengan karyawan yang absen pulang. Recalculate tidak tabrakan dengan generate-absent.

---

### Fix #2 — `summary()` Method (AttendanceController)
**Masalah:** 7 query `COUNT()` terpisah per request API.

```php
// SEBELUM: 7 round-trips ke MySQL
$total     = $query->count();
$hadir     = (clone $query)->where('status','hadir')->count();
$terlambat = (clone $query)->where('status','terlambat')->count();
// ... dst

// SESUDAH: 1 query GROUP BY
$statusCounts = $query->clone()
    ->select('status', DB::raw('COUNT(*) as total'))
    ->groupBy('status')
    ->pluck('total', 'status');
```

**Dampak:** -6 query per request summary (86% pengurangan).

---

### Fix #3 — `report()` Method (AttendanceController)
**Masalah:** 5 query `COUNT()` terpisah per page load halaman report.

```php
// SEBELUM: 5 round-trips
$totalAttendance = $query->count();
$hadirCount      = (clone $query)->where('status','hadir')->count();
// ... dst

// SESUDAH: 1 query GROUP BY
$statusCounts = (clone $query)
    ->select('status', DB::raw('COUNT(*) as total'))
    ->groupBy('status')
    ->pluck('total', 'status');
```

**Dampak:** -4 query per page load report (80% pengurangan).

---

### Fix #4 — `bulkCheckOut()` Observer N+1 (AttendanceController)
**Masalah:** Loop `foreach` memanggil `$attendance->save()` per karyawan. Setiap `save()` memicu `AttendanceObserver` yang menjalankan 2–3 extra queries ke `attendance_monthly_summaries`.

```php
// SEBELUM: N×(save + observer queries)
foreach ($attendances as $attendance) {
    $attendance->save(); // → trigger observer → 2-3 extra queries
}

// SESUDAH: withoutObservers() + batch collect
$attendanceUpdates[$attendance->id] = [...fields...];
// Setelah loop:
foreach ($attendanceUpdates as $attId => $fields) {
    Attendance::withoutObservers(function () use ($attId, $fields) {
        Attendance::where('id', $attId)->update($fields);
    });
}
```

**Dampak:** Observer tidak fire saat bulk update. Untuk 50 karyawan: dari ~150 queries → 50 queries (−67%).  
**Catatan:** `overtime_minutes` tetap di-set 0 saat bulk checkout; kalkulasi akurat dilakukan oleh cron `attendance:recalculate-overtime` jam 19:00.

---

### Fix #5 — OvertimeCalculator Fallback Guard (OvertimeCalculator.php)
**Masalah:** Method `getWeeklyUsedOvertimeMinutes()` adalah fallback N+1 yang dipanggil saat caller tidak meneruskan `$weeklyUsedMinutes`. Sulit terdeteksi di production.

```php
// SESUDAH: Tambah warning log
private function getWeeklyUsedOvertimeMinutes(object $attendance): int
{
    Log::warning('[OvertimeCalculator] Fallback N+1 query triggered', [
        'employee_id'   => $attendance->employee_id,
        'attendance_id' => $attendance->id,
        'caller'        => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2] ?? [],
    ]);
    // ... query ...
}
```

**Dampak:** Setiap kali fallback terpanggil, akan muncul entry `WARNING` di `storage/logs/laravel.log`. Tim dapat `grep '[OvertimeCalculator]'` untuk mendeteksi caller mana yang belum meneruskan weekly cache.

---

## 📊 Estimasi Pengurangan Query Saat Peak Hour

| Skenario | Sebelum | Sesudah | Pengurangan |
|----------|---------|---------|-------------|
| 1 request `/summary` | 7 queries | 1 query | −86% |
| 1 page `/report` | 5 queries | 1 query | −80% |
| bulkCheckOut 50 karyawan | ~150 queries | ~50 queries | −67% |
| fingerspot:sync jam 17–18 | Aktif | **Dimatikan** | −100% |
| recalculate-overtime jam 18:30 | Tabrakan | Digeser 19:00 | Tidak tabrakan |

---

## 🔧 Rekomendasi Lanjutan (Belum Diimplementasi)

### A. Index Database yang Direkomendasikan
Jalankan di MySQL untuk mempercepat query yang sering:

```sql
-- Index untuk filter status + tanggal (dipakai summary & report)
ALTER TABLE attendances 
  ADD INDEX idx_status_date (status, attendance_date);

-- Index untuk employee + tanggal (dipakai di OvertimeCalculator)
ALTER TABLE attendances 
  ADD INDEX idx_emp_date_status (employee_id, attendance_date, status);

-- Index untuk fingerspot logs (dipakai bulkCheckOut)
ALTER TABLE fingerspot_logs 
  ADD INDEX idx_emp_scantime_status (employee_id, scan_time, process_status);
```

### B. Cache Hasil Summary
Jika halaman dashboard sering dibuka banyak user, pertimbangkan cache:

```php
$summary = Cache::remember("attendance_summary_{$date}", 300, function () use ($query) {
    return $query->select('status', DB::raw('COUNT(*) as total'))
        ->groupBy('status')
        ->pluck('total', 'status');
});
```

### C. Queue Horizon (jika traffic tinggi)
Ganti `queue:work` via cron dengan **Laravel Horizon** untuk monitoring antrian real-time dan auto-scaling worker.

### D. Slow Query Log MySQL
Aktifkan slow query log untuk monitoring berkelanjutan:

```ini
# my.cnf / my.ini
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
log_queries_not_using_indexes = 1
```

---

## 🕐 Timeline Jadwal Setelah Perbaikan

```
06:00  fingerspot:sync mulai (setiap 5 menit)
07:00  generate-absent sweep pagi (setiap 10 menit s/d 10:00)
       [fingerspot:sync BERHENTI jam 7-8 = peak check-in]
09:00  fingerspot:sync lanjut (s/d 15:59)
       [fingerspot:sync BERHENTI jam 16 = peak check-out]
17:30  generate-absent final sweep ✓
19:00  fingerspot:sync lanjut (s/d 21:00)
19:00  recalculate-overtime hari ini ✓ (tidak tabrakan)
02:00  recalculate-overtime kemarin (final check) ✓
```

---

*Laporan ini dibuat otomatis oleh audit AI. Untuk pertanyaan teknis, lihat komentar `[FIX 2026-08-06]` di source code.*
