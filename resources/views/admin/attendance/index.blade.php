@extends('layouts.app')

@section('title', 'Daftar Absensi Karyawan')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">
                <span class="text-muted fw-light">Absensi /</span> Daftar Absensi
            </h4>

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
                                <h4 class="mb-0">{{ $stats['total'] }}</h4>
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
                                <h4 class="mb-0">{{ $stats['hadir'] }}</h4>
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
                                <h4 class="mb-0">{{ $stats['terlambat'] }}</h4>
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
                                <h4 class="mb-0">{{ $stats['alpha'] }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Table Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Filter & Pencarian</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.attendance.index') }}" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Dari</label>
                            <input type="date" class="form-control" name="date_from"
                                value="{{ request('date_from', $dateFrom) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Sampai</label>
                            <input type="date" class="form-control" name="date_to"
                                value="{{ request('date_to', $dateTo) }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">Semua Status</option>
                                <option value="hadir" {{ request('status') == 'hadir' ? 'selected' : '' }}>Hadir</option>
                                <option value="terlambat" {{ request('status') == 'terlambat' ? 'selected' : '' }}>Terlambat
                                </option>
                                <option value="cuti" {{ request('status') == 'cuti' ? 'selected' : '' }}>Cuti</option>
                                <option value="izin" {{ request('status') == 'izin' ? 'selected' : '' }}>Izin</option>
                                <option value="sakit" {{ request('status') == 'sakit' ? 'selected' : '' }}>Sakit</option>
                                <option value="alpha" {{ request('status') == 'alpha' ? 'selected' : '' }}>Alpha</option>
                            </select>
                        </div>
                        <div class="col-md-2">
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
                            <label class="form-label">Cari</label>
                            <input type="text" class="form-control" name="search" placeholder="NIP atau Nama"
                                value="{{ request('search') }}">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class='bx bx-filter-alt me-1'></i> Filter
                            </button>
                            <a href="{{ route('admin.attendance.index') }}" class="btn btn-secondary">
                                <i class='bx bx-reset me-1'></i> Reset
                            </a>
                            <button type="button" class="btn btn-success" id="exportBtn">
                                <i class='bx bx-download me-1'></i> Export Excel
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Attendance Table -->
        <div class="card mt-4">
            <div class="card-body">
                <!-- Desktop Table View -->
                <div class="table-responsive d-none d-lg-block">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIP</th>
                                <th>Nama Karyawan</th>
                                <th>Departemen</th>
                                <th>Tanggal</th>
                                <th>Check In</th>
                                <th>Foto In</th>
                                <th>Check Out</th>
                                <th>Foto Out</th>
                                <th>Status</th>
                                <th>Terlambat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendances as $index => $attendance)
                                <tr>
                                    <td>{{ $attendances->firstItem() + $index }}</td>
                                    <td><strong>{{ $attendance->employee->employee_code }}</strong></td>
                                    <td>{{ $attendance->employee->name }}</td>
                                    <td>{{ $attendance->employee->department->name }}</td>
                                    <td>{{ \Carbon\Carbon::parse($attendance->attendance_date)->locale('id')->translatedFormat('l, d F Y') }}
                                    </td>
                                    <td>
                                        @if ($attendance->check_in)
                                            <span
                                                class="badge bg-label-success">{{ \Carbon\Carbon::parse($attendance->check_in)->format('H:i') }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($attendance->photo_in)
                                            <a href="{{ asset('storage/' . $attendance->photo_in) }}" target="_blank">
                                                <img src="{{ asset('storage/' . $attendance->photo_in) }}"
                                                    alt="Foto Check In" class="rounded"
                                                    style="width: 40px; height: 40px; object-fit: cover; cursor: pointer;"
                                                    data-bs-toggle="tooltip" title="Klik untuk memperbesar">
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($attendance->check_out)
                                            <span
                                                class="badge bg-label-warning">{{ \Carbon\Carbon::parse($attendance->check_out)->format('H:i') }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($attendance->photo_out)
                                            <a href="{{ asset('storage/' . $attendance->photo_out) }}" target="_blank">
                                                <img src="{{ asset('storage/' . $attendance->photo_out) }}"
                                                    alt="Foto Check Out" class="rounded"
                                                    style="width: 40px; height: 40px; object-fit: cover; cursor: pointer;"
                                                    data-bs-toggle="tooltip" title="Klik untuk memperbesar">
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($attendance->status == 'hadir')
                                            <span class="badge bg-success">HADIR</span>
                                        @elseif($attendance->status == 'terlambat')
                                            <span class="badge bg-warning">TERLAMBAT</span>
                                        @elseif($attendance->status == 'cuti')
                                            <span class="badge bg-primary">CUTI</span>
                                        @elseif($attendance->status == 'izin')
                                            <span class="badge bg-info">IZIN</span>
                                        @elseif($attendance->status == 'sakit')
                                            <span class="badge bg-secondary">SAKIT</span>
                                        @elseif($attendance->status == 'alpha')
                                            <span class="badge bg-danger">ALPHA</span>
                                        @else
                                            <span class="badge bg-dark">{{ strtoupper($attendance->status) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($attendance->late_minutes > 0)
                                            <span class="badge bg-label-warning">{{ $attendance->late_minutes }}
                                                menit</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow"
                                                data-bs-toggle="dropdown">
                                                <i class="bx bx-dots-vertical-rounded"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item detail-btn" href="javascript:void(0);"
                                                    data-id="{{ $attendance->id }}">
                                                    <i class="bx bx-detail me-1"></i> Detail
                                                </a>
                                                <a class="dropdown-item text-danger delete-btn" href="javascript:void(0);"
                                                    data-id="{{ $attendance->id }}"
                                                    data-name="{{ $attendance->employee->name }}"
                                                    data-date="{{ \Carbon\Carbon::parse($attendance->attendance_date)->locale('id')->translatedFormat('d F Y') }}">
                                                    <i class="bx bx-trash me-1"></i> Hapus
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center py-4">
                                        <i class='bx bx-info-circle bx-lg text-muted'></i>
                                        <p class="mt-2 mb-0 text-muted">Tidak ada data absensi</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="d-lg-none">
                    @forelse($attendances as $index => $attendance)
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="mb-1">{{ $attendance->employee->name }}</h6>
                                        <small class="text-muted">
                                            <i class='bx bx-id-card'></i> {{ $attendance->employee->employee_code }}
                                        </small>
                                    </div>
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown">
                                            <i class="bx bx-dots-vertical-rounded"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item detail-btn" href="javascript:void(0);"
                                                data-id="{{ $attendance->id }}">
                                                <i class="bx bx-detail me-1"></i> Detail
                                            </a>
                                            <a class="dropdown-item text-danger delete-btn" href="javascript:void(0);"
                                                data-id="{{ $attendance->id }}"
                                                data-name="{{ $attendance->employee->name }}"
                                                data-date="{{ \Carbon\Carbon::parse($attendance->attendance_date)->locale('id')->translatedFormat('d F Y') }}">
                                                <i class="bx bx-trash me-1"></i> Hapus
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <small class="text-muted d-block mb-1">
                                        <i class='bx bx-buildings'></i> {{ $attendance->employee->department->name }}
                                    </small>
                                    <small class="text-muted d-block">
                                        <i class='bx bx-calendar'></i>
                                        {{ \Carbon\Carbon::parse($attendance->attendance_date)->locale('id')->translatedFormat('d F Y') }}
                                    </small>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <div class="border rounded p-2 text-center">
                                            <small class="text-muted d-block">Check In</small>
                                            @if ($attendance->check_in)
                                                <strong
                                                    class="text-success">{{ \Carbon\Carbon::parse($attendance->check_in)->format('H:i') }}</strong>
                                            @else
                                                <strong class="text-muted">-</strong>
                                            @endif
                                            <div class="mt-2">
                                                @if ($attendance->photo_in)
                                                    <a href="{{ asset('storage/' . $attendance->photo_in) }}"
                                                        target="_blank">
                                                        <img src="{{ asset('storage/' . $attendance->photo_in) }}"
                                                            alt="Foto In" class="rounded"
                                                            style="width: 50px; height: 50px; object-fit: cover;">
                                                    </a>
                                                @else
                                                    <i class='bx bx-image-alt text-muted' style="font-size: 24px;"></i>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="border rounded p-2 text-center">
                                            <small class="text-muted d-block">Check Out</small>
                                            @if ($attendance->check_out)
                                                <strong
                                                    class="text-warning">{{ \Carbon\Carbon::parse($attendance->check_out)->format('H:i') }}</strong>
                                            @else
                                                <strong class="text-muted">-</strong>
                                            @endif
                                            <div class="mt-2">
                                                @if ($attendance->photo_out)
                                                    <a href="{{ asset('storage/' . $attendance->photo_out) }}"
                                                        target="_blank">
                                                        <img src="{{ asset('storage/' . $attendance->photo_out) }}"
                                                            alt="Foto Out" class="rounded"
                                                            style="width: 50px; height: 50px; object-fit: cover;">
                                                    </a>
                                                @else
                                                    <i class='bx bx-image-alt text-muted' style="font-size: 24px;"></i>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        @if ($attendance->status == 'hadir')
                                            <span class="badge bg-success">HADIR</span>
                                        @elseif($attendance->status == 'terlambat')
                                            <span class="badge bg-warning">TERLAMBAT</span>
                                        @elseif($attendance->status == 'cuti')
                                            <span class="badge bg-primary">CUTI</span>
                                        @elseif($attendance->status == 'izin')
                                            <span class="badge bg-info">IZIN</span>
                                        @elseif($attendance->status == 'sakit')
                                            <span class="badge bg-secondary">SAKIT</span>
                                        @elseif($attendance->status == 'alpha')
                                            <span class="badge bg-danger">ALPHA</span>
                                        @else
                                            <span class="badge bg-dark">{{ strtoupper($attendance->status) }}</span>
                                        @endif
                                    </div>
                                    @if ($attendance->late_minutes > 0)
                                        <span class="badge bg-label-warning">
                                            <i class='bx bx-time'></i> {{ $attendance->late_minutes }} menit
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <i class='bx bx-info-circle' style="font-size: 48px; color: #ccc;"></i>
                            <p class="mt-2 mb-0 text-muted">Tidak ada data absensi</p>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                <div class="mt-3">
                    {{ $attendances->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Absensi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Initialize tooltips
            $(document).ready(function() {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            });

            // Export Excel
            $('#exportBtn').on('click', function() {
                const params = new URLSearchParams(window.location.search);
                window.location.href = '{{ route('admin.attendance.export') }}?' + params.toString();
            });

            // Detail attendance
            $('.detail-btn').on('click', function() {
                const id = $(this).data('id');
                const modal = new bootstrap.Modal(document.getElementById('detailModal'));

                $('#detailContent').html(`
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `);

                modal.show();

                $.ajax({
                    url: `/api/admin/attendance/${id}/detail`,
                    method: 'GET',
                    success: function(response) {
                        const data = response.data;
                        const emp = data.employee;
                        const schedule = emp.work_schedule;

                        $('#detailContent').html(`
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="mb-3">Informasi Karyawan</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">NIP</th>
                                            <td>${emp.employee_code}</td>
                                        </tr>
                                        <tr>
                                            <th>Nama</th>
                                            <td>${emp.name}</td>
                                        </tr>
                                        <tr>
                                            <th>Departemen</th>
                                            <td>${emp.department.name}</td>
                                        </tr>
                                        <tr>
                                            <th>Jabatan</th>
                                            <td>${emp.position.name}</td>
                                        </tr>
                                        <tr>
                                            <th>Jenis Shift</th>
                                            <td>${schedule ? schedule.name : '-'}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="mb-3">Detail Absensi</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">Tanggal</th>
                                            <td>${new Date(data.attendance_date).toLocaleDateString('id-ID', {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'})}</td>
                                        </tr>
                                        <tr>
                                            <th>Jam Masuk</th>
                                            <td>${schedule ? schedule.start_time : '-'}</td>
                                        </tr>
                                        <tr>
                                            <th>Jam Pulang</th>
                                            <td>${schedule ? schedule.end_time : '-'}</td>
                                        </tr>
                                        <tr>
                                            <th>Check In</th>
                                            <td><span class="badge bg-label-success">${data.check_in || '-'}</span></td>
                                        </tr>
                                        <tr>
                                            <th>Check Out</th>
                                            <td><span class="badge bg-label-warning">${data.check_out || '-'}</span></td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td>
                                                ${data.status === 'hadir' ? '<span class="badge bg-success">HADIR</span>' : ''}
                                                ${data.status === 'terlambat' ? '<span class="badge bg-warning">TERLAMBAT</span>' : ''}
                                                ${data.status === 'cuti' ? '<span class="badge bg-primary">CUTI</span>' : ''}
                                                ${data.status === 'izin' ? '<span class="badge bg-info">IZIN</span>' : ''}
                                                ${data.status === 'sakit' ? '<span class="badge bg-secondary">SAKIT</span>' : ''}
                                                ${data.status === 'alpha' ? '<span class="badge bg-danger">ALPHA</span>' : ''}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Terlambat</th>
                                            <td>${data.late_minutes > 0 ? data.late_minutes + ' menit' : '-'}</td>
                                        </tr>
                                    </table>
                                    ${data.notes ? `
                                                                                                        <div class="alert alert-info mt-3 mb-0">
                                                                                                            <strong>Catatan:</strong><br>${data.notes}
                                                                                                        </div>
                                                                                                    ` : ''}
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="mb-3">Foto Check In</h6>
                                    ${data.photo_in ? `
                                                                                        <div class="text-center">
                                                                                            <a href="/storage/${data.photo_in}" target="_blank">
                                                                                                <img src="/storage/${data.photo_in}"
                                                                                                     alt="Foto Check In"
                                                                                                     class="img-fluid rounded border"
                                                                                                     style="max-height: 300px; cursor: pointer;">
                                                                                            </a>
                                                                                            <p class="text-muted mt-2 mb-0">
                                                                                                <small><i class='bx bx-time-five'></i> ${data.check_in || '-'}</small>
                                                                                            </p>
                                                                                        </div>
                                                                                    ` : `
                                                                                        <div class="text-center py-4">
                                                                                            <i class='bx bx-image-add bx-lg text-muted'></i>
                                                                                            <p class="text-muted mt-2 mb-0">Tidak ada foto check in</p>
                                                                                        </div>
                                                                                    `}
                                </div>
                                <div class="col-md-6">
                                    <h6 class="mb-3">Foto Check Out</h6>
                                    ${data.photo_out ? `
                                                                                        <div class="text-center">
                                                                                            <a href="/storage/${data.photo_out}" target="_blank">
                                                                                                <img src="/storage/${data.photo_out}"
                                                                                                     alt="Foto Check Out"
                                                                                                     class="img-fluid rounded border"
                                                                                                     style="max-height: 300px; cursor: pointer;">
                                                                                            </a>
                                                                                            <p class="text-muted mt-2 mb-0">
                                                                                                <small><i class='bx bx-time-five'></i> ${data.check_out || '-'}</small>
                                                                                            </p>
                                                                                        </div>
                                                                                    ` : `
                                                                                        <div class="text-center py-4">
                                                                                            <i class='bx bx-image-add bx-lg text-muted'></i>
                                                                                            <p class="text-muted mt-2 mb-0">Tidak ada foto check out</p>
                                                                                        </div>
                                                                                    `}
                                </div>
                            </div>
                        `);
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

            // Delete attendance
            $('.delete-btn').on('click', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                const date = $(this).data('date');

                Swal.fire({
                    title: 'Hapus Data Absensi?',
                    html: `Apakah Anda yakin ingin menghapus data absensi:<br><strong>${name}</strong><br>Tanggal: <strong>${date}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        Swal.fire({
                            title: 'Menghapus...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Send delete request
                        $.ajax({
                            url: `/api/admin/attendance/${id}`,
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil!',
                                    text: response.message,
                                    showConfirmButton: false,
                                    timer: 2000
                                }).then(() => {
                                    // Reload page
                                    window.location.reload();
                                });
                            },
                            error: function(xhr) {
                                let errorMessage = 'Gagal menghapus data absensi';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }

                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal!',
                                    text: errorMessage,
                                    confirmButtonText: 'OK'
                                });
                            }
                        });
                    }
                });
            });
        </script>
    @endpush
@endsection
