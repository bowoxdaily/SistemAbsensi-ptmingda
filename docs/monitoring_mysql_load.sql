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
