<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Peringatan {{ $warningLetter->sp_type }} - {{ $warningLetter->sp_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; background: linear-gradient(180deg, #eef3f9 0%, #f7f9fc 100%); color: #10233e; line-height: 1.6; padding: 24px 12px; }
        .wrapper { max-width: 620px; margin: 0 auto; background: #fff; border: 1px solid #d7e0ec; border-radius: 20px; overflow: hidden; box-shadow: 0 12px 40px rgba(16,35,62,.09); }
        .top-accent { height: 6px; background: linear-gradient(90deg, #0f2f57 0%, #2563eb 52%, #0f766e 100%); }
        .header { padding: 32px 36px 28px; text-align: center; color: #fff; background: linear-gradient(135deg, #0f2f57 0%, #2563eb 60%, #0f766e 100%); }
        .header-label { display: inline-block; margin-bottom: 12px; padding: 4px 14px; border: 1px solid rgba(255,255,255,.2); border-radius: 20px; background: rgba(255,255,255,.15); color: rgba(255,255,255,.9); font-size: 11px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; }
        .header h1 { margin-bottom: 4px; color: #fff; font-size: 22px; font-weight: 700; }
        .header p { color: rgba(255,255,255,.78); font-size: 13px; }
        .body { padding: 32px 36px 28px; }
        .greeting { margin-bottom: 18px; color: #374151; font-size: 15px; }
        .meta { margin: 0 0 20px; padding: 14px 16px; border: 1px solid #dce5ef; border-radius: 12px; background: #f8fafc; }
        .meta-row { padding: 4px 0; font-size: 13px; }
        .meta-label { display: inline-block; width: 145px; color: #64748b; }
        .meta-value { color: #1e3a5f; font-weight: 700; }
        .section-title { margin: 20px 0 8px; color: #1e3a5f; font-size: 14px; font-weight: 700; }
        .message-box { margin-bottom: 18px; padding: 16px 18px; border-left: 4px solid #2563eb; border-radius: 0 10px 10px 0; background: #f8fafc; color: #1e293b; font-size: 14px; white-space: pre-line; }
        .footer { padding: 20px 36px; border-top: 1px solid #e5e7eb; background: #f8fafc; text-align: center; }
        .footer p { margin-bottom: 4px; color: #9ca3af; font-size: 12px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="top-accent"></div>
        <div class="header">
            <div class="header-label">{{ $appName }}</div>
            <h1>Surat Peringatan {{ $warningLetter->sp_type }}</h1>
            <p>Surat resmi dari Human Resources Department</p>
        </div>
        <div class="body">
            <p class="greeting">Kepada Yth. <strong>{{ $recipientName }}</strong>,</p>
            <p style="margin-bottom:18px;font-size:14px;color:#374151;">Dengan hormat, surat peringatan berikut diterbitkan untuk menjadi perhatian Saudara/i.</p>
            <div class="meta">
                <div class="meta-row"><span class="meta-label">Nomor surat</span><span class="meta-value">{{ $warningLetter->sp_number }}</span></div>
                <div class="meta-row"><span class="meta-label">Tanggal terbit</span><span class="meta-value">{{ $warningLetter->issue_date->format('d-m-Y') }}</span></div>
                <div class="meta-row"><span class="meta-label">Mulai berlaku</span><span class="meta-value">{{ $warningLetter->effective_date->format('d-m-Y') }}</span></div>
                @if ($warningLetter->completion_date)
                    <div class="meta-row"><span class="meta-label">Berlaku sampai</span><span class="meta-value">{{ $warningLetter->completion_date->format('d-m-Y') }}</span></div>
                @endif
            </div>
            <p class="section-title">Uraian Pelanggaran</p>
            <div class="message-box">{{ $warningLetter->violation }}</div>
            @if ($warningLetter->description)
                <p class="section-title">Keterangan Tambahan</p>
                <div class="message-box">{{ $warningLetter->description }}</div>
            @endif
            <p style="margin-bottom:18px;font-size:14px;color:#374151;">Kami meminta perhatian dan tindak lanjut Saudara/i sesuai ketentuan perusahaan. Untuk penjelasan lebih lanjut, silakan menghubungi Human Resources Department.</p>
            <p style="font-size:14px;color:#374151;">Hormat kami,<br><strong>Human Resources Department<br>{{ $appName }}</strong></p>
        </div>
        <div class="footer">
            <p><strong>{{ $appName }}</strong></p>
            <p>Email ini berisi informasi kepegawaian yang bersifat rahasia.</p>
            <p style="margin-top:8px;">© {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
