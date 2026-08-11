@extends('layouts.app')

@section('title', 'Pengajuan & Klarifikasi Absensi')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold mb-1">
                        <span class="text-muted fw-light">Karyawan / Absensi /</span> Pengajuan & Klarifikasi Absensi
                    </h4>
                    <p class="text-muted mb-0"><i class='bx bx-user'></i> {{ $employee->name }} — {{ $employee->employee_code }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('employee.attendance.history') }}" class="btn btn-secondary">
                        <i class='bx bx-calendar'></i> <span class="d-none d-sm-inline">Riwayat Absensi</span>
                    </a>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary"><i class='bx bx-home'></i></a>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-pills flex-column flex-md-row mb-4">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('employee.attendance.edit-requests') ? 'active' : '' }}" href="{{ route('employee.attendance.edit-requests') }}">
                    <i class="bx bx-edit me-1"></i> Form Edit Presensi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('employee.attendance.clarifications') ? 'active' : '' }}" href="{{ route('employee.attendance.clarifications') }}">
                    <i class="bx bx-file me-1"></i> Klarifikasi Berkas Fisik
                </a>
            </li>
        </ul>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-4">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body py-3">
                        <span class="avatar-initial rounded bg-label-warning d-inline-flex p-2 mb-2" style="font-size:1.5rem;"><i class='bx bx-time'></i></span>
                        <h4 class="mb-0 text-warning" id="statPending">-</h4>
                        <small class="text-muted">Menunggu</small>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body py-3">
                        <span class="avatar-initial rounded bg-label-success d-inline-flex p-2 mb-2" style="font-size:1.5rem;"><i class='bx bx-check'></i></span>
                        <h4 class="mb-0 text-success" id="statApproved">-</h4>
                        <small class="text-muted">Disetujui</small>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body py-3">
                        <span class="avatar-initial rounded bg-label-danger d-inline-flex p-2 mb-2" style="font-size:1.5rem;"><i class='bx bx-x'></i></span>
                        <h4 class="mb-0 text-danger" id="statRejected">-</h4>
                        <small class="text-muted">Ditolak</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- List -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><i class='bx bx-file-blank me-2'></i>Daftar Klarifikasi</h5>
                <select id="filterStatus" class="form-select form-select-sm" style="width:auto;">
                    <option value="">Semua Status</option>
                    <option value="pending">Menunggu</option>
                    <option value="approved">Disetujui</option>
                    <option value="rejected">Ditolak</option>
                </select>
            </div>
            <div class="card-body p-0" id="listContainer">
                <div class="text-center py-5"><div class="spinner-border text-warning"></div><p class="text-muted mt-2">Memuat data...</p></div>
            </div>
            <div class="card-footer" id="paginationContainer"></div>
        </div>
    </div>

    <!-- Modal Detail -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Klarifikasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <div class="text-center py-4"><div class="spinner-border text-warning"></div></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    let currentPage = 1, currentStatus = '';

    loadStats();
    loadList();

    $('#filterStatus').on('change', function () { currentStatus = $(this).val(); currentPage = 1; loadList(); });

    function loadStats() {
        $.get('/api/employee/attendance/clarifications/stats', function (res) {
            if (res.success) {
                $('#statPending').text(res.data.pending);
                $('#statApproved').text(res.data.approved);
                $('#statRejected').text(res.data.rejected);
            }
        });
    }

    function statusBadge(status) {
        if (status === 'pending')  return '<span class="badge bg-warning text-dark">Menunggu</span>';
        if (status === 'approved') return '<span class="badge bg-success">Disetujui</span>';
        return '<span class="badge bg-danger">Ditolak</span>';
    }

    function loadList() {
        $('#listContainer').html('<div class="text-center py-5"><div class="spinner-border text-warning"></div></div>');
        $.get('/api/employee/attendance/clarifications', { status: currentStatus, page: currentPage, per_page: 15 }, function (res) {
            if (!res.success || !res.data.data.length) {
                $('#listContainer').html(`<div class="text-center py-5">
                    <i class="bx bx-file-blank" style="font-size:4rem;color:#ddd;"></i>
                    <p class="text-muted mt-3 mb-2">Belum ada klarifikasi.</p>
                    <a href="{{ route('employee.attendance.history') }}" class="btn btn-warning btn-sm"><i class='bx bx-calendar'></i> Riwayat Absensi</a>
                    </div>`);
                $('#paginationContainer').html(''); return;
            }
            const data = res.data;

            let html = '<div class="table-responsive d-none d-md-block"><table class="table table-hover mb-0"><thead class="table-light"><tr>'
                + '<th>Tanggal</th><th>Status Baru</th><th>Masuk</th><th>Keluar</th><th>Review</th><th>Aksi</th></tr></thead><tbody>';
            data.data.forEach(item => {
                const attDate = item.formatted_attendance_date || (item.attendance?.attendance_date ? String(item.attendance.attendance_date).split('T')[0] : '-');
                html += `<tr>
                    <td>${attDate}</td>
                    <td><span class="badge bg-label-primary">${item.new_status?.toUpperCase() || '-'}</span></td>
                    <td>${item.new_check_in || '-'}</td><td>${item.new_check_out || '-'}</td>
                    <td>${statusBadge(item.status)}</td>
                    <td><button class="btn btn-sm btn-outline-primary btn-detail" data-id="${item.id}"><i class='bx bx-show'></i></button></td>
                </tr>`;
            });
            html += '</tbody></table></div>';

            html += '<div class="d-md-none p-3">';
            data.data.forEach(item => {
                const attDate = item.formatted_attendance_date || (item.attendance?.attendance_date ? String(item.attendance.attendance_date).split('T')[0] : '-');
                const sbc = item.status==='pending' ? 'bg-warning text-dark' : item.status==='approved' ? 'bg-success' : 'bg-danger';
                const sbl = item.status==='pending' ? 'Menunggu' : item.status==='approved' ? 'Disetujui' : 'Ditolak';
                html += `<div class="card mb-2 shadow-sm"><div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <strong>${attDate}</strong>
                        <span class="badge ${sbc}">${sbl}</span>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><small class="text-muted d-block">Status Baru</small><strong>${item.new_status?.toUpperCase() || '-'}</strong></div>
                        <div class="col-3"><small class="text-muted d-block">Masuk</small><strong>${item.new_check_in || '-'}</strong></div>
                        <div class="col-3"><small class="text-muted d-block">Keluar</small><strong>${item.new_check_out || '-'}</strong></div>
                    </div>
                    <button class="btn btn-sm btn-outline-primary w-100 btn-detail" data-id="${item.id}"><i class='bx bx-show'></i> Detail</button>
                </div></div>`;
            });
            html += '</div>';
            $('#listContainer').html(html);

            if (data.last_page > 1) {
                let pages = '<nav><ul class="pagination pagination-sm mb-0">';
                for (let p = 1; p <= data.last_page; p++) {
                    pages += `<li class="page-item ${p===data.current_page?'active':''}"><button class="page-link page-btn" data-page="${p}">${p}</button></li>`;
                }
                pages += '</ul></nav>';
                $('#paginationContainer').html(`<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">Menampilkan ${data.from}-${data.to} dari ${data.total}</small>${pages}</div>`);
            } else { $('#paginationContainer').html(''); }
        }).fail(() => { $('#listContainer').html('<div class="alert alert-danger m-3">Gagal memuat data.</div>'); });
    }

    $(document).on('click', '.page-btn', function () { currentPage = parseInt($(this).data('page')); loadList(); });

    $(document).on('click', '.btn-detail', function () {
        const id = $(this).data('id');
        $('#detailContent').html('<div class="text-center py-4"><div class="spinner-border text-warning"></div></div>');
        $('#detailModal').modal('show');

        $.get('/api/employee/attendance/clarifications/' + id, function (res) {
            if (!res.success) { $('#detailContent').html('<div class="alert alert-danger">Gagal memuat.</div>'); return; }
            const d = res.data;
            let html = `<div class="row">
                <div class="col-md-6 mb-3">
                    <h6 class="fw-semibold">Informasi Klarifikasi</h6>
                    <table class="table table-borderless table-sm">
                        <tr><td width="130"><strong>Tanggal Absensi</strong></td><td>: ${d.formatted_attendance_date || (d.attendance?.attendance_date ? String(d.attendance.attendance_date).split('T')[0] : '-')}</td></tr>
                        <tr><td><strong>Diajukan Pada</strong></td><td>: ${d.formatted_created_at || (d.created_at ? String(d.created_at).substring(0, 16) : '-')}</td></tr>
                        <tr><td><strong>Status Baru</strong></td><td>: <span class="badge bg-label-primary">${d.new_status?.toUpperCase() || '-'}</span></td></tr>
                        <tr><td><strong>Jam Masuk</strong></td><td>: ${d.new_check_in || '-'}</td></tr>
                        <tr><td><strong>Jam Keluar</strong></td><td>: ${d.new_check_out || '-'}</td></tr>
                        <tr><td><strong>Status</strong></td><td>: ${statusBadge(d.status)}</td></tr>
                        ${d.reviewed_at ? `<tr><td><strong>Direview</strong></td><td>: ${d.reviewed_at}</td></tr>` : ''}
                    </table>
                </div>
                <div class="col-md-6 mb-3">
                    <h6 class="fw-semibold">Alasan</h6>
                    <div class="alert alert-light border">${d.reason}</div>
                    ${d.review_notes ? `<h6 class="fw-semibold">Catatan Admin</h6>
                        <div class="alert ${d.status==='rejected'?'alert-danger':'alert-success'}">${d.review_notes}</div>` : ''}
                </div>
            </div>
            <h6 class="fw-semibold">Lampiran Formulir</h6>`;

            if (d.attachment_url) {
                if (d.is_image) {
                    html += `<div class="text-center">
                        <img src="${d.attachment_url}" class="img-fluid rounded border" style="max-height:400px;" alt="Lampiran">
                        <br><a href="${d.attachment_url}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                            <i class='bx bx-external-link'></i> Buka di Tab Baru</a></div>`;
                } else {
                    html += `<div class="alert alert-info"><i class='bx bx-file-blank me-2'></i><strong>${d.attachment_original_name}</strong>
                        <a href="${d.attachment_url}" target="_blank" class="btn btn-sm btn-primary ms-2">
                            <i class='bx bx-download'></i> Download</a></div>`;
                }
            }
            $('#detailContent').html(html);
        });
    });
});
</script>
@endpush
