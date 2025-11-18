@extends('layouts.app')

@section('title', 'Rekap & Laporan Absensi')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">
                <span class="text-muted fw-light">Absensi /</span> Rekap & Laporan
            </h4>
        </div>

        <!-- Filter Card -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.attendance.report') }}" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Bulan</label>
                            <select class="form-select" name="month" id="monthSelect">
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ $month == $i ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create()->month($i)->locale('id')->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tahun</label>
                            <select class="form-select" name="year" id="yearSelect">
                                @for ($i = now()->year; $i >= now()->year - 3; $i--)
                                    <option value="{{ $i }}" {{ $year == $i ? 'selected' : '' }}>
                                        {{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Departemen</label>
                            <select class="form-select" name="department">
                                <option value="">Semua Departemen</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}"
                                        {{ request('department') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label d-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class='bx bx-filter-alt me-1'></i> Tampilkan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class='bx bx-calendar-check bx-sm'></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Total Absensi</small>
                                <h4 class="mb-0">{{ $totalAttendance }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class='bx bx-check-circle bx-sm'></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Hadir</small>
                                <h4 class="mb-0">{{ $hadirCount }}</h4>
                                <small
                                    class="text-success">{{ $totalAttendance > 0 ? round(($hadirCount / $totalAttendance) * 100, 1) : 0 }}%</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-warning">
                                    <i class='bx bx-time bx-sm'></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Terlambat</small>
                                <h4 class="mb-0">{{ $terlambatCount }}</h4>
                                <small
                                    class="text-warning">{{ $totalAttendance > 0 ? round(($terlambatCount / $totalAttendance) * 100, 1) : 0 }}%</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-danger">
                                    <i class='bx bx-x-circle bx-sm'></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Alpha</small>
                                <h4 class="mb-0">{{ $alphaCount }}</h4>
                                <small
                                    class="text-danger">{{ $totalAttendance > 0 ? round(($alphaCount / $totalAttendance) * 100, 1) : 0 }}%</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Grafik Trend Harian -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Grafik Absensi Harian</h5>
                        <small
                            class="text-muted">{{ \Carbon\Carbon::create($year, $month)->locale('id')->translatedFormat('F Y') }}</small>
                    </div>
                    <div class="card-body">
                        <canvas id="dailyChart" height="300"></canvas>
                    </div>
                </div>
            </div>

            <!-- Grafik Status Absensi (Pie Chart) -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Distribusi Status</h5>
                    </div>
                    <div class="card-body">
                        <div style="position: relative; height: 250px; margin-bottom: 1rem;">
                            <canvas id="statusPieChart"></canvas>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class='bx bxs-circle text-success me-1'></i> Hadir</span>
                                <strong>{{ $hadirCount }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class='bx bxs-circle text-warning me-1'></i> Terlambat</span>
                                <strong>{{ $terlambatCount }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class='bx bxs-circle text-info me-1'></i> Izin</span>
                                <strong>{{ $izinCount }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span><i class='bx bxs-circle text-danger me-1'></i> Alpha</span>
                                <strong>{{ $alphaCount }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top 10 Karyawan Terlambat -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Top 10 Karyawan Terlambat</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>NIP</th>
                                <th>Nama Karyawan</th>
                                <th>Jumlah Terlambat</th>
                                <th>Total Menit Terlambat</th>
                                <th>Rata-rata</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topLateEmployees as $index => $emp)
                                <tr>
                                    <td>
                                        @if ($index == 0)
                                            <span class="badge bg-warning">🥇 #1</span>
                                        @elseif($index == 1)
                                            <span class="badge bg-secondary">🥈 #2</span>
                                        @elseif($index == 2)
                                            <span class="badge bg-label-warning">🥉 #3</span>
                                        @else
                                            <span class="text-muted">#{{ $index + 1 }}</span>
                                        @endif
                                    </td>
                                    <td><strong>{{ $emp->employee_code }}</strong></td>
                                    <td>{{ $emp->name }}</td>
                                    <td>
                                        <span class="badge bg-label-warning">{{ $emp->late_count }}x</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-danger">{{ $emp->total_late }} menit</span>
                                    </td>
                                    <td>
                                        {{ round($emp->total_late / $emp->late_count, 1) }} menit/hari
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class='bx bx-happy bx-lg text-success'></i>
                                        <p class="mt-2 mb-0 text-muted">Tidak ada keterlambatan! 🎉</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
            // Daily Attendance Chart
            const dailyCtx = document.getElementById('dailyChart').getContext('2d');
            new Chart(dailyCtx, {
                type: 'line',
                data: {
                    labels: @json($chartData['dates']),
                    datasets: [{
                            label: 'Hadir',
                            data: @json($chartData['hadir']),
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Terlambat',
                            data: @json($chartData['terlambat']),
                            borderColor: 'rgb(255, 205, 86)',
                            backgroundColor: 'rgba(255, 205, 86, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Izin',
                            data: @json($chartData['izin']),
                            borderColor: 'rgb(54, 162, 235)',
                            backgroundColor: 'rgba(54, 162, 235, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Alpha',
                            data: @json($chartData['alpha']),
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });

            // Status Pie Chart
            const pieCtx = document.getElementById('statusPieChart').getContext('2d');
            new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Hadir', 'Terlambat', 'Izin', 'Alpha'],
                    datasets: [{
                        data: [
                            {{ $hadirCount }},
                            {{ $terlambatCount }},
                            {{ $izinCount }},
                            {{ $alphaCount }}
                        ],
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(255, 205, 86, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 99, 132, 0.8)'
                        ],
                        borderColor: [
                            'rgb(75, 192, 192)',
                            'rgb(255, 205, 86)',
                            'rgb(54, 162, 235)',
                            'rgb(255, 99, 132)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Auto-submit on filter change
            $('#monthSelect, #yearSelect').on('change', function() {
                $('#filterForm').submit();
            });
        </script>
    @endpush
@endsection
