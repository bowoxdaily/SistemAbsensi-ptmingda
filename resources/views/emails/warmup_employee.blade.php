<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #f9f9f9;
        }
        .content {
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            <p>Selamat Datang, {{ $employee->nama_lengkap }}!</p>
            
            <p>Kami dengan senang hati menyambut Anda bergabung dengan PT Mingda.</p>
            
            <p>Akun Anda telah berhasil dibuat di Sistem Absensi kami.</p>
            
            <p>Jika ada pertanyaan, silakan hubungi HRD kami.</p>
            
            <p>Terima kasih,<br>
            Tim {{ $appName }}</p>
        </div>
    </div>
</body>
</html>
