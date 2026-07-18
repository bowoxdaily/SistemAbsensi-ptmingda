-- Query monitoring beban overnight recalculation
-- Jalankan di MySQL/phpMyAdmin untuk analisis

-- 1. Hitung jumlah attendance yang perlu di-recalculate setiap hari (target query)
SELECT
    attendance_date,
    COUNT(*) as total_records,
    COUNT(CASE WHEN status IN ('hadir', 'terlambat') AND check_out IS NOT NULL THEN 1 END) as records_to_process
FROM attendances
WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY attendance_date
ORDER BY attendance_date DESC;

-- 2. Rata-rata attendance per hari dalam sebulan terakhir
SELECT
    AVG(daily_count) as avg_daily_attendance,
    MAX(daily_count) as peak_daily_attendance,
    MIN(daily_count) as min_daily_attendance
FROM (
    SELECT
        attendance_date,
        COUNT(*) as daily_count
    FROM attendances
    WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        AND status IN ('hadir', 'terlambat')
        AND check_out IS NOT NULL
    GROUP BY attendance_date
) daily_stats;

-- 3. Cek index yang ada di tabel attendances
SHOW INDEX FROM attendances;

-- 4. Estimasi waktu eksekusi (slow query log)
-- Aktifkan di my.cnf/my.ini:
-- slow_query_log = 1
-- long_query_time = 2  # Query > 2 detik akan dicatat
-- slow_query_log_file = /path/to/slow-query.log

-- 5. Monitor real-time query yang sedang berjalan
SELECT
    id,
    USER,
    HOST,
    DB,
    COMMAND,
    TIME,
    STATE,
    SUBSTRING(INFO, 1, 100) as QUERY_PREVIEW
FROM information_schema.PROCESSLIST
WHERE COMMAND != 'Sleep'
ORDER BY TIME DESC;

-- 6. Snapshot query yang paling sering jalan di jam 17:30-18:30
-- (aktifkan performance_schema jika belum aktif)
SELECT
    DIGEST_TEXT,
    COUNT_STAR,
    ROUND(SUM_TIMER_WAIT / 1000000000000, 2) AS total_seconds,
    ROUND(AVG_TIMER_WAIT / 1000000000000, 4) AS avg_seconds
FROM performance_schema.events_statements_summary_by_digest
WHERE DIGEST_TEXT IS NOT NULL
ORDER BY SUM_TIMER_WAIT DESC
LIMIT 20;

-- 7. Cek ukuran data Fingerspot yang masuk per jam
SELECT
    DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') AS hour_bucket,
    COUNT(*) AS total_logs,
    COUNT(CASE WHEN process_status = 'success' THEN 1 END) AS success_logs,
    COUNT(CASE WHEN process_status = 'failed' THEN 1 END) AS failed_logs,
    COUNT(CASE WHEN process_status = 'skipped' THEN 1 END) AS skipped_logs
FROM fingerspot_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)
GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')
ORDER BY hour_bucket DESC;

-- 8. Cek duplikasi scan (indikasi API kirim ulang data lama)
SELECT
    pin,
    scan_time,
    COUNT(*) AS duplicate_count
FROM fingerspot_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)
GROUP BY pin, scan_time
HAVING COUNT(*) > 1
ORDER BY duplicate_count DESC
LIMIT 50;

-- Catatan operasi production (setelah deploy patch terbaru):
-- - Auto sync sekarang default pakai date filter (hari ini) untuk menekan load.
-- - Override manual jika perlu:
--   php artisan fingerspot:sync --days-back=1
--   php artisan fingerspot:sync --sync-date=2026-07-17
--   php artisan fingerspot:sync --no-date-filter   (hanya untuk backfill terbatas)
