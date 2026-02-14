# Recalculate Overtime Command

Command ini digunakan untuk menghitung ulang data lembur (`overtime_minutes`) pada data attendance yang sudah ada.

## 📋 Kapan Menggunakan Command Ini?

- Setelah implementasi fitur lembur pertama kali
- Jika ada perubahan `overtime_threshold` pada work schedule
- Jika ada data attendance lama yang belum memiliki nilai lembur
- Untuk memperbaiki data lembur yang salah

## 🚀 Cara Penggunaan

### 1. Recalculate Semua Data

```bash
php artisan attendance:recalculate-overtime
```

Akan memproses **semua** attendance yang memiliki:
- Status: `hadir` atau `terlambat`
- `check_out` tidak null
- Employee memiliki work schedule

### 2. Preview Tanpa Menyimpan (Dry Run)

```bash
php artisan attendance:recalculate-overtime --dry-run
```

Mode ini akan:
- Menampilkan berapa banyak data yang akan diupdate
- **TIDAK mengubah database**
- Berguna untuk preview sebelum eksekusi sesungguhnya

### 3. Filter Berdasarkan Tanggal

#### Dari tanggal tertentu
```bash
php artisan attendance:recalculate-overtime --from=2024-01-01
```

#### Sampai tanggal tertentu
```bash
php artisan attendance:recalculate-overtime --to=2024-12-31
```

#### Range tanggal
```bash
php artisan attendance:recalculate-overtime --from=2024-01-01 --to=2024-12-31
```

### 4. Filter Berdasarkan Karyawan

```bash
php artisan attendance:recalculate-overtime --employee=123
```

### 5. Kombinasi Filter

```bash
# Dry run untuk bulan Januari 2024 saja
php artisan attendance:recalculate-overtime --dry-run --from=2024-01-01 --to=2024-01-31

# Update lembur karyawan ID 5 untuk Februari 2024
php artisan attendance:recalculate-overtime --employee=5 --from=2024-02-01 --to=2024-02-29

# Preview untuk semua data
php artisan attendance:recalculate-overtime --dry-run
```

## 📊 Output Command

Setelah eksekusi, command akan menampilkan:

```
=== Recalculation Complete ===
+-----------------+-------+
| Metric          | Count |
+-----------------+-------+
| Total Processed | 5533  |
| Updated         | 4521  |
| Skipped         | 45    |
| No Changes      | 967   |
+-----------------+-------+
```

**Penjelasan:**
- **Total Processed**: Total data attendance yang diproses
- **Updated**: Jumlah data yang diupdate (nilai lembur berubah)
- **Skipped**: Data yang di-skip (error atau tidak ada work schedule)
- **No Changes**: Data yang tidak berubah (nilai lembur sudah benar)

## ⚙️ Cara Kerja Command

1. Query attendance dengan kriteria:
   - `check_out IS NOT NULL`
   - `status IN ('hadir', 'terlambat')`
   - Filter tambahan jika ada (tanggal, employee)

2. Untuk setiap attendance:
   - Ambil work schedule karyawan
   - Hitung `threshold_time = end_time + overtime_threshold`
   - Jika `checkout > threshold_time`:
     - `overtime_minutes = checkout - end_time`
   - Jika tidak:
     - `overtime_minutes = 0`

3. Update database jika nilai berbeda

## ⚠️ Catatan Penting

1. **Backup Database**: Selalu backup database sebelum menjalankan command tanpa `--dry-run`

2. **Work Schedule**: Data yang tidak punya work schedule akan di-skip

3. **Performance**: Untuk data besar (>10,000 records), command akan memakan waktu beberapa menit

4. **Best Practice**:
   - Gunakan `--dry-run` dulu untuk preview
   - Gunakan filter tanggal untuk batch kecil
   - Jalankan di luar jam kerja untuk data besar

## 🔍 Troubleshooting

### Command tidak menemukan data
```bash
No attendance records found matching the criteria.
```
**Solusi**: Cek filter tanggal atau pastikan ada data attendance dengan status hadir/terlambat yang sudah checkout

### Banyak data di-skip
**Penyebab**: Karyawan tidak memiliki work schedule

**Solusi**: Pastikan semua karyawan sudah di-assign ke work schedule

## 📝 Contoh Skenario

### Skenario 1: Implementasi Pertama Kali
```bash
# Preview dulu
php artisan attendance:recalculate-overtime --dry-run

# Jika OK, jalankan
php artisan attendance:recalculate-overtime
```

### Skenario 2: Update Threshold Shift Pagi
Misal Anda ubah threshold shift pagi dari 50 menit ke 60 menit:

```bash
# Preview untuk bulan ini saja
php artisan attendance:recalculate-overtime --dry-run --from=2024-02-01

# Apply
php artisan attendance:recalculate-overtime --from=2024-02-01
```

### Skenario 3: Fix Data Karyawan Tertentu
```bash
# Recalculate karyawan ID 10 untuk semua waktu
php artisan attendance:recalculate-overtime --employee=10
```

## 🎯 Tips

1. **Gunakan `--dry-run` dulu**: Selalu preview sebelum apply
2. **Batch Processing**: Untuk data besar, proses per bulan
3. **Monitor Progress**: Command menampilkan progress bar real-time
4. **Schedule**: Bisa dijadwalkan dengan Laravel Scheduler jika perlu update berkala
