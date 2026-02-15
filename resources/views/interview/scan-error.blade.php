<!DOCTYPE html>
<html lang="id" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="{{ asset('assets') }}/">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>QR Code Error - {{ config('app.name') }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            text-align: center;
            padding: 50px 30px;
        }
        .error-icon {
            font-size: 80px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        .error-message {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .info-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            text-align: left;
        }
        .info-row {
            display: flex;
            margin-bottom: 10px;
        }
        .info-row:last-child {
            margin-bottom: 0;
        }
        .info-label {
            font-weight: 600;
            color: #666;
            width: 120px;
            flex-shrink: 0;
        }
        .info-value {
            color: #333;
        }
        .btn-contact {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        .btn-contact:hover {
            transform: scale(1.05);
            color: white;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <h1 class="error-title">QR Code Tidak Valid</h1>
        
        <p class="error-message">
            {{ $message }}
        </p>
        
        @if(isset($interview))
            <div class="info-section">
                <h5 class="mb-3">Informasi Interview:</h5>
                <div class="info-row">
                    <div class="info-label">Nama</div>
                    <div class="info-value">{{ $interview->candidate_name }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Posisi</div>
                    <div class="info-value">{{ $interview->position->name }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Tanggal</div>
                    <div class="info-value">{{ $interview->interview_date->format('d/m/Y') }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Waktu</div>
                    <div class="info-value">{{ $interview->interview_time->format('H:i') }} WIB</div>
                </div>
            </div>
        @endif
        
        <div class="mt-4">
            <a href="https://wa.me/62{{ ltrim(env('HRD_PHONE', '8123456789'), '0') }}" class="btn-contact" target="_blank">
                <i class="fab fa-whatsapp me-2"></i> Hubungi HRD
            </a>
        </div>
        
        <p class="text-muted mt-3 mb-0">
            <small>Jika ada pertanyaan, silakan hubungi bagian HRD</small>
        </p>
    </div>
</body>
</html>
