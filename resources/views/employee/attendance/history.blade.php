@extends('layouts.app')

@section('title', 'Riwayat Absensi')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold mb-2">
                        <span class="text-muted fw-light">Karyawan /</span> Riwayat Absensi
                    </h4>
                    <p class="text-muted mb-0">
                        <i class='bx bx-user'></i> {{ $employee->name }}
                        <span class="d-none d-sm-inline">- {{ $employee->employee_code }}</span>
                    </p>
                </div>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    <i class='bx bx-home'></i>
                    <span class="d-none d-sm-inline">Home</span>
                    <span class="d-sm-none">Dashboard</span>
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Filter Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="{{ route('employee.attendance.history') }}" id="filterForm">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label">Bulan</label>
                                    <select name="month" class="form-select" id="monthFilter">
                                        @for ($m = 1; $m <= 12; $m++)
                                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                                {{ \Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Tahun</label>
                                    <select name="year" class="form-select" id="yearFilter">
                                        @for ($y = date('Y'); $y >= date('Y') - 3; $y--)
                                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                                {{ $y }}
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select" id="statusFilter">
                                        <option value="">Semua Status</option>
                                        <option value="hadir" {{ request('status') == 'hadir' ? 'selected' : '' }}>Hadir
                                        </option>
                                        <option value="terlambat" {{ request('status') == 'terlambat' ? 'selected' : '' }}>
                                            Terlambat</option>
                                        <option value="cuti" {{ request('status') == 'cuti' ? 'selected' : '' }}>Cuti
                                        </option>
                                        <option value="izin" {{ request('status') == 'izin' ? 'selected' : '' }}>Izin
                                        </option>
                                        <option value="sakit" {{ request('status') == 'sakit' ? 'selected' : '' }}>Sakit
                                        </option>
                                        <option value="alpha" {{ request('status') == 'alpha' ? 'selected' : '' }}>Alpha
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class='bx bx-filter'></i> Filter
                                    </button>
                                    <a href="{{ route('employee.attendance.history') }}" class="btn btn-secondary">
                                        <i class='bx bx-reset'></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Attendance List Card -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            Riwayat Absensi -
                            {{ \Carbon\Carbon::create()->month($month)->locale('id')->translatedFormat('F') }}
                            {{ $year }}
                        </h5>
                        <span class="badge bg-primary">{{ $attendances->total() }} Record</span>
                    </div>
                    <div class="card-body p-0">
                        @if ($attendances->isEmpty())
                            <div class="text-center py-5">
                                <i class="bx bx-calendar-x" style="font-size: 4rem; color: #ddd;"></i>
                                <p class="text-muted mt-3 mb-0">Tidak ada data absensi</p>
                                <small class="text-muted">Ubah filter untuk melihat data lainnya</small>
                            </div>
                        @else
                            <!-- Desktop Table View -->
                            <div class="table-responsive d-none d-md-block">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Check In</th>
                                            <th>Check Out</th>
                                            <th>Status</th>
                                            <th>Terlambat</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($attendances as $attendance)
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong>{{ \Carbon\Carbon::parse($attendance->attendance_date)->locale('id')->translatedFormat('l') }}</strong>
                                                        <br>
                                                        <small
                                                            class="text-muted">{{ \Carbon\Carbon::parse($attendance->attendance_date)->format('d/m/Y') }}</small>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if ($attendance->check_in)
                                                        <span class="badge bg-label-success">
                                                            <i class='bx bx-time-five'></i>
                                                            {{ \Carbon\Carbon::parse($attendance->check_in)->format('H:i') }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($attendance->check_out)
                                                        <span class="badge bg-label-warning">
                                                            <i class='bx bx-time-five'></i>
                                                            {{ \Carbon\Carbon::parse($attendance->check_out)->format('H:i') }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($attendance->status == 'hadir')
                                                        <span class="badge bg-success">Hadir</span>
                                                    @elseif($attendance->status == 'terlambat')
                                                        <span class="badge bg-warning">Terlambat</span>
                                                    @elseif($attendance->status == 'cuti')
                                                        <span class="badge bg-primary">Cuti</span>
                                                    @elseif($attendance->status == 'izin')
                                                        <span class="badge bg-info">Izin</span>
                                                    @elseif($attendance->status == 'sakit')
                                                        <span class="badge bg-secondary">Sakit</span>
                                                    @elseif($attendance->status == 'alpha')
                                                        <span class="badge bg-danger">Alpha</span>
                                                    @else
                                                        <span
                                                            class="badge bg-dark">{{ ucfirst($attendance->status) }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($attendance->late_minutes > 0)
                                                        <span class="badge bg-label-danger">
                                                            {{ $attendance->late_minutes }} menit
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-primary view-detail"
                                                        data-id="{{ $attendance->id }}">
                                                        <i class='bx bx-show'></i> Detail
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Mobile Card View -->
                            <div class="d-md-none p-3">
                                @foreach ($attendances as $attendance)
                                    <div class="card mb-3 shadow-sm">
                                        <div class="card-body">
                                            <!-- Header with Date and Status -->
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h6 class="mb-1">
                                                        {{ \Carbon\Carbon::parse($attendance->attendance_date)->locale('id')->translatedFormat('l') }}
                                                    </h6>
                                                    <small class="text-muted">
                                                        <i class='bx bx-calendar'></i>
                                                        {{ \Carbon\Carbon::parse($attendance->attendance_date)->format('d/m/Y') }}
                                                    </small>
                                                </div>
                                                <div>
                                                    @if ($attendance->status == 'hadir')
                                                        <span class="badge bg-success">Hadir</span>
                                                    @elseif($attendance->status == 'terlambat')
                                                        <span class="badge bg-warning">Terlambat</span>
                                                    @elseif($attendance->status == 'cuti')
                                                        <span class="badge bg-primary">Cuti</span>
                                                    @elseif($attendance->status == 'izin')
                                                        <span class="badge bg-info">Izin</span>
                                                    @elseif($attendance->status == 'sakit')
                                                        <span class="badge bg-secondary">Sakit</span>
                                                    @elseif($attendance->status == 'alpha')
                                                        <span class="badge bg-danger">Alpha</span>
                                                    @else
                                                        <span
                                                            class="badge bg-dark">{{ ucfirst($attendance->status) }}</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Time Info -->
                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <div class="border rounded p-2">
                                                        <small class="text-muted d-block mb-1">
                                                            <i class='bx bx-log-in'></i> Check In
                                                        </small>
                                                        @if ($attendance->check_in)
                                                            <strong class="text-success">
                                                                {{ \Carbon\Carbon::parse($attendance->check_in)->format('H:i') }}
                                                            </strong>
                                                        @else
                                                            <strong class="text-muted">-</strong>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="border rounded p-2">
                                                        <small class="text-muted d-block mb-1">
                                                            <i class='bx bx-log-out'></i> Check Out
                                                        </small>
                                                        @if ($attendance->check_out)
                                                            <strong class="text-warning">
                                                                {{ \Carbon\Carbon::parse($attendance->check_out)->format('H:i') }}
                                                            </strong>
                                                        @else
                                                            <strong class="text-muted">-</strong>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Late Info & Action -->
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    @if ($attendance->late_minutes > 0)
                                                        <span class="badge bg-label-danger">
                                                            <i class='bx bx-time'></i> Terlambat
                                                            {{ $attendance->late_minutes }} menit
                                                        </span>
                                                    @else
                                                        <span class="text-muted small">Tepat waktu</span>
                                                    @endif
                                                </div>
                                                <button type="button" class="btn btn-sm btn-primary view-detail"
                                                    data-id="{{ $attendance->id }}">
                                                    <i class='bx bx-show'></i> Detail
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Pagination -->
                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div class="text-muted small mb-2 mb-md-0">
                                        Menampilkan {{ $attendances->firstItem() }} - {{ $attendances->lastItem() }} dari
                                        {{ $attendances->total() }} data
                                    </div>
                                    {{ $attendances->links() }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-3">
                <!-- Statistics Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Statistik Periode Ini</h6>
                    </div>
                    <div class="card-body">
                        <!-- Desktop Stats -->
                        <div class="d-none d-lg-block">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2">
                                        <span class="avatar-initial rounded bg-label-success">
                                            <i class='bx bx-check'></i>
                                        </span>
                                    </div>
                                    <span>Hadir</span>
                                </div>
                                <strong class="text-success">{{ $stats['hadir'] }}</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2">
                                        <span class="avatar-initial rounded bg-label-warning">
                                            <i class='bx bx-time'></i>
                                        </span>
                                    </div>
                                    <span>Terlambat</span>
                                </div>
                                <strong class="text-warning">{{ $stats['terlambat'] }}</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2">
                                        <span class="avatar-initial rounded bg-label-primary">
                                            <i class='bx bx-calendar'></i>
                                        </span>
                                    </div>
                                    <span>Cuti</span>
                                </div>
                                <strong class="text-primary">{{ $stats['cuti'] ?? 0 }}</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2">
                                        <span class="avatar-initial rounded bg-label-info">
                                            <i class='bx bx-file'></i>
                                        </span>
                                    </div>
                                    <span>Izin</span>
                                </div>
                                <strong class="text-info">{{ $stats['izin'] }}</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2">
                                        <span class="avatar-initial rounded bg-label-secondary">
                                            <i class='bx bx-plus-medical'></i>
                                        </span>
                                    </div>
                                    <span>Sakit</span>
                                </div>
                                <strong class="text-secondary">{{ $stats['sakit'] ?? 0 }}</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2">
                                        <span class="avatar-initial rounded bg-label-danger">
                                            <i class='bx bx-x'></i>
                                        </span>
                                    </div>
                                    <span>Alpha</span>
                                </div>
                                <strong class="text-danger">{{ $stats['alpha'] }}</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>Total</strong>
                                <strong class="text-primary">{{ array_sum($stats) }}</strong>
                            </div>
                        </div>

                        <!-- Mobile Stats (Grid) -->
                        <div class="d-lg-none">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="text-center p-2 border rounded">
                                        <i class='bx bx-check text-success' style="font-size: 24px;"></i>
                                        <div class="mt-1">
                                            <strong class="d-block text-success">{{ $stats['hadir'] }}</strong>
                                            <small class="text-muted">Hadir</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-2 border rounded">
                                        <i class='bx bx-time text-warning' style="font-size: 24px;"></i>
                                        <div class="mt-1">
                                            <strong class="d-block text-warning">{{ $stats['terlambat'] }}</strong>
                                            <small class="text-muted">Terlambat</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-2 border rounded">
                                        <i class='bx bx-calendar text-primary' style="font-size: 24px;"></i>
                                        <div class="mt-1">
                                            <strong class="d-block text-primary">{{ $stats['cuti'] ?? 0 }}</strong>
                                            <small class="text-muted">Cuti</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-2 border rounded">
                                        <i class='bx bx-file text-info' style="font-size: 24px;"></i>
                                        <div class="mt-1">
                                            <strong class="d-block text-info">{{ $stats['izin'] }}</strong>
                                            <small class="text-muted">Izin</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-2 border rounded">
                                        <i class='bx bx-plus-medical text-secondary' style="font-size: 24px;"></i>
                                        <div class="mt-1">
                                            <strong class="d-block text-secondary">{{ $stats['sakit'] ?? 0 }}</strong>
                                            <small class="text-muted">Sakit</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-2 border rounded">
                                        <i class='bx bx-x text-danger' style="font-size: 24px;"></i>
                                        <div class="mt-1">
                                            <strong class="d-block text-danger">{{ $stats['alpha'] }}</strong>
                                            <small class="text-muted">Alpha</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="text-center">
                                <small class="text-muted">Total Absensi</small>
                                <h4 class="mb-0 text-primary">{{ array_sum($stats) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="card d-none d-lg-block">
                    <div class="card-body">
                        <h6 class="mb-3">Informasi</h6>
                        <div class="mb-3">
                            <small class="text-muted d-block">Nama</small>
                            <strong>{{ $employee->name }}</strong>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block">NIP</small>
                            <strong>{{ $employee->employee_code }}</strong>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block">Departemen</small>
                            <strong>{{ $employee->department->name }}</strong>
                        </div>
                        <div>
                            <small class="text-muted d-block">Jabatan</small>
                            <strong>{{ $employee->position->name }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Absensi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Auto submit on filter change
            $('#monthFilter, #yearFilter, #statusFilter').on('change', function() {
                $('#filterForm').submit();
            });

            // View Detail
            $('.view-detail').on('click', function() {
                const attendanceId = $(this).data('id');

                $('#detailModal').modal('show');
                $('#detailContent').html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `);

                // Fetch detail data
                $.ajax({
                    url: `/api/employee/attendance/${attendanceId}/detail`,
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            let html = `
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <h6>Informasi Waktu</h6>
                                        <table class="table table-borderless table-sm">
                                            <tr>
                                                <td width="120"><strong>Tanggal</strong></td>
                                                <td>: ${new Date(data.attendance_date).toLocaleDateString('id-ID', {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'})}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Check In</strong></td>
                                                <td>: ${data.check_in || '-'}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Check Out</strong></td>
                                                <td>: ${data.check_out || '-'}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Status</strong></td>
                                                <td>: <span class="badge bg-${
                                                    data.status == 'hadir' ? 'success' :
                                                    data.status == 'terlambat' ? 'warning' :
                                                    data.status == 'cuti' ? 'primary' :
                                                    data.status == 'izin' ? 'info' :
                                                    data.status == 'sakit' ? 'secondary' :
                                                    data.status == 'alpha' ? 'danger' : 'dark'
                                                }">${data.status.toUpperCase()}</span></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Terlambat</strong></td>
                                                <td>: ${data.late_minutes > 0 ? data.late_minutes + ' menit' : '-'}</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <h6>Informasi GPS</h6>
                                        <table class="table table-borderless table-sm">
                                            <tr>
                                                <td width="120"><strong>Lokasi Masuk</strong></td>
                                                <td>: ${data.location_in || '-'}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Lokasi Keluar</strong></td>
                                                <td>: ${data.location_out || '-'}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>GPS Akurasi In</strong></td>
                                                <td>: ${data.gps_accuracy_in ? data.gps_accuracy_in + 'm' : '-'}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>GPS Akurasi Out</strong></td>
                                                <td>: ${data.gps_accuracy_out ? data.gps_accuracy_out + 'm' : '-'}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    ${data.photo_in ? `
                                                                                <div class="col-md-6 text-center">
                                                                                    <h6>Foto Check In</h6>
                                                                                    <img src="/storage/${data.photo_in}" class="img-fluid rounded border" alt="Check In" style="max-height: 300px;">
                                                                                </div>
                                                                            ` : ''}
                                    ${data.photo_out ? `
                                                                                <div class="col-md-6 text-center">
                                                                                    <h6>Foto Check Out</h6>
                                                                                    <img src="/storage/${data.photo_out}" class="img-fluid rounded border" alt="Check Out" style="max-height: 300px;">
                                                                                </div>
                                                                            ` : ''}
                                </div>

                                ${data.notes ? `
                                                                            <div class="mt-3">
                                                                                <h6>Catatan</h6>
                                                                                <div class="alert alert-info">${data.notes}</div>
                                                                            </div>
                                                                        ` : ''}
                            `;

                            $('#detailContent').html(html);
                        } else {
                            $('#detailContent').html(`
                                <div class="alert alert-danger">
                                    ${response.message}
                                </div>
                            `);
                        }
                    },
                    error: function() {
                        $('#detailContent').html(`
                            <div class="alert alert-danger">
                                Gagal memuat detail absensi
                            </div>
                        `);
                    }
                });
            });
        });
    </script>
@endpush
