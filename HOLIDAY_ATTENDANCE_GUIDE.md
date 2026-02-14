# Panduan: Sistem Absensi Hari Libur

## 📋 Overview

Sistem ini mengimplementasikan **Status Attendance Sesuai Jenis Hari Libur** untuk menangani hari libur nasional dan cuti bersama.

## 🎯 Cara Kerja

### 1. **Hari Libur Diaktifkan**
Ketika hari libur diaktifkan di sistem, status attendance akan **mengikuti jenis hari libur**:

| Jenis Hari Libur | Status Attendance | Badge | Contoh |
|------------------|-------------------|-------|--------|
| **Libur Nasional** | `libur` | **LIBUR** | Hari Kemerdekaan, Tahun Baru, dll |
| **Cuti Bersama** | `cuti_bersama` | **CUTI BERSAMA** | Cuti bersama Lebaran, Natal, dll |
| **Custom** | `libur` | **LIBUR** | Hari libur custom perusahaan |

- Command `attendance:generate-absent` otomatis generate status sesuai jenis untuk **semua karyawan aktif**
- Notes: "Hari Libur ({Jenis}): {Nama Hari Libur}"

### 2. **Karyawan Check-In di Hari Libur**
Jika ada karyawan yang **seharusnya masuk** dan melakukan check-in:
- Status (`libur`/`cuti_bersama`) akan **otomatis berubah** menjadi **`Hadir`** atau **`Terlambat`**
- Check-in time dan lokasi akan tercatat seperti biasa
- Tidak perlu intervensi manual dari admin

### 3. **Karyawan Seharusnya Masuk Tapi Tidak Check-In**
Untuk karyawan yang **seharusnya kerja** di hari libur (contoh: Security, Satpam) tapi **tidak check-in**:
- Status awal: `libur` atau `cuti_bersama` (auto-generated)
- **Admin harus manual edit** status menjadi `Alpha` via menu **Absensi**
- Langkah:
  1. Buka menu **Absensi**
  2. Cari karyawan yang bersangkutan pada tanggal hari libur
  3. Klik **Edit**
  4. Ubah status dari `libur`/`cuti_bersama` menjadi `Alpha`
  5. Tambahkan notes jika perlu: "Seharusnya masuk tapi tidak hadir"

### 4. **Karyawan yang Sedang Cuti**
Jika karyawan **sudah ajukan cuti yang disetujui** untuk hari libur:
- Status: sesuai jenis cuti yang diajukan (`Cuti`, `Izin`, atau `Sakit`)
- Notes: "Hari Libur ({Jenis Hari Libur}): {Nama Hari Libur} - {Jenis Cuti} (approved): {Alasan}"

## 🔧 Konfigurasi

### Menambah Hari Libur
1. Login sebagai Admin
2. Buka menu **Pengaturan** → **Cron Job**
3. Di bagian "Pengaturan Hari Libur":
   - Klik **"Tambah Hari Libur"** untuk input manual
   - Pilih **Jenis**: Libur Nasional / Cuti Bersama / Custom
   - Atau klik **"Import Libur Nasional"** untuk import otomatis dari API

### Import Hari Libur Nasional
- Klik tombol **"Import Libur Nasional"**
- Pilih tahun yang diinginkan
- Sistem akan otomatis import semua hari libur nasional Indonesia dari API

### Menonaktifkan Hari Libur
- Di tabel hari libur, klik tombol toggle (ikon mata)
- Hari libur yang nonaktif **tidak akan** trigger generate attendance

## ⚙️ Command Line

### Manual Generate Attendance untuk Hari Libur

```bash
# Generate untuk tanggal tertentu (contoh: 31 Maret 2026 - Cuti Bersama Lebaran)
php artisan attendance:generate-absent 2026-03-31

# Generate untuk kemarin (default)
php artisan attendance:generate-absent
```

### Output Command untuk Hari Libur
```
Processing holiday: Tuesday, 31 March 2026 - Cuti Bersama Idul Fitri
✓ Generated cuti_bersama for: John Doe (EMP001)
✓ Generated cuti_bersama for: Jane Smith (EMP002)
✓ Generated cuti for: Bob Johnson (EMP003) [sudah ajukan cuti]
...

Holiday attendance generation completed!
Holiday: Cuti Bersama Idul Fitri (Cuti Bersama)
Generated: 45 records
Skipped: 3 employees (already have attendance)
```

## 📊 Contoh Skenario

### Skenario 1: Libur Nasional (Semua Karyawan Libur)
**Tanggal:** 17 Agustus 2026 (Hari Kemerdekaan)
**Jenis:** Libur Nasional

1. Cronjob jalan jam 08:00 pagi tanggal 18 Agustus
2. Generate status `libur` untuk semua karyawan pada 17 Agustus
3. Hasil: Semua karyawan status **LIBUR** ✅
4. Admin: **Tidak perlu lakukan apa-apa**

### Skenario 2: Cuti Bersama dengan Security yang Harus Masuk
**Tanggal:** 31 Maret 2026 (Cuti Bersama Lebaran)
**Jenis:** Cuti Bersama
**Karyawan:** Ahmad (Security Shift Pagi)

**Sub-skenario A: Ahmad Check-In**
1. Cronjob generate `cuti_bersama` untuk semua karyawan termasuk Ahmad
2. Ahmad check-in jam 07:00 via mobile app
3. Status Ahmad otomatis berubah: `cuti_bersama` → `Hadir` ✅
4. Admin: **Tidak perlu lakukan apa-apa**

**Sub-skenario B: Ahmad Tidak Check-In (Mangkir)**
1. Cronjob generate `cuti_bersama` untuk semua karyawan termasuk Ahmad
2. Ahmad **tidak check-in** (mangkir)
3. Status Ahmad: tetap `cuti_bersama` ❌ (seharusnya `Alpha`)
4. Admin: **Harus manual edit** `cuti_bersama` → `Alpha`
   - Buka menu Absensi
   - Cari Ahmad, tanggal 31 Maret
   - Edit status: cuti_bersama → Alpha
   - Notes: "Security shift pagi. Tidak hadir tanpa keterangan."

### Skenario 3: Karyawan Ajukan Cuti di Hari Libur
**Tanggal:** 17 Agustus 2026 (Hari Kemerdekaan)
**Jenis:** Libur Nasional
**Karyawan:** Sarah (sudah ajukan cuti 16-18 Agustus, approved)

1. Cronjob cek: Sarah sudah punya approved leave
2. Generate status: `Cuti` (bukan libur) ✅
3. Notes: "Hari Libur (Libur Nasional): Hari Kemerdekaan RI - cuti (approved): Mudik kampung"
4. Admin: **Tidak perlu lakukan apa-apa**

### Skenario 4: Perbedaan Libur Nasional vs Cuti Bersama
**Tanggal A:** 17 Agustus (Libur Nasional)
**Tanggal B:** 31 Maret (Cuti Bersama)

| Aspek | Libur Nasional | Cuti Bersama |
|-------|----------------|--------------|
| Status Generated | `libur` | `cuti_bersama` |
| Badge | **LIBUR** (dark) | **CUTI BERSAMA** (info/blue) |
| Hak Cuti | Tidak memotong cuti | Tidak memotong cuti |
| Bisa Check-In? | Ya (override ke Hadir) | Ya (override ke Hadir) |

## 🔄 Cronjob Schedule

Command `attendance:generate-absent` di-schedule otomatis:
- **Waktu:** Setiap hari jam **08:00 pagi**
- **Scope:** Monday - Friday (weekdays) + Holiday (detected)
- **Target:** Generate attendance untuk **hari kemarin**

**Catatan:**
- Untuk hari kemarin: generate **langsung** tanpa grace period
- Untuk hari ini (manual): cek **grace period** (check-in + 10 menit)

## 📝 Catatan Penting

### ✅ Kelebihan Sistem Ini
- **Jelas:** Status badge langsung menunjukkan jenis hari libur
- **Fleksibel:** Karyawan yang seharusnya masuk bisa check-in normal
- **Otomatis:** Admin tidak perlu manual generate satu-satu
- **Aman:** Data yang sudah ada (check-in) tidak akan ditimpa
- **Transparan:** Semua tercatat di notes dengan jelas
- **Pembedaan:** Libur Nasional dan Cuti Bersama punya status berbeda

### ⚠️ Yang Perlu Diperhatikan
1. **Karyawan Shift Khusus di Hari Libur:**
   - Security, Satpam, atau karyawan yang **wajib masuk** di hari libur
   - Jika tidak check-in → Admin **harus manual edit** ke `Alpha`
   - **Workaround:** Beri tahu karyawan shift khusus untuk **wajib check-in** meski hari libur

2. **Departemen/Karyawan Pengecualian:**
   - Jika ada dept/karyawan yang **selalu tidak libur** (contoh: Security 24/7)
   - Saat ini belum ada fitur exception list otomatis
   - Admin harus **manual cek dan edit** per hari libur

3. **Laporan Absensi:**
   - Status `libur` dan `cuti_bersama` akan termasuk dalam laporan
   - Filter/laporan perlu consider kedua status ini = hari libur (bukan absen)

4. **Jenis Hari Libur:**
   - Pastikan pilih jenis yang benar saat input/import hari libur
   - Libur Nasional vs Cuti Bersama akan generate status berbeda
   - Status berbeda = badge berbeda = lebih mudah analisa laporan

## 🎨 Badge Reference

Untuk referensi visual di sistem:

| Status | Badge | Warna | Keterangan |
|--------|-------|-------|------------|
| Hadir | **HADIR** | Success (hijau) | Karyawan hadir tepat waktu |
| Terlambat | **TERLAMBAT** | Warning (kuning) | Karyawan hadir terlambat |
| Cuti | **CUTI** | Primary (biru) | Cuti yang diajukan karyawan |
| Cuti Bersama | **CUTI BERSAMA** | Info (biru muda) | Cuti bersama hari libur |
| Izin | **IZIN** | Info (biru muda) | Izin karyawan |
| Sakit | **SAKIT** | Secondary (abu) | Sakit dengan/tanpa surat dokter |
| Libur | **LIBUR** | Dark (hitam) | Libur nasional atau custom |
| Alpha | **ALPHA** | Danger (merah) | Tidak hadir tanpa keterangan |

## 🚀 Future Improvements (Opsional)

Jika ke depan ada kebutuhan:
1. **Exception List per Departemen:**
   - Tambah setting: "Departemen yang Tidak Libur"
   - Auto-generate `Alpha` (bukan libur) untuk dept tersebut di hari libur

2. **Exception List per Karyawan:**
   - Tambah flag di master karyawan: "Tidak Mengikuti Hari Libur"
   - Auto-generate `Alpha` (bukan libur) untuk karyawan tersebut

3. **Bulk Edit Status:**
   - Fitur edit massal dari libur → Alpha
   - Untuk case: semua Security dept di hari libur tidak hadir

4. **Auto Reminder:**
   - Kirim notifikasi ke karyawan shift khusus H-1 hari libur
   - Reminder: "Besok hari libur tapi Anda shift, jangan lupa check-in"

## 📞 Support

Jika ada pertanyaan atau kendala terkait sistem hari libur:
1. Cek **menu Pengaturan → Cron Job** untuk dokumentasi
2. Test manual via command: `php artisan attendance:generate-absent {tanggal}`
3. Hubungi developer/admin sistem untuk issue teknis

---

**Last Updated:** 14 Februari 2026
**Version:** 2.0
**Implementation:** Status Sesuai Jenis Hari Libur (Libur Nasional, Cuti Bersama, Custom)
