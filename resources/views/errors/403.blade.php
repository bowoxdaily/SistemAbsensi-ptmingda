<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak</title>
    <link rel="stylesheet" href="{{ asset('sneat-1.0.0/assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('sneat-1.0.0/assets/vendor/css/theme-default.css') }}" />
    <link rel="stylesheet" href="{{ asset('sneat-1.0.0/assets/vendor/fonts/boxicons.css') }}" />
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Public Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
        }

        .error-container {
            text-align: center;
            padding: 2rem;
        }

        .error-card {
            background: white;
            border-radius: 20px;
            padding: 3rem 2rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            margin: 0 auto;
        }

        .error-icon {
            font-size: 120px;
            color: #ea5455;
            margin-bottom: 1rem;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            10%,
            30%,
            50%,
            70%,
            90% {
                transform: translateX(-10px);
            }

            20%,
            40%,
            60%,
            80% {
                transform: translateX(10px);
            }
        }

        .error-code {
            font-size: 72px;
            font-weight: 800;
            color: #333;
            margin: 0;
            line-height: 1;
        }

        .error-title {
            font-size: 24px;
            font-weight: 600;
            color: #555;
            margin: 1rem 0;
        }

        .error-message {
            color: #777;
            font-size: 16px;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .error-details {
            background: #f8f9fa;
            border-left: 4px solid #ea5455;
            padding: 1rem;
            margin: 1.5rem 0;
            border-radius: 8px;
            text-align: left;
        }

        .error-details strong {
            color: #333;
            display: block;
            margin-bottom: 0.5rem;
        }

        .error-details p {
            color: #666;
            margin: 0;
            font-size: 14px;
        }

        .btn-container {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #667eea;
            color: white;
            border: 2px solid #667eea;
        }

        .btn-primary:hover {
            background: #5568d3;
            border-color: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-outline {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-outline:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        .user-info {
            background: #e3f2fd;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .user-info p {
            margin: 0;
            color: #1976d2;
            font-size: 14px;
        }

        @media (max-width: 576px) {
            .error-card {
                padding: 2rem 1rem;
            }

            .error-icon {
                font-size: 80px;
            }

            .error-code {
                font-size: 56px;
            }

            .error-title {
                font-size: 20px;
            }

            .btn-container {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="error-container">
        <div class="error-card">
            <i class='bx bx-block error-icon'></i>
            <h1 class="error-code">403</h1>
            <h2 class="error-title">Akses Ditolak</h2>
            <p class="error-message">
                Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.
            </p>

            @auth
                <div class="user-info">
                    <p><strong>Pengguna:</strong> {{ Auth::user()->name }}</p>
                    <p><strong>Role:</strong> {{ ucfirst(Auth::user()->role ?? 'employee') }}</p>
                </div>
            @endauth

            <div class="error-details">
                <strong><i class='bx bx-info-circle'></i> Kenapa ini terjadi?</strong>
                <p>
                    Halaman yang Anda coba akses memerlukan hak akses khusus.
                    @if (Auth::check())
                        @if (Auth::user()->role === 'admin')
                            Jika Anda merasa ini adalah kesalahan, hubungi administrator sistem.
                        @else
                            Halaman ini hanya dapat diakses oleh Administrator.
                        @endif
                    @else
                        Silakan login terlebih dahulu untuk melanjutkan.
                    @endif
                </p>
            </div>

            <div class="btn-container">
                @auth
                    @if (Auth::user()->role === 'admin')
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">
                            <i class='bx bx-home-circle'></i> Dashboard Admin
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">
                            <i class='bx bx-home-circle'></i> Dashboard Saya
                        </a>
                    @endif
                    <a href="javascript:history.back()" class="btn btn-outline">
                        <i class='bx bx-arrow-back'></i> Kembali
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">
                        <i class='bx bx-log-in'></i> Login
                    </a>
                @endauth
            </div>
        </div>

        <div style="margin-top: 2rem; color: white; opacity: 0.8; font-size: 14px;">
            <p>&copy; {{ date('Y') }} Sistem Absensi Karyawan. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
