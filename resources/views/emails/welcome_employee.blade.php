<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang — {{ $appName }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.14), transparent 34%),
                radial-gradient(circle at top right, rgba(15, 118, 110, 0.10), transparent 28%),
                linear-gradient(180deg, #eef3f9 0%, #f7f9fc 100%);
            color: #10233e;
            line-height: 1.6;
            padding: 24px 12px;
        }

        .wrapper {
            max-width: 640px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #d7e0ec;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 18px 50px rgba(16, 35, 62, 0.10);
        }

        .top-accent {
            height: 8px;
            background: linear-gradient(90deg, #0f2f57 0%, #2563eb 52%, #0f766e 100%);
        }

        .header {
            background:
                linear-gradient(135deg, rgba(15, 47, 87, 0.96) 0%, rgba(37, 99, 235, 0.95) 56%, rgba(15, 118, 110, 0.96) 100%);
            padding: 40px 40px 34px;
            text-align: center;
            color: #ffffff;
        }

        .header-pill {
            display: inline-block;
            background: rgba(255,255,255,0.14);
            border: 1px solid rgba(255,255,255,0.18);
            color: rgba(255,255,255,0.92);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            border-radius: 999px;
            padding: 5px 12px;
            margin-bottom: 18px;
        }

        .header-eyebrow {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1.4px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.75);
            margin-bottom: 10px;
        }

        .header-title {
            font-size: 31px;
            line-height: 1.15;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 10px;
            letter-spacing: -0.02em;
        }

        .header-subtitle {
            font-size: 15px;
            color: rgba(255,255,255,0.82);
            max-width: 480px;
            margin: 0 auto;
        }

        .header-emoji {
            font-size: 46px;
            margin-bottom: 14px;
            display: block;
        }

        .body {
            padding: 36px 40px 40px;
        }

        .greeting {
            font-size: 19px;
            font-weight: 700;
            color: #10233e;
            margin-bottom: 12px;
        }

        .intro-text {
            font-size: 15px;
            color: #51627a;
            margin-bottom: 28px;
        }

        .info-card {
            background: linear-gradient(180deg, #fbfdff 0%, #f7faff 100%);
            border: 1px solid #dbe5f1;
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 26px;
            overflow: hidden;
        }

        .info-card-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.7px;
            text-transform: uppercase;
            color: #6c7f97;
            margin-bottom: 18px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e5edf6;
            font-size: 14px;
            gap: 16px;
        }

        .info-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .info-label {
            color: #71839a;
            font-weight: 600;
            flex-shrink: 0;
            margin-right: 16px;
        }

        .info-value {
            color: #10233e;
            font-weight: 700;
            text-align: right;
        }

        .steps-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.7px;
            text-transform: uppercase;
            color: #6c7f97;
            margin-bottom: 16px;
        }

        .step {
            width: 100%;
            margin-bottom: 12px;
        }

        .step-number {
            background: linear-gradient(135deg, #2563eb 0%, #0f766e 100%);
            color: #ffffff;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            margin-top: 1px;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.20);
        }

        .step-text {
            font-size: 14px;
            color: #51627a;
            line-height: 1.7;
        }

        .step-text strong {
            color: #10233e;
        }

        .cta-wrapper {
            text-align: center;
            margin: 32px 0 22px;
        }

        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #0f2f57 0%, #2563eb 52%, #0f766e 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 15px 38px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.2px;
            box-shadow: 0 12px 24px rgba(15, 47, 87, 0.22);
        }

        .cta-note {
            font-size: 12px;
            color: #7a8ca4;
            margin-top: 10px;
        }

        .warning-box {
            background: linear-gradient(180deg, #fffdf6 0%, #fff8ea 100%);
            border: 1px solid #f5dfb2;
            border-left: 4px solid #d18b1f;
            border-radius: 14px;
            padding: 16px 18px;
            margin-bottom: 26px;
            font-size: 13px;
            color: #7a4a13;
        }

        .warning-box strong {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            color: #8c5715;
        }

        .support-box {
            background: #effbf6;
            border: 1px solid #b9ead0;
            border-left: 4px solid #1f8a5b;
            border-radius: 14px;
            padding: 16px 18px;
            margin-top: 22px;
            color: #24684a;
            font-size: 13px;
        }

        .support-box strong {
            display: block;
            margin-bottom: 8px;
            color: #18553b;
        }

        .divider {
            border: none;
            border-top: 1px solid #e4ebf3;
            margin: 24px 0;
        }

        .footer {
            background: #f7faff;
            border-top: 1px solid #e4ebf3;
            padding: 24px 40px;
            text-align: center;
        }

        .footer-company {
            font-size: 13px;
            font-weight: 600;
            color: #41556e;
            margin-bottom: 4px;
        }

        .footer-note {
            font-size: 12px;
            color: #7a8ca4;
        }

        .badge {
            display: inline-block;
            background: linear-gradient(135deg, #e8f0ff 0%, #e7faf7 100%);
            color: #1552cc;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 99px;
            letter-spacing: 0.5px;
        }

        .copy-link {
            word-break: break-all;
            color: #2563eb;
            font-size: 12px;
        }

        @media only screen and (max-width: 640px) {
            body {
                padding: 12px 8px;
            }

            .wrapper {
                border-radius: 20px;
            }

            .header,
            .body,
            .footer {
                padding-left: 22px;
                padding-right: 22px;
            }

            .header-title {
                font-size: 26px;
            }

            .greeting {
                font-size: 18px;
            }

            .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }

            .info-value {
                text-align: left;
            }

            .cta-button {
                display: block;
                width: 100%;
                padding-left: 22px;
                padding-right: 22px;
            }

            .step-text {
                line-height: 1.6;
            }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="top-accent"></div>

    <!-- Header -->
    <div class="header">
        <div class="header-pill">Akun Baru</div>
        <span class="header-emoji">🎉</span>
        <div class="header-eyebrow">{{ $appName }}</div>
        <div class="header-title">Selamat Datang di Sistem Absensi</div>
        <div class="header-subtitle">Akun Anda sudah aktif, dan Anda bisa mulai mengatur password pertama sebelum menggunakan portal HR.</div>
    </div>

    <!-- Body -->
    <div class="body">

        @php
            $loginEmail = $employee->user->email ?? $employee->email;
        @endphp

        <p class="greeting">Halo, {{ $employee->name }}!</p>
        <p class="intro-text">
            Kami dengan bangga menyambut Anda sebagai bagian dari keluarga besar
            <strong>PT Mingda International Footwear</strong>.
            Di bawah ini adalah ringkasan akun dan langkah awal untuk mulai menggunakan sistem.
        </p>

        <!-- Employee Info Card -->
        <div class="info-card">
            <div class="info-card-title">📋 Ringkasan Akun</div>

            <div class="info-row">
                <span class="info-label">Kode Karyawan</span>
                <span class="info-value">
                    <span class="badge">{{ $employee->employee_code }}</span>
                </span>
            </div>

            @if($employee->department)
            <div class="info-row">
                <span class="info-label">Departemen</span>
                <span class="info-value">{{ $employee->department->name }}</span>
            </div>
            @endif

            @if($employee->position)
            <div class="info-row">
                <span class="info-label">Posisi</span>
                <span class="info-value">{{ $employee->position->name }}</span>
            </div>
            @endif

            <div class="info-row">
                <span class="info-label">Tanggal Bergabung</span>
                <span class="info-value">
                    {{ $employee->join_date
                        ? \Carbon\Carbon::parse($employee->join_date)->locale('id')->translatedFormat('d F Y')
                        : '-' }}
                </span>
            </div>

            @if($employee->user)
            <div class="info-row">
                <span class="info-label">Email Login</span>
                <span class="info-value">{{ $loginEmail }}</span>
            </div>
            @endif
        </div>

        <!-- Warning -->
        <div class="warning-box">
            <strong>⚠️ Keamanan Akun</strong>
            Password Anda sudah disetel oleh sistem. Segera buat password baru yang kuat setelah login pertama kali.
        </div>

        <!-- Steps -->
        <div class="steps-title">🚀 Langkah Memulai</div>

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td width="34" valign="top" style="padding:0 12px 14px 0;">
                    <div class="step-number">1</div>
                </td>
                <td valign="top" style="padding:0 0 14px 0;">
                    <div class="step-text">Klik tombol <strong>"Atur Password Saya"</strong> di bawah untuk membuat password baru yang aman.</div>
                </td>
            </tr>
            <tr>
                <td width="34" valign="top" style="padding:0 12px 14px 0;">
                    <div class="step-number">2</div>
                </td>
                <td valign="top" style="padding:0 0 14px 0;">
                    <div class="step-text">Login menggunakan email <strong>{{ $loginEmail }}</strong> dan password yang baru Anda buat.</div>
                </td>
            </tr>
            <tr>
                <td width="34" valign="top" style="padding:0 12px 0 0;">
                    <div class="step-number">3</div>
                </td>
                <td valign="top" style="padding:0;">
                    <div class="step-text">Gunakan portal untuk absensi harian, cuti, dan melihat informasi gaji Anda.</div>
                </td>
            </tr>
        </table>

        <!-- CTA -->
        <div class="cta-wrapper">
            <table role="presentation" align="center" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td align="center" bgcolor="#2563eb" style="border-radius:12px; background: linear-gradient(135deg, #0f2f57 0%, #2563eb 52%, #0f766e 100%);">
                        <a href="{{ $resetUrl }}" class="cta-button" style="display:inline-block;">
                            🔐 Atur Password Saya
                        </a>
                    </td>
                </tr>
            </table>
            <p class="cta-note">Link ini berlaku selama <strong>7 hari</strong></p>
        </div>

        <hr class="divider">

        <p style="font-size:13px; color:#718096; text-align:center;">
            Jika tombol tidak berfungsi, salin URL berikut ke browser Anda:<br>
            <span class="copy-link">{{ $resetUrl }}</span>
        </p>

        <hr class="divider">

        <!-- Expired link instructions -->
        <div class="support-box">
            <strong>🔄 Jika link sudah kadaluarsa (setelah 7 hari):</strong>
            <ol style="margin:0; padding-left:18px; line-height:1.8;">
                <li>Buka halaman: <a href="{{ $appUrl }}/forgot-password" style="color:#2563eb;">{{ $appUrl }}/forgot-password</a></li>
                <li>Masukkan email: <strong>{{ $loginEmail }}</strong></li>
                <li>Klik <strong>"Kirim Link Reset"</strong> untuk mengirim link baru ke email Anda</li>
            </ol>
        </div>

    </div>

    <!-- Footer -->
    <div class="footer">
        <p class="footer-company">HRD PT Mingda International Footwear</p>
        <p class="footer-note">
            Email ini dikirim otomatis oleh sistem. Jika ada pertanyaan, hubungi HRD.<br>
            © {{ date('Y') }} PT Mingda International Footwear. All rights reserved.
        </p>
    </div>

</div>
</body>
</html>
