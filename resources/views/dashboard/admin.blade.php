@extends('layouts.app')

@section('title', 'Dashboard Admin')

@push('head')
    <!-- Preload LCP image untuk performa optimal -->
    <link rel="preload" as="image" href="{{ asset('sneat-1.0.0/assets/img/illustrations/man-with-laptop-light.png') }}"
        fetchpriority="high">
@endpush

@section('content')
    <div class="row">
        <!-- Welcome Card -->
        <div class="col-lg-8 mb-4 order-0">
            <div class="card">
                <div class="d-flex align-items-end row">
                    <div class="col-sm-7">
                        <div class="card-body">
                            <h5 class="card-title text-primary">Selamat Datang Admin! 👋</h5>
                            <p class="mb-4">
                                Hari ini adalah <span
                                    class="fw-bold">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>.
                                Kelola sistem absensi karyawan dengan mudah.
                            </p>
                            <a href="#" class="btn btn-sm btn-outline-primary">Lihat Semua
                                Absensi</a>
                        </div>
                    </div>
                    <div class="col-sm-5 text-center text-sm-left">
                        <div class="card-body pb-0 px-0 px-md-4">
                            <img src="{{ asset('sneat-1.0.0/assets/img/illustrations/man-with-laptop-light.png') }}"
                                width="200" height="140" alt="Admin Dashboard" fetchpriority="high" decoding="async" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistik Hari Ini -->
        <div class="col-lg-4 col-md-4 order-1">
            <div class="row">
                <div class="col-lg-6 col-md-12 col-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar flex-shrink-0">
                                    <i class="bx bx-user-check bx-md text-success"></i>
                                </div>
                            </div>
                            <span class="fw-semibold d-block mb-1">Hadir Hari Ini</span>
                            <h3 class="card-title mb-2">{{ $hadirHariIni }}</h3>
                            <small class="text-success fw-semibold">Karyawan</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-12 col-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar flex-shrink-0">
                                    <i class="bx bx-user-x bx-md text-danger"></i>
                                </div>
                            </div>
                            <span class="fw-semibold d-block mb-1">Tidak Hadir</span>
                            <h3 class="card-title mb-2">{{ $tidakHadirHariIni }}</h3>
                            <small class="text-danger fw-semibold">Karyawan</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Statistik Cards -->
        <div class="col-12 col-md-8 col-lg-4 order-3 order-md-2">
            <div class="row">
                <div class="col-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar flex-shrink-0">
                                    <i class="bx bx-group bx-md text-primary"></i>
                                </div>
                            </div>
                            <span>Total Karyawan</span>
                            <h3 class="card-title text-nowrap mb-1">{{ $totalKaryawan }}</h3>
                            <small class="text-success fw-semibold">Aktif</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar flex-shrink-0">
                                    <i class="bx bx-building bx-md text-info"></i>
                                </div>
                            </div>
                            <span>Departemen</span>
                            <h3 class="card-title text-nowrap mb-1">{{ $totalDepartemen }}</h3>
                            <small class="text-muted fw-semibold">Total</small>
                        </div>
                    </div>
                </div>
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar flex-shrink-0">
                                    <i class="bx bx-calendar-event bx-md text-warning"></i>
                                </div>
                            </div>
                            <span>Pengajuan Cuti Pending</span>
                            <h3 class="card-title text-nowrap mb-1">{{ $totalCutiPending }}</h3>
                            <small class="text-warning fw-semibold">Menunggu Approval</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Absensi Terbaru -->
        <div class="col-12 col-md-8 col-lg-8 order-2 order-md-3 order-lg-2 mb-4">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="card-title m-0 me-2">
                        <span class="d-none d-sm-inline">Absensi Terbaru Hari Ini</span>
                        <span class="d-sm-none">Absensi Hari Ini</span>
                    </h5>
                    <a href="{{ route('admin.attendance.index') }}" class="btn btn-sm btn-outline-primary">
                        <span class="d-none d-sm-inline">Lihat Semua</span>
                        <span class="d-sm-none">Semua</span>
                    </a>
                </div>
                <div class="card-body">
                    <!-- Desktop Table View -->
                    <div class="table-responsive text-nowrap d-none d-md-block">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Karyawan</th>
                                    <th>Departemen</th>
                                    <th>Jabatan</th>
                                    <th>Jam Masuk</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                @forelse($absensiTerbaru as $absensi)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-3">
                                                    @if ($absensi->employee->profile_photo)
                                                        <img src="{{ asset('storage/' . $absensi->employee->profile_photo) }}"
                                                            alt="Avatar" class="rounded-circle" loading="lazy"
                                                            width="38" height="38" style="object-fit: cover;">
                                                    @else
                                                        <img src="{{ asset('sneat-1.0.0/assets/img/avatars/1.png') }}"
                                                            alt="Avatar" class="rounded-circle" loading="lazy"
                                                            width="38" height="38">
                                                    @endif
                                                </div>
                                                <div>
                                                    <strong>{{ $absensi->employee->name }}</strong><br>
                                                    <small
                                                        class="text-muted">{{ $absensi->employee->employee_code }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $absensi->employee->department->name ?? '-' }}</td>
                                        <td>{{ $absensi->employee->position->name ?? '-' }}</td>
                                        <td>{{ $absensi->check_in ? \Carbon\Carbon::parse($absensi->check_in)->format('H:i') : '-' }}
                                        </td>
                                        <td>
                                            @if ($absensi->status == 'hadir')
                                                <span class="badge bg-label-success">Hadir</span>
                                            @elseif($absensi->status == 'terlambat')
                                                <span class="badge bg-label-warning">Terlambat</span>
                                            @elseif($absensi->status == 'izin')
                                                <span class="badge bg-label-info">Izin</span>
                                            @elseif($absensi->status == 'sakit')
                                                <span class="badge bg-label-secondary">Sakit</span>
                                            @elseif($absensi->status == 'cuti')
                                                <span class="badge bg-label-primary">Cuti</span>
                                            @elseif($absensi->status == 'alpha')
                                                <span class="badge bg-label-danger">Alpha</span>
                                            @else
                                                <span class="badge bg-label-danger">{{ ucfirst($absensi->status) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">Belum ada data absensi hari ini</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="d-md-none">
                        @forelse($absensiTerbaru as $absensi)
                            <div class="card mb-3 shadow-sm">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-start mb-2">
                                        <div class="avatar avatar-sm me-2">
                                            @if ($absensi->employee->profile_photo)
                                                <img src="{{ asset('storage/' . $absensi->employee->profile_photo) }}"
                                                    alt="Avatar" class="rounded-circle" loading="lazy" width="38"
                                                    height="38" style="object-fit: cover;">
                                            @else
                                                <img src="{{ asset('sneat-1.0.0/assets/img/avatars/1.png') }}"
                                                    alt="Avatar" class="rounded-circle" loading="lazy" width="38"
                                                    height="38">
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">{{ $absensi->employee->name }}</h6>
                                            <small class="text-muted">
                                                <i class='bx bx-id-card'></i> {{ $absensi->employee->employee_code }}
                                            </small>
                                        </div>
                                        <div>
                                            @if ($absensi->status == 'hadir')
                                                <span class="badge bg-success">Hadir</span>
                                            @elseif($absensi->status == 'terlambat')
                                                <span class="badge bg-warning">Terlambat</span>
                                            @elseif($absensi->status == 'izin')
                                                <span class="badge bg-info">Izin</span>
                                            @elseif($absensi->status == 'sakit')
                                                <span class="badge bg-secondary">Sakit</span>
                                            @elseif($absensi->status == 'cuti')
                                                <span class="badge bg-primary">Cuti</span>
                                            @elseif($absensi->status == 'alpha')
                                                <span class="badge bg-danger">Alpha</span>
                                            @else
                                                <span class="badge bg-danger">{{ ucfirst($absensi->status) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <small class="text-muted d-block">
                                                <i class='bx bx-buildings'></i> Departemen
                                            </small>
                                            <strong
                                                class="small">{{ $absensi->employee->department->name ?? '-' }}</strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">
                                                <i class='bx bx-briefcase'></i> Jabatan
                                            </small>
                                            <strong
                                                class="small">{{ $absensi->employee->position->name ?? '-' }}</strong>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-center border-top pt-2">
                                        <small class="text-muted">Jam Masuk</small>
                                        <h5 class="mb-0 text-success">
                                            {{ $absensi->check_in ? \Carbon\Carbon::parse($absensi->check_in)->format('H:i') : '-' }}
                                        </h5>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <i class='bx bx-calendar-x' style="font-size: 48px; color: #ccc;"></i>
                                <p class="text-muted mt-2 mb-0">Belum ada data absensi hari ini</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pengajuan Cuti Pending -->
    <div class="row">
        <div class="col-md-6 col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="card-title m-0">
                        <span class="d-none d-sm-inline">Pengajuan Cuti Pending</span>
                        <span class="d-sm-none">Cuti Pending</span>
                    </h5>
                    <a href="#" class="btn btn-sm btn-outline-primary">
                        <span class="d-none d-sm-inline">Lihat Semua</span>
                        <span class="d-sm-none">Semua</span>
                    </a>
                </div>
                <div class="card-body">
                    @forelse($cutiPending as $cuti)
                        <div class="d-flex justify-content-between align-items-start mb-3 pb-3 border-bottom">
                            <div class="flex-grow-1 me-2">
                                <h6 class="mb-1">{{ $cuti->employee->name }}</h6>
                                <small class="text-muted">
                                    <i class='bx bx-calendar-event'></i> {{ $cuti->leave_type }} -
                                    {{ $cuti->total_days }} hari
                                </small><br>
                                <small class="text-muted">
                                    <i class='bx bx-calendar'></i>
                                    {{ \Carbon\Carbon::parse($cuti->start_date)->format('d M Y') }} -
                                    {{ \Carbon\Carbon::parse($cuti->end_date)->format('d M Y') }}
                                </small>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="badge bg-warning">Pending</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-3">
                            <i class='bx bx-calendar-event' style="font-size: 48px; color: #ccc;"></i>
                            <p class="text-muted mt-2 mb-0">Tidak ada pengajuan cuti pending</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Grafik Statistik Mingguan -->
        <div class="col-md-6 col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <span class="d-none d-sm-inline">Statistik Absensi 7 Hari Terakhir</span>
                        <span class="d-sm-none">Statistik 7 Hari</span>
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="chartAbsensiAdmin" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
    <script defer>
        // Chart Statistik Mingguan - wrapped in DOMContentLoaded
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('chartAbsensiAdmin');
            if (ctx && typeof Chart !== 'undefined') {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: {!! json_encode($statistikMingguIni['labels'] ?? []) !!},
                        datasets: [{
                            label: 'Hadir',
                            data: {!! json_encode($statistikMingguIni['hadir'] ?? []) !!},
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.1)',
                            tension: 0.4
                        }, {
                            label: 'Tidak Hadir',
                            data: {!! json_encode($statistikMingguIni['tidak_hadir'] ?? []) !!},
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
@endpush
