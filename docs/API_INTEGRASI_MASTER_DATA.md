# Integrasi API Karyawan dan Master Data

Panduan menghubungkan aplikasi lain ke data karyawan, departemen, sub-departemen, dan jabatan Sistem Absensi & HRIS PT Mingda.

> Penting: API karyawan eksternal memakai Bearer token Sanctum. Endpoint departemen/jabatan/sub-departemen saat ini memakai autentikasi web session, bukan Bearer token. Aplikasi server-ke-server tidak dapat memanggil endpoint master data tersebut hanya dengan token API. Minta administrator menyiapkan endpoint eksternal ber-token sebelum integrasi master data; dokumentasi ini mencantumkan kondisi aktual.

## Konfigurasi dan autentikasi

Minta URL server dari administrator. Produksi wajib memakai HTTPS. Ganti host contoh dengan domain sebenarnya.

```text
API_BASE_URL=https://absensi.example.com/api
```

Login: `POST {API_BASE_URL}/auth/login`

Header: `Accept: application/json`, `Content-Type: application/json`

```json
{
  "email": "integrasi@example.com",
  "password": "PASSWORD_DARI_ADMIN",
  "token_name": "aplikasi-lain"
}
```

Respons sukses `200`:

```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "token": "1|TOKEN_RAHASIA",
    "token_type": "Bearer",
    "user": {
      "id": 12,
      "name": "Pengguna API",
      "email": "integrasi@example.com",
      "role": "viewer"
    }
  }
}
```

Gunakan `data.token` pada request: `Authorization: Bearer {TOKEN}` dan `Accept: application/json`. Role API yang diizinkan: `admin`, `manager`, `viewer`; role karyawan biasa ditolak. Simpan token/password di secret store server-side, jangan commit/log atau kirim ke client publik. Revoke token: `POST {API_BASE_URL}/auth/logout` dengan Bearer token.

## Karyawan (API eksternal, Bearer token)

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/v1/karyawan` | Daftar berpaginasi. |
| GET | `/api/v1/karyawan/all` | Semua data tanpa pagination; gunakan hemat. |
| GET | `/api/v1/karyawan/{id}` | Detail karyawan. |

Daftar dengan filter:

```http
GET {API_BASE_URL}/v1/karyawan?search=andi&department_id=2&position_id=4&status=active&per_page=25&page=1
Authorization: Bearer {TOKEN}
Accept: application/json
```

| Parameter | Jenis | Keterangan |
|---|---|---|
| `search` | string | Cari nama, `employee_code`, atau email. |
| `department_id` | integer | Filter departemen. |
| `position_id` | integer | Filter jabatan. |
| `status` | string | Status karyawan sesuai nilai di sistem. |
| `per_page` | integer | Ukuran halaman; default `25`, maksimum `100`. |
| `page` | integer | Halaman; default `1`. |

Bentuk respons:

```json
{
  "success": true,
  "data": [
    {
      "id": 35,
      "employee_code": "EMP0035",
      "name": "Andi",
      "department_id": 2,
      "sub_department_id": 5,
      "position_id": 4,
      "status": "active",
      "department": { "id": 2, "name": "Produksi" },
      "sub_department": { "id": 5, "name": "Perakitan" },
      "position": { "id": 4, "name": "Operator" },
      "profile_photo_url": null
    }
  ],
  "meta": {
    "total": 1,
    "per_page": 25,
    "current_page": 1,
    "last_page": 1,
    "from": 1,
    "to": 1
  }
}
```

`data` berisi atribut record karyawan dan relasi departemen, sub-departemen, jabatan. Field `ktp` dihapus. Field lain dapat kosong/bervariasi; data dapat mengandung informasi pribadi, jadi batasi akses dan penyimpanan.

Semua data tanpa pagination:

```http
GET {API_BASE_URL}/v1/karyawan/all?department_id=2&status=active
Authorization: Bearer {TOKEN}
```

Filter sama, tanpa `per_page`/`page`. Respons `{ "success": true, "data": [...] }`. Pilih endpoint berpaginasi untuk sinkronisasi rutin.

Detail:

```http
GET {API_BASE_URL}/v1/karyawan/35
Authorization: Bearer {TOKEN}
Accept: application/json
```

Sukses memberi `{ "success": true, "data": { ... } }`; ID tidak ditemukan memberi `404`.

## Departemen, sub-departemen, jabatan

Route tersedia, tetapi middleware aktualnya `web` + `auth` + `admin` (session login web dan hak admin), bukan `auth:sanctum`. Bearer token dari login API di atas tidak mengautentikasi route ini. Route cocok untuk UI web internal, bukan integrasi server-ke-server berbasis token.

| Method | Endpoint | Parameter GET | Akses saat ini |
|---|---|---|---|
| GET | `/api/departments` | `search`, `per_page`, `paginate=false` | Web session + admin |
| GET | `/api/sub-departments` | `department_id`, `is_active`, `search`, `per_page` | Web session + admin |
| GET | `/api/sub-departments/by-department/{departmentId}` | `departmentId` | Web session + admin |
| GET | `/api/sub-departments/{id}` | `id` | Web session + admin |
| GET | `/api/positions` | `search`, `per_page` | Web session + admin |
| GET | `/api/karyawan/master-data` | — | Web session + autentikasi |

`/api/karyawan/master-data` mengembalikan `data.departments`, `data.positions` (aktif saja), `data.work_schedules` (aktif saja), `data.sub_departments`, dan `data.supervisors` (karyawan aktif). Endpoint ini juga bukan endpoint Bearer eksternal.

Untuk aplikasi lain, minta administrator menambahkan endpoint read-only menggunakan `auth:sanctum` dan role sesuai kebutuhan, misalnya GET departments, positions, sub-departments, dan/atau master-data gabungan. Endpoint master data tersebut belum tersedia untuk Bearer eksternal. Jangan menganggap route web di atas dapat dipanggil dengan API token.

## Contoh cURL

```sh
curl --request POST "$API_BASE_URL/auth/login" \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{"email":"integrasi@example.com","password":"PASSWORD_DARI_ADMIN","token_name":"aplikasi-lain"}'

curl --get "$API_BASE_URL/v1/karyawan" \
  --header "Authorization: Bearer $API_TOKEN" \
  --header 'Accept: application/json' \
  --data-urlencode 'department_id=2' \
  --data-urlencode 'status=active' \
  --data-urlencode 'per_page=25' \
  --data-urlencode 'page=1'
```

## Contoh JavaScript server-side

```js
const baseUrl = process.env.ABSENSI_API_BASE_URL; // https://absensi.example.com/api
const login = await fetch(`${baseUrl}/auth/login`, {
  method: 'POST',
  headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: process.env.ABSENSI_API_EMAIL,
    password: process.env.ABSENSI_API_PASSWORD,
    token_name: 'aplikasi-lain',
  }),
});
if (!login.ok) throw new Error(`Login gagal: HTTP ${login.status}`);
const { data: { token } } = await login.json();

const response = await fetch(`${baseUrl}/v1/karyawan?department_id=2&per_page=25&page=1`, {
  headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
});
if (!response.ok) throw new Error(`Ambil karyawan gagal: HTTP ${response.status}`);
const { data: employees, meta } = await response.json();
```

## Pagination dan error

Untuk `/api/v1/karyawan`, naikkan `page` sampai `meta.current_page` sama dengan `meta.last_page`; `per_page` maksimum `100`.

| HTTP | Makna / tindakan |
|---|---|
| `401` | Token tidak ada/tidak valid atau kredensial salah. Login ulang dan periksa header. |
| `403` | Role tidak diizinkan, akun tidak aktif, atau endpoint master data dipanggil tanpa session admin. |
| `404` | Karyawan tidak ditemukan; periksa ID. |
| `422` | Validasi gagal; baca `message` dan `errors`. |

## OpenAPI dan kesiapan produksi

Jika diaktifkan dan akses diizinkan server: UI `{BASE_URL}/docs/api`, JSON `{BASE_URL}/docs/api.json`.

Sebelum go-live, administrator perlu menyediakan akun integrasi dengan role minimum. Simpan secret di server, pakai HTTPS, uji filter/pagination/revoke token di staging, serta minta endpoint Sanctum read-only untuk master data jika aplikasi lain memerlukannya. Route dan middleware diverifikasi terhadap `routes/api.php` dan `php artisan route:list`.
