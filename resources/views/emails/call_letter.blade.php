<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Panggilan {{ $callLetter->letter_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; background: #f7f9fc; color: #10233e; line-height: 1.6; padding: 24px 12px; }
        .wrapper { max-width: 620px; margin: 0 auto; background: #fff; border: 1px solid #d7e0ec; border-radius: 16px; overflow: hidden; }
        .header { padding: 28px 36px; text-align: center; color: #fff; background: linear-gradient(135deg, #0f2f57, #2563eb, #0f766e); }
        .header h1 { font-size: 22px; }
        .body { padding: 32px 36px; }
        .meta { margin: 20px 0; padding: 14px 16px; border: 1px solid #dce5ef; border-radius: 12px; background: #f8fafc; }
        .meta p { padding: 4px 0; font-size: 13px; }
        .footer { padding: 20px 36px; border-top: 1px solid #e5e7eb; background: #f8fafc; text-align: center; color: #9ca3af; font-size: 12px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header"><p>{{ $appName }}</p><h1>Surat Panggilan</h1><p>Surat resmi dari Human Resources Department</p></div>
        <div class="body">
            <p>Kepada Yth. <strong>{{ $recipientName }}</strong>,</p>
            <p style="margin-top:16px">Dengan hormat, surat panggilan berikut diterbitkan untuk menjadi perhatian Saudara/i.</p>
            <div class="meta">
                <p><strong>Nomor surat:</strong> {{ $callLetter->letter_number }}</p>
                <p><strong>Tanggal terbit:</strong> {{ $callLetter->issue_date->format('d-m-Y') }}</p>
                <p><strong>Jadwal klarifikasi:</strong> {{ $callLetter->meeting_at->format('d-m-Y H:i') }}</p>
                <p><strong>Tempat:</strong> {{ $callLetter->location }}</p>
            </div>
            <p><strong>Keperluan / Hal yang Diklarifikasi</strong></p>
            <p style="white-space:pre-line">{{ $callLetter->purpose }}</p>
            @if ($callLetter->description)
                <p style="margin-top:16px"><strong>Keterangan Tambahan</strong></p>
                <p style="white-space:pre-line">{{ $callLetter->description }}</p>
            @endif
            <p style="margin-top:20px">Surat resmi tersedia sebagai lampiran PDF email ini.</p>
            <p style="margin-top:20px">Hormat kami,<br><strong>Human Resources Department<br>{{ $appName }}</strong></p>
        </div>
        <div class="footer"><p><strong>{{ $appName }}</strong></p><p>Email ini berisi informasi kepegawaian yang bersifat rahasia.</p><p style="margin-top:8px">© {{ date('Y') }} {{ $appName }}. All rights reserved.</p></div>
    </div>
</body>
</html>
