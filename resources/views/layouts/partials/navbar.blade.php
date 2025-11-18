<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
    id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="bx bx-menu bx-sm"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        <!-- Search -->
        <div class="navbar-nav align-items-center">
            <div class="nav-item d-flex align-items-center">
                <i class="bx bx-search fs-4 lh-0"></i>
                <input type="text" class="form-control border-0 shadow-none" placeholder="Cari..."
                    aria-label="Search..." />
            </div>
        </div>
        <!-- /Search -->

        <ul class="navbar-nav flex-row align-items-center ms-auto">
            <!-- Tanggal dan Waktu -->
            <li class="nav-item lh-1 me-3">
                <!-- Full datetime for large screens -->
                <span class="text-muted d-none d-lg-inline" id="current-datetime-full"></span>
                <!-- Date only for medium screens -->
                <span class="text-muted d-none d-md-inline d-lg-none" id="current-datetime-medium"></span>
                <!-- Time only for small screens -->
                <span class="text-muted d-inline d-md-none" id="current-datetime-small"></span>
            </li>

            <!-- User -->
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        @if (Auth::user()->role == 'admin' && Auth::user()->profile_photo)
                            <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}" alt
                                class="w-px-40 h-px-40 rounded-circle" style="object-fit: cover;" />
                        @elseif (Auth::user()->employee && Auth::user()->employee->profile_photo)
                            <img src="{{ asset('storage/' . Auth::user()->employee->profile_photo) }}" alt
                                class="w-px-40 h-px-40 rounded-circle" style="object-fit: cover;" />
                        @else
                            <img src="{{ asset('sneat-1.0.0/assets/img/avatars/1.png') }}" alt
                                class="w-px-40 h-px-40 rounded-circle" />
                        @endif
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="#">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-online">
                                        @if (Auth::user()->role == 'admin' && Auth::user()->profile_photo)
                                            <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}" alt
                                                class="w-px-40 h-px-40 rounded-circle" style="object-fit: cover;" />
                                        @elseif (Auth::user()->employee && Auth::user()->employee->profile_photo)
                                            <img src="{{ asset('storage/' . Auth::user()->employee->profile_photo) }}"
                                                alt class="w-px-40 h-px-40 rounded-circle" style="object-fit: cover;" />
                                        @else
                                            <img src="{{ asset('sneat-1.0.0/assets/img/avatars/1.png') }}" alt
                                                class="w-px-40 h-px-40 rounded-circle" />
                                        @endif
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="fw-semibold d-block">{{ Auth::user()->name ?? 'User' }}</span>
                                    <small class="text-muted">{{ Auth::user()->role ?? 'Admin' }}</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        @if (Auth::user()->role == 'admin')
                            <a class="dropdown-item" href="{{ route('admin.profile.index') }}">
                                <i class="bx bx-user me-2"></i>
                                <span class="align-middle">Profil Saya</span>
                            </a>
                        @else
                            <a class="dropdown-item" href="{{ route('employee.profile.index') }}">
                                <i class="bx bx-user me-2"></i>
                                <span class="align-middle">Profil Saya</span>
                            </a>
                        @endif
                    </li>
                    <li>
                        <a class="dropdown-item" href="#">
                            <i class="bx bx-cog me-2"></i>
                            <span class="align-middle">Pengaturan</span>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="bx bx-power-off me-2"></i>
                                <span class="align-middle">Keluar</span>
                            </button>
                        </form>
                    </li>
                </ul>
            </li>
            <!--/ User -->
        </ul>
    </div>
</nav>

@push('styles')
    <style>
        /* Ensure dropdown appears below navbar */
        .navbar-dropdown .dropdown-menu {
            z-index: 1050 !important;
            margin-top: 0.5rem !important;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }

        /* Fix for dropdown positioning */
        .layout-navbar {
            position: relative;
            z-index: 1000;
        }

        /* Avatar in dropdown should be visible */
        .dropdown-menu .avatar,
        .dropdown-menu .avatar img,
        .dropdown-menu .avatar-initial {
            position: relative;
            z-index: 1051;
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Update waktu real-time
        function updateDateTime() {
            const now = new Date();

            // Full datetime for large screens (lg+)
            const optionsFull = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            const fullDateTime = now.toLocaleDateString('id-ID', optionsFull);
            const fullElement = document.getElementById('current-datetime-full');
            if (fullElement) fullElement.textContent = fullDateTime;

            // Date only for medium screens (md-lg)
            const optionsMedium = {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            const mediumDateTime = now.toLocaleDateString('id-ID', optionsMedium);
            const mediumElement = document.getElementById('current-datetime-medium');
            if (mediumElement) mediumElement.textContent = mediumDateTime;

            // Time only for small screens (sm and below)
            const optionsSmall = {
                hour: '2-digit',
                minute: '2-digit'
            };
            const smallDateTime = now.toLocaleTimeString('id-ID', optionsSmall);
            const smallElement = document.getElementById('current-datetime-small');
            if (smallElement) smallElement.textContent = smallDateTime;
        }

        // Update setiap detik
        setInterval(updateDateTime, 1000);
        updateDateTime(); // Panggil sekali saat load
    </script>
@endpush
