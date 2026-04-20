# Fitur Auto-Parse Geographic Data

## Overview

Sistem sekarang bisa otomatis extract data geografis (Desa, Kecamatan, Kabupaten) dari field **Alamat Lengkap** yang sudah ada di database. Ini menghilangkan kebutuhan manual entry untuk field baru.

## Cara Kerja

### 1. **Migrasi Data Lama** (Sudah Dilakukan ✓)

Command untuk migrasi semua karyawan lama yang belum punya data geografis:

```bash
# Preview (tidak save)
php artisan geographic:migrate --dry-run

# Execute (save ke database)
php artisan geographic:migrate
```

**Hasil Migrasi:**
- ✅ **296 karyawan** sudah otomatis terisi data dari parsing alamat
- Format parsing dari alamat:
  - **Kabupaten**: Diambil dari field `city` yang sudah ada
  - **Kecamatan**: Di-extract dari pattern "KEC [NAMA]" di alamat
  - **Desa**: Di-extract dari pattern "DESA/KEL/KELURAHAN [NAMA]" di alamat

**Contoh:**
```
Alamat Lengkap: DESA LOSARANG RT 002 RW 001 KEL/DESA LOSARANG KEC LOSARANG INDRAMAYU JAWA BARAT 45253
City: INDRAMAYU
Province: JAWA BARAT

Hasil Parse:
├─ Desa: LOSARANG
├─ Kecamatan: LOSARANG
├─ Kabupaten: INDRAMAYU
└─ Province: JAWA BARAT
```

### 2. **API Endpoint untuk Parse Saat Edit** (Opsional)

Akan ditambahkan endpoint API `/api/admin/karyawan/parse-address` yang bisa:
- Menerima `address` sebagai input
- Return desa, kecamatan, kabupaten yang di-parse
- Bisa dipakai di form untuk auto-fill saat user edit alamat

Usage di form (jQuery):
```javascript
$.ajax({
    url: '/api/admin/karyawan/parse-address',
    method: 'POST',
    data: { address: addressValue },
    success: function(response) {
        $('#desa').val(response.desa);
        $('#kecamatan').val(response.kecamatan);
        $('#kabupaten').val(response.kabupaten);
    }
});
```

### 3. **Pattern Parsing**

Sistem recognize format alamat yang umum:

#### Format 1: Standar Pemerintah (Bandung/Indramayu)
```
DESA [VILLAGE] RT [NO] RW [NO] KEL/DESA [VILLAGE] KEC [DISTRICT] [CITY] [PROVINCE]
Contoh:
DESA LOSARANG RT 002 RW 001 KEL/DESA LOSARANG KEC LOSARANG INDRAMAYU JAWA BARAT 45253
```

#### Format 2: Dengan Jalan
```
JL. [STREET] NO. [NO] RT [NO] RW [NO] KEL/DESA [VILLAGE] KEC [DISTRICT] [CITY] [PROVINCE]
Contoh:
JL. HALMAHERA III NO.567 RT 006 RW 008 KEL/DESA LIMBANGAN WETAN KEC BREBES JAWA TENGAH 52211
```

#### Format 3: Singkat (SDA/Data Minimal)
```
[CUSTOM FORMAT]
Hasil: Hanya kabupaten yang terisi (dari city field)
```

## Perintah Management

### Melihat Statistik
```bash
# Lihat berapa karyawan yang sudah punya data geografis
php artisan tinker
> App\Models\Karyawans::whereNotNull('kabupaten')->count()
# Output: 296

# Lihat sample data
> App\Models\Karyawans::whereNotNull('desa')->limit(3)->get(['name', 'desa', 'kecamatan', 'kabupaten'])
```

### Rollback (Jika Ada Error)
```bash
# Clear data geografis yang baru diisi
php artisan tinker
> App\Models\Karyawans::query()->update(['desa' => null, 'kecamatan' => null, 'kabupaten' => null])
# Ctrl+D untuk exit
```

## Implementation Flow

### Skenario 1: Karyawan Lama (Data Sudah Ada di Alamat)
```
Database:
  address: "DESA LOSARANG RT 002 RW 001 KEL/DESA LOSARANG KEC LOSARANG INDRAMAYU JAWA BARAT"
  city: "INDRAMAYU"
  province: "JAWA BARAT"

Jalankan:
  php artisan geographic:migrate

Hasil:
  desa: "LOSARANG"
  kecamatan: "LOSARANG"
  kabupaten: "INDRAMAYU"
  province: "JAWA BARAT" (tetap dari db)
```

### Skenario 2: Karyawan Baru (Dari Form Edit)
```
User mengisi:
  Alamat Lengkap: "DESA PUNTANG RT 003 RW 001 KEL/DESA PUNTANG KEC LOSARANG INDRAMAYU JAWA BARAT"
  (Other fields)

Form Submit:
  - API auto-parse alamat (optional)
  - Save ke database
  - Data geografis otomatis terisi
```

## Technical Details

### File-file Terkait
- **Command**: `app/Console/Commands/MigrateGeographicData.php` - Main parsing logic
- **Pattern**: Regex di method `parseAddress()` untuk extract desa, kecamatan
- **Models**: `app/Models/Karyawans.php` - $fillable updated dengan new fields

### Regex Patterns (Di `MigrateGeographicData.php`)

**Desa:**
```php
/(?:DESA|KEL|KELURAHAN)\s+([A-Z\s]+?)(?:\s+RT|\s+RW|\s+KEC|$)/i
```

**Kecamatan:**
```php
/\s+KEC\s+([A-Z\s]+?)(?:\s+INDRAMAYU|\s+BANDUNG|...province names...)/i
```

### Catatan Penting
- Command skip karyawan yang sudah punya kabupaten (tidak overwrite)
- Parsing case-insensitive (handle uppercase/lowercase)
- Untuk alamat yang tidak standard format, hanya kabupaten yang terisi

## Troubleshooting

### Q: Data tidak tersimpan?
A: Cek:
1. Jalankan tanpa `--dry-run`: `php artisan geographic:migrate`
2. Verifikasi: `php artisan tinker` → `App\Models\Karyawans::whereNotNull('kabupaten')->count()`

### Q: Parsing tidak akurat untuk desa/kecamatan?
A: Kemungkinan format alamat berbeda. Option:
1. Edit alamat ke format standard di form
2. Manual entry di field desa/kecamatan
3. Update regex pattern di `MigrateGeographicData.php`

### Q: Mau parse saat form input?
A: Akan dibuat API endpoint `/api/admin/karyawan/parse-address` untuk real-time parsing

## Next Steps

- [ ] Tambah API endpoint untuk real-time address parsing
- [ ] Update form dengan auto-fill dari parsing
- [ ] Tambah validasi untuk memastikan format alamat consistent
- [ ] Optional: Integrate dengan database geografis (desa_id reference)
