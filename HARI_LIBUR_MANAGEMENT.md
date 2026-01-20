# Fitur Pengaturan Hari Libur - Sistem Absensi

## Overview

Fitur pengaturan hari libur memungkinkan admin untuk mendefinisikan tanggal-tanggal libur sehingga karyawan yang tidak melakukan absensi pada hari tersebut **tidak akan dianggap alpha** oleh sistem auto-generate.

## Fitur Utama

### 1. **Management Hari Libur**
- Tambah, edit, hapus hari libur
- Filter berdasarkan tahun dan bulan
- Tiga jenis hari libur:
  - **Libur Nasional** - Hari libur resmi pemerintah
  - **Cuti Bersama** - Cuti bersama yang ditetapkan pemerintah
  - **Custom** - Hari libur khusus perusahaan

### 2. **Import Hari Libur Nasional**
- Import otomatis hari libur nasional Indonesia untuk tahun tertentu
- Data sudah dikonfigurasi untuk tahun 2026
- Mudah ditambahkan untuk tahun lain dengan update di HolidayController

### 3. **Status Aktif/Nonaktif**
- Toggle status hari libur tanpa menghapus data
- Hanya hari libur dengan status aktif yang akan diproses

### 4. **Integrasi dengan Cron Job**
- Command `attendance:generate-absent` otomatis skip hari libur
- Mencegah generate alpha untuk semua karyawan pada hari libur

## Struktur Database

### Tabel: `holidays`

```sql
CREATE TABLE holidays (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    date DATE UNIQUE NOT NULL COMMENT 'Tanggal libur',
    name VARCHAR(255) NOT NULL COMMENT 'Nama hari libur',
    type ENUM('nasional', 'cuti_bersama', 'custom') DEFAULT 'custom',
    description TEXT COMMENT 'Keterangan tambahan',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Status aktif',
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## API Endpoints

### Base URL: `/api/settings/holidays`

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/` | List semua hari libur (filter: year, month) |
| POST | `/` | Tambah hari libur baru |
| GET | `/{id}` | Detail hari libur |
| PUT | `/{id}` | Update hari libur |
| DELETE | `/{id}` | Hapus hari libur |
| POST | `/{id}/toggle` | Toggle status aktif/nonaktif |
| GET | `/calendar` | Data calendar untuk bulan tertentu |
| POST | `/import` | Import hari libur nasional (param: year) |

### Contoh Request

#### 1. Tambah Hari Libur
```json
POST /api/settings/holidays
{
    "date": "2026-08-17",
    "name": "Hari Kemerdekaan RI",
    "type": "nasional",
    "description": "HUT RI ke-81",
    "is_active": true
}
```

#### 2. List Hari Libur Bulan Tertentu
```http
GET /api/settings/holidays?year=2026&month=8
```

#### 3. Import Hari Libur Nasional
```json
POST /api/settings/holidays/import
{
    "year": 2026
}
```

## Integrasi dengan Cron Job

### File: `app/Console/Commands/GenerateAbsentAttendance.php`

Command ini sudah diupdate untuk skip hari libur:

```php
// Check if date is a holiday
if (Holiday::isHoliday($date)) {
    $holiday = Holiday::where('date', $date->format('Y-m-d'))
        ->where('is_active', true)
        ->first();
    $this->warn("Skipping holiday: " . $date->format('l, d F Y') . " - {$holiday->name}");
    return 0;
}
```

### Cara Kerja
1. Setiap jam (08:00-23:59, weekdays), cron job jalan
2. Sebelum generate alpha, cek apakah tanggal adalah hari libur aktif
3. Jika iya, skip dan tidak generate alpha untuk semua karyawan
4. Jika bukan hari libur, proses normal (cek attendance, generate alpha jika perlu)

## Cara Penggunaan

### Untuk Admin

#### 1. Akses Halaman Pengaturan
- Login sebagai admin/manager
- Buka menu **Pengaturan → Cron Job**
- Scroll ke section **Pengaturan Hari Libur**

#### 2. Import Hari Libur Nasional (Rekomendasi)
- Pilih tahun (contoh: 2026)
- Klik tombol **"Import Libur Nasional"**
- Sistem akan otomatis menambahkan hari libur nasional Indonesia

#### 3. Tambah Hari Libur Custom
- Klik tombol **"Tambah Hari Libur"**
- Isi form:
  - **Tanggal**: Pilih tanggal libur
  - **Nama**: Contoh "Libur Khusus Perusahaan"
  - **Jenis**: Pilih Custom
  - **Keterangan**: Opsional
  - **Status**: Aktif/Nonaktif
- Klik **Simpan**

#### 4. Edit/Hapus Hari Libur
- Pada tabel, klik icon **Edit** (pensil) untuk edit
- Klik icon **Toggle** (mata) untuk aktifkan/nonaktifkan
- Klik icon **Hapus** (tempat sampah) untuk hapus permanent

#### 5. Filter Data
- Gunakan dropdown **Tahun** untuk filter berdasarkan tahun
- Gunakan dropdown **Bulan** untuk filter bulan tertentu atau tampilkan semua

### Testing

#### Test Manual via Terminal
```powershell
# Test generate absent untuk tanggal hari libur
php artisan attendance:generate-absent 2026-08-17

# Output yang diharapkan:
# Skipping holiday: Minggu, 17 Agustus 2026 - Hari Kemerdekaan RI
```

#### Test via UI
1. Tambah hari libur untuk hari ini
2. Pastikan status aktif
3. Buka halaman Cron Job
4. Klik **"Generate Absent Now"**
5. Sistem akan skip karena hari libur

## Data Hari Libur Nasional 2026

Berikut daftar hari libur yang sudah dikonfigurasi untuk tahun 2026:

| Tanggal | Nama Hari Libur |
|---------|-----------------|
| 01 Jan 2026 | Tahun Baru Masehi |
| 17 Feb 2026 | Tahun Baru Imlek 2577 |
| 11 Mar 2026 | Isra Mi'raj Nabi Muhammad SAW |
| 22 Mar 2026 | Hari Suci Nyepi (Tahun Baru Saka 1948) |
| 03 Apr 2026 | Wafat Isa Almasih |
| 01 Mei 2026 | Hari Buruh Internasional |
| 07 Mei 2026 | Kenaikan Isa Almasih |
| 01 Jun 2026 | Hari Lahir Pancasila |
| 17 Agu 2026 | Hari Kemerdekaan RI |
| 25 Des 2026 | Hari Raya Natal |

**Note:** Data ini harus diupdate setiap tahun sesuai dengan kalender resmi pemerintah. Untuk menambah tahun baru, edit method `getIndonesianHolidays()` di `HolidayController.php`.

## Model Helper Methods

### `Holiday::isHoliday($date)`
Cek apakah tanggal adalah hari libur aktif.

```php
if (Holiday::isHoliday('2026-08-17')) {
    echo "Hari ini libur!";
}
```

### `Holiday::getHolidaysByMonth($year, $month)`
Ambil semua hari libur dalam bulan tertentu.

```php
$holidays = Holiday::getHolidaysByMonth(2026, 8);
// Returns Collection of holidays in August 2026
```

### `Holiday::getHolidaysInRange($startDate, $endDate)`
Ambil hari libur dalam rentang tanggal.

```php
$holidays = Holiday::getHolidaysInRange('2026-08-01', '2026-08-31');
```

## Security & Validation

### Middleware
- Semua endpoint dilindungi middleware `web`, `auth`, `admin`
- Hanya admin dan manager yang bisa mengakses

### Validasi
- **date**: Required, format date, unique (tidak boleh duplikat)
- **name**: Required, string, max 255 karakter
- **type**: Required, enum ('nasional', 'cuti_bersama', 'custom')
- **description**: Optional, string
- **is_active**: Boolean

## Best Practices

### 1. **Update Data Setiap Tahun**
- Di awal tahun, lakukan import hari libur nasional untuk tahun berjalan
- Cek kalender resmi pemerintah untuk memastikan data akurat

### 2. **Backup Data**
- Backup tabel `holidays` secara berkala
- Export data hari libur sebelum update massal

### 3. **Testing**
- Sebelum deploy, test dengan command artisan
- Verifikasi cron job tidak generate alpha pada hari libur

### 4. **Notifikasi**
- Informasikan karyawan tentang hari libur yang sudah ditambahkan
- Buat pengumuman di dashboard atau kirim via WhatsApp

## Troubleshooting

### Q: Karyawan tetap kena alpha padahal hari libur
**A:** Cek:
1. Status hari libur = Aktif
2. Tanggal sudah benar
3. Cache cleared: `php artisan cache:clear`
4. Test command: `php artisan attendance:generate-absent [tanggal]`

### Q: Import hari libur tidak ada data
**A:** Data hanya tersedia untuk tahun yang sudah dikonfigurasi di `HolidayController::getIndonesianHolidays()`. Tambahkan data untuk tahun lain jika diperlukan.

### Q: Bisa import dari file Excel?
**A:** Saat ini belum ada fitur import Excel. Bisa dikembangkan dengan Laravel Excel. Alternatif: tambah manual atau update di database langsung.

## Pengembangan Lebih Lanjut

### Fitur yang Bisa Ditambahkan
1. **Import dari API External**
   - Integrasi dengan API kalender pemerintah
   - Auto-update data hari libur

2. **Import/Export Excel**
   - Upload Excel untuk bulk import
   - Export data hari libur

3. **Hari Libur per Departemen**
   - Hari libur khusus untuk departemen tertentu
   - Relasi `holidays` → `departments`

4. **Calendar View**
   - Tampilan kalender visual
   - Highlight tanggal libur di calendar

5. **Notifikasi Reminder**
   - Auto-send reminder 1 hari sebelum libur
   - WhatsApp broadcast ke semua karyawan

## File Terkait

- **Migration**: `database/migrations/2026_01_20_105146_create_holidays_table.php`
- **Model**: `app/Models/Holiday.php`
- **Controller**: `app/Http/Controllers/Admin/HolidayController.php`
- **Routes**: `routes/api.php` (prefix: `/api/settings/holidays`)
- **View**: `resources/views/admin/settings/cronjob.blade.php`
- **Command**: `app/Console/Commands/GenerateAbsentAttendance.php`

---

**Dokumentasi dibuat pada:** 20 Januari 2026  
**Versi Sistem:** Laravel 11  
**Developer:** PT Mingda Indonesia
