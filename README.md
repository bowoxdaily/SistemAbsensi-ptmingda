# 📋 Sistem Absensi Karyawan

Aplikasi manajemen absensi karyawan berbasis web yang dibangun dengan Laravel 11. Sistem ini menyediakan fitur lengkap untuk pengelolaan data karyawan, absensi, dan pelaporan dengan visualisasi grafik interaktif.

---

## 📑 Daftar Isi

1. [Fitur Utama](#-fitur-utama)
2. [Teknologi yang Digunakan](#-teknologi-yang-digunakan)
3. [Persyaratan Sistem](#-persyaratan-sistem)
4. [Cara Instalasi](#-cara-instalasi)
5. [Konfigurasi](#-konfigurasi)
6. [Fitur-Fitur Detail](#-fitur-fitur-detail)
7. [Scheduled Tasks](#-scheduled-tasks)
8. [Dokumentasi](#-dokumentasi)
9. [Troubleshooting](#-troubleshooting)
10. [Lisensi](#-lisensi)

---

## ✨ Fitur Utama

### 👥 Manajemen Karyawan

-   ✅ CRUD Karyawan (Create, Read, Update, Delete)
-   ✅ Import data karyawan dari Excel (.xlsx, .xls, .csv)
-   ✅ Export data karyawan ke Excel dengan formatting
-   ✅ Template Excel untuk import data
-   ✅ Validasi data saat import
-   ✅ Pencarian dan filter karyawan

### 🏢 Manajemen Departemen & Posisi

-   ✅ CRUD Departemen
-   ✅ CRUD Posisi/Jabatan
-   ✅ Relasi karyawan dengan departemen dan posisi

### ⏰ Sistem Absensi

-   ✅ Deteksi wajah untuk absensi masuk/pulang
-   ✅ Pencatatan waktu check-in dan check-out
-   ✅ Perhitungan otomatis keterlambatan
-   ✅ Status absensi: Hadir, Terlambat, Izin, Sakit, Alpha
-   ✅ **Auto-generate Alpha**: Sistem otomatis menandai karyawan yang tidak absen sebagai alpha
-   ✅ Filter absensi (tanggal, status, departemen)
-   ✅ Detail absensi dengan foto bukti

### 📊 Rekap & Laporan

-   ✅ Dashboard laporan dengan grafik interaktif (Chart.js)
-   ✅ Grafik tren absensi harian (Line Chart)
-   ✅ Grafik distribusi status absensi (Doughnut Chart)
-   ✅ Statistik kehadiran dengan persentase
-   ✅ Top 10 karyawan yang sering terlambat
-   ✅ Export laporan ke Excel
-   ✅ Filter laporan per bulan, tahun, dan departemen

### 🤖 Automasi

-   ✅ Scheduler untuk generate alpha otomatis (setiap hari pukul 23:59)
-   ✅ Deteksi hari kerja berdasarkan jadwal shift
-   ✅ Skip otomatis untuk weekend
-   ✅ Pencegahan duplikasi data

---

## 🛠 Teknologi yang Digunakan

-   **Backend**: Laravel 11
-   **Frontend**: Bootstrap 5 (Sneat Admin Template)
-   **Database**: MySQL
-   **Charting**: Chart.js v4.4.0
-   **Export/Import**: Maatwebsite/Laravel-Excel
-   **Date Handling**: Carbon
-   **JavaScript**: jQuery, AJAX
-   **Icons**: Bootstrap Icons

---

## 📋 Persyaratan Sistem

-   PHP >= 8.2
-   Composer
-   MySQL >= 5.7 atau MariaDB >= 10.3
-   Node.js & NPM (untuk compile assets)
-   Web Server (Apache/Nginx)

**Extension PHP yang Diperlukan:**

-   OpenSSL
-   PDO
-   Mbstring
-   Tokenizer
-   XML
-   Ctype
-   JSON
-   BCMath
-   Fileinfo
-   GD (untuk manipulasi gambar)
-   ZipArchive (untuk Excel)

---

## 🚀 Cara Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/bowoxdaily/SistemAbsensiKaryawan.git
cd SistemAbsensiKaryawan
```

### 2. Install Dependencies

**Install PHP Dependencies:**

```bash
composer install
```

**Install Node Dependencies (opsional, jika ingin compile assets):**

```bash
npm install
```

### 3. Konfigurasi Environment

**Copy file `.env.example` menjadi `.env`:**

```bash
# Windows (PowerShell)
Copy-Item .env.example .env

# Linux/Mac
cp .env.example .env
```

**Generate Application Key:**

```bash
php artisan key:generate
```

**Edit file `.env` sesuai konfigurasi database Anda:**

```env
APP_NAME="Sistem Absensi Karyawan"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database_anda
DB_USERNAME=username_anda
DB_PASSWORD=password_anda
```

### 4. Setup Database

**Buat database baru:**

```sql
CREATE DATABASE nama_database_anda CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Jalankan migration:**

```bash
php artisan migrate
```

**Jalankan seeder (opsional, untuk data dummy):**

```bash
php artisan db:seed
```

### 5. Setup Storage

**Buat symbolic link untuk storage:**

```bash
php artisan storage:link
```

**Set permission folder (Linux/Mac):**

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 6. Compile Assets (Opsional)

```bash
# Development
npm run dev

# Production
npm run build
```

### 7. Jalankan Aplikasi

**Development Server:**

```bash
php artisan serve
```

Akses aplikasi di: `http://localhost:8000`

**Production (dengan Apache/Nginx):**

-   Arahkan Document Root ke folder `public/`
-   Pastikan file `.htaccess` ada di folder `public/`

---

## ⚙️ Konfigurasi

### 1. Setup Cron Job (PENTING! - Untuk Auto-Generate Alpha)

**📌 Cara Mudah via Dashboard Admin:**

1. Login sebagai Admin
2. Buka menu **Pengaturan → Cron Job**
3. Pilih platform hosting Anda (Linux/Windows)
4. Klik tombol **Copy** untuk copy command
5. Paste ke cPanel/Plesk/Task Scheduler
6. Klik **Check Status** untuk verifikasi

**✅ Fitur Auto-Detect:**

-   System otomatis detect OS, PHP path, dan base path
-   Command sudah siap pakai, tinggal copy-paste
-   Panduan lengkap untuk cPanel, Plesk, VPS, dan Windows
-   Monitor status cron real-time

**📖 Dokumentasi Lengkap:**

-   Quick Start: Lihat file `CRONJOB_QUICKSTART.md`
-   Tutorial Detail: Lihat file `CRONJOB_SETUP.md`

**🔧 Manual Setup (jika tidak pakai dashboard):**

**Linux/Mac (cPanel/Plesk/VPS):**

```bash
# Edit crontab
crontab -e

# Tambahkan baris ini:
* * * * * cd /path/to/SistemAbsensiKaryawan && php artisan schedule:run >> /dev/null 2>&1
```

**Windows (Task Scheduler):**

```powershell
# Buka Task Scheduler, buat task baru dengan:
# Program: C:\Path\To\PHP\php.exe
# Arguments: artisan schedule:run
# Start in: D:\Project\SistemAbsensiKaryawan
# Trigger: Daily, repeat every 1 minute
```

**📊 Scheduled Tasks yang Berjalan:**
| Command | Jadwal | Fungsi |
|---------|--------|--------|
| `attendance:generate-absent` | Setiap jam (08:00-23:59, Senin-Jumat) | Auto-generate absensi alpha setelah melewati jam checkout + 30 menit |

**🔔 Catatan Penting:**

-   Karyawan akan otomatis dianggap **alpha** jika tidak melakukan check-in **30 menit setelah jam checkout**
-   Grace period 30 menit memberikan toleransi untuk karyawan yang lupa absen
-   Command berjalan setiap jam untuk memastikan deteksi real-time
-   Hanya berjalan pada hari kerja (Senin-Jumat) jam 08:00-23:59

### 2. Verifikasi Schedule

Cek jadwal yang terdaftar:

```bash
php artisan schedule:list
```

Output:

```
59 23 * * * php artisan attendance:generate-absent ... Next Due: X hours from now
```

### 3. Manual Generate Alpha (Jika Diperlukan)

Jalankan command manual untuk generate alpha:

```bash
# Generate untuk kemarin (default)
php artisan attendance:generate-absent

# Generate untuk tanggal tertentu
php artisan attendance:generate-absent 2025-10-21

# Lihat help
php artisan attendance:generate-absent --help
```

---

## 🌐 Deployment ke Hosting (Shared/VPS)

### A. Deployment ke Shared Hosting (cPanel/Plesk)

#### 1. Upload File

**Via FTP/File Manager:**

```bash
# Upload semua file KECUALI:
- .env (buat baru di server)
- node_modules/
- .git/
```

**Struktur di hosting:**

```
public_html/          # Root domain
├── public/          # Arahkan Document Root ke sini
├── app/
├── bootstrap/
├── config/
├── database/
├── resources/
├── routes/
├── storage/
└── vendor/
```

#### 2. Setup .env di Server

**Buat file `.env` baru via cPanel File Manager atau FTP:**

```env
APP_NAME="Sistem Absensi Karyawan"
APP_ENV=production
APP_KEY=base64:... # Generate dengan: php artisan key:generate
APP_DEBUG=false    # PENTING: false di production
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=namadatabase_anda
DB_USERNAME=username_anda
DB_PASSWORD=password_anda

# Timezone
APP_TIMEZONE=Asia/Jakarta
```

#### 3. Install Dependencies via SSH (jika ada akses SSH)

```bash
composer install --optimize-autoloader --no-dev
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Jika TIDAK ada SSH:**

-   Upload folder `vendor/` yang sudah di-install di local
-   Jalankan migration via route temporary: `/migrate-setup` (buat controller khusus)

#### 4. Setup Cron Job di cPanel

**Langkah-langkah:**

1. Login ke cPanel
2. Cari menu **Cron Jobs**
3. Pada bagian **Add New Cron Job**, set:
    - **Common Settings**: Custom
    - **Minute**: `*` (setiap menit)
    - **Hour**: `*`
    - **Day**: `*`
    - **Month**: `*`
    - **Weekday**: `*`
    - **Command**:
    ```bash
    /usr/local/bin/php /home/username/public_html/artisan schedule:run >> /dev/null 2>&1
    ```

**Alternatif command (sesuaikan path):**

```bash
# Cara 1: Path absolut
/usr/bin/php /home/cpanelusername/public_html/artisan schedule:run >> /dev/null 2>&1

# Cara 2: Dengan cd
cd /home/cpanelusername/public_html && /usr/bin/php artisan schedule:run >> /dev/null 2>&1

# Cara 3: Dengan log (untuk debug)
cd /home/cpanelusername/public_html && /usr/bin/php artisan schedule:run >> /home/cpanelusername/cron.log 2>&1
```

**Cek path PHP di server:**

```bash
which php
# Output: /usr/local/bin/php atau /usr/bin/php
```

4. Klik **Add New Cron Job**

**Screenshot setup cPanel:**

```
┌─────────────────────────────────────────┐
│ Cron Jobs                               │
├─────────────────────────────────────────┤
│ Common Settings: Custom                 │
│ Minute:  *                              │
│ Hour:    *                              │
│ Day:     *                              │
│ Month:   *                              │
│ Weekday: *                              │
│ Command: /usr/bin/php /home/user/...   │
│ [Add New Cron Job]                      │
└─────────────────────────────────────────┘
```

#### 5. Verifikasi Cron Job Berjalan

**Cek via cron log:**

```bash
# Edit command cron dengan log
cd /home/username/public_html && /usr/bin/php artisan schedule:run >> /home/username/cron.log 2>&1

# Tunggu 1-2 menit, lalu cek file cron.log
cat /home/username/cron.log
```

**Cek via database:**

```sql
-- Cek apakah alpha ter-generate
SELECT * FROM attendances
WHERE notes LIKE '%Auto-generated%'
AND attendance_date >= CURDATE() - INTERVAL 2 DAY
ORDER BY created_at DESC;
```

**Cek via Laravel log:**

```bash
cat /home/username/public_html/storage/logs/laravel.log
```

### B. Deployment ke VPS (Ubuntu/CentOS)

#### 1. Persiapan Server

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install dependencies
sudo apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-zip php8.2-curl php8.2-gd composer nginx mysql-server

# Install Node.js (opsional)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

#### 2. Clone & Setup Project

```bash
# Clone project
cd /var/www
sudo git clone https://github.com/bowoxdaily/SistemAbsensiKaryawan.git
cd SistemAbsensiKaryawan

# Set permission
sudo chown -R www-data:www-data /var/www/SistemAbsensiKaryawan
sudo chmod -R 755 /var/www/SistemAbsensiKaryawan
sudo chmod -R 775 /var/www/SistemAbsensiKaryawan/storage
sudo chmod -R 775 /var/www/SistemAbsensiKaryawan/bootstrap/cache

# Install dependencies
composer install --optimize-autoloader --no-dev

# Setup environment
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 3. Setup Nginx

```bash
sudo nano /etc/nginx/sites-available/absensi
```

**Config Nginx:**

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/SistemAbsensiKaryawan/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**Aktifkan site:**

```bash
sudo ln -s /etc/nginx/sites-available/absensi /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

#### 4. Setup Cron Job di VPS

```bash
# Edit crontab
sudo crontab -e -u www-data

# Tambahkan baris ini:
* * * * * cd /var/www/SistemAbsensiKaryawan && php artisan schedule:run >> /dev/null 2>&1
```

**Atau dengan user biasa:**

```bash
crontab -e

# Tambahkan:
* * * * * cd /var/www/SistemAbsensiKaryawan && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

**Verifikasi cron:**

```bash
# Cek crontab
sudo crontab -l -u www-data

# Monitor cron execution
sudo tail -f /var/log/syslog | grep CRON

# Test manual
cd /var/www/SistemAbsensiKaryawan
sudo -u www-data php artisan schedule:run
```

### C. Deployment dengan Laravel Forge (Recommended)

**Laravel Forge** adalah platform deployment otomatis untuk Laravel:

1. **Signup** di [forge.laravel.com](https://forge.laravel.com)
2. **Connect VPS** (DigitalOcean, AWS, Linode, dll)
3. **Create Site** - pilih repository GitHub
4. **Deploy Script** - otomatis ter-setup
5. **Scheduler** - otomatis ter-setup (tidak perlu cron manual!)

**Kelebihan Forge:**

-   ✅ Cron job otomatis ter-setup
-   ✅ SSL gratis (Let's Encrypt)
-   ✅ Deploy otomatis dari Git
-   ✅ Queue worker management
-   ✅ Database backup
-   ✅ Monitoring

### D. Troubleshooting Cron Job di Hosting

#### Problem 1: Cron tidak jalan

**Cek:**

```bash
# 1. Path PHP benar?
which php
/usr/local/bin/php --version

# 2. Permission folder storage
ls -la storage/
chmod -R 775 storage/

# 3. Cek cron log
cat ~/cron.log

# 4. Test manual
/usr/bin/php /path/to/artisan schedule:run
```

#### Problem 2: Permission denied

```bash
# Set owner
chown -R username:username /home/username/public_html

# Set permission
chmod -R 755 /home/username/public_html
chmod -R 775 storage/ bootstrap/cache/
```

#### Problem 3: Command not found

```bash
# Gunakan path absolut
/usr/local/bin/php /home/user/public_html/artisan schedule:run

# Atau tambahkan PATH
PATH=/usr/local/bin:/usr/bin:/bin
* * * * * cd /home/user/public_html && php artisan schedule:run
```

#### Problem 4: Environment variables tidak load

```bash
# Tambahkan di cron command
* * * * * cd /home/user/public_html && /usr/bin/php artisan schedule:run --env=production >> /dev/null 2>&1
```

### E. Monitoring Cron Job

#### 1. Via Log File

```bash
# Ubah cron command untuk log
* * * * * cd /path/to/project && php artisan schedule:run >> /home/user/cron.log 2>&1

# Lihat log
tail -f /home/user/cron.log
```

#### 2. Via Database Query

```sql
-- Cek alpha generated hari ini
SELECT
    DATE(created_at) as tanggal,
    COUNT(*) as total_alpha,
    GROUP_CONCAT(DISTINCT CONCAT(e.employee_code, ' - ', e.name) SEPARATOR ', ') as karyawan
FROM attendances a
JOIN employees e ON a.employee_id = e.id
WHERE a.status = 'alpha'
AND a.notes LIKE '%Auto-generated%'
AND DATE(a.created_at) >= CURDATE() - INTERVAL 7 DAY
GROUP BY DATE(a.created_at)
ORDER BY tanggal DESC;
```

#### 3. Via Email Notification (Optional)

**Tambahkan di Command:**

```php
// app/Console/Commands/GenerateAbsentAttendance.php
protected function sendEmailNotification($generated, $skipped)
{
    Mail::to('admin@example.com')->send(new AlphaGeneratedReport([
        'date' => $this->date,
        'generated' => $generated,
        'skipped' => $skipped
    ]));
}
```

### F. Alternative: Setup Tanpa Cron (Untuk Shared Hosting Tanpa Cron Access)

Jika hosting tidak support cron, gunakan **external cron service**:

#### 1. Cron-Job.org (Gratis)

1. Daftar di [cron-job.org](https://cron-job.org)
2. Buat cron job baru:

    - **URL**: `https://yourdomain.com/api/run-scheduler`
    - **Schedule**: Every 1 minute
    - **HTTP Method**: GET

3. Buat route & controller:

```php
// routes/web.php
Route::get('/api/run-scheduler', function () {
    Artisan::call('schedule:run');
    return response()->json([
        'status' => 'success',
        'message' => 'Scheduler executed',
        'time' => now()->toDateTimeString()
    ]);
})->middleware('throttle:60,1'); // Max 60 request per menit
```

#### 2. EasyCron.com (Gratis dengan limit)

1. Daftar di [easycron.com](https://www.easycron.com)
2. Setup sama seperti cron-job.org

**⚠️ Security:**

```php
// Tambahkan token untuk keamanan
Route::get('/api/run-scheduler/{token}', function ($token) {
    if ($token !== config('app.scheduler_token')) {
        abort(403);
    }

    Artisan::call('schedule:run');
    return response()->json(['status' => 'success']);
});

// .env
SCHEDULER_TOKEN=your-random-secure-token-here
```

---

## 📖 Fitur-Fitur Detail

### 1. Import Karyawan dari Excel

**Langkah-langkah:**

1. Buka menu **Karyawan** > **Import Data**
2. Download template Excel
3. Isi data karyawan sesuai format template
4. Upload file Excel (.xlsx, .xls, atau .csv)
5. Sistem akan validasi dan import data

**Format Template:**

| employee_code | name     | email            | phone       | department_id | position_id | hire_date  |
| ------------- | -------- | ---------------- | ----------- | ------------- | ----------- | ---------- |
| MIF-001       | John Doe | john@example.com | 08123456789 | 1             | 1           | 2025-01-15 |

**Validasi:**

-   ✅ Employee code harus unik
-   ✅ Email harus valid dan unik
-   ✅ Department dan Position harus exist di database
-   ✅ Format tanggal: Y-m-d

### 2. Export Data ke Excel

**Karyawan:**

-   Buka menu **Karyawan** > klik tombol **Export Excel**
-   File akan didownload dengan format: `karyawan_YYYYMMDD_HHmmss.xlsx`
-   Include: Kode, Nama, Email, Telepon, Departemen, Posisi, Tanggal Masuk

**Absensi:**

-   Buka menu **Absensi** > set filter (opsional) > klik **Export Excel**
-   File akan didownload dengan format: `absensi_YYYYMMDD_HHmmss.xlsx`
-   Include: Tanggal, Kode Karyawan, Nama, Departemen, Check In, Check Out, Status, Keterlambatan

### 3. Absensi Face Detection

**Langkah-langkah:**

1. Buka menu **Absensi** > **Deteksi Wajah**
2. Izinkan akses kamera
3. Posisikan wajah di depan kamera
4. Klik **Check In** (masuk) atau **Check Out** (pulang)
5. Sistem akan capture foto dan simpan data absensi

**Perhitungan Status:**

-   **Hadir**: Check-in sebelum jam shift + toleransi
-   **Terlambat**: Check-in setelah jam shift + toleransi
-   **Izin/Sakit**: Input manual oleh admin
-   **Alpha**: Auto-generate jika tidak ada absensi

### 4. Rekap & Laporan

**Grafik Tren Harian (Line Chart):**

-   Menampilkan tren absensi per hari
-   Warna berbeda untuk setiap status
-   Hover untuk lihat detail
-   Responsive dan interactive

**Grafik Distribusi (Doughnut Chart):**

-   Menampilkan persentase status absensi
-   Hadir (hijau), Terlambat (kuning), Izin (biru), Sakit (orange), Alpha (merah)

**Top 10 Terlambat:**

-   Ranking karyawan dengan total keterlambatan tertinggi
-   Medal badge (🥇🥈🥉) untuk top 3
-   Jumlah frekuensi dan total menit terlambat

**Filter:**

-   Per bulan dan tahun
-   Per departemen
-   Export hasil filter ke Excel

### 5. Auto-Generate Alpha

Sistem otomatis akan menandai karyawan sebagai alpha jika:

-   ✅ Karyawan memiliki jadwal kerja (work_schedule_id)
-   ✅ Hari tersebut adalah hari kerja sesuai shift
-   ✅ Bukan hari weekend (Sabtu/Minggu)
-   ✅ Tidak ada record absensi untuk hari itu

**Waktu Eksekusi:**

-   Setiap hari pukul 23:59
-   Auto-generate untuk data kemarin

**Catatan:**

-   Record alpha akan memiliki notes: "Auto-generated: Tidak melakukan absensi"
-   Dapat di-override manual oleh admin jika diperlukan

---

## ⏱ Scheduled Tasks

Sistem menggunakan Laravel Scheduler untuk menjalankan task otomatis:

| Command                      | Waktu       | Fungsi                                         |
| ---------------------------- | ----------- | ---------------------------------------------- |
| `attendance:generate-absent` | 23:59 daily | Generate alpha untuk karyawan yang tidak absen |

**⚠️ PENTING - Integrasi dengan Sistem Cuti:**

-   Cronjob **TIDAK AKAN** mark karyawan sebagai alpha jika mereka punya cuti/izin yang sudah di-approve
-   Hanya karyawan yang benar-benar tidak masuk tanpa izin yang akan di-mark alpha
-   Detail lengkap: [CRONJOB_LEAVE_INTEGRATION.md](./CRONJOB_LEAVE_INTEGRATION.md)

**Monitoring:**

```bash
# Cek schedule list
php artisan schedule:list

# Test run schedule (tanpa tunggu waktu)
php artisan schedule:run

# Test untuk tanggal spesifik
php artisan attendance:generate-absent 2025-10-25

# Monitor execution logs
tail -f storage/logs/laravel.log
```

---

## 📚 Dokumentasi

Dokumentasi lengkap tersedia di folder root project:

-   **[DATABASE_DOCUMENTATION.md](./DATABASE_DOCUMENTATION.md)**: Dokumentasi struktur database lengkap
-   **[DATABASE_SUMMARY.md](./DATABASE_SUMMARY.md)**: Ringkasan database dan relasi tabel
-   **[DEPARTMENT_CRUD.md](./DEPARTMENT_CRUD.md)**: Dokumentasi CRUD Departemen
-   **[KARYAWAN_CRUD.md](./KARYAWAN_CRUD.md)**: Dokumentasi CRUD Karyawan
-   **[KARYAWAN_IMPORT.md](./KARYAWAN_IMPORT.md)**: Dokumentasi import karyawan dari Excel
-   **[KARYAWAN_EXPORT.md](./KARYAWAN_EXPORT.md)**: Dokumentasi export karyawan ke Excel
-   **[POSITION_CRUD.md](./POSITION_CRUD.md)**: Dokumentasi CRUD Posisi
-   **[LAYOUT_README.md](./LAYOUT_README.md)**: Dokumentasi struktur layout dan template
-   **[SWEETALERT_IMPLEMENTATION.md](./SWEETALERT_IMPLEMENTATION.md)**: Dokumentasi implementasi SweetAlert
-   **[AUTO_ALPHA_ATTENDANCE.md](./AUTO_ALPHA_ATTENDANCE.md)**: Dokumentasi sistem auto-generate alpha
-   **[CRONJOB_LEAVE_INTEGRATION.md](./CRONJOB_LEAVE_INTEGRATION.md)**: 🔥 Integrasi cronjob dengan sistem cuti/izin

---

## 🔧 Troubleshooting

### Problem 1: Migration Error

**Error:** `SQLSTATE[42S01]: Base table or view already exists`

**Solution:**

```bash
# Reset database
php artisan migrate:fresh

# Atau drop manual di MySQL
DROP DATABASE nama_database;
CREATE DATABASE nama_database;
php artisan migrate
```

### Problem 2: Excel Export Tidak Berfungsi

**Error:** `Class 'ZipArchive' not found`

**Solution:**

```bash
# Install PHP zip extension
# Ubuntu/Debian
sudo apt-get install php8.2-zip

# Windows (edit php.ini)
# Uncomment: extension=zip
```

### Problem 3: Storage Link Error

**Error:** `The "public/storage" directory already exists`

**Solution:**

```bash
# Hapus link lama
# Windows
rmdir public\storage

# Linux/Mac
rm public/storage

# Buat link baru
php artisan storage:link
```

### Problem 4: Scheduler Tidak Jalan

**Error:** Alpha tidak ter-generate otomatis

**Solution:**

```bash
# Cek apakah cron job sudah setup
# Linux/Mac
crontab -l

# Windows - cek Task Scheduler

# Test manual
php artisan attendance:generate-absent

# Cek logs
cat storage/logs/laravel.log
```

### Problem 5: Chart Tidak Muncul

**Error:** Chart tidak render di halaman laporan

**Solution:**

```html
<!-- Pastikan Chart.js loaded -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Cek console browser untuk error -->
<!-- F12 > Console -->
```

### Problem 6: Permission Error (Linux)

**Error:** `The stream or file "storage/logs/laravel.log" could not be opened`

**Solution:**

```bash
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

---

## 🧪 Testing

**Query Monitoring Alpha:**

```sql
-- Cek alpha yang ter-generate hari ini
SELECT e.employee_code, e.name, a.attendance_date, a.status, a.notes
FROM attendances a
JOIN employees e ON a.employee_id = e.id
WHERE a.notes LIKE '%Auto-generated%'
AND DATE(a.attendance_date) = CURDATE() - INTERVAL 1 DAY
ORDER BY e.employee_code;

-- Count alpha per bulan
SELECT
    MONTH(attendance_date) as bulan,
    COUNT(*) as total_alpha
FROM attendances
WHERE status = 'alpha'
AND YEAR(attendance_date) = 2025
GROUP BY MONTH(attendance_date)
ORDER BY bulan;
```

**Test Command:**

```bash
# Test untuk tanggal tertentu
php artisan attendance:generate-absent 2025-10-21

# Verifikasi hasil
php artisan tinker
>>> Attendance::where('status', 'alpha')->whereDate('attendance_date', '2025-10-21')->count();
```

---

## 🔐 Security

-   ✅ Password hashing dengan bcrypt
-   ✅ CSRF protection
-   ✅ SQL injection prevention (Eloquent ORM)
-   ✅ XSS protection
-   ✅ Input validation
-   ✅ File upload validation

**Best Practices:**

-   Jangan commit file `.env`
-   Set `APP_DEBUG=false` di production
-   Set strong `APP_KEY`
-   Update dependencies secara berkala
-   Backup database secara rutin

---

## 🤝 Kontribusi

Kontribusi sangat diterima! Silakan:

1. Fork repository ini
2. Buat branch baru (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

---

## 📝 Lisensi

Aplikasi ini menggunakan [MIT License](https://opensource.org/licenses/MIT).

**Laravel Framework** adalah open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
