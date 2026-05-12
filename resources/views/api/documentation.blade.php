<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - Sistem Absensi Mobile</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Highlight.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/atom-one-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
    <!-- Marked.js -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        .markdown-body { font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Helvetica,Arial,sans-serif,"Apple Color Emoji","Segoe UI Emoji"; }
        .markdown-body h1 { font-size: 2em; font-weight: 600; border-bottom: 1px solid #eaecef; padding-bottom: .3em; margin-top: 24px; margin-bottom: 16px; color: #111827;}
        .markdown-body h2 { font-size: 1.5em; font-weight: 600; border-bottom: 1px solid #eaecef; padding-bottom: .3em; margin-top: 32px; margin-bottom: 16px; color: #1f2937; }
        .markdown-body h3 { font-size: 1.25em; font-weight: 600; margin-top: 24px; margin-bottom: 16px; color: #374151;}
        .markdown-body p { margin-top: 0; margin-bottom: 16px; line-height: 1.6; color: #4b5563;}
        .markdown-body ul { padding-left: 2em; margin-top: 0; margin-bottom: 16px; list-style: disc; color: #4b5563;}
        .markdown-body li { margin-bottom: 0.5em; }
        .markdown-body pre { background-color: #282c34; border-radius: 8px; padding: 16px; overflow: auto; margin-bottom: 16px; box-shadow: inset 0 0 0 1px rgba(255,255,255,0.1); }
        .markdown-body code { background-color: rgba(226, 232, 240, 0.6); border-radius: 4px; font-size: 85%; margin: 0; padding: .2em .4em; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; color: #dc2626;}
        .markdown-body pre code { background-color: transparent; padding: 0; font-size: 14px; color: #abb2bf; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;}
        .markdown-body blockquote { border-left: .25em solid #dfe2e5; color: #6a737d; padding: 0 1em; margin: 0 0 16px 0; }
        .markdown-body hr { height: .25em; padding: 0; margin: 24px 0; background-color: #e1e4e8; border: 0; }
        .markdown-body a { color: #2563eb; text-decoration: none; }
        .markdown-body a:hover { text-decoration: underline; }
        .markdown-body strong { font-weight: 600; color: #111827; }
        
        /* Custom scrollbar for sidebar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-gray-50 flex font-sans text-gray-900">

    <!-- Mobile Header -->
    <div class="md:hidden fixed top-0 w-full bg-white border-b shadow-sm z-50 flex items-center justify-between p-4">
        <h1 class="text-lg font-bold text-indigo-600">Mobile API Docs</h1>
        <button id="menu-btn" class="p-2 bg-gray-100 rounded text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
    </div>

    <!-- Sidebar -->
    <div id="sidebar" class="fixed inset-y-0 left-0 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out w-72 bg-white border-r shadow-sm z-40 overflow-y-auto flex flex-col">
        <div class="p-6 pb-2 hidden md:block">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 rounded bg-indigo-600 text-white flex items-center justify-center font-bold text-xl">A</div>
                <h2 class="text-xl font-bold text-gray-800 tracking-tight">API Docs</h2>
            </div>
            <div class="w-full h-px bg-gray-200 mb-4"></div>
        </div>
        <div class="p-6 md:pt-0 flex-1 overflow-y-auto mt-16 md:mt-0">
            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">TOC</div>
            <nav id="toc" class="text-sm flex flex-col gap-1">
                <!-- Table of contents will be generated here -->
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="w-full md:ml-72 flex justify-center min-h-screen pt-16 md:pt-0">
        <div class="w-full max-w-4xl p-6 md:p-10 bg-white shadow-sm min-h-screen border-l border-r border-gray-100">
            <div id="content" class="markdown-body pb-24">
                <!-- Markdown content will be rendered here -->
            </div>
        </div>
    </div>

    <!-- Overlay for mobile menu -->
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden"></div>

<textarea id="markdown-content" class="hidden">
# Dokumentasi API - Aplikasi Mobile Karyawan

Dokumentasi ini berisi daftar endpoint API yang digunakan untuk membangun Aplikasi Mobile (HP) khusus Karyawan pada Sistem Absensi PT Mingda. 

## Base URL
Semua endpoint dalam dokumentasi ini menggunakan prefix Base URL berikut:
```text
https://absensi.mingda.my.id/api
```


## Autentikasi (Bearer Token)
Sebagian besar endpoint memerlukan autentikasi menggunakan Sanctum Bearer Token. Token ini didapatkan saat karyawan melakukan **Login**. 
Tambahkan Header berikut pada setiap request yang membutuhkan autentikasi:
```text
Authorization: Bearer <token_anda>
Accept: application/json
```

---

## 1. Autentikasi (Auth)

### 1.1 Login Karyawan
Mendapatkan access token untuk karyawan.
- **URL**: `/auth/login`
- **Method**: `POST`
- **Body** (JSON/Form Data):
  ```json
  {
    "email": "karyawan@email.com",
    "password": "password123",
    "token_name": "mobile-app"
  }
  ```
- **Response Success (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Login berhasil",
    "data": {
      "token": "1|abcdef123456...",
      "token_type": "Bearer",
      "user": {
        "id": 1,
        "name": "Nama Karyawan",
        "email": "karyawan@email.com",
        "role": "karyawan"
      }
    }
  }
  ```

### 1.2 Logout
Menghapus (revoke) token yang sedang digunakan.
- **URL**: `/auth/logout`
- **Method**: `POST`
- **Header**: `Authorization: Bearer <token>`
- **Response Success (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Logout berhasil. Token telah dicabut."
  }
  ```

---

## 2. Profil Karyawan

### 2.1 Get Current Profile
Mendapatkan detail profil karyawan beserta jadwal shift hariannya.
- **URL**: `/mobile/v1/profile`
- **Method**: `GET`
- **Header**: `Authorization: Bearer <token>`
- **Response Success**:
  ```json
  {
    "success": true,
    "data": {
      "id": 10,
      "name": "Budi Santoso",
      "employee_code": "EMP001",
      "shift_type": "Shift Pagi (08:00 - 17:00)",
      "department": { "name": "IT" },
      "position": { "name": "Staff" }
    }
  }
  ```

### 2.2 Update Foto Profil
- **URL**: `/mobile/v1/profile/photo`
- **Method**: `POST` (Gunakan `multipart/form-data`)
- **Body**:
  - `photo`: File gambar (JPG, PNG)

### 2.3 Update Password
- **URL**: `/mobile/v1/profile/password`
- **Method**: `PUT`
- **Body** (JSON):
  ```json
  {
    "current_password": "password_lama",
    "password": "password_baru",
    "password_confirmation": "password_baru"
  }
  ```

---

## 3. Absensi (Attendance)

### 3.1 Cek Absensi Hari Ini
Melihat apakah karyawan sudah check-in / check-out pada hari ini.
- **URL**: `/mobile/v1/attendance/today`
- **Method**: `GET`
- **Response Success**:
  ```json
  {
    "success": true,
    "data": {
      "id": 105,
      "attendance_date": "2026-05-12",
      "check_in": "08:15:00",
      "check_out": null,
      "status": "hadir"
    }
  }
  ```
*(Catatan: Jika `data` bernilai `null`, berarti belum ada catatan absensi hari ini).*

### 3.2 Riwayat Absensi
Melihat daftar riwayat absensi bulanan.
- **URL**: `/mobile/v1/attendance/history`
- **Method**: `GET`
- **Query Params (Opsional)**:
  - `month` (int) - Default: Bulan ini
  - `year` (int) - Default: Tahun ini
  - `per_page` (int) - Default: 10

### 3.3 Ringkasan Absensi (Summary)
Melihat rekap jumlah kehadiran, keterlambatan, sakit, cuti, dll dalam sebulan.
- **URL**: `/mobile/v1/attendance/summary`
- **Method**: `GET`
- **Query Params**: `month` & `year`

---

## 4. Pengajuan Cuti / Izin (Leave)

### 4.1 Daftar Pengajuan Cuti/Izin
- **URL**: `/mobile/v1/leave`
- **Method**: `GET`

### 4.2 Buat Pengajuan Baru
- **URL**: `/mobile/v1/leave`
- **Method**: `POST`
- **Body** (Gunakan `multipart/form-data` jika ada file lampiran):
  - `leave_type`: `cuti`, `izin`, atau `sakit`
  - `start_date`: `YYYY-MM-DD`
  - `end_date`: `YYYY-MM-DD`
  - `reason`: "Alasan lengkap..."
  - `attachment`: File lampiran (Opsional, PDF/JPG)

### 4.3 Batalkan Pengajuan (Status Pending)
- **URL**: `/mobile/v1/leave/{id}`
- **Method**: `DELETE`

---

## 5. Pengumuman (Announcements)

- **GET `/mobile/v1/announcements`**  
  Mendapatkan daftar pengumuman untuk karyawan (mendukung pagination).
- **GET `/mobile/v1/announcements/unread-count`**  
  Mendapatkan jumlah pengumuman yang belum dibaca.
- **GET `/mobile/v1/announcements/popups`**  
  Mendapatkan pengumuman penting/mendesak yang perlu dimunculkan sebagai popup di Aplikasi.
- **POST `/mobile/v1/announcements/{id}/mark-read`**  
  Menandai satu pengumuman sudah dibaca.
- **POST `/mobile/v1/announcements/mark-all-read`**  
  Menandai semua pengumuman telah dibaca.

---

## 6. Slip Gaji (Payslip)

- **GET `/mobile/v1/payslip`**  
  Mendapatkan riwayat slip gaji bulanan karyawan.
- **GET `/mobile/v1/payslip/download?id={payslip_id}`**  
  Download/Mendapatkan file PDF slip gaji.

---

## 7. Surat Peringatan (Warning Letters)

- **GET `/mobile/v1/warning-letters`**  
  Melihat daftar Surat Peringatan (SP).
- **GET `/mobile/v1/warning-letters/statistics`**  
  Melihat statistik status Surat Peringatan aktif.
- **GET `/mobile/v1/warning-letters/{id}`**  
  Melihat detail sebuah SP.
- **GET `/mobile/v1/warning-letters/{id}/download`**  
  Download file dokumen/PDF surat peringatan tersebut.

---

### Catatan Keamanan & Troubleshooting:
1. Pastikan Header `Accept: application/json` disematkan dalam setiap HTTP Request agar server selalu membalas dengan format JSON (meski terjadi error 500/404).
</textarea>

<script>
    // Parse Markdown
    document.getElementById('content').innerHTML = marked.parse(document.getElementById('markdown-content').value);
    
    // Highlight Code Blocks
    document.querySelectorAll('pre code').forEach((block) => {
        hljs.highlightElement(block);
    });

    // Generate Table of Contents
    const headings = document.querySelectorAll('#content h2, #content h3');
    const toc = document.getElementById('toc');
    
    headings.forEach(heading => {
        const id = heading.innerText.toLowerCase().replace(/[\s\W-]+/g, '-');
        heading.id = id;
        
        const link = document.createElement('a');
        link.href = '#' + id;
        link.innerText = heading.innerText;
        
        if (heading.tagName === 'H2') {
            link.className = 'font-semibold text-gray-800 mt-3 hover:text-indigo-600 transition-colors py-1';
        } else {
            link.className = 'ml-4 text-gray-500 hover:text-indigo-600 transition-colors py-1 pl-2 border-l-2 border-transparent hover:border-indigo-400';
            
            // Highlight GET/POST badges in TOC if any
            if(heading.innerText.includes('GET') || heading.innerText.includes('POST') || heading.innerText.includes('PUT') || heading.innerText.includes('DELETE')) {
                const badge = document.createElement('span');
                badge.className = 'px-1.5 py-0.5 rounded text-[10px] font-bold mr-1.5 text-white';
                
                if(heading.innerText.includes('GET')) { badge.classList.add('bg-blue-500'); badge.innerText = 'GET'; link.innerText = link.innerText.replace('GET', '');}
                else if(heading.innerText.includes('POST')) { badge.classList.add('bg-green-500'); badge.innerText = 'POST'; link.innerText = link.innerText.replace('POST', '');}
                else if(heading.innerText.includes('PUT')) { badge.classList.add('bg-yellow-500'); badge.innerText = 'PUT'; link.innerText = link.innerText.replace('PUT', '');}
                else if(heading.innerText.includes('DELETE')) { badge.classList.add('bg-red-500'); badge.innerText = 'DEL'; link.innerText = link.innerText.replace('DELETE', '');}
                
                link.prepend(badge);
            }
        }
        
        // Add click listener for smooth scrolling and mobile menu close
        link.addEventListener('click', (e) => {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            if (window.innerWidth < 768) {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        });

        toc.appendChild(link);
    });

    // Mobile Menu Toggle
    const menuBtn = document.getElementById('menu-btn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');

    menuBtn.addEventListener('click', () => {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
    });

    overlay.addEventListener('click', () => {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    });

    // Color code HTTP Methods in content
    const contentHtml = document.getElementById('content').innerHTML;
    document.getElementById('content').innerHTML = contentHtml
        .replace(/\b(GET)\b/g, '<span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded font-bold text-sm">GET</span>')
        .replace(/\b(POST)\b/g, '<span class="px-2 py-0.5 bg-green-100 text-green-800 rounded font-bold text-sm">POST</span>')
        .replace(/\b(PUT)\b/g, '<span class="px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded font-bold text-sm">PUT</span>')
        .replace(/\b(DELETE)\b/g, '<span class="px-2 py-0.5 bg-red-100 text-red-800 rounded font-bold text-sm">DELETE</span>');
</script>
</body>
</html>
