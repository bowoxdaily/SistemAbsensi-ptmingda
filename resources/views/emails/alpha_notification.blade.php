<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemberitahuan Alpha</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(180deg, #fff6f6 0%, #fffafa 100%);
            color: #1f2937;
            line-height: 1.6;
            padding: 24px 12px;
        }
        .wrapper {
            max-width: 620px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #f2d5d5;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 35px rgba(127, 29, 29, 0.08);
        }
        .top-accent {
            height: 6px;
            background: linear-gradient(90deg, #991b1b 0%, #ef4444 55%, #f59e0b 100%);
        }
        .header {
            background: linear-gradient(135deg, #991b1b 0%, #ef4444 55%, #f59e0b 100%);
            padding: 32px 36px 24px;
            color: #ffffff;
        }
        .header-label {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.1px;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.15);
            margin-bottom: 12px;
        }
        .header h1 {
            font-size: 22px;
            margin-bottom: 6px;
        }
        .header p {
            font-size: 13px;
            color: rgba(255,255,255,0.85);
        }
        .body {
            padding: 30px 36px 28px;
        }
        .greeting {
            margin-bottom: 18px;
            color: #374151;
        }
        .summary {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 18px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dashed #fda4af;
            padding: 8px 0;
            font-size: 14px;
        }
        .summary-row:last-child {
            border-bottom: none;
        }
        .summary .label {
            color: #6b7280;
        }
        .summary .value {
            color: #7f1d1d;
            font-weight: 700;
            text-align: right;
            margin-left: 12px;
        }
        .note {
            font-size: 14px;
            color: #4b5563;
            margin-top: 12px;
        }
        .footer {
            background: #fff7ed;
            border-top: 1px solid #fed7aa;
            padding: 18px 36px;
            text-align: center;
        }
        .footer p {
            font-size: 12px;
            color: #9a3412;
            margin-bottom: 4px;
        }
        .footer a {
            color: #b45309;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="top-accent"></div>

        <div class="header">
            <div class="header-label">{{ $appName }}</div>
            <h1>Pemberitahuan Alpha</h1>
            <p>Informasi kehadiran karyawan</p>
        </div>

        <div class="body">
            <p class="greeting">Kepada <strong>{{ $recipientName }}</strong>,</p>

            <div class="summary">
                <div class="summary-row">
                    <span class="label">NIP</span>
                    <span class="value">{{ $employeeCode }}</span>
                </div>
                <div class="summary-row">
                    <span class="label">Departemen</span>
                    <span class="value">{{ $departmentName }}</span>
                </div>
                <div class="summary-row">
                    <span class="label">Tanggal Alpha</span>
                    <span class="value">{{ $attendanceDate }}</span>
                </div>
                <div class="summary-row">
                    <span class="label">Total Alpha Bulan Ini</span>
                    <span class="value">{{ $totalAlpha }} hari</span>
                </div>
            </div>

            <p class="note">
                Status alpha dapat memengaruhi perhitungan penggajian. Jika Anda merasa ada ketidaksesuaian data,
                segera hubungi HRD untuk klarifikasi.
            </p>
            <p class="note" style="margin-top:10px; font-size:13px; color:#6b7280;">
                Email ini dikirim otomatis oleh sistem. Mohon tidak membalas email ini.
            </p>
        </div>

        <div class="footer">
            <p><strong>{{ $appName }}</strong></p>
            <p><a href="{{ $appUrl }}">{{ $appUrl }}</a></p>
            <p style="margin-top:8px;">&copy; {{ date('Y') }} {{ $appName }}</p>
        </div>
    </div>
</body>
</html>
