<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ route('dashboard') }}" class="app-brand-link">
            <span class="app-brand-logo demo">
                <img src="{{ asset('sneat-1.0.0/assets/img/logo.png') }}" alt="Logo"
                    style="max-height: 40px; width: auto;">
            </span>
            <span class="app-brand-text demo menu-text fw-bolder ms-2"
                style="text-transform: uppercase; letter-spacing: 1px; font-size: 1.1rem;">PT. MIF</span>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <!-- Dashboard -->
        <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <a href="{{ route('dashboard') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Dashboard">Dashboard</div>
            </a>
        </li>

        @if (Auth::user()->role == 'admin' || Auth::user()->role == 'manager')
            <!-- Menu untuk Admin & Manager -->
            <!-- Menu Header - Master Data -->
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Master Data</span>
            </li>

            <!-- Karyawan -->
            <li class="menu-item {{ request()->routeIs('admin.karyawan.*') ? 'active' : '' }}">
                <a href="{{ route('admin.karyawan.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-user"></i>
                    <div data-i18n="Karyawan">Karyawan</div>
                </a>
            </li>

            <!-- Organisasi (Dropdown) -->
            <li
                class="menu-item {{ request()->is('admin/department*') || request()->is('admin/sub-departments*') || request()->routeIs('admin.positions.*') ? 'active open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-buildings"></i>
                    <div data-i18n="Organisasi">Organisasi</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item {{ request()->is('admin/department*') ? 'active' : '' }}">
                        <a href="{{ route('admin.department.index') }}" class="menu-link">
                            <div data-i18n="Departemen">Departemen</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->is('admin/sub-departments*') ? 'active' : '' }}">
                        <a href="{{ route('admin.sub-departments.index') }}" class="menu-link">
                            <div data-i18n="Sub Departemen">Sub Departemen</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('admin.positions.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.positions.index') }}" class="menu-link">
                            <div data-i18n="Jabatan">Jabatan</div>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Menu Header - Absensi -->
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Absensi</span>
            </li>

            <!-- Absensi (Dropdown) -->
            <li
                class="menu-item {{ request()->routeIs('admin.attendance.*') || request()->routeIs('admin.leave.*') ? 'active open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-calendar-check"></i>
                    <div data-i18n="Absensi">Absensi & Cuti</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item {{ request()->routeIs('admin.attendance.index') ? 'active' : '' }}">
                        <a href="{{ route('admin.attendance.index') }}" class="menu-link">
                            <div data-i18n="Daftar Absensi">Daftar Absensi</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('admin.attendance.manual') ? 'active' : '' }}">
                        <a href="{{ route('admin.attendance.manual') }}" class="menu-link">
                            <div data-i18n="Absensi Manual">Absensi Manual</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('admin.attendance.report') ? 'active' : '' }}">
                        <a href="{{ route('admin.attendance.report') }}" class="menu-link">
                            <div data-i18n="Rekap">Rekap & Laporan</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('admin.leave.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.leave.index') }}" class="menu-link">
                            <div data-i18n="Leave">Cuti & Izin</div>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Menu Header - Keuangan -->
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Keuangan</span>
            </li>

            <!-- Payroll (Manager only) -->
            @if (Auth::user()->role == 'manager')
                <li class="menu-item {{ request()->routeIs('admin.payroll.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.payroll.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-money"></i>
                        <div data-i18n="Payroll">Payroll Karyawan</div>
                    </a>
                </li>
            @endif

            <!-- Menu Header - Pengaturan -->
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Pengaturan</span>
            </li>

            <!-- Pengaturan Sistem (Dropdown) -->
            <li class="menu-item {{ request()->routeIs('admin.settings.*') ? 'active open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-cog"></i>
                    <div data-i18n="Pengaturan">Pengaturan Sistem</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item {{ request()->routeIs('admin.settings.office*') ? 'active' : '' }}">
                        <a href="{{ route('admin.settings.office') }}" class="menu-link">
                            <div data-i18n="Lokasi Kantor">Lokasi Kantor</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('admin.settings.work-schedule*') ? 'active' : '' }}">
                        <a href="{{ route('admin.settings.work-schedule') }}" class="menu-link">
                            <div data-i18n="Jadwal Kerja">Jadwal Kerja</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('admin.settings.cronjob*') ? 'active' : '' }}">
                        <a href="{{ route('admin.settings.cronjob') }}" class="menu-link">
                            <div data-i18n="Cron Job">Cron Job</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('admin.settings.whatsapp*') ? 'active' : '' }}">
                        <a href="{{ route('admin.settings.whatsapp') }}" class="menu-link">
                            <div data-i18n="WhatsApp">WhatsApp</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('admin.settings.fingerspot*') ? 'active' : '' }}">
                        <a href="{{ route('admin.settings.fingerspot') }}" class="menu-link">
                            <div data-i18n="Fingerspot">Fingerspot</div>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Menu Header - Akun Admin -->
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Akun</span>
            </li>

            <!-- Profil Admin -->
            <li class="menu-item {{ request()->routeIs('admin.profile.*') ? 'active' : '' }}">
                <a href="{{ route('admin.profile.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-user"></i>
                    <div data-i18n="Profile">Profil Saya</div>
                </a>
            </li>
        @else
            <!-- Menu untuk Karyawan -->
            <!-- Menu Header - Absensi Saya -->
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Absensi</span>
            </li>

            <!-- Absen Hari Ini -->
            <li class="menu-item {{ request()->routeIs('employee.attendance.index') ? 'active' : '' }}">
                <a href="{{ route('employee.attendance.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-fingerprint"></i>
                    <div data-i18n="MyAttendance">Absen Saya</div>
                </a>
            </li>

            <!-- Riwayat Absensi -->
            <li class="menu-item {{ request()->routeIs('employee.attendance.history') ? 'active' : '' }}">
                <a href="{{ route('employee.attendance.history') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-history"></i>
                    <div data-i18n="Rekap">Riwayat Absensi</div>
                </a>
            </li>

            <!-- Cuti & Izin -->
            <li class="menu-item {{ request()->routeIs('employee.leave.*') ? 'active' : '' }}">
                <a href="{{ route('employee.leave.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-calendar-event"></i>
                    <div data-i18n="Leave">Cuti & Izin</div>
                </a>
            </li>

            <!-- Riwayat Payroll -->
            <li class="menu-item {{ request()->routeIs('employee.payroll.*') ? 'active' : '' }}">
                <a href="{{ route('employee.payroll.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-money"></i>
                    <div data-i18n="Payroll">Riwayat Payroll</div>
                </a>
            </li>

            <!-- Menu Header - Profile & Settings -->
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Akun</span>
            </li>

            <!-- Profile -->
            <li class="menu-item {{ request()->routeIs('employee.profile.*', 'admin.profile.*') ? 'active' : '' }}">
                @if (Auth::user()->role == 'admin')
                    <a href="{{ route('admin.profile.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-user"></i>
                        <div data-i18n="Profile">Profil Saya</div>
                    </a>
                @else
                    <a href="{{ route('employee.profile.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-user"></i>
                        <div data-i18n="Profile">Profil Saya</div>
                    </a>
                @endif
            </li>

        @endif
    </ul>
</aside>
