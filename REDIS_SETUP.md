# Redis Setup — SistemAbsensi PT Mingda
> **Dibuat:** 2026-08-06  
> **Tujuan:** Menggantikan MySQL sebagai backend Cache, Queue, dan Session untuk mengurangi overload DB saat jam selesai kerja (17:00–19:00 WIB)

---

## 🔴 Kenapa Redis?

| Driver Lama | Masalah | Redis Solusi |
|-------------|---------|--------------|
| `CACHE_STORE=database` | Setiap `Cache::remember()` baca/tulis ke tabel `cache` = +2 query MySQL | Redis in-memory, 0 query MySQL |
| `QUEUE_CONNECTION=database` | Worker poll tabel `jobs` setiap detik = beban konstan ke MySQL | Redis push/pop, tidak ada polling |
| `SESSION_DRIVER=database` | Setiap request baca/tulis tabel `sessions` = +2 query MySQL | Redis in-memory |

**Estimasi pengurangan query MySQL saat peak hour:**
- 50 user aktif × 2 query session = **−100 queries/menit**
- Queue worker polling = **−60 queries/menit**  
- Cache summary attendance = **−300 queries/menit** (sebelumnya 7 query per request)

---

## ⚙️ Instalasi Redis

### Opsi A — Server Linux (Ubuntu/Debian)
```bash
sudo apt update
sudo apt install redis-server -y
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Test
redis-cli ping
# Output: PONG
```

### Opsi B — Windows (Development)
Download dari: https://github.com/tporadowski/redis/releases  
Install sebagai Windows Service:
```cmd
redis-server --service-install
redis-server --service-start
```

### Opsi C — Docker
```bash
docker run -d --name redis-absensi \
  -p 6379:6379 \
  --restart unless-stopped \
  redis:7-alpine
```

### Opsi D — cPanel/Shared Hosting
Jika hosting mendukung Redis, aktifkan melalui panel Redis Manager.  
Dapatkan `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD` dari panel.

---

## 🔌 PHP Extension

Project ini menggunakan `phpredis` (bukan `predis`).  
Cek apakah extension sudah aktif:

```bash
php -m | grep redis
# Jika tidak muncul, install:
```

**Ubuntu/Debian:**
```bash
sudo apt install php-redis
sudo systemctl restart php8.2-fpm   # sesuaikan versi PHP
```

**cPanel/WHM:**
Aktifkan `redis` di `WHM → PHP Extensions` atau minta ke hosting provider.

---

## 🔧 Konfigurasi .env

Ubah nilai berikut di file `.env`:

```ini
# ============================================
# REDIS — Ganti dari database ke Redis
# ============================================

# Cache: dari 'database' → 'redis'
CACHE_STORE=redis

# Queue: dari 'database' → 'redis'  
QUEUE_CONNECTION=redis

# Session: dari 'database' → 'redis'
SESSION_DRIVER=redis

# ============================================
# Redis Connection
# ============================================
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# DB 0 = default (queue, locks)
REDIS_DB=0

# DB 1 = cache (terpisah dari queue)
REDIS_CACHE_DB=1

# Prefix agar tidak tabrakan jika sharing Redis instance
REDIS_PREFIX=absensi-database-
```

> **Jika Redis pakai password:**
> ```ini
> REDIS_PASSWORD=password_redis_anda
> ```

---

## 🚀 Setelah Konfigurasi .env

```bash
# 1. Clear semua cache lama (dari database)
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# 2. Restart queue worker (penting! worker lama masih baca database queue)
php artisan queue:restart

# 3. Verifikasi Redis terhubung
php artisan tinker
>>> Cache::put('test', 'ok', 60);
>>> Cache::get('test');   // → "ok"
>>> exit
```

---

## 📦 Redis Database Layout

| Redis DB | Kegunaan | TTL |
|----------|----------|-----|
| DB 0 | Queue jobs, locks, default | Permanen (sampai diproses) |
| DB 1 | Cache aplikasi (summary, overtime) | 5–10 menit |
| DB 0 | Session user | 120 menit (sesuai SESSION_LIFETIME) |

---

## 🔑 Cache Keys yang Digunakan

| Key Pattern | Lokasi | TTL | Keterangan |
|-------------|--------|-----|------------|
| `attendance_summary:{employeeId}:{year}:{month}` | `AttendanceController::summary()` | 300 detik | Di-invalidate saat data absensi berubah |
| `overtime_weekly:{employeeId}:{weekStart}` | `OvertimeCalculator::getWeeklyUsedOvertimeMinutes()` | 600 detik | Fallback N+1 guard |

**Invalidation otomatis:**  
Saat record attendance di-create/update/delete, `AttendanceObserver` otomatis menghapus cache `attendance_summary:*` yang relevan.

---

## 📊 Monitoring Redis

```bash
# Real-time stats
redis-cli monitor

# Info memory
redis-cli info memory

# Lihat semua key cache absensi
redis-cli keys "absensi-*"

# Cek berapa key tersimpan
redis-cli dbsize

# Flush cache DB 1 (jika perlu reset)
redis-cli -n 1 flushdb
```

---

## 🔒 Keamanan Redis (Production)

```ini
# /etc/redis/redis.conf

# Bind hanya ke localhost (jangan expose ke public)
bind 127.0.0.1

# Set password wajib di production
requirepass password_kuat_anda

# Nonaktifkan command berbahaya
rename-command FLUSHALL ""
rename-command FLUSHDB  ""
rename-command CONFIG   ""
```

---

## ⚡ Laravel Horizon (Opsional — Monitoring Queue)

Jika ingin monitoring queue real-time + auto-scaling worker:

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan horizon:publish

# Jalankan Horizon (ganti queue:work)
php artisan horizon
```

Dashboard tersedia di: `http://your-domain.com/horizon`

---

## 🐛 Troubleshooting

| Error | Solusi |
|-------|--------|
| `Connection refused 127.0.0.1:6379` | Redis belum jalan: `sudo systemctl start redis` |
| `Class Redis not found` | Extension phpredis belum aktif, install `php-redis` |
| `WRONGTYPE Operation` | Prefix Redis tabrakan, ubah `REDIS_PREFIX` di .env |
| Queue worker tidak berjalan | Jalankan: `php artisan queue:work redis` |
| Cache tidak ter-invalidate | Pastikan `AttendanceObserver` terdaftar di `AppServiceProvider` |

---

*Dokumen ini dibuat saat audit MySQL overload 2026-08-06. Lihat juga: `MYSQL_LOAD_OPTIMIZATION.md`*
