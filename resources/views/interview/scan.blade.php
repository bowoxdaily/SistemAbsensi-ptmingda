<!DOCTYPE html>
<html lang="id" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="{{ asset('assets') }}/">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Interview Check-in - {{ config('app.name') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .scan-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
        }
        .scan-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .scan-header i {
            font-size: 60px;
            margin-bottom: 15px;
        }
        .scan-body {
            padding: 30px;
        }
        .info-row {
            display: flex;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
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
            font-weight: 500;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-scheduled {
            background: #fff3cd;
            color: #856404;
        }
        .status-confirmed {
            background: #d1ecf1;
            color: #0c5460;
        }
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        .btn-checkin {
            width: 100%;
            padding: 15px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 10px;
            margin-top: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            transition: transform 0.2s;
        }
        .btn-checkin:hover {
            transform: scale(1.02);
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        .btn-checkin:active {
            transform: scale(0.98);
        }
        .checked-in-info {
            background: #d4edda;
            border: 2px solid #28a745;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-top: 20px;
        }
        .checked-in-info i {
            font-size: 50px;
            color: #28a745;
            margin-bottom: 15px;
        }
        .security-input {
            margin-top: 15px;
        }
        .qr-code-section {
            text-align: center;
            padding: 30px 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .qr-code-section h5 {
            color: #333;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .qr-code-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            display: inline-block;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .qr-code-container img {
            width: 250px;
            height: 250px;
            border: 3px solid #667eea;
            border-radius: 10px;
        }
        .qr-instructions {
            margin-top: 15px;
            color: #666;
            font-size: 14px;
        }
        .qr-instructions i {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="scan-card">
        <div class="scan-header">
            <i class="fas fa-user-tie"></i>
            <h3 class="mb-0">Interview Check-in</h3>
            <small>PT. Mingda International Footwear</small>
        </div>
        
        <div class="scan-body">
            @if(!$interview->isCheckedIn())
                <!-- QR Code Section -->
                <div class="qr-code-section">
                    <h5><i class="fas fa-qrcode me-2"></i> QR Code Check-in</h5>
                    <div class="qr-code-container">
                        <img src="{{ $interview->qr_code_image }}" alt="QR Code" id="qrCodeImage">
                    </div>
                    <div class="qr-instructions">
                        <i class="fas fa-info-circle"></i>
                        <strong>Tunjukkan QR Code ini kepada Security di pintu masuk</strong><br>
                        <small>Security akan menscan untuk melakukan check-in</small>
                    </div>
                </div>
            @endif
            
            @if($interview->isCheckedIn())
                <div class="checked-in-info">
                    <i class="fas fa-check-circle"></i>
                    <h5 class="text-success mb-2">Sudah Check-in</h5>
                    <p class="mb-1">
                        <strong>{{ $interview->checked_in_at->format('d/m/Y H:i') }}</strong>
                    </p>
                    <small class="text-muted">Oleh: {{ $interview->checked_in_by }}</small>
                </div>
            @endif
            
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
            
            <div class="info-row">
                <div class="info-label">Lokasi</div>
                <div class="info-value">{{ $interview->location }}</div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Status</div>
                <div class="info-value">
                    @php
                        $statusClass = 'status-scheduled';
                        $statusText = 'Terjadwal';
                        if($interview->status === 'confirmed') {
                            $statusClass = 'status-confirmed';
                            $statusText = 'Terkonfirmasi';
                        } elseif($interview->status === 'completed') {
                            $statusClass = 'status-completed';
                            $statusText = 'Selesai';
                        } elseif($interview->status === 'cancelled') {
                            $statusClass = 'status-cancelled';
                            $statusText = 'Dibatalkan';
                        }
                    @endphp
                    <span class="status-badge {{ $statusClass }}">{{ $statusText }}</span>
                </div>
            </div>
            
            @if(!$interview->isCheckedIn())
                <div class="alert alert-info text-center mt-3 mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Tunjukkan QR Code di atas kepada Petugas Security</strong><br>
                    <small>Security akan menscan QR Code untuk check-in</small>
                </div>
            @else
                <div class="alert alert-success text-center mt-3 mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Kandidat dapat menuju ke ruang HRD
                </div>
            @endif
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
