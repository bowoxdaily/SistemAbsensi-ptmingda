@extends('layouts.app')

@section('title', 'Manajemen Cuti & Izin')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">
                <span class="text-muted fw-light">Admin /</span> Manajemen Cuti & Izin
            </h4>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-4 col-md-6 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="fw-semibold d-block mb-1">Menunggu Persetujuan</span>
                                <h3 class="card-title mb-0">{{ $stats['pending'] }}</h3>
                            </div>
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-warning">
                                    <i class='bx bx-time-five bx-sm'></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="fw-semibold d-block mb-1">Disetujui</span>
                                <h3 class="card-title mb-0">{{ $stats['approved'] }}</h3>
                            </div>
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class='bx bx-check-circle bx-sm'></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12 col-sm-12 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="fw-semibold d-block mb-1">Ditolak</span>
                                <h3 class="card-title mb-0">{{ $stats['rejected'] }}</h3>
                            </div>
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-danger">
                                    <i class='bx bx-x-circle bx-sm'></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Table Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Daftar Pengajuan Cuti & Izin</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse"
                    data-bs-target="#filterCollapse">
                    <i class='bx bx-filter-alt'></i> Filter
                </button>
            </div>

            <!-- Filter Form -->
            <div class="collapse {{ request()->hasAny(['status', 'leave_type', 'start_date', 'end_date']) ? 'show' : '' }}"
                id="filterCollapse">
                <div class="card-body border-bottom">
                    <form method="GET" action="{{ route('admin.leave.index') }}">
                        <div class="row g-3">
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending
                                    </option>
                                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>
                                        Disetujui</option>
                                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label">Jenis</label>
                                <select name="leave_type" class="form-select">
                                    <option value="">Semua Jenis</option>
                                    <option value="cuti" {{ request('leave_type') == 'cuti' ? 'selected' : '' }}>Cuti
                                    </option>
                                    <option value="izin" {{ request('leave_type') == 'izin' ? 'selected' : '' }}>Izin
                                    </option>
                                    <option value="sakit" {{ request('leave_type') == 'sakit' ? 'selected' : '' }}>Sakit
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label">Dari Tanggal</label>
                                <input type="date" name="start_date" class="form-control"
                                    value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label">Sampai Tanggal</label>
                                <input type="date" name="end_date" class="form-control"
                                    value="{{ request('end_date') }}">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class='bx bx-search'></i> Filter
                            </button>
                            <a href="{{ route('admin.leave.index') }}" class="btn btn-outline-secondary">
                                <i class='bx bx-reset'></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="table-responsive text-nowrap d-none d-md-block">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Karyawan</th>
                            <th>Jenis</th>
                            <th>Tanggal</th>
                            <th>Durasi</th>
                            <th>Status</th>
                            <th>Diajukan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse($leaves as $leave)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if ($leave->employee->photo)
                                            <img src="{{ asset('storage/' . $leave->employee->photo) }}" alt="Foto"
                                                class="rounded-circle me-2"
                                                style="width: 40px; height: 40px; object-fit: cover;">
                                        @else
                                            <div class="avatar avatar-sm me-2">
                                                <span class="avatar-initial rounded-circle bg-label-primary">
                                                    {{ strtoupper(substr($leave->employee->name, 0, 1)) }}
                                                </span>
                                            </div>
                                        @endif
                                        <div>
                                            <strong>{{ $leave->employee->name }}</strong><br>
                                            <small class="text-muted">{{ $leave->employee->department->name ?? '-' }} -
                                                {{ $leave->employee->position->name ?? '-' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if ($leave->leave_type == 'cuti')
                                        <span class="badge bg-label-primary">
                                            <i class='bx bx-calendar'></i> Cuti
                                        </span>
                                    @elseif($leave->leave_type == 'izin')
                                        <span class="badge bg-label-info">
                                            <i class='bx bx-time-five'></i> Izin
                                        </span>
                                    @else
                                        <span class="badge bg-label-warning">
                                            <i class='bx bx-first-aid'></i> Sakit
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ \Carbon\Carbon::parse($leave->start_date)->format('d M Y') }}</small><br>
                                    <small class="text-muted">s/d
                                        {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}</small>
                                </td>
                                <td>
                                    <strong>{{ $leave->total_days }}</strong> hari
                                </td>
                                <td>
                                    @if ($leave->status == 'pending')
                                        <span class="badge bg-label-warning">
                                            <i class='bx bx-time-five'></i> Pending
                                        </span>
                                    @elseif($leave->status == 'approved')
                                        <span class="badge bg-label-success">
                                            <i class='bx bx-check-circle'></i> Disetujui
                                        </span>
                                    @else
                                        <span class="badge bg-label-danger">
                                            <i class='bx bx-x-circle'></i> Ditolak
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $leave->created_at->format('d M Y') }}</small><br>
                                    <small class="text-muted">{{ $leave->created_at->format('H:i') }}</small>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown">
                                            <i class="bx bx-dots-vertical-rounded"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <button class="dropdown-item view-leave" data-id="{{ $leave->id }}">
                                                <i class="bx bx-show me-1"></i> Lihat Detail
                                            </button>
                                            @if ($leave->status == 'pending')
                                                <button class="dropdown-item approve-leave"
                                                    data-id="{{ $leave->id }}">
                                                    <i class="bx bx-check me-1"></i> Setujui
                                                </button>
                                                <button class="dropdown-item reject-leave" data-id="{{ $leave->id }}"
                                                    data-employee="{{ $leave->employee->name }}"
                                                    data-type="{{ ucfirst($leave->leave_type) }}"
                                                    data-dates="{{ \Carbon\Carbon::parse($leave->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}">
                                                    <i class="bx bx-x me-1"></i> Tolak
                                                </button>
                                            @endif
                                            <button class="dropdown-item text-danger delete-leave"
                                                data-id="{{ $leave->id }}">
                                                <i class="bx bx-trash me-1"></i> Hapus
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class='bx bx-calendar-x' style="font-size: 48px; color: #ccc;"></i>
                                    <p class="text-muted mt-2 mb-0">Tidak ada data pengajuan cuti</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="d-md-none">
                @forelse($leaves as $leave)
                    <div class="card-body border-bottom">
                        <div class="d-flex align-items-start mb-3">
                            @if ($leave->employee->photo)
                                <img src="{{ asset('storage/' . $leave->employee->photo) }}" alt="Foto"
                                    class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                            @else
                                <div class="avatar me-3">
                                    <span class="avatar-initial rounded-circle bg-label-primary">
                                        {{ strtoupper(substr($leave->employee->name, 0, 1)) }}
                                    </span>
                                </div>
                            @endif
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ $leave->employee->name }}</h6>
                                <small class="text-muted">{{ $leave->employee->department->name ?? '-' }}</small>
                            </div>
                            @if ($leave->status == 'pending')
                                <span class="badge bg-label-warning">Pending</span>
                            @elseif($leave->status == 'approved')
                                <span class="badge bg-label-success">Disetujui</span>
                            @else
                                <span class="badge bg-label-danger">Ditolak</span>
                            @endif
                        </div>

                        <div class="mb-2">
                            @if ($leave->leave_type == 'cuti')
                                <span class="badge bg-label-primary"><i class='bx bx-calendar'></i> Cuti</span>
                            @elseif($leave->leave_type == 'izin')
                                <span class="badge bg-label-info"><i class='bx bx-time-five'></i> Izin</span>
                            @else
                                <span class="badge bg-label-warning"><i class='bx bx-first-aid'></i> Sakit</span>
                            @endif
                            <strong>{{ $leave->total_days }} hari</strong>
                        </div>

                        <small class="text-muted d-block mb-2">
                            {{ \Carbon\Carbon::parse($leave->start_date)->format('d M Y') }} -
                            {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}
                        </small>

                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary view-leave" data-id="{{ $leave->id }}">
                                <i class='bx bx-show'></i> Detail
                            </button>
                            @if ($leave->status == 'pending')
                                <button class="btn btn-sm btn-success approve-leave" data-id="{{ $leave->id }}">
                                    <i class='bx bx-check'></i> Setujui
                                </button>
                                <button class="btn btn-sm btn-danger reject-leave" data-id="{{ $leave->id }}"
                                    data-employee="{{ $leave->employee->name }}"
                                    data-type="{{ ucfirst($leave->leave_type) }}"
                                    data-dates="{{ \Carbon\Carbon::parse($leave->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}">
                                    <i class='bx bx-x'></i> Tolak
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="card-body text-center py-4">
                        <i class='bx bx-calendar-x' style="font-size: 48px; color: #ccc;"></i>
                        <p class="text-muted mt-2 mb-0">Tidak ada data pengajuan cuti</p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if ($leaves->hasPages())
                <div class="card-footer">
                    <div class="d-flex justify-content-center">
                        {{ $leaves->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Detail Leave -->
    <div class="modal fade" id="detailLeaveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pengajuan Cuti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detailLeaveContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" id="detailLeaveFooter">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // View Leave Detail
            $(document).on('click', '.view-leave', function() {
                const leaveId = $(this).data('id');
                const modal = new bootstrap.Modal(document.getElementById('detailLeaveModal'));

                modal.show();

                // Reset content
                $('#detailLeaveContent').html(`
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `);

                $.ajax({
                    url: `/api/leave/${leaveId}`,
                    type: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        if (response.success) {
                            const leave = response.data;
                            const statusBadge = leave.status === 'pending' ?
                                '<span class="badge bg-label-warning"><i class="bx bx-time-five"></i> Pending</span>' :
                                leave.status === 'approved' ?
                                '<span class="badge bg-label-success"><i class="bx bx-check-circle"></i> Disetujui</span>' :
                                '<span class="badge bg-label-danger"><i class="bx bx-x-circle"></i> Ditolak</span>';

                            const typeBadge = leave.leave_type === 'cuti' ?
                                '<span class="badge bg-label-primary"><i class="bx bx-calendar"></i> Cuti</span>' :
                                leave.leave_type === 'izin' ?
                                '<span class="badge bg-label-info"><i class="bx bx-time-five"></i> Izin</span>' :
                                '<span class="badge bg-label-warning"><i class="bx bx-first-aid"></i> Sakit</span>';

                            let content = `
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h6 class="mb-2">Informasi Karyawan</h6>
                                        <table class="table table-sm table-borderless">
                                            <tr>
                                                <td width="120"><strong>Nama</strong></td>
                                                <td>: ${leave.employee.name}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>NIP</strong></td>
                                                <td>: ${leave.employee.nip || '-'}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Departemen</strong></td>
                                                <td>: ${leave.employee.department?.name || '-'}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Posisi</strong></td>
                                                <td>: ${leave.employee.position?.name || '-'}</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="mb-2">Informasi Cuti</h6>
                                        <table class="table table-sm table-borderless">
                                            <tr>
                                                <td width="120"><strong>Jenis</strong></td>
                                                <td>: ${typeBadge}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Status</strong></td>
                                                <td>: ${statusBadge}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Tanggal Mulai</strong></td>
                                                <td>: ${new Date(leave.start_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Tanggal Selesai</strong></td>
                                                <td>: ${new Date(leave.end_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Durasi</strong></td>
                                                <td>: <strong>${leave.total_days} hari</strong></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                <hr>
                                <div class="mb-3">
                                    <h6 class="mb-2">Alasan</h6>
                                    <p class="text-muted">${leave.reason || '-'}</p>
                                </div>
                            `;

                            if (leave.status === 'rejected' && leave.rejection_reason) {
                                content += `
                                    <hr>
                                    <div class="alert alert-danger">
                                        <h6 class="alert-heading mb-2">Alasan Penolakan</h6>
                                        <p class="mb-0">${leave.rejection_reason}</p>
                                    </div>
                                `;
                            }

                            if (leave.attachment) {
                                content += `
                                    <hr>
                                    <div class="mb-3">
                                        <h6 class="mb-2">Lampiran</h6>
                                        <a href="/storage/${leave.attachment}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bx bx-file"></i> Lihat Lampiran
                                        </a>
                                    </div>
                                `;
                            }

                            content += `
                                <hr>
                                <small class="text-muted">
                                    <i class="bx bx-calendar"></i> Diajukan pada: ${new Date(leave.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })}
                                </small>
                            `;

                            $('#detailLeaveContent').html(content);

                            // Update footer buttons based on status
                            let footerButtons =
                                '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>';

                            if (leave.status === 'pending') {
                                footerButtons = `
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                    <button type="button" class="btn btn-danger reject-leave-modal"
                                        data-id="${leave.id}"
                                        data-employee="${leave.employee.name}"
                                        data-type="${leave.leave_type.charAt(0).toUpperCase() + leave.leave_type.slice(1)}"
                                        data-dates="${new Date(leave.start_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })} - ${new Date(leave.end_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}">
                                        <i class="bx bx-x"></i> Tolak
                                    </button>
                                    <button type="button" class="btn btn-success approve-leave-modal" data-id="${leave.id}">
                                        <i class="bx bx-check"></i> Setujui
                                    </button>
                                `;
                            }

                            $('#detailLeaveFooter').html(footerButtons);
                        }
                    },
                    error: function(xhr) {
                        $('#detailLeaveContent').html(`
                            <div class="alert alert-danger">
                                <i class="bx bx-error-circle"></i>
                                ${xhr.responseJSON?.message || 'Gagal memuat detail cuti'}
                            </div>
                        `);
                    }
                });
            });

            // Approve Leave from Modal
            $(document).on('click', '.approve-leave-modal', function() {
                const leaveId = $(this).data('id');
                const button = $(this);

                Swal.fire({
                    title: 'Setujui Pengajuan Cuti?',
                    text: "Cuti akan disetujui dan karyawan akan menerima notifikasi",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Setujui',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        button.prop('disabled', true);

                        $.ajax({
                            url: `/api/leave/${leaveId}/approve`,
                            type: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.success) {
                                    $('#detailLeaveModal').modal('hide');

                                    // Close SweetAlert first, then show toastr
                                    Swal.close();

                                    setTimeout(() => {
                                        toastr.success(response.message ||
                                            'Pengajuan cuti berhasil disetujui'
                                            );
                                    }, 100);

                                    setTimeout(() => location.reload(), 1500);
                                }
                            },
                            error: function(xhr) {
                                button.prop('disabled', false);
                                Swal.close();

                                setTimeout(() => {
                                    toastr.error(xhr.responseJSON?.message ||
                                        'Terjadi kesalahan saat menyetujui cuti'
                                        );
                                }, 100);
                            }
                        });
                    }
                });
            });

            // Reject Leave from Modal
            $(document).on('click', '.reject-leave-modal', function() {
                const leaveId = $(this).data('id');
                const employee = $(this).data('employee');
                const type = $(this).data('type');
                const dates = $(this).data('dates');
                const button = $(this);

                Swal.fire({
                    title: 'Tolak Pengajuan Cuti?',
                    html: `
                        <div style="text-align: left;">
                            <p><strong>Karyawan:</strong> ${employee}</p>
                            <p><strong>Jenis:</strong> ${type}</p>
                            <p><strong>Tanggal:</strong> ${dates}</p>
                        </div>
                    `,
                    input: 'textarea',
                    inputLabel: 'Alasan Penolakan',
                    inputPlaceholder: 'Jelaskan alasan penolakan...',
                    inputAttributes: {
                        'aria-label': 'Alasan penolakan',
                        'rows': 3
                    },
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Tolak',
                    cancelButtonText: 'Batal',
                    preConfirm: (reason) => {
                        if (!reason) {
                            Swal.showValidationMessage('Alasan penolakan harus diisi');
                        }
                        return reason;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        button.prop('disabled', true);

                        $.ajax({
                            url: `/api/leave/${leaveId}/reject`,
                            type: 'POST',
                            data: {
                                rejection_reason: result.value
                            },
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.success) {
                                    $('#detailLeaveModal').modal('hide');

                                    // Close SweetAlert first, then show toastr
                                    Swal.close();

                                    setTimeout(() => {
                                        toastr.success(response.message ||
                                            'Pengajuan cuti berhasil ditolak'
                                            );
                                    }, 100);

                                    setTimeout(() => location.reload(), 1500);
                                }
                            },
                            error: function(xhr) {
                                button.prop('disabled', false);
                                Swal.close();

                                setTimeout(() => {
                                    if (xhr.status === 422) {
                                        const errors = xhr.responseJSON.errors;
                                        Object.keys(errors).forEach(key => {
                                            toastr.error(errors[key][
                                            0]);
                                        });
                                    } else {
                                        toastr.error(xhr.responseJSON
                                            ?.message ||
                                            'Terjadi kesalahan saat menolak cuti'
                                            );
                                    }
                                }, 100);
                            }
                        });
                    }
                });
            });

            // Approve Leave
            $(document).on('click', '.approve-leave', function() {
                const leaveId = $(this).data('id');
                const button = $(this);

                Swal.fire({
                    title: 'Setujui Pengajuan Cuti?',
                    text: "Cuti akan disetujui dan karyawan akan menerima notifikasi",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Setujui',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        button.prop('disabled', true);

                        $.ajax({
                            url: `/api/leave/${leaveId}/approve`,
                            type: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Close SweetAlert first, then show toastr
                                    Swal.close();

                                    setTimeout(() => {
                                        toastr.success(response.message ||
                                            'Pengajuan cuti berhasil disetujui'
                                            );
                                    }, 100);

                                    setTimeout(() => location.reload(), 1500);
                                }
                            },
                            error: function(xhr) {
                                button.prop('disabled', false);
                                Swal.close();

                                setTimeout(() => {
                                    toastr.error(xhr.responseJSON?.message ||
                                        'Terjadi kesalahan saat menyetujui cuti'
                                        );
                                }, 100);
                            }
                        });
                    }
                });
            });

            // Reject Leave
            $(document).on('click', '.reject-leave', function() {
                const leaveId = $(this).data('id');
                const employee = $(this).data('employee');
                const type = $(this).data('type');
                const dates = $(this).data('dates');
                const button = $(this);

                Swal.fire({
                    title: 'Tolak Pengajuan Cuti?',
                    html: `
                        <div style="text-align: left;">
                            <p><strong>Karyawan:</strong> ${employee}</p>
                            <p><strong>Jenis:</strong> ${type}</p>
                            <p><strong>Tanggal:</strong> ${dates}</p>
                        </div>
                    `,
                    input: 'textarea',
                    inputLabel: 'Alasan Penolakan',
                    inputPlaceholder: 'Jelaskan alasan penolakan...',
                    inputAttributes: {
                        'aria-label': 'Alasan penolakan',
                        'rows': 3
                    },
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Tolak',
                    cancelButtonText: 'Batal',
                    preConfirm: (reason) => {
                        if (!reason) {
                            Swal.showValidationMessage('Alasan penolakan harus diisi');
                        }
                        return reason;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        button.prop('disabled', true);

                        $.ajax({
                            url: `/api/leave/${leaveId}/reject`,
                            type: 'POST',
                            data: {
                                rejection_reason: result.value
                            },
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Close SweetAlert first, then show toastr
                                    Swal.close();

                                    setTimeout(() => {
                                        toastr.success(response.message ||
                                            'Pengajuan cuti berhasil ditolak'
                                            );
                                    }, 100);

                                    setTimeout(() => location.reload(), 1500);
                                }
                            },
                            error: function(xhr) {
                                button.prop('disabled', false);
                                Swal.close();

                                setTimeout(() => {
                                    if (xhr.status === 422) {
                                        const errors = xhr.responseJSON.errors;
                                        Object.keys(errors).forEach(key => {
                                            toastr.error(errors[key][
                                            0]);
                                        });
                                    } else {
                                        toastr.error(xhr.responseJSON
                                            ?.message ||
                                            'Terjadi kesalahan saat menolak cuti'
                                            );
                                    }
                                }, 100);
                            }
                        });
                    }
                });
            });

            // Delete Leave
            $(document).on('click', '.delete-leave', function() {
                const leaveId = $(this).data('id');
                const button = $(this);

                Swal.fire({
                    title: 'Hapus Pengajuan Cuti?',
                    text: "Data akan dihapus permanen dan tidak dapat dikembalikan",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        button.prop('disabled', true);

                        $.ajax({
                            url: `/api/leave/${leaveId}`,
                            type: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Close SweetAlert first, then show toastr
                                    Swal.close();

                                    setTimeout(() => {
                                        toastr.success(response.message ||
                                            'Pengajuan cuti berhasil dihapus'
                                            );
                                    }, 100);

                                    setTimeout(() => location.reload(), 1500);
                                }
                            },
                            error: function(xhr) {
                                button.prop('disabled', false);
                                Swal.close();

                                setTimeout(() => {
                                    toastr.error(xhr.responseJSON?.message ||
                                        'Terjadi kesalahan saat menghapus cuti'
                                        );
                                }, 100);
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
