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
            <!-- Notifikasi Pengumuman -->
            @if(Auth::user()->role === 'karyawan' || Auth::user()->role === 'employee')
            <li class="nav-item me-2" id="notif-bell-wrap">
                <a href="javascript:void(0)" class="nav-link position-relative" id="notif-bell-btn" onclick="toggleNotifPanel()" title="Pengumuman">
                    <i class="bx bx-bell fs-4"></i>
                    <span class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle" id="notif-badge" style="font-size:.65rem;padding:2px 5px;display:none">0</span>
                </a>
            </li>
            @endif

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
                        @if (Auth::user()->role == 'admin' && Auth::user()->profile_photo_url)
                            <img src="{{ Auth::user()->profile_photo_url }}" alt
                                class="w-px-40 h-px-40 rounded-circle" style="object-fit: cover;" />
                        @elseif (Auth::user()->employee && Auth::user()->employee->profile_photo_url)
                            <img src="{{ Auth::user()->employee->profile_photo_url }}" alt
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
                                        @if (Auth::user()->role == 'admin' && Auth::user()->profile_photo_url)
                                            <img src="{{ Auth::user()->profile_photo_url }}" alt
                                                class="w-px-40 h-px-40 rounded-circle" style="object-fit: cover;" />
                                        @elseif (Auth::user()->employee && Auth::user()->employee->profile_photo_url)
                                            <img src="{{ Auth::user()->employee->profile_photo_url }}"
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
                        <form action="{{ route('logout') }}" method="POST" id="logout-form">
                            @csrf
                            <button type="submit" class="dropdown-item" onclick="handleLogout(event)">
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

    {{-- Panel Notifikasi Pengumuman (karyawan only) --}}
    @if(Auth::user()->role === 'karyawan' || Auth::user()->role === 'employee')
    <div id="notif-panel" style="display:none;position:fixed;top:70px;right:16px;width:360px;max-height:480px;z-index:9999;box-shadow:0 8px 30px rgba(0,0,0,.15);border-radius:12px;overflow:hidden;background:#fff;">
        <div style="background:linear-gradient(135deg,#667eea,#764ba2);padding:14px 16px;" class="d-flex justify-content-between align-items-center">
            <span class="text-white fw-semibold"><i class="bx bx-bell me-2"></i>Pengumuman</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-light py-0 px-2" style="font-size:.75rem" onclick="markAllRead()">Tandai semua</button>
                <button class="btn btn-sm btn-light py-0 px-2" onclick="toggleNotifPanel()"><i class="bx bx-x"></i></button>
            </div>
        </div>
        <div id="notif-list" style="overflow-y:auto;max-height:400px;padding:8px;">
            <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
        </div>
    </div>
    @endif

    @push('scripts')
        <script>
            function handleLogout(event) {
                // If CSRF token fails, fallback to GET request
                const form = document.getElementById('logout-form');

                // Try POST first
                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                }).then(response => {
                    if (response.ok || response.redirected) {
                        window.location.href = '{{ route('login') }}';
                    } else {
                        // Fallback to GET if POST fails
                        window.location.href = '/logout';
                    }
                }).catch(error => {
                    // Fallback to GET if fetch fails
                    window.location.href = '/logout';
                });

                event.preventDefault();
                return false;
            }
        </script>
    @endpush
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

@if(Auth::user()->role === 'karyawan' || Auth::user()->role === 'employee')
@push('styles')
<style>
/* ── Notifikasi Bell ─────────────────────────────── */
#notif-bell-btn { transition: transform .2s; }
#notif-bell-btn:hover { transform: scale(1.15); }
#notif-bell-btn .bx-bell { animation: bellRing 2s ease-in-out infinite; transform-origin: top center; }
@@keyframes bellRing {
    0%,100% { transform: rotate(0); }
    10%,30%  { transform: rotate(-12deg); }
    20%,40%  { transform: rotate(12deg); }
    50%      { transform: rotate(0); }
}
#notif-badge { min-width: 18px; }

/* ── Notif Dropdown Panel ────────────────────────── */
#notif-panel {
    display: none;
    position: fixed;
    top: 70px; right: 16px;
    width: 370px;
    max-height: 500px;
    z-index: 9999;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,.18);
    background: #fff;
    animation: slideDown .25s ease;
}
@@keyframes slideDown {
    from { opacity: 0; transform: translateY(-12px); }
    to   { opacity: 1; transform: translateY(0); }
}
.notif-item {
    border-radius: 10px;
    padding: 10px 12px;
    margin-bottom: 6px;
    border-left: 4px solid;
    cursor: pointer;
    transition: background .15s, transform .15s;
}
.notif-item:hover { transform: translateX(3px); filter: brightness(.97); }
.notif-item.unread { background: #f0f7ff; }
.notif-item.read   { background: #f8f9fa; opacity: .85; }

/* ── Custom Popup Overlay ────────────────────────── */
#ann-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15,15,40,.55);
    backdrop-filter: blur(4px);
    z-index: 99998;
    animation: fadeIn .3s ease;
}
#ann-popup {
    display: none;
    position: fixed;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%) scale(.9);
    width: min(480px, 94vw);
    border-radius: 24px;
    overflow: hidden;
    z-index: 99999;
    box-shadow: 0 30px 80px rgba(0,0,0,.25);
    animation: popIn .35s cubic-bezier(.34,1.56,.64,1) forwards;
}
@@keyframes fadeIn { from { opacity:0 } to { opacity:1 } }
@@keyframes popIn  {
    from { opacity:0; transform: translate(-50%,-50%) scale(.75); }
    to   { opacity:1; transform: translate(-50%,-50%) scale(1); }
}
.ann-header {
    padding: 0;
    position: relative;
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.ann-header-bg {
    position: absolute;
    inset: 0;
    opacity: .13;
    background-size: 40px 40px;
    background-image: radial-gradient(circle, #fff 1px, transparent 1px);
}
.ann-header img {
    height: 145px;
    width: auto;
    position: relative;
    z-index: 1;
    filter: drop-shadow(0 8px 20px rgba(0,0,0,.2));
    animation: floatImg 3s ease-in-out infinite;
}
@@keyframes floatImg {
    0%,100% { transform: translateY(0); }
    50%     { transform: translateY(-8px); }
}
.ann-body { background: #fff; padding: 28px 28px 24px; }
.ann-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .6px;
    text-transform: uppercase;
    margin-bottom: 10px;
    color: #fff;
}
.ann-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 10px;
    line-height: 1.3;
}
.ann-content {
    font-size: .92rem;
    color: #555;
    line-height: 1.65;
    margin-bottom: 20px;
    max-height: 120px;
    overflow-y: auto;
    padding-right: 4px;
}
.ann-content::-webkit-scrollbar { width: 4px; }
.ann-content::-webkit-scrollbar-thumb { background: #ddd; border-radius: 2px; }
.ann-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}
.ann-counter {
    font-size: .78rem;
    color: #aaa;
}
.ann-btn {
    padding: 10px 26px;
    border-radius: 50px;
    border: none;
    font-size: .9rem;
    font-weight: 600;
    color: #fff;
    cursor: pointer;
    transition: transform .15s, box-shadow .15s, opacity .15s;
    box-shadow: 0 4px 15px rgba(0,0,0,.15);
    display: flex;
    align-items: center;
    gap: 6px;
}
.ann-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,.2); }
.ann-btn:active { transform: translateY(0); opacity: .9; }
.ann-close {
    position: absolute;
    top: 12px; right: 14px;
    width: 32px; height: 32px;
    border-radius: 50%;
    background: rgba(255,255,255,.25);
    border: none;
    color: #fff;
    font-size: 1.2rem;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .2s;
    z-index: 2;
}
.ann-close:hover { background: rgba(255,255,255,.45); }
.ann-progress {
    height: 4px;
    border-radius: 0;
    background: rgba(255,255,255,.3);
    position: absolute;
    bottom: 0; left: 0; right: 0;
}
.ann-progress-bar {
    height: 100%;
    background: rgba(255,255,255,.85);
    border-radius: 0;
    transition: width .1s linear;
}
/* Dots counter */
.ann-dots { display: flex; gap: 5px; justify-content: center; }
.ann-dot { width: 7px; height: 7px; border-radius: 50%; background: #ddd; transition: background .2s, transform .2s; }
.ann-dot.active { background: var(--ann-color, #667eea); transform: scale(1.4); }

/* ── New Modern Swal Popup ───────────────────────── */
.swal2-popup.ann-modern-popup {
    padding: 0 !important;
    border-radius: 24px !important;
    overflow: hidden !important;
    box-shadow: 0 30px 80px rgba(0,0,0,.25) !important;
    width: min(460px, 94vw) !important;
}
.swal2-popup.ann-modern-popup .swal2-html-container {
    margin: 0 !important;
    padding: 0 !important;
    overflow: visible !important;
}
.swal2-popup.ann-modern-popup .swal2-actions {
    margin: 0 !important;
    padding: 0 24px 22px !important;
    width: 100%;
    justify-content: center;
}
.swal2-popup.ann-modern-popup .swal2-close {
    position: absolute;
    top: 14px; right: 16px;
    width: 34px; height: 34px;
    border-radius: 50%;
    background: rgba(255,255,255,.28) !important;
    color: #fff !important;
    font-size: 1.4rem !important;
    box-shadow: none !important;
    transition: background .2s, transform .2s;
    z-index: 10;
}
.swal2-popup.ann-modern-popup .swal2-close:hover {
    background: rgba(255,255,255,.5) !important;
    transform: rotate(90deg);
}
.ann-hero {
    position: relative;
    height: 110px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.ann-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle at 20% 30%, rgba(255,255,255,.25) 0, transparent 40%),
                      radial-gradient(circle at 80% 70%, rgba(255,255,255,.2)  0, transparent 45%),
                      radial-gradient(circle, rgba(255,255,255,.18) 1px, transparent 1.5px);
    background-size: auto, auto, 26px 26px;
    opacity: .9;
}
.ann-hero::after {
    content: '';
    position: absolute;
    bottom: -1px; left: 0; right: 0;
    height: 30px;
    background: #fff;
    clip-path: ellipse(60% 100% at 50% 100%);
}
.swal2-popup.ann-modern-popup .ann-hero img,
.ann-hero img {
    height: 70px !important;
    max-height: 70px !important;
    max-width: 45% !important;
    width: auto !important;
    object-fit: contain !important;
    position: relative;
    z-index: 2;
    filter: drop-shadow(0 6px 14px rgba(0,0,0,.28));
    animation: floatImg 3.2s ease-in-out infinite;
    margin: 0 !important;
    border-radius: 0 !important;
}
.ann-body-modern {
    background: #fff;
    padding: 8px 28px 18px;
    text-align: center;
}
.ann-badge-modern {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    border-radius: 20px;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .8px;
    text-transform: uppercase;
    color: #fff;
    margin-bottom: 12px;
    box-shadow: 0 4px 14px rgba(0,0,0,.12);
}
.ann-title-modern {
    font-size: 1.35rem;
    font-weight: 800;
    color: #1a1a2e;
    margin: 0 0 10px;
    line-height: 1.3;
    letter-spacing: -.3px;
}
.ann-content-modern {
    font-size: .92rem;
    color: #555;
    line-height: 1.65;
    text-align: left;
    max-height: 180px;
    overflow-y: auto;
    padding: 4px 6px 4px 0;
    margin-bottom: 14px;
    word-wrap: break-word;
}
.ann-content-modern::-webkit-scrollbar { width: 5px; }
.ann-content-modern::-webkit-scrollbar-thumb { background: #e0e0e0; border-radius: 3px; }
.ann-content-modern::-webkit-scrollbar-thumb:hover { background: #c0c0c0; }
.ann-meta-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 12px;
    border-top: 1px dashed #eee;
    font-size: .72rem;
    color: #888;
}
.ann-priority-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 12px;
    background: #f1f3f9;
    color: #555;
    font-weight: 600;
    font-size: .68rem;
    text-transform: uppercase;
    letter-spacing: .4px;
}
.ann-btn-modern {
    padding: 11px 32px !important;
    border-radius: 50px !important;
    font-size: .92rem !important;
    font-weight: 600 !important;
    box-shadow: 0 6px 18px rgba(67,97,238,.3) !important;
    transition: transform .15s, box-shadow .15s !important;
    display: inline-flex !important;
    align-items: center;
    gap: 6px;
}
.ann-btn-modern:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(67,97,238,.4) !important; }
.ann-btn-modern:active { transform: translateY(0); }

/* ── Responsive Popup ────────────────────────────── */
@@media (max-width: 576px) {
    .swal2-popup.ann-modern-popup {
        width: 94vw !important;
        border-radius: 20px !important;
    }
    .swal2-popup.ann-modern-popup .swal2-actions {
        padding: 0 18px 18px !important;
    }
    .swal2-popup.ann-modern-popup .swal2-close {
        width: 30px; height: 30px;
        top: 10px; right: 10px;
        font-size: 1.2rem !important;
    }
    .ann-hero { height: 110px; }
    .swal2-popup.ann-modern-popup .ann-hero img,
    .ann-hero img { height: 75px !important; max-height: 75px !important; max-width: 55% !important; }
    .ann-body-modern { padding: 6px 18px 14px; }
    .ann-badge-modern { font-size: .65rem; padding: 4px 11px; }
    .ann-title-modern { font-size: 1.1rem; }
    .ann-content-modern { font-size: .85rem; max-height: 140px; }
    .ann-meta-row { font-size: .68rem; }
    .ann-priority-chip { font-size: .62rem; padding: 2px 8px; }
    .ann-btn-modern {
        padding: 9px 24px !important;
        font-size: .85rem !important;
        width: 100%;
        justify-content: center;
    }
}
@@media (max-width: 360px) {
    .ann-hero { height: 95px; }
    .swal2-popup.ann-modern-popup .ann-hero img,
    .ann-hero img { height: 60px !important; max-height: 60px !important; max-width: 50% !important; }
    .ann-title-modern { font-size: 1rem; }
    .ann-content-modern { font-size: .8rem; max-height: 120px; }
}
</style>
@endpush

@push('scripts')
<script>
(function() {
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

/* ─── Config ─────────────────────────────────────────── */
const CONFIG = {
    info:    { color: '#4361ee', grad: 'linear-gradient(135deg,#4361ee,#4cc9f0)', img: '{{ asset("images/announcements/info.png") }}',    icon: 'bx-info-circle',   label: 'Informasi' },
    warning: { color: '#f77f00', grad: 'linear-gradient(135deg,#f77f00,#fcbf49)', img: '{{ asset("images/announcements/warning.png") }}', icon: 'bx-error',          label: 'Peringatan' },
    success: { color: '#06d6a0', grad: 'linear-gradient(135deg,#06d6a0,#1b9e77)', img: '{{ asset("images/announcements/success.png") }}', icon: 'bx-check-circle',   label: 'Informasi Baik' },
    danger:  { color: '#ef233c', grad: 'linear-gradient(135deg,#ef233c,#d62839)', img: '{{ asset("images/announcements/danger.png") }}',  icon: 'bx-bell',           label: 'Penting' },
};

/* ─── Badge ──────────────────────────────────────────── */
async function loadNotifBadge() {
    try {
        const r    = await fetch('/api/employee/announcements/unread-count', { headers: { Accept: 'application/json' } });
        const data = await r.json();
        const badge = document.getElementById('notif-badge');
        if (badge) {
            badge.textContent = data.count > 99 ? '99+' : data.count;
            badge.style.display = data.count > 0 ? 'inline' : 'none';
        }
    } catch(e) {}
}

/* ─── Dropdown Panel ─────────────────────────────────── */
let panelOpen = false;

async function loadNotifList() {
    const list = document.getElementById('notif-list');
    if (!list) return;
    list.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
    try {
        const r    = await fetch('/api/employee/announcements', { headers: { Accept: 'application/json' } });
        const data = await r.json();
        if (!data.success || !data.data.length) {
            list.innerHTML = `<div class="text-center py-5 text-muted">
                <i class="bx bx-bell-off" style="font-size:2.5rem;opacity:.4"></i>
                <p class="mt-2 mb-0 small">Tidak ada pengumuman</p>
            </div>`;
            return;
        }
        list.innerHTML = data.data.map(a => {
            const c = CONFIG[a.type] || CONFIG.info;
            return `
            <div class="notif-item ${a.is_read ? 'read' : 'unread'}" style="border-color:${c.color}" onclick="readAndShowItem(${a.id})">
                <div class="d-flex align-items-start gap-2">
                    <div style="width:36px;height:36px;border-radius:10px;background:${c.grad};flex-shrink:0;display:flex;align-items:center;justify-content:center">
                        <i class="bx ${c.icon} text-white" style="font-size:1.1rem"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong style="font-size:.82rem;color:${a.is_read ? '#888' : '#1a1a2e'}">${a.title}</strong>
                            ${!a.is_read ? `<span style="width:8px;height:8px;border-radius:50%;background:${c.color};flex-shrink:0;display:inline-block"></span>` : ''}
                        </div>
                        <p class="mb-0" style="font-size:.76rem;color:#777;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">${a.content}</p>
                        <small style="font-size:.68rem;color:#aaa">${a.created_at}</small>
                    </div>
                </div>
            </div>`;
        }).join('');
    } catch(e) {}
}

async function readAndShowItem(id) {
    await fetch(`/api/employee/announcements/${id}/mark-read`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }
    });
    loadNotifBadge();
    loadNotifList();
}

async function markAllRead() {
    await fetch('/api/employee/announcements/mark-all-read', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }
    });
    loadNotifBadge();
    loadNotifList();
}

window.toggleNotifPanel = function() {
    const panel = document.getElementById('notif-panel');
    panelOpen = !panelOpen;
    if (panelOpen) {
        panel.style.display = 'block';
        loadNotifList();
    } else {
        panel.style.display = 'none';
    }
};

window.markAllRead = markAllRead;
window.readAndShowItem = readAndShowItem;

document.addEventListener('click', e => {
    const panel = document.getElementById('notif-panel');
    const bell  = document.getElementById('notif-bell-btn');
    if (panel && bell && panelOpen && !panel.contains(e.target) && !bell.contains(e.target)) {
        panelOpen = false;
        panel.style.display = 'none';
    }
});

/* ─── Custom Popup dengan SweetAlert2 ───────────────────────────────────── */
let popupQueue = [];
let currentIdx = 0;

/**
 * Pastikan semua <a> di konten terbuka di tab baru dan aman.
 * Juga styling link agar terlihat klikabel di dalam popup.
 */
function sanitizeLinks(html) {
    const parser = new DOMParser();
    const doc    = parser.parseFromString(html, 'text/html');
    doc.querySelectorAll('a').forEach(el => {
        el.setAttribute('target', '_blank');
        el.setAttribute('rel', 'noopener noreferrer');
        el.style.cssText += ';color:#4361ee;font-weight:600;text-decoration:underline;word-break:break-all;';
    });
    return doc.body.innerHTML;
}

async function showPopupAtIndex(idx) {
    if (idx >= popupQueue.length) {
        loadNotifList();
        return;
    }
    
    currentIdx = idx;
    const a = popupQueue[idx];
    const c = CONFIG[a.type] || CONFIG.info;
    
    const isLast = idx === popupQueue.length - 1;

    // Dots indicator (hanya jika lebih dari 1 popup)
    let dotsHtml = '';
    if (popupQueue.length > 1) {
        const dots = popupQueue.map((_, i) =>
            `<span class="ann-dot ${i === idx ? 'active' : ''}" style="${i === idx ? `background:${c.color};` : ''}"></span>`
        ).join('');
        dotsHtml = `<div class="ann-dots" style="margin-top:6px;">${dots}</div>`;
    }

    // Gradient untuk hero (sedikit lebih gelap dari c.color sebagai aksen)
    const heroGradient = `linear-gradient(135deg, ${c.color} 0%, ${c.color}dd 100%)`;

    const isMobile = window.innerWidth <= 480;
    const imgSize   = isMobile ? '60px' : '75px';
    const heroH     = isMobile ? '100px' : '120px';
    const titleSize = isMobile ? '1.1rem' : '1.25rem';
    const bodyPad   = isMobile ? '10px 16px 14px' : '12px 26px 18px';
    const btnWidth  = isMobile ? '100%' : 'auto';

    const html = `
        <!-- Hero Header -->
        <div style="background:${heroGradient};
                    position:relative;
                    height:${heroH};
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    overflow:hidden;
                    border-radius:0;">
            <!-- Dot pattern overlay -->
            <div style="position:absolute;inset:0;
                        background-image:radial-gradient(circle,rgba(255,255,255,.2) 1px,transparent 1.5px);
                        background-size:22px 22px;
                        opacity:.7;pointer-events:none;"></div>
            <!-- Wave bottom -->
            <div style="position:absolute;bottom:-1px;left:0;right:0;
                        height:28px;background:#fff;
                        clip-path:ellipse(60% 100% at 50% 100%);z-index:1;"></div>
            <!-- Image -->
            <img src="${c.img}" alt=""
                 style="height:${imgSize};
                        max-height:${imgSize};
                        max-width:40%;
                        width:auto;
                        object-fit:contain;
                        position:relative;
                        z-index:2;
                        display:block;
                        margin:0 auto;
                        filter:drop-shadow(0 6px 16px rgba(0,0,0,.25));
                        animation:floatImg 3s ease-in-out infinite;">
        </div>

        <!-- Body -->
        <div style="background:#fff;padding:${bodyPad};text-align:center;font-family:inherit;">

            <!-- Type Badge -->
            <span style="display:inline-flex;align-items:center;gap:5px;
                         background:${heroGradient};
                         color:#fff;padding:4px 13px;border-radius:20px;
                         font-size:.68rem;font-weight:700;
                         letter-spacing:.7px;text-transform:uppercase;
                         box-shadow:0 3px 10px rgba(0,0,0,.15);
                         margin-bottom:10px;">
                <i class="bx ${c.icon}"></i> ${c.label}
            </span>

            <!-- Title -->
            <h3 style="font-size:${titleSize};font-weight:800;color:#1a1a2e;
                       margin:0 0 8px;line-height:1.3;letter-spacing:-.3px;">
                ${a.title}
            </h3>

            <!-- Content -->
            <div style="font-size:.88rem;color:#555;line-height:1.65;
                        text-align:left;max-height:160px;overflow-y:auto;
                        padding:0 2px 0 0;margin-bottom:12px;word-wrap:break-word;">
                ${sanitizeLinks(a.content)}
            </div>

            <!-- Meta Row -->
            <div style="display:flex;align-items:center;justify-content:space-between;
                        padding-top:10px;border-top:1px dashed #eee;
                        font-size:.72rem;color:#888;flex-wrap:wrap;gap:6px;">
                <span style="display:inline-flex;align-items:center;gap:4px;
                             background:#f1f3f9;color:#555;
                             padding:3px 10px;border-radius:12px;
                             font-weight:600;font-size:.68rem;
                             text-transform:uppercase;letter-spacing:.3px;">
                    <i class="bx bx-flag" style="color:${c.color};"></i>
                    ${a.priority_label}
                </span>
                ${popupQueue.length > 1
                    ? `<span style="font-weight:700;color:${c.color};font-size:.8rem;">
                           ${idx + 1} / ${popupQueue.length}
                       </span>`
                    : `<span style="display:inline-flex;align-items:center;gap:3px;">
                           <i class="bx bx-bell" style="color:${c.color};"></i> Pengumuman
                       </span>`
                }
            </div>

            <!-- Dots (hanya jika >1 popup) -->
            ${dotsHtml}
        </div>
    `;

    let result;
    try {
        result = await Swal.fire({
            html: html,
            confirmButtonText: isLast
                ? '<i class="bx bx-check" style="margin-right:5px;"></i>Mengerti'
                : 'Lanjut <i class="bx bx-chevron-right" style="margin-left:3px;"></i>',
            confirmButtonColor: c.color,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showCloseButton: true,
            buttonsStyling: true,
            width: Math.min(460, window.innerWidth * 0.94),
            customClass: {
                popup:         'ann-modern-popup',
                confirmButton: 'ann-btn-modern',
            },
            showClass: { popup: 'swal2-show' },
            hideClass: { popup: 'swal2-hide' },
            didOpen: (popup) => {
                // Pastikan semua link di dalam popup bisa diklik (buka tab baru)
                popup.querySelectorAll('a[target="_blank"]').forEach(link => {
                    link.addEventListener('click', e => {
                        e.stopPropagation();
                        window.open(link.href, '_blank', 'noopener,noreferrer');
                        e.preventDefault();
                    });
                });
            }
        });
    } catch (e) {
        console.warn('Swal error:', e);
        return;
    }

    // Tandai sudah dibaca (baik klik Confirm maupun X)
    try {
        await fetch(`/api/employee/announcements/${a.id}/mark-read`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }
        });
    } catch (e) { /* offline-safe */ }

    loadNotifBadge();

    // Jika user klik X (close) → hentikan queue, refresh list panel.
    // Jika klik Confirm → lanjut ke popup berikutnya.
    if (result && result.isConfirmed) {
        showPopupAtIndex(idx + 1);
    } else {
        loadNotifList();
    }
}

async function checkPopups() {
    try {
        const r    = await fetch('/api/employee/announcements/popups', { headers: { Accept: 'application/json' } });
        const data = await r.json();
        if (!data.success || !data.data.length) return;

        popupQueue = data.data;
        showPopupAtIndex(0);
    } catch(e) {
        console.warn('Announcement popup error:', e);
    }
}

/* ─── Init ───────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    loadNotifBadge();
    setInterval(loadNotifBadge, 60000);

    // Tunggu SweetAlert2 siap (karena di-load dengan defer), lalu cek popup
    waitForSwalThenCheckPopups();
});

function waitForSwalThenCheckPopups(attempt = 0) {
    if (typeof Swal !== 'undefined') {
        setTimeout(checkPopups, 800);
    } else if (attempt < 20) {
        setTimeout(() => waitForSwalThenCheckPopups(attempt + 1), 300);
    }
}

})();
</script>
@endpush
@endif


