@extends('layouts.app')

@section('title', 'Dashboard Viewer')

@section('content')
    <div class="row">
        <!-- Welcome Card -->
        <div class="col-lg-8 mb-4 order-0">
            <div class="card">
                <div class="d-flex align-items-end row">
                    <div class="col-sm-7">
                        <div class="card-body">
                            <h5 class="card-title text-primary">Selamat Datang! 👋</h5>
                            <p class="mb-4">
                                Hari ini adalah <span
                                    class="fw-bold">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>.
                                Anda dapat memantau data absensi dan karyawan.
                            </p>
                            <a href="{{ route('admin.attendance.index') }}" class="btn btn-sm btn-outline-primary">
                                Lihat Semua Absensi
                            </a>
                        </div>
                    </div>
                    <div class="col-sm-5 text-center text-sm-left">
                        <div class="card-body pb-0 px-0 px-md-4">
                            <img src="{{ asset('sneat-1.0.0/assets/img/illustrations/man-with-laptop-light.png') }}"
                                width="200" height="140" alt="Viewer Dashboard" decoding="async" />
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
                                    <i class="bx bx-user-minus bx-md text-danger"></i>
                                </div>
                            </div>
                            <span>Total Resign</span>
                            <h3 class="card-title text-nowrap mb-1">{{ $totalResign }}</h3>
                            <small class="text-danger fw-semibold">Resign</small>
                        </div>
                    </div>
                </div>
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar flex-shrink-0">
                                    <i class="bx bx-time bx-md text-warning"></i>
                                </div>
                            </div>
                            <span>Terlambat Hari Ini</span>
                            <h3 class="card-title text-nowrap mb-1">{{ $terlambatHariIni }}</h3>
                            <small class="text-warning fw-semibold">Karyawan</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Absensi Terbaru -->
        <div class="col-12 col-md-8 col-lg-8 order-2 order-md-3 order-lg-2 mb-4">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="card-title m-0 me-2">Absensi Terbaru Hari Ini</h5>
                    <a href="{{ route('admin.attendance.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                </div>
                <div class="card-body">
                    <!-- Desktop Table -->
                    <div class="table-responsive text-nowrap d-none d-md-block">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Karyawan</th>
                                    <th>Departemen</th>
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
                                                            alt="Avatar" class="rounded-circle" width="38" height="38"
                                                            style="object-fit: cover;">
                                                    @else
                                                        <img src="{{ asset('sneat-1.0.0/assets/img/avatars/1.png') }}"
                                                            alt="Avatar" class="rounded-circle" width="38" height="38">
                                                    @endif
                                                </div>
                                                <div>
                                                    <strong>{{ $absensi->employee->name }}</strong><br>
                                                    <small class="text-muted">{{ $absensi->employee->employee_code }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $absensi->employee->department->name ?? '-' }}</td>
                                        <td>{{ $absensi->check_in ? \Carbon\Carbon::parse($absensi->check_in)->format('H:i') : '-' }}</td>
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
                                                <span class="badge bg-label-dark">{{ ucfirst($absensi->status) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">Belum ada data absensi hari ini</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards -->
                    <div class="d-md-none">
                        @forelse($absensiTerbaru as $absensi)
                            <div class="card mb-2 border shadow-sm">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar avatar-sm">
                                                @if ($absensi->employee->profile_photo)
                                                    <img src="{{ asset('storage/' . $absensi->employee->profile_photo) }}"
                                                        alt="Avatar" class="rounded-circle" width="36" height="36"
                                                        style="object-fit: cover;">
                                                @else
                                                    <img src="{{ asset('sneat-1.0.0/assets/img/avatars/1.png') }}"
                                                        alt="Avatar" class="rounded-circle" width="36" height="36">
                                                @endif
                                            </div>
                                            <div>
                                                <strong class="small">{{ $absensi->employee->name }}</strong>
                                                <div class="text-muted" style="font-size:0.75rem">{{ $absensi->employee->department->name ?? '-' }}</div>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            @if ($absensi->status == 'hadir')
                                                <span class="badge bg-success">Hadir</span>
                                            @elseif($absensi->status == 'terlambat')
                                                <span class="badge bg-warning">Terlambat</span>
                                            @elseif($absensi->status == 'alpha')
                                                <span class="badge bg-danger">Alpha</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($absensi->status) }}</span>
                                            @endif
                                            <div class="text-muted mt-1" style="font-size:0.75rem">
                                                {{ $absensi->check_in ? \Carbon\Carbon::parse($absensi->check_in)->format('H:i') : '-' }}
                                            </div>
                                        </div>
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

    <!-- Grafik + Statistik Bawah -->
    <div class="row">
        <!-- Statistik Status Absensi Hari Ini -->
        <div class="col-md-5 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Ringkasan Absensi Hari Ini</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span><i class='bx bx-check-circle text-success me-2'></i>Hadir</span>
                        <span class="badge bg-label-success fs-6">{{ $hadirHariIni }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span><i class='bx bx-time text-warning me-2'></i>Terlambat</span>
                        <span class="badge bg-label-warning fs-6">{{ $terlambatHariIni }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span><i class='bx bx-x-circle text-danger me-2'></i>Alpha</span>
                        <span class="badge bg-label-danger fs-6">{{ $alphaHariIni }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span><i class='bx bx-calendar-event text-info me-2'></i>Izin/Sakit/Cuti</span>
                        <span class="badge bg-label-info fs-6">{{ $izinHariIni }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span><i class='bx bx-group text-primary me-2'></i>Total Karyawan Aktif</span>
                        <span class="badge bg-label-primary fs-6">{{ $totalKaryawan }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grafik 7 Hari Terakhir -->
        <div class="col-md-7 col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Statistik Absensi 7 Hari Terakhir</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartAbsensiViewer" height="160"></canvas>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
    <script defer>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('chartAbsensiViewer');
            if (ctx && typeof Chart !== 'undefined') {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode($statistikMingguIni['labels'] ?? []) !!},
                        datasets: [{
                            label: 'Hadir',
                            data: {!! json_encode($statistikMingguIni['hadir'] ?? []) !!},
                            backgroundColor: 'rgba(75, 192, 192, 0.7)',
                            borderColor: 'rgb(75, 192, 192)',
                            borderWidth: 1
                        }, {
                            label: 'Tidak Hadir',
                            data: {!! json_encode($statistikMingguIni['tidak_hadir'] ?? []) !!},
                            backgroundColor: 'rgba(255, 99, 132, 0.7)',
                            borderColor: 'rgb(255, 99, 132)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { position: 'top' }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 }
                            }
                        }
                    }
                });
            }
        });
    </script>
@endpush
