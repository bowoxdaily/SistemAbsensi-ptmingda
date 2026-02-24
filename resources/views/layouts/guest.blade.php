<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Monitoring') | Guest View</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('sneat-1.0.0/assets/img/favicon/favicon.ico') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="{{ asset('sneat-1.0.0/assets/vendor/fonts/boxicons.css') }}" />

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="{{ asset('sneat-1.0.0/assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('sneat-1.0.0/assets/vendor/css/theme-default.css') }}" />
    <link rel="stylesheet" href="{{ asset('sneat-1.0.0/assets/css/demo.css') }}" />

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />

    @yield('styles')

    <style>
        body { background: #f5f5f9; font-family: 'Public Sans', sans-serif; }
        .guest-topbar {
            background: #fff;
            box-shadow: 0 1px 6px rgba(0,0,0,.08);
            padding: .6rem 1.2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .guest-topbar .brand { font-weight: 700; font-size: 1rem; color: #696cff; display:flex; align-items:center; gap:.4rem; }
        .guest-topbar .nav-links a { font-size: .85rem; color: #555; text-decoration: none; margin-left: .8rem; padding: .25rem .5rem; border-radius: 4px; transition: background .15s; }
        .guest-topbar .nav-links a:hover, .guest-topbar .nav-links a.active { background: #f0f0ff; color: #696cff; }
        .guest-topbar .badge-guest { font-size: .7rem; background: #fff3cd; color: #856404; border: 1px solid #ffc107; border-radius: 20px; padding: .15rem .5rem; margin-left: .5rem; }
        .guest-main { padding: 1.5rem 1rem; max-width: 1400px; margin: 0 auto; }
        @media (min-width: 576px) { .guest-main { padding: 1.5rem; } }
    </style>
</head>
<body>

<!-- Top Navigation Bar -->
<div class="guest-topbar">
    <div class="brand">
        <i class='bx bx-desktop' style="font-size:1.3rem"></i>
        Monitoring
        <span class="badge-guest"><i class='bx bx-show-alt me-1'></i>View Only</span>
    </div>
    <nav class="nav-links d-flex flex-wrap">
        <a href="{{ route('guest.dashboard') }}" class="{{ request()->routeIs('guest.dashboard') ? 'active' : '' }}">
            <i class='bx bxs-dashboard'></i> Dashboard
        </a>
        <a href="{{ route('guest.karyawan') }}" class="{{ request()->routeIs('guest.karyawan') ? 'active' : '' }}">
            <i class='bx bx-group'></i> Karyawan
        </a>
        <a href="{{ route('guest.absensi') }}" class="{{ request()->routeIs('guest.absensi') ? 'active' : '' }}">
            <i class='bx bx-calendar-check'></i> Absensi
        </a>
        <a href="{{ route('guest.interview') }}" class="{{ request()->routeIs('guest.interview') ? 'active' : '' }}">
            <i class='bx bx-user-voice'></i> Interview
        </a>
    </nav>
</div>

<!-- Main Content -->
<main class="guest-main">
    @yield('content')
</main>

<!-- Scripts -->
<script src="{{ asset('sneat-1.0.0/assets/vendor/libs/jquery/jquery.js') }}"></script>
<script src="{{ asset('sneat-1.0.0/assets/vendor/js/bootstrap.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@yield('scripts')
</body>
</html>
