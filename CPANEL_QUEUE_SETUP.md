# Queue Worker Setup di cPanel (Ubuntu)

## Overview

cPanel dengan Ubuntu memudahkan setup queue worker via **Cron Jobs**. Ini adalah method paling recommended untuk cPanel karena simple dan sudah built-in.

---

## Setup via cPanel UI (Paling Simple) ⭐

### Step 1: Login cPanel

1. Buka `https://your-domain.com:2083` atau panel URL yang Anda punya
2. Masuk dengan username & password

### Step 2: Buka Cron Jobs

Navigasi ke: **Home → Advanced → Cron Jobs**

### Step 3: Add New Cron Job

Di bagian **"Add New Cron Job"**, isi:

**Common Settings:**
```
Minute:     *
Hour:       *
Day:        *
Month:      *
Weekday:    *
```

**Command:**
```bash
cd /home/username/public_html && /usr/bin/php -d register_argc_argv=On artisan queue:work database --stop-when-empty >> /dev/null 2>&1
```

**Ganti:**
- `username` = cPanel username Anda
- `public_html` = folder aplikasi (atau `public_html/subfolder` jika di subfolder)

### Step 4: Save

Klik **"Add New Cron Job"**

### Result

✅ Cron akan berjalan **setiap menit**
✅ Queue worker akan process jobs otomatis
✅ `--stop-when-empty` = hemat resources (stop jika tidak ada job)

---

## Setup via Terminal (SSH) 

Jika lebih comfortable dengan terminal:

### Step 1: Login SSH

```bash
ssh username@your-domain.com
# Or via IP: ssh username@xxx.xxx.xxx.xxx
# Port biasanya 22 (atau yang Anda configure)
```

### Step 2: Edit Crontab

```bash
crontab -e
```

Ini akan buka editor (vim/nano). Pilih editor jika ditanya.

### Step 3: Add Cron Job

Tambahkan line ini di bawah:

```bash
* * * * * cd /home/username/public_html && /usr/bin/php -d register_argc_argv=On artisan queue:work database --stop-when-empty >> /dev/null 2>&1
```

**Atau dengan logging** (rekomendasi untuk monitoring):

```bash
* * * * * cd /home/username/public_html && /usr/bin/php -d register_argc_argv=On artisan queue:work database --stop-when-empty >> /home/username/public_html/storage/logs/cron-queue.log 2>&1
```

### Step 4: Save & Exit

- **Vim**: Press `ESC` → type `:wq` → Enter
- **Nano**: Press `CTRL+X` → `Y` → Enter

✅ Cron setup selesai!

---

## Verify Setup

### Check Cron is Running

Via cPanel UI:
1. **Home → Advanced → Cron Jobs**
2. Cek list ada command Anda

Via SSH:
```bash
crontab -l
# Akan list semua cron jobs
```

### Monitor Queue Processing

#### Option 1: Check Log File

```bash
# Real-time monitoring
tail -f /home/username/public_html/storage/logs/cron-queue.log
```

#### Option 2: Check Database

Login ke phpMyAdmin atau MySQL:

```sql
-- Lihat pending jobs
SELECT COUNT(*) as pending_jobs FROM jobs WHERE failed_at IS NULL;

-- Lihat failed jobs
SELECT COUNT(*) as failed_jobs FROM jobs WHERE failed_at IS NOT NULL;

-- Lihat recent jobs
SELECT id, queue, created_at, failed_at FROM jobs ORDER BY created_at DESC LIMIT 10;
```

#### Option 3: Test Manually

SSH ke server:

```bash
cd /home/username/public_html

# Jalankan queue worker sekali
php artisan queue:work database --stop-when-empty

# Output akan show job diproses
```

---

## Production Configuration

Edit `.env` di root aplikasi Anda:

```
APP_ENV=production
APP_DEBUG=false

# Queue
QUEUE_CONNECTION=database

# Log
LOG_CHANNEL=stack
LOG_LEVEL=error
```

Pastikan sudah jalankan migrations untuk create `jobs` table:

```bash
ssh username@your-domain.com
cd /home/username/public_html
php artisan queue:table
php artisan migrate
```

---

## Testing Queue

### Import Test Karyawan

1. Login admin dashboard
2. Buka **Admin → Karyawan → Import**
3. Upload file Excel dengan 5 karyawan test
4. Klik Import

### Monitor Queue Processing

Buka terminal/SSH dan watch logs:

```bash
tail -f /home/username/public_html/storage/logs/cron-queue.log
```

### Expected Output

```
[2026-02-04 10:05:00] Processing SendWelcomeNotificationJob...
[2026-02-04 10:05:05] Job completed successfully
[2026-02-04 10:05:05] Processing SendWelcomeNotificationJob...
[2026-02-04 10:05:10] Job completed successfully
```

### Check WhatsApp Notification

- Seharusnya WhatsApp diterima di nomor employee

Jika tidak diterima, check logs untuk error.

---

## Troubleshooting cPanel

### Issue 1: PHP Path Error

**Error:** `php: command not found`

**Fix:**

Cari path PHP yang benar:

```bash
which php
# Hasilnya misalnya: /usr/bin/php
# Atau: /usr/local/bin/php
# Atau di cPanel: /opt/alt/php82/usr/bin/php (jika multi-PHP)
```

Ganti command di cron dengan path yang tepat.

### Issue 2: Jobs Not Processing

**Check 1: Cron actually running**

```bash
# Check last execution
tail -f /var/log/syslog | grep CRON
```

**Check 2: Queue table exists**

```bash
# SSH ke database
cd /home/username/public_html
php artisan tinker
>>> DB::table('jobs')->count()
# Harusnya return number atau table exists
```

**Check 3: Permission issues**

```bash
# Check folder permissions
ls -la /home/username/public_html/storage/
# Harusnya readable/writable oleh web user

# Fix permissions jika perlu
chmod -R 755 /home/username/public_html/storage/
chmod -R 755 /home/username/public_html/bootstrap/
```

### Issue 3: WhatsApp Notification Not Sent

Check WhatsApp settings di database:

```sql
SELECT * FROM whatsapp_settings WHERE id = 1;
```

Pastikan:
- `is_enabled` = 1 (true)
- `notify_welcome` = 1 (true)
- `api_key` filled
- `sender` filled
- `welcome_template` not empty

---

## Advanced: Supervisor Alternative

Jika cron tidak cukup reliable, bisa pakai Supervisor di cPanel:

### Install Supervisor (SSH)

```bash
sudo apt-get update
sudo apt-get install supervisor
```

### Create Config

```bash
sudo nano /etc/supervisor/conf.d/absensi-queue.conf
```

Add:

```ini
[program:absensi-queue]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php /home/username/public_html/artisan queue:work database --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/absensi-queue.log
stopwaitsecs=60
user=nobody
```

### Start Supervisor

```bash
sudo service supervisor restart
sudo supervisorctl status
```

**Keuntungan:**
- More reliable than cron
- Always running (no 1 minute delay)
- Better for high volume

**Kekurangan:**
- Perlu SSH access
- Sedikit lebih complex

---

## Performance Tips for cPanel

### Tip 1: Use Multiple PHP Versions

cPanel usually support multiple PHP versions. Use latest:

```bash
# Check available PHP versions
/opt/alt/php82/usr/bin/php -v
/opt/alt/php81/usr/bin/php -v

# Use latest in cron (PHP 8.2+)
/opt/alt/php82/usr/bin/php artisan queue:work ...
```

### Tip 2: Optimize for Shared Hosting

Jika shared hosting dengan resources terbatas:

```bash
# In cron command, add memory limit
php -d memory_limit=256M artisan queue:work database --stop-when-empty
```

### Tip 3: Monitor Disk Usage

Queue jobs bisa pake disk space:

```bash
# Check disk usage
du -sh /home/username/public_html/

# Check database size
mysql -u username -p database_name -e "SELECT SUM(data_length + index_length) FROM information_schema.tables WHERE table_schema = 'database_name';"

# Clean old logs
rm /home/username/public_html/storage/logs/laravel*.log
```

### Tip 4: Database Maintenance

Jika jobs table terlalu besar, clean it:

```bash
# SSH ke app
cd /home/username/public_html
php artisan tinker

# Clear all jobs
>>> DB::table('jobs')->truncate();
>>> DB::table('failed_jobs')->truncate();
```

---

## Monitoring Checklist

Setup monitoring dengan checklist ini:

- [ ] Cron job visible di cPanel → Cron Jobs
- [ ] `jobs` table exists di database
- [ ] WhatsApp settings enabled
- [ ] Test import 5 karyawan
- [ ] Check WhatsApp notification terkirim
- [ ] Monitor log file untuk errors
- [ ] Set up log rotation (optional)

---

## Log Rotation (Optional)

Agar log file tidak terlalu besar, setup rotation:

Create file `/etc/logrotate.d/absensi-queue`:

```bash
sudo nano /etc/logrotate.d/absensi-queue
```

Add:

```
/home/username/public_html/storage/logs/cron-queue.log {
    daily
    rotate 7
    compress
    delaycompress
    notifempty
    create 0644 nobody nobody
}
```

This rotates log daily, keeps 7 days, compresses old logs.

---

## Security Considerations

### Tip 1: Protect .env File

Ensure `.env` tidak accessible publicly:

```bash
# Check .env permissions
ls -la /home/username/public_html/.env
# Harusnya: -rw-r--r-- (644) atau lebih restrictive

# Add .htaccess protection (if Apache)
echo "deny from all" > /home/username/public_html/.env.htaccess
```

### Tip 2: Database User Permissions

Queue worker hanya perlu read-write ke `jobs` table, bukan full admin:

```sql
-- Create limited user untuk queue (optional)
CREATE USER 'queue_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON absensi.jobs TO 'queue_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON absensi.failed_jobs TO 'queue_user'@'localhost';
FLUSH PRIVILEGES;
```

### Tip 3: Monitor Failed Jobs

Failed jobs bisa jadi security issue. Monitor regular:

```sql
SELECT COUNT(*) FROM jobs WHERE failed_at IS NOT NULL;
SELECT * FROM jobs WHERE failed_at IS NOT NULL LIMIT 10;
```

---

## Step-by-Step Implementation

### Step 1: Prepare (5 min)

```bash
# SSH ke server
ssh username@your-domain.com
cd /home/username/public_html

# Create queue table
php artisan queue:table
php artisan migrate
```

### Step 2: Configure .env (2 min)

```bash
nano .env
# Change: QUEUE_CONNECTION=database
# Change: APP_ENV=production (if ready)
# Change: LOG_LEVEL=error (if ready)
```

### Step 3: Setup Cron (3 min)

Via cPanel UI atau terminal:
```bash
crontab -e
# Add: * * * * * cd /home/username/public_html && /usr/bin/php artisan queue:work database --stop-when-empty >> /dev/null 2>&1
```

### Step 4: Verify (5 min)

```bash
# Check cron exists
crontab -l

# Check queue table
php artisan tinker
>>> DB::table('jobs')->count()

# Test import
# Login admin → import 5 karyawan
# Check WhatsApp notification
```

### Step 5: Monitor (ongoing)

```bash
# Watch logs
tail -f storage/logs/laravel.log

# Check queue processing
php artisan queue:monitor
```

---

## Estimation

| Task | Time | Difficulty |
|------|------|-----------|
| Create queue table | 2 min | Easy |
| Configure .env | 2 min | Easy |
| Setup cron | 3 min | Easy |
| Test import | 5 min | Easy |
| **Total** | **12 min** | **Very Easy** |

---

## Support & Monitoring

### Check Status Anytime

```bash
# SSH commands
cd /home/username/public_html

# List pending jobs
php artisan queue:monitor

# See failed jobs
php artisan queue:failed

# Test one job manually
php artisan queue:work database --once
```

### Auto Alert Setup (Advanced)

Setup email alert jika ada failed jobs:

Create script `/home/username/public_html/scripts/check-queue.php`:

```php
<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

$failed = DB::table('jobs')->where('failed_at', '!=', null)->count();

if ($failed > 0) {
    mail('admin@your-domain.com', 'Queue Failed Jobs Alert', "Found $failed failed jobs in queue");
}
```

Add to cron (run every hour):

```bash
0 * * * * php /home/username/public_html/scripts/check-queue.php
```

---

## Quick Reference Commands

```bash
# SSH login
ssh username@your-domain.com

# Check cron list
crontab -l

# Edit cron
crontab -e

# Check queue status
cd /home/username/public_html && php artisan queue:monitor

# Process queue manually (debug)
php artisan queue:work database --once

# Clear failed jobs
php artisan queue:flush

# View logs
tail -f storage/logs/laravel.log

# Database check
php artisan tinker
>>> DB::table('jobs')->count()
>>> exit
```

---

## Summary

**Untuk cPanel + Ubuntu:**

1. **Buat queue table**: `php artisan queue:table && php artisan migrate`
2. **Set .env**: `QUEUE_CONNECTION=database`
3. **Add Cron** (via cPanel UI atau SSH):
   ```
   * * * * * cd /home/username/public_html && /usr/bin/php artisan queue:work database --stop-when-empty >> /dev/null 2>&1
   ```
4. **Test**: Import karyawan, check WhatsApp notification
5. **Monitor**: Watch logs dengan `tail -f storage/logs/laravel.log`

**Selesai!** Queue worker siap production. 🚀

---

**Need help?** Tanya apa yang mau di-setup:
- [ ] Exact cPanel username & domain?
- [ ] Exact path aplikasi di server?
- [ ] SSH access working?

Saya bisa kasih exact commands untuk setup Anda!
