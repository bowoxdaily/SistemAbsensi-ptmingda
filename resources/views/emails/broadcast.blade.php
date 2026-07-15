<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $broadcastTitle }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(180deg, #eef3f9 0%, #f7f9fc 100%);
            color: #10233e;
            line-height: 1.6;
            padding: 24px 12px;
        }
        .wrapper {
            max-width: 620px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #d7e0ec;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 12px 40px rgba(16, 35, 62, 0.09);
        }
        .top-accent {
            height: 6px;
            background: linear-gradient(90deg, #0f2f57 0%, #2563eb 52%, #0f766e 100%);
        }
        .header {
            background: linear-gradient(135deg, #0f2f57 0%, #2563eb 60%, #0f766e 100%);
            padding: 32px 36px 28px;
            text-align: center;
            color: #fff;
        }
        .header-label {
            display: inline-block;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.9);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            padding: 4px 14px;
            border-radius: 20px;
            margin-bottom: 12px;
        }
        .header h1 {
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
        }
        .header p {
            color: rgba(255,255,255,0.75);
            font-size: 13px;
        }
        .body {
            padding: 36px 36px 28px;
        }
        .greeting {
            font-size: 15px;
            color: #374151;
            margin-bottom: 20px;
        }
        .greeting strong {
            color: #1e3a5f;
        }
        .message-box {
            background: #f8fafc;
            border-left: 4px solid #2563eb;
            border-radius: 0 10px 10px 0;
            padding: 20px 22px;
            font-size: 15px;
            color: #1e293b;
            white-space: pre-line;
            margin-bottom: 24px;
        }
        .image-container {
            text-align: center;
            margin-bottom: 24px;
        }
        .image-container img {
            max-width: 100%;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }
        .footer {
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
            padding: 20px 36px;
            text-align: center;
        }
        .footer p {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 4px;
        }
        .footer a {
            color: #2563eb;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="top-accent"></div>

        <div class="header">
            <div class="header-label">{{ $appName }}</div>
            <h1>{{ $broadcastTitle }}</h1>
            <p>Pesan resmi dari manajemen</p>
        </div>

        <div class="body">
            <p class="greeting">Kepada <strong>{{ $recipientName }}</strong>,</p>

            @if($imageUrl)
            <div class="image-container">
                <img src="{{ $imageUrl }}" alt="Broadcast Image">
            </div>
            @endif

            <div class="message-box">{{ $broadcastMessage }}</div>

            <p style="font-size:13px; color:#6b7280;">
                Email ini dikirim secara otomatis oleh sistem. Mohon tidak membalas email ini.
            </p>
        </div>

        <div class="footer">
            <p><strong>{{ $appName }}</strong></p>
            <p>
                <a href="{{ $appUrl }}">{{ $appUrl }}</a>
            </p>
            <p style="margin-top:8px;">© {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
