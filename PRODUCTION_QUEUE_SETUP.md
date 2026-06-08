# Queue Worker Setup untuk Production

## Overview

Di production, Anda perlu menjalankan `queue:work` secara **persistent** agar notification WhatsApp (dan job lainnya) diproses otomatis.

Ada beberapa pilihan setup:

---

## Option 1: Cron Job (Paling Simple) ⭐ RECOMMENDED

### Setup

Edit **crontab**:
```bash
# Login ke server
ssh user@your-server.com

# Edit crontab
crontab -e
```

Tambahkan line:
```bash
* * * * * cd /path/to/app && php artisan queue:work --stop-when-empty >> /dev/null 2>&1
```

**Atau dengan logging:**
```bash
* * * * * cd /path/to/app && php artisan queue:work --stop-when-empty >> /var/log/queue-worker.log 2>&1
```

### Cara Kerja
- Cron **setiap menit** mengecek dan menjalankan queue worker
- `--stop-when-empty` = berhenti saat tidak ada job (hemat resources)
- Cron otomatis spawn worker baru setiap menit
- Job diproses dengan delay yang dijadwalkan

### Keuntungan
✅ Simple, tidak perlu install extra tools
✅ Bawaan Linux/Unix
✅ Hemat memory (stop-when-empty)

### Kekurangan
❌ Delay sampai 1 menit (tergantung cron timing)
❌ Tidak ideal untuk real-time notification

---

## Option 2: Supervisor (Recommended untuk High Volume) ⭐⭐

Supervisor adalah process manager yang keep queue worker always running.

### Install Supervisor

```bash
# Ubuntu/Debian
sudo apt-get install supervisor

# CentOS/RHEL
sudo yum install supervisor
```

### Configure Supervisor

Create config file:
```bash
sudo nano /etc/supervisor/conf.d/absensi-queue.conf
```

Add this:
```ini
[program:absensi-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/app/artisan queue:work database --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/absensi-queue.log
stopwaitsecs=60
```

### Key Settings

| Setting | Value | Purpose |
|---------|-------|---------|
| `command` | `php artisan queue:work ...` | Command to run |
| `numprocs` | `2` | Run 2 workers (concurrency) |
| `autostart` | `true` | Start on boot |
| `autorestart` | `true` | Restart if crash |
| `sleep` | `3` | Check queue every 3 seconds |
| `tries` | `3` | Retry failed jobs 3x |
| `stopwaitsecs` | `60` | Wait 60s before force kill |

### Start Supervisor

```bash
# Reload config
sudo supervisorctl reread
sudo supervisorctl update

# Start worker
sudo supervisorctl start absensi-queue:*

# Check status
sudo supervisorctl status
```

### Monitor

```bash
# See real-time status
sudo supervisorctl tail -f absensi-queue:0 stdout
sudo supervisorctl tail -f absensi-queue:0 stderr

# View logs
tail -f /var/log/absensi-queue.log
```

### Keuntungan
✅ Always running (truly persistent)
✅ Auto-restart on crash
✅ Multiple workers (parallelization)
✅ Better for high volume
✅ Easy to monitor/manage

### Kekurangan
❌ Perlu install extra package
❌ Sedikit lebih complex setup

---

## Option 3: Systemd Service

Untuk Linux dengan systemd (modern approach).

### Create Service File

```bash
sudo nano /etc/systemd/system/absensi-queue.service
```

Add:
```ini
[Unit]
Description=Absensi Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/app
ExecStart=/usr/bin/php artisan queue:work database --sleep=3 --tries=3
Restart=always
RestartSec=10
StandardOutput=append:/var/log/absensi-queue.log
StandardError=append:/var/log/absensi-queue-error.log

[Install]
WantedBy=multi-user.target
```

### Enable & Start

```bash
sudo systemctl enable absensi-queue.service
sudo systemctl start absensi-queue.service
sudo systemctl status absensi-queue.service
```

### Monitor

```bash
# View logs
sudo journalctl -u absensi-queue.service -f

# Restart
sudo systemctl restart absensi-queue.service
```

---

## Option 4: Docker (If Containerized)

Jika app dalam Docker:

### Dockerfile Addition

```dockerfile
# Add queue worker as separate service
FROM php:8.2-fpm

WORKDIR /app

# Copy app
COPY . .

# Install dependencies
RUN composer install

# Command runs queue worker
CMD ["php", "artisan", "queue:work", "database", "--sleep=3"]
```

### Docker Compose

```yaml
version: '3.8'

services:
  app:
    image: absensi-app
    container_name: absensi-app
    # ... other config

  queue-worker:
    image: absensi-app
    container_name: absensi-queue-worker
    command: php artisan queue:work database --sleep=3 --tries=3
    depends_on:
      - app
    restart: always
    environment:
      - DB_HOST=mysql
      - DB_DATABASE=${DB_DATABASE}
      - DB_USERNAME=${DB_USERNAME}
      - DB_PASSWORD=${DB_PASSWORD}
    volumes:
      - .:/app
```

---

## Recommended Setup by Use Case

### Case 1: Small App (< 100 employees)
**Use: Cron Job (Option 1)**
- Simple, minimal overhead
- Delay 1 menit acceptable
- Cost efficient

### Case 2: Medium App (100 - 1000 employees)
**Use: Supervisor with 2-4 workers (Option 2)**
- Better reliability
- Multiple concurrent workers
- Better performance

### Case 3: Large App (> 1000 employees)
**Use: Supervisor with 4+ workers OR Kubernetes (Option 2+)**
- Multiple workers
- Load balancing
- Auto-scaling

### Case 4: Containerized/Cloud
**Use: Docker/Kubernetes (Option 4)**
- Auto-scaling
- Self-healing
- Easy deployment

---

## Production Checklist

### Before Deploy

- [ ] `.env` set to `QUEUE_CONNECTION=database`
- [ ] Queue table created: `php artisan queue:table && php artisan migrate`
- [ ] WhatsApp settings configured & enabled
- [ ] Welcome notification enabled
- [ ] Employee phone numbers filled in
- [ ] Test job dispatched: `php artisan tinker` → `SendWelcomeNotificationJob::dispatch(...)`

### During Deployment

- [ ] Deploy code
- [ ] Run migrations: `php artisan migrate`
- [ ] Setup queue worker (cron/supervisor/systemd)
- [ ] Test import karyawan → check notification received

### After Deployment

- [ ] Monitor queue: `php artisan queue:monitor`
- [ ] Check logs regularly
- [ ] Setup alerts for failed jobs
- [ ] Monitor database `jobs` table size

---

## Database Queue Table

First time setup, create queue table:

```bash
php artisan queue:table
php artisan migrate
```

This creates `jobs` table:
```sql
CREATE TABLE jobs (
    id bigint unsigned auto_increment primary key,
    queue varchar(255),
    payload longtext,
    exceptions longtext,
    failed_at timestamp null,
    created_at timestamp,
    updated_at timestamp
);
```

---

## Monitoring & Maintenance

### Check Queue Status

```bash
# Count pending jobs
SELECT COUNT(*) as pending FROM jobs WHERE failed_at IS NULL;

# Count failed jobs
SELECT COUNT(*) as failed FROM jobs WHERE failed_at IS NOT NULL;

# See oldest job (if stuck)
SELECT id, queue, created_at FROM jobs ORDER BY created_at ASC LIMIT 1;
```

### Clear Failed Jobs (after investigation)

```bash
# Via Artisan
php artisan queue:failed
php artisan queue:forget {id}
php artisan queue:flush

# Or SQL
DELETE FROM jobs WHERE failed_at IS NOT NULL;
```

### Retry Failed Jobs

```bash
# Retry specific job
php artisan queue:retry {id}

# Retry all failed
php artisan queue:retry all
```

### Monitor Command

```bash
# Watch queue in real-time
php artisan queue:monitor
```

---

## Troubleshooting Production

### Issue 1: Jobs Not Processing

**Check 1: Queue worker running?**
```bash
# For cron
* * * * * ps aux | grep "queue:work" | grep -v grep

# For supervisor
sudo supervisorctl status

# For systemd
sudo systemctl status absensi-queue.service
```

**Check 2: Queue connection correct?**
```bash
# .env
QUEUE_CONNECTION=database

# Check jobs table
SELECT COUNT(*) FROM jobs;
```

**Check 3: Logs**
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Queue logs (if setup)
tail -f /var/log/queue-worker.log
```

### Issue 2: Jobs Stuck

**Cause: Memory leak or process crash**

Solution:
```bash
# For supervisor - auto restart
sudo supervisorctl restart absensi-queue:*

# For cron - will auto-restart next minute

# For systemd
sudo systemctl restart absensi-queue.service
```

### Issue 3: Slow Processing

**Increase workers (Supervisor only):**
```ini
[program:absensi-queue]
numprocs=4  # Increase from 2 to 4
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
```

### Issue 4: High Memory Usage

**Reduce workers or add memory limit:**
```bash
# Option A: Fewer workers
numprocs=1

# Option B: Add memory limit
php --memory-limit=256M artisan queue:work ...

# Option C: Daemon mode with restart
php artisan queue:work --max-jobs=1000
# Restart worker after 1000 jobs processed
```

---

## Performance Tuning

### For High Volume

```bash
# Increase concurrency
numprocs=8

# Faster polling
php artisan queue:work database --sleep=1

# Process multiple jobs at once
php artisan queue:work database --sleep=1 --max-jobs=100

# Set timeout
php artisan queue:work database --timeout=300
```

### Environment Variables

Add to `.env`:

```
# Queue
QUEUE_CONNECTION=database
QUEUE_FAILED_TABLE=failed_jobs

# Job settings
QUEUE_SLEEP=3
QUEUE_MAX_TRIES=3
QUEUE_TIMEOUT=300

# WhatsApp retry settings
WHATSAPP_RETRY_TIMES=3
WHATSAPP_RETRY_DELAY=60
```

---

## Scale Horizontally

Jika satu server tidak cukup, jalankan queue worker di multiple servers:

```
Server 1: php artisan queue:work database
Server 2: php artisan queue:work database
Server 3: php artisan queue:work database
          ↓
          Shared MySQL Queue DB
```

Semua workers share satu database, so jobs didistribusikan otomatis.

---

## Cost Comparison

| Option | Cost | Complexity | Performance | Notes |
|--------|------|-----------|-------------|-------|
| Cron | $0 | Low | Fair | 1 min delay |
| Supervisor | $0 | Medium | Good | Always running |
| Systemd | $0 | Medium | Good | Modern approach |
| Docker | $5-50/mo | Medium | Excellent | Cloud-native |
| Kubernetes | $20-100+/mo | High | Excellent | Enterprise |

---

## Summary: Recommended for You

Untuk production **Sistem Absensi**, recommend:

### Step 1: Production Setup (Now)
**Use Supervisor** (Option 2)
```bash
sudo apt-get install supervisor
# Follow supervisor setup above
```

### Step 2: Configuration
Edit `.env`:
```
QUEUE_CONNECTION=database
```

Create `queue:work` config in supervisor with:
- 2-4 workers (`numprocs=2`)
- 3 second polling (`--sleep=3`)
- Retry 3x (`--tries=3`)

### Step 3: Monitor
```bash
sudo supervisorctl status
tail -f /var/log/absensi-queue.log
```

### Step 4: Test
- Import 10 karyawan
- Check WhatsApp notification terkirim
- Monitor logs untuk errors

---

**Butuh bantuan setup? Beri tahu:**
1. Server OS (Ubuntu/CentOS/etc)
2. Hosting provider (VPS/Dedicated/Cloud)
3. Berapa banyak karyawan (scale)?

Saya bisa kasih step-by-step commands untuk production Anda! 🚀
