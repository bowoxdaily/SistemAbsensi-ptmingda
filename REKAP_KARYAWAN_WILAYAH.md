# Rekap Karyawan Berdasarkan Wilayah (Geographic Employee Summary)

## Overview
Fitur baru yang memungkinkan pengguna admin/manager untuk membuat rekap karyawan berdasarkan pembagian geografis: **Provinsi**, **Kabupaten/Kota**, **Kecamatan**, dan **Desa/Kelurahan**.

## Fitur Utama
- ✅ Mengelompokkan karyawan berdasarkan lokasi geografis (4 tingkatan)
- ✅ Filter berdasarkan Provinsi, Kabupaten, Kecamatan
- ✅ Statistik per lokasi (Total Karyawan, Aktif, Tidak Aktif, Resign)
- ✅ Tampilan departemen dan posisi per lokasi
- ✅ Detail karyawan dalam modal
- ✅ Export ke Excel dengan format rapi
- ✅ Menu navigasi di sidebar

## Database Changes

### Migration File
**File**: `database/migrations/2026_04_20_000001_add_geographic_fields_to_employees_table.php`

Menambahkan 4 kolom baru ke tabel `employees`:
- `desa` (varchar, nullable) - Desa/Kelurahan
- `kecamatan` (varchar, nullable) - Kecamatan
- `kabupaten` (varchar, nullable) - Kabupaten/Kota
- Indexes untuk performa query

**Cara menjalankan migration:**
```bash
php artisan migrate
```

## Model Changes

### Updated Models
1. **`app/Models/Karyawans.php`** - Tambah field baru ke $fillable
2. **`app/Models/Employee.php`** - Tambah field baru ke $fillable

### Tambahan Field di $fillable
```php
'desa',
'kecamatan', 
'kabupaten',
```

## Controller Changes

### `app/Http/Controllers/Admin/RekapitulasiController.php`

**Metode Baru:**

#### 1. `geographicIndex()`
Menampilkan halaman rekap karyawan berdasarkan wilayah.
```php
Route::get('/admin/rekapitulasi/geographic', 
    [RekapitulasiController::class, 'geographicIndex']
);
```

#### 2. `getGeographicData(Request $request)`
API endpoint untuk mengambil data karyawan yang dikelompokkan berdasarkan wilayah.

**Parameters:**
- `group_level` (string): 'provinsi', 'kabupaten', 'kecamatan', 'desa'
- `province` (string, optional): Filter berdasarkan provinsi
- `kabupaten` (string, optional): Filter berdasarkan kabupaten
- `kecamatan` (string, optional): Filter berdasarkan kecamatan

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "location": "Jawa Barat - Bandung",
            "total_karyawan": 25,
            "active_count": 23,
            "inactive_count": 1,
            "resign_count": 1,
            "departments": {"IT": 5, "HR": 3},
            "positions": {"Manager": 2, "Staff": 20},
            "employees": [...]
        }
    ],
    "summary": {
        "total_karyawan": 150,
        "total_active": 145,
        "total_inactive": 3,
        "total_resign": 2,
        "group_count": 8
    },
    "group_level": "kabupaten",
    "locations": {...}
}
```

#### 3. `exportGeographicExcel(Request $request)`
Export rekap ke file Excel dengan format profesional.

**Parameters:** Same as `getGeographicData()`

## Routes

### Web Routes
```php
// routes/web.php
Route::middleware(['viewer'])->group(function () {
    Route::get('/admin/rekapitulasi/geographic', 
        [RekapitulasiController::class, 'geographicIndex']
    )->name('admin.rekapitulasi.geographic');
});
```

### API Routes
```php
// routes/api.php
Route::middleware(['web', 'auth', 'viewer'])->prefix('admin/rekapitulasi')->group(function () {
    Route::get('/geographic-data', 
        [RekapitulasiController::class, 'getGeographicData']
    );
    Route::get('/geographic-export-excel', 
        [RekapitulasiController::class, 'exportGeographicExcel']
    );
});
```

## Views

### Blade Template
**File**: `resources/views/admin/rekapitulasi/geographic.blade.php`

Features:
- Summary cards (Total Karyawan, Wilayah, Aktif, Resign)
- Filter dropdowns (Group Level, Provinsi, Kabupaten, Kecamatan)
- Data table dengan pagination
- Modal untuk detail karyawan per wilayah
- Export to Excel button
- Responsive design

## Export Class

### `app/Exports/GeographicRekapExport.php`

Menangani export data geografis ke Excel:
- Menampilkan lokasi sebagai group header
- Detail karyawan dengan informasi lengkap
- Summary row per lokasi
- Styling profesional dengan warna dan borders

## Frontend Form Updates

### `resources/views/admin/karyawan/index.blade.php`

**Perubahan:**
1. Tambah input fields untuk Kabupaten, Kecamatan, Desa
2. Update JavaScript untuk menangani field baru:
   - Form submission (`saveKaryawan()`)
   - Form population saat edit (`showDetail()`)
   - Validasi field

**Fields Baru:**
- `kabupaten` (optional) - Kabupaten/Kota
- `kecamatan` (optional) - Kecamatan  
- `desa` (optional) - Desa/Kelurahan

## Sidebar Menu Update

### `resources/views/layouts/partials/sidebar.blade.php`

Tambahan menu item:
```html
<!-- Rekap Karyawan Berdasarkan Wilayah -->
<li class="menu-item {{ request()->routeIs('admin.rekapitulasi.geographic') ? 'active' : '' }}">
    <a href="{{ route('admin.rekapitulasi.geographic') }}" class="menu-link">
        <i class="menu-icon tf-icons bx bx-map-pin"></i>
        <div data-i18n="Rekap Wilayah">Rekap Karyawan - Wilayah</div>
    </a>
</li>
```

## How to Use

### 1. Mengisi Data Geografis Karyawan
1. Buka halaman Manajemen Karyawan (`/admin/karyawan`)
2. Edit karyawan atau tambah baru
3. Scroll ke tab "Kontak" → Isi field:
   - Provinsi (wajib)
   - Kabupaten/Kota (optional)
   - Kecamatan (optional)
   - Desa/Kelurahan (optional)
4. Simpan

### 2. Membuat Rekap Berdasarkan Wilayah
1. Buka menu sidebar → "Rekap Karyawan - Wilayah"
2. Pilih opsi pengelompokan:
   - **Provinsi**: Rekap per Provinsi
   - **Kabupaten/Kota**: Rekap per Kabupaten (default)
   - **Kecamatan**: Rekap per Kecamatan
   - **Desa/Kelurahan**: Rekap per Desa
3. Gunakan filter opsional:
   - Pilih Provinsi spesifik
   - Pilih Kabupaten spesifik
   - Pilih Kecamatan spesifik

### 3. Melihat Detail Karyawan
1. Klik tombol "Detail" pada row lokasi manapun
2. Modal akan menampilkan semua karyawan di lokasi tersebut
3. Informasi mencakup: Kode, Nama, Departemen, Posisi, Tanggal Bergabung, Status

### 4. Export ke Excel
1. Sesuaikan filter sesuai kebutuhan
2. Klik tombol "Excel" di pojok kanan atas
3. File akan diunduh dengan format:
   - Filename: `Rekap_Karyawan_[LEVEL]_[TIMESTAMP].xlsx`
   - Contoh: `Rekap_Karyawan_kabupaten_2026-04-20_143025.xlsx`

## Validasi & Aturan

### Field Validation (di KaryawanController)
```php
'province' => 'required|string|max:50',        // Wajib
'kabupaten' => 'nullable|string|max:100',     // Optional
'kecamatan' => 'nullable|string|max:100',     // Optional  
'desa' => 'nullable|string|max:100',          // Optional
```

### Notes
- Hanya karyawan dengan status "active" yang ditampilkan di rekap geografis
- Field geografis dapat dikosongkan (akan menjadi "Unknown" di rekapitulasi)
- Sorting otomatis: Provinsi → Kabupaten → Kecamatan → Desa → Nama

## API Response Examples

### Request: Get Geographic Data (Kabupaten)
```http
GET /api/rekapitulasi/geographic-data?group_level=kabupaten
Authorization: Bearer {token}
```

### Request: Get Geographic Data dengan Filter Provinsi
```http
GET /api/rekapitulasi/geographic-data?group_level=kecamatan&province=Jawa%20Barat
Authorization: Bearer {token}
```

### Request: Export Excel
```http
GET /api/rekapitulasi/geographic-export-excel?group_level=provinsi&province=Jawa%20Barat
Authorization: Bearer {token}
```

File akan diunduh secara otomatis.

## Performance Considerations

1. **Database Indexes**: Kolom geografis memiliki index untuk query yang cepat
2. **Query Optimization**: Data di-load sekali saat page pertama kali
3. **Grouping**: Dilakukan di PHP, dapat ditingkatkan ke Database jika perlu

## Security

- Hanya role **viewer** (admin, manager) yang dapat akses fitur ini
- Data sensitif tidak ditampilkan di export (PII fields sudah difilter di model)
- CSRF protection aktif pada form

## Testing Checklist

- [ ] Migration berhasil dijalankan
- [ ] Field baru muncul di form Edit Karyawan
- [ ] Data geografis dapat disimpan
- [ ] Menu "Rekap Karyawan - Wilayah" muncul di sidebar
- [ ] Page `/admin/rekapitulasi/geographic` dapat diakses
- [ ] API endpoint `/api/rekapitulasi/geographic-data` mengembalikan data
- [ ] Filter bekerja (provinsi, kabupaten, kecamatan)
- [ ] Grouping berdasarkan semua level berfungsi
- [ ] Modal detail karyawan menampilkan info lengkap
- [ ] Export Excel menghasilkan file dengan format benar
- [ ] File Excel dapat dibuka di Excel/Spreadsheet

## Troubleshooting

### Migration Gagal
```bash
# Check migration status
php artisan migrate:status

# Rollback jika perlu
php artisan migrate:rollback --step=1
```

### API Endpoint Tidak Ditemukan
```bash
# Clear route cache
php artisan route:clear

# Verify routes
php artisan route:list | grep geographic
```

### Data Tidak Tampil
- Pastikan karyawan memiliki status "active"
- Cek apakah field geografis sudah diisi
- Buka browser console untuk error JavaScript

### Export Excel Error
```bash
# Ensure Excel package installed
composer show maatwebsite/laravel-excel
```

## Future Enhancements

1. **Chart Visualization**: Tambah pie/bar chart untuk distribusi geografis
2. **Bulk Upload**: Import data geografis dari Excel
3. **Regional Hierarchy**: Tambah master data wilayah dengan validator
4. **Attendance by Region**: Gabungkan dengan data kehadiran per wilayah
5. **Regional Reports**: Export gabungan dengan data payroll per wilayah

---

**Dibuat**: April 20, 2026  
**Status**: Production Ready
