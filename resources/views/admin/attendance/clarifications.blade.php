@extends('layouts.app')

@section('title', 'Request Edit & Klarifikasi Absensi')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold mb-1">
                        <span class="text-muted fw-light">Absensi /</span> Persetujuan Edit & Klarifikasi
                    </h4>
                    <p class="text-muted mb-0">Review dan setujui klarifikasi absensi karyawan yang disertai formulir fisik.</p>
                </div>
                <a href="{{ url('/admin/attendance') }}" class="btn btn-secondary"><i class='bx bx-calendar'></i></a>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-pills flex-column flex-md-row mb-4">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.attendance.edit-requests') ? 'active' : '' }}" href="{{ route('admin.attendance.edit-requests') }}">
                    <i class="bx bx-edit me-1"></i> Request Edit Digital
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.attendance.clarifications') ? 'active' : '' }}" href="{{ route('admin.attendance.clarifications') }}">
                    <i class="bx bx-file me-1"></i> Klarifikasi Berkas Fisik
                </a>
            </li>
        </ul>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm cursor-pointer stat-card" data-status="pending">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="avatar-initial rounded bg-label-warning p-3" style="font-size:1.5rem;"><i class='bx bx-time'></i></span>
                        <div><h2 class="mb-0 text-warning" id="statPending">-</h2><small class="text-muted">Menunggu Review</small></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm cursor-pointer stat-card" data-status="approved">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="avatar-initial rounded bg-label-success p-3" style="font-size:1.5rem;"><i class='bx bx-check'></i></span>
                        <div><h2 class="mb-0 text-success" id="statApproved">-</h2><small class="text-muted">Disetujui</small></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm cursor-pointer stat-card" data-status="rejected">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="avatar-initial rounded bg-label-danger p-3" style="font-size:1.5rem;"><i class='bx bx-x'></i></span>
                        <div><h2 class="mb-0 text-danger" id="statRejected">-</h2><small class="text-muted">Ditolak</small></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><i class='bx bx-file-blank me-2'></i>Daftar Klarifikasi</h5>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Cari karyawan..." style="width:200px;">
                    <select id="filterStatus" class="form-select form-select-sm" style="width:auto;">
                        <option value="pending">Menunggu Review</option>
                        <option value="">Semua Status</option>
                        <option value="approved">Disetujui</option>
                        <option value="rejected">Ditolak</option>
                    </select>
                </div>
            </div>
            <div class="card-body p-0" id="listContainer">
                <div class="text-center py-5"><div class="spinner-border text-warning" role="status"></div></div>
            </div>
            <div class="card-footer" id="paginationContainer"></div>
        </div>
    </div>

    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class='bx bx-file-blank me-1'></i> Review Klarifikasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="reviewContent">
                    <div class="text-center py-5"><div class="spinner-border text-warning" role="status"></div></div>
                </div>
                <div class="modal-footer" id="reviewFooter"></div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class='bx bx-x-circle me-1'></i> Tolak Klarifikasi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="rejectId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejectNotes" rows="4" maxlength="500"
                            placeholder="Contoh: Formulir tidak lengkap, tanda tangan tidak sesuai..."></textarea>
                    </div>
                    <div id="rejectAlert" class="d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="rejectConfirmBtn">
                        <span id="rejectSpinner" class="spinner-border spinner-border-sm d-none me-1"></span>Tolak
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {
    let currentPage = 1, currentStatus = 'pending', currentSearch = '', searchTimeout, currentReviewId = null;
    loadStats(); loadList();

    $('.stat-card').on('click', function () {
        currentStatus = $(this).data('status'); currentPage = 1; $('#filterStatus').val(currentStatus); loadList();
    });
    $('#filterStatus').on('change', function () { currentStatus = $(this).val(); currentPage = 1; loadList(); });
    $('#searchInput').on('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => { currentSearch = $(this).val(); currentPage = 1; loadList(); }, 400);
    });

    // Approve
    $(document).on('click', '.btn-approve', function () {
        const id = $(this).data('id');
        Swal.fire({ title: 'Setujui Klarifikasi?', text: 'Data absensi akan langsung diperbarui.', icon: 'question',
            showCancelButton: true, confirmButtonText: 'Ya, Setujui', cancelButtonText: 'Batal', confirmButtonColor: '#28a745'
        }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: '/api/admin/attendance/clarifications/' + id + '/approve', method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: res => { if (res.success) { $('#reviewModal').modal('hide'); Swal.fire('Disetujui!', res.message, 'success'); loadStats(); loadList(); } },
                error: xhr => Swal.fire('Error', xhr.responseJSON?.message || 'Gagal menyetujui.', 'error')
            });
        });
    });

    // Open reject modal
    $(document).on('click', '.btn-open-reject', function () {
        $('#rejectId').val(currentReviewId); $('#rejectNotes').val('');
        $('#rejectAlert').addClass('d-none'); $('#rejectModal').modal('show');
    });

    // Reject confirm
    $('#rejectConfirmBtn').on('click', function () {
        const notes = $('#rejectNotes').val().trim();
        if (!notes) { $('#rejectAlert').removeClass('d-none').addClass('alert alert-danger').text('Alasan wajib diisi.'); return; }
        $('#rejectSpinner').removeClass('d-none'); $(this).prop('disabled', true);
        $.ajax({
            url: '/api/admin/attendance/clarifications/' + $('#rejectId').val() + '/reject', method: 'POST',
            data: { review_notes: notes },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: res => {
                if (res.success) { $('#rejectModal').modal('hide'); $('#reviewModal').modal('hide'); Swal.fire('Ditolak', res.message, 'warning'); loadStats(); loadList(); }
            },
            error: xhr => { $('#rejectAlert').removeClass('d-none').addClass('alert alert-danger').text(xhr.responseJSON?.message || 'Gagal.'); },
            complete: () => { $('#rejectSpinner').addClass('d-none'); $('#rejectConfirmBtn').prop('disabled', false); }
        });
    });

    function loadStats() {
        $.get('/api/admin/attendance/clarifications/stats', function (res) {
            if (res.success) { $('#statPending').text(res.data.pending); $('#statApproved').text(res.data.approved); $('#statRejected').text(res.data.rejected); }
        });
    }
    function statusBadge(s) {
        if (s==='pending')  return '<span class="badge bg-warning text-dark">Menunggu</span>';
        if (s==='approved') return '<span class="badge bg-success">Disetujui</span>';
        return '<span class="badge bg-danger">Ditolak</span>';
    }
    function loadList() {
        $('#listContainer').html('<div class="text-center py-5"><div class="spinner-border text-warning"></div></div>');
        $.get('/api/admin/attendance/clarifications', { status: currentStatus, search: currentSearch, page: currentPage, per_page: 15 }, function (res) {
            if (!res.success || !res.data.data.length) {
                $('#listContainer').html('<div class="text-center py-5"><i class="bx bx-file-blank" style="font-size:4rem;color:#ddd;"></i><p class="text-muted mt-3">Tidak ada klarifikasi.</p></div>');
                $('#paginationContainer').html(''); return;
            }
            const data = res.data;
            let html = '<div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>Karyawan</th><th>Tanggal</th><th>Status Baru</th><th>Diajukan</th><th>Status</th><th>Aksi</th></tr></thead><tbody>';
            data.data.forEach(item => {
                const emp = item.employee;
                const attDate = item.formatted_attendance_date || (item.attendance?.attendance_date ? String(item.attendance.attendance_date).split('T')[0] : '-');
                const createdDate = item.formatted_created_at || (item.created_at ? String(item.created_at).substring(0, 10) : '-');
                html += `<tr>
                    <td><strong>${emp?.name||'-'}</strong><br><small class="text-muted">${emp?.employee_code||''}</small></td>
                    <td>${attDate}</td>
                    <td><span class="badge bg-label-primary">${item.new_status?.toUpperCase()||'-'}</span></td>
                    <td><small>${createdDate}</small></td>
                    <td>${statusBadge(item.status)}</td>
                    <td><button class="btn btn-sm btn-primary btn-review" data-id="${item.id}"><i class='bx bx-show'></i> Review</button></td>
                </tr>`;
            });
            html += '</tbody></table></div>';
            $('#listContainer').html(html);

            if (data.last_page > 1) {
                let pages = '<nav><ul class="pagination pagination-sm mb-0">';
                for (let p=1; p<=data.last_page; p++) {
                    pages += `<li class="page-item ${p===data.current_page?'active':''}"><button class="page-link page-btn" data-page="${p}">${p}</button></li>`;
                }
                pages += '</ul></nav>';
                $('#paginationContainer').html(`<div class="d-flex justify-content-between align-items-center flex-wrap gap-2"><small class="text-muted">Menampilkan ${data.from}-${data.to} dari ${data.total}</small>${pages}</div>`);
            } else { $('#paginationContainer').html(''); }
        }).fail(() => { $('#listContainer').html('<div class="alert alert-danger m-3">Gagal memuat data.</div>'); });
    }
    $(document).on('click', '.page-btn', function () { currentPage = parseInt($(this).data('page')); loadList(); });

    // Review modal open
    $(document).on('click', '.btn-review', function () {
        currentReviewId = $(this).data('id');
        $('#reviewContent').html('<div class="text-center py-5"><div class="spinner-border text-warning"></div></div>');
        $('#reviewFooter').html('');
        $('#reviewModal').modal('show');

        $.get('/api/admin/attendance/clarifications/' + currentReviewId, function (res) {
            if (!res.success) { $('#reviewContent').html('<div class="alert alert-danger">Gagal memuat.</div>'); return; }
            const d = res.data, emp = d.employee;

            let html = `<div class="row">
                <div class="col-md-4 mb-3">
                    <h6 class="fw-semibold border-bottom pb-2">Karyawan</h6>
                    <table class="table table-borderless table-sm">
                        <tr><td width="110"><strong>Nama</strong></td><td>: ${emp?.name||'-'}</td></tr>
                        <tr><td><strong>Kode</strong></td><td>: ${emp?.employee_code||'-'}</td></tr>
                        <tr><td><strong>Dept</strong></td><td>: ${emp?.department?.name||'-'}</td></tr>
                    </table>
                    <h6 class="fw-semibold border-bottom pb-2 mt-3">Detail</h6>
                    <table class="table table-borderless table-sm">
                        <tr><td width="110"><strong>Tanggal Absensi</strong></td><td>: ${d.formatted_attendance_date || (d.attendance?.attendance_date ? String(d.attendance.attendance_date).split('T')[0] : '-')}</td></tr>
                        <tr><td><strong>Diajukan Pada</strong></td><td>: ${d.formatted_created_at || (d.created_at ? String(d.created_at).substring(0, 16) : '-')}</td></tr>
                        <tr><td><strong>Status Lama</strong></td><td>: <span class="badge bg-label-secondary">${d.attendance?.status?.toUpperCase()||'-'}</span></td></tr>
                        <tr><td><strong>Status Baru</strong></td><td>: <span class="badge bg-label-primary">${d.new_status?.toUpperCase()||'-'}</span></td></tr>
                        <tr><td><strong>Masuk</strong></td><td>: ${d.new_check_in||'-'}</td></tr>
                        <tr><td><strong>Keluar</strong></td><td>: ${d.new_check_out||'-'}</td></tr>
                        <tr><td><strong>Status</strong></td><td>: ${statusBadge(d.status)}</td></tr>
                    </table>
                    <h6 class="fw-semibold border-bottom pb-2 mt-3">Alasan</h6>
                    <div class="alert alert-light border small">${d.reason}</div>
                    ${d.review_notes ? `<h6 class="fw-semibold">Catatan Review</h6><div class="alert ${d.status==='rejected'?'alert-danger':'alert-success'} small">${d.review_notes}</div>` : ''}
                </div>
                <div class="col-md-8 mb-3">
                    <h6 class="fw-semibold border-bottom pb-2">Scan Formulir Fisik</h6>`;

            if (d.attachment_url) {
                if (d.is_image) {
                    html += `<div class="text-center border rounded p-2" style="background:#f8f9fa;">
                        <img src="${d.attachment_url}" class="img-fluid rounded" style="max-height:520px;" alt="Scan">
                        <br><a href="${d.attachment_url}" target="_blank" class="btn btn-sm btn-outline-primary mt-2"><i class='bx bx-external-link'></i> Tab Baru</a>
                    </div>`;
                } else {
                    html += `<div class="alert alert-info d-flex align-items-center gap-3">
                        <i class='bx bx-file-pdf' style="font-size:2.5rem;"></i>
                        <div><strong>${d.attachment_original_name}</strong>
                        <br><a href="${d.attachment_url}" target="_blank" class="btn btn-sm btn-primary mt-2"><i class='bx bx-external-link'></i> Buka PDF</a></div></div>`;
                }
            } else { html += '<div class="alert alert-secondary">Tidak ada lampiran.</div>'; }

            html += '</div></div>';
            $('#reviewContent').html(html);

            if (d.status === 'pending') {
                $('#reviewFooter').html(`
                    <button class="btn btn-danger btn-open-reject me-auto"><i class='bx bx-x-circle'></i> Tolak</button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button class="btn btn-success btn-approve" data-id="${d.id}"><i class='bx bx-check-circle'></i> Setujui & Update Absensi</button>`);
            } else { $('#reviewFooter').html('<button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>'); }
        });
    });
});
</script>
@endpush
