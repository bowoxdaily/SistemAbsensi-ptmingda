@extends('layouts.app')

@section('title', 'Request Edit Absensi')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">
                <span class="text-muted fw-light">Absensi /</span> Request Edit Absensi
            </h4>
        </div>

        <!-- Statistik -->
        <div class="row mb-4">
            <div class="col-lg-4 col-md-6 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="fw-semibold d-block mb-1">Menunggu Persetujuan</span>
                                <h3 class="card-title mb-0" id="stat-pending">—</h3>
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
                                <h3 class="card-title mb-0" id="stat-approved">—</h3>
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
                                <h3 class="card-title mb-0" id="stat-rejected">—</h3>
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

        <!-- Tabel Request -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Request Edit Absensi</h5>
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm" id="filter-status" style="width:auto;">
                        <option value="">Semua Status</option>
                        <option value="pending" selected>Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefresh">
                        <i class='bx bx-refresh'></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <!-- Desktop Table -->
                <div class="table-responsive d-none d-lg-block">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Karyawan</th>
                                <th>Tanggal Absensi</th>
                                <th>Perubahan</th>
                                <th>Alasan</th>
                                <th>Diajukan Oleh</th>
                                <th>Diajukan Pada</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                    Memuat data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="d-lg-none p-3" id="mobileCards">
                    <div class="text-center py-4">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                        Memuat data...
                    </div>
                </div>

                <!-- Pagination -->
                <div class="p-3 d-flex justify-content-between align-items-center flex-wrap" id="paginationArea"></div>
            </div>
        </div>
    </div>

    {{-- ───── Modal Detail / Approve / Reject ───── --}}
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Request Edit Absensi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary"></div>
                    </div>
                </div>
                <div class="modal-footer" id="detailFooter"></div>
            </div>
        </div>
    </div>

    {{-- ───── Modal Tolak (butuh catatan) ───── --}}
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class='bx bx-x-circle me-1'></i> Tolak Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="reject_id">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan Penolakan <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reject_notes" rows="3"
                            placeholder="Jelaskan alasan penolakan..."></textarea>
                        <div class="invalid-feedback" id="reject_notes_error"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmReject">
                        <i class='bx bx-x-circle me-1'></i> Tolak
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .card-header { background-color: #fff; border-bottom: 1px solid #d9dee3; }
        .diff-old { background: #fff3cd; padding: 2px 6px; border-radius: 4px; text-decoration: line-through; color: #856404; }
        .diff-new { background: #d1e7dd; padding: 2px 6px; border-radius: 4px; color: #0f5132; font-weight: 600; }
    </style>
    @endpush

    @push('scripts')
    <script>
    let currentPage = 1;
    const tableColspan = 9;

    function statusBadge(status) {
        const map = {
            pending:  '<span class="badge bg-warning text-dark">Pending</span>',
            approved: '<span class="badge bg-success">Approved</span>',
            rejected: '<span class="badge bg-danger">Rejected</span>',
        };
        return map[status] || `<span class="badge bg-secondary">${status}</span>`;
    }

    function attendanceStatusLabel(status) {
        const map = {
            hadir: 'Hadir', terlambat: 'Terlambat', lembur: 'Lembur',
            izin: 'Izin', sakit: 'Sakit', cuti: 'Cuti',
            cuti_khusus: 'Cuti Khusus', cuti_bersama: 'Cuti Bersama',
            off: 'Off', libur: 'Libur', alpha: 'Alpha',
        };
        return map[status] || status;
    }

    function formatTime(t) {
        if (!t) return '-';
        return String(t).substring(0, 5);
    }

    function formatDate(d) {
        if (!d) return '-';
        const match = String(d).match(/(\d{4})-(\d{2})-(\d{2})/);
        if (match) {
            const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            return `${match[3]} ${months[parseInt(match[2])-1]} ${match[1]}`;
        }
        return d;
    }

    function loadStats() {
        $.get('/api/admin/attendance-edit-requests/stats', function(res) {
            if (!res.success) return;
            $('#stat-pending').text(res.data.pending);
            $('#stat-approved').text(res.data.approved);
            $('#stat-rejected').text(res.data.rejected);
        });
    }

    function loadRequests(page) {
        page = page || 1;
        currentPage = page;
        const status = $('#filter-status').val();

        $.get('/api/admin/attendance-edit-requests', { status: status, per_page: 15, page: page }, function(res) {
            if (!res.success) return;
            const rows   = res.data.data;
            const meta   = res.data;
            const isManager = {{ Auth::user()->role == 'manager' ? 'true' : 'false' }};

            // ── Desktop table ──
            let html = '';
            if (!rows.length) {
                html = `<tr><td colspan="${tableColspan}" class="text-center py-5 text-muted">
                    <i class='bx bx-info-circle bx-lg'></i><p class="mt-2 mb-0">Tidak ada data</p></td></tr>`;
            } else {
                rows.forEach((r, i) => {
                    const emp   = r.attendance?.employee ?? {};
                    const diff  = buildDiffHtml(r);
                    html += `<tr>
                        <td>${(page-1)*15 + i + 1}</td>
                        <td>
                            <div class="fw-semibold">${emp.name ?? '-'}</div>
                            <small class="text-muted">${emp.employee_code ?? ''}</small>
                        </td>
                        <td>${formatDate(r.old_attendance_date)}</td>
                        <td>${diff}</td>
                        <td style="max-width:200px;"><small>${r.reason}</small></td>
                        <td><small>${r.requester?.name ?? '-'}</small></td>
                        <td><small>${formatDate(r.created_at)}</small></td>
                        <td>${statusBadge(r.status)}</td>
                        ${isManager ? `<td>
                            <button class="btn btn-sm btn-outline-primary btn-detail" data-id="${r.id}" title="Detail">
                                <i class='bx bx-detail'></i>
                            </button>
                            ${r.status === 'pending' ? `
                            <button class="btn btn-sm btn-success btn-approve" data-id="${r.id}" title="Setujui">
                                <i class='bx bx-check'></i>
                            </button>
                            <button class="btn btn-sm btn-danger btn-reject" data-id="${r.id}" title="Tolak">
                                <i class='bx bx-x'></i>
                            </button>` : ''}
                        </td>` : '<td><button class="btn btn-sm btn-outline-secondary btn-detail" data-id="'+r.id+'"><i class=\'bx bx-detail\'></i></button></td>'}
                    </tr>`;
                });
            }
            $('#tableBody').html(html);

            // ── Mobile cards ──
            let mobileHtml = '';
            if (!rows.length) {
                mobileHtml = `<div class="text-center py-5 text-muted">
                    <i class='bx bx-info-circle' style="font-size:48px;color:#ccc;"></i>
                    <p class="mt-2 mb-0">Tidak ada data</p></div>`;
            } else {
                rows.forEach(r => {
                    const emp = r.attendance?.employee ?? {};
                    mobileHtml += `<div class="card mb-3 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-0">${emp.name ?? '-'}</h6>
                                    <small class="text-muted">${emp.employee_code ?? ''} · ${formatDate(r.old_attendance_date)}</small>
                                </div>
                                ${statusBadge(r.status)}
                            </div>
                            <div class="mb-2">${buildDiffHtml(r)}</div>
                            <small class="text-muted d-block mb-2"><strong>Alasan:</strong> ${r.reason}</small>
                            <small class="text-muted d-block mb-3">Diajukan oleh <strong>${r.requester?.name ?? '-'}</strong> pada ${formatDate(r.created_at)}</small>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary btn-detail" data-id="${r.id}">
                                    <i class='bx bx-detail me-1'></i>Detail
                                </button>
                                ${isManager && r.status === 'pending' ? `
                                <button class="btn btn-sm btn-success btn-approve" data-id="${r.id}">
                                    <i class='bx bx-check me-1'></i>Setujui
                                </button>
                                <button class="btn btn-sm btn-danger btn-reject" data-id="${r.id}">
                                    <i class='bx bx-x me-1'></i>Tolak
                                </button>` : ''}
                            </div>
                        </div>
                    </div>`;
                });
            }
            $('#mobileCards').html(mobileHtml);

            // ── Pagination ──
            let pagHtml = `<span class="text-muted small">Menampilkan ${meta.from ?? 0}–${meta.to ?? 0} dari ${meta.total} data</span>`;
            if (meta.last_page > 1) {
                pagHtml += `<nav><ul class="pagination pagination-sm mb-0">`;
                for (let p = 1; p <= meta.last_page; p++) {
                    pagHtml += `<li class="page-item ${p === page ? 'active' : ''}">
                        <button class="page-link btn-page" data-page="${p}">${p}</button></li>`;
                }
                pagHtml += `</ul></nav>`;
            }
            $('#paginationArea').html(pagHtml);
        });
    }

    function buildDiffHtml(r) {
        let parts = [];

        // Tanggal
        const oldDate = formatDate(r.old_attendance_date);
        const newDate = formatDate(r.new_attendance_date);
        if (oldDate !== newDate) {
            parts.push(`<div><small class="text-muted">Tgl:</small> <span class="diff-old">${oldDate}</span> → <span class="diff-new">${newDate}</span></div>`);
        }

        // Status
        if (r.old_status !== r.new_status) {
            parts.push(`<div><small class="text-muted">Status:</small> <span class="diff-old">${attendanceStatusLabel(r.old_status)}</span> → <span class="diff-new">${attendanceStatusLabel(r.new_status)}</span></div>`);
        }

        // Check In
        const oldIn  = formatTime(r.old_check_in);
        const newIn  = formatTime(r.new_check_in);
        if (oldIn !== newIn) {
            parts.push(`<div><small class="text-muted">In:</small> <span class="diff-old">${oldIn}</span> → <span class="diff-new">${newIn}</span></div>`);
        }

        // Check Out
        const oldOut = formatTime(r.old_check_out);
        const newOut = formatTime(r.new_check_out);
        if (oldOut !== newOut) {
            parts.push(`<div><small class="text-muted">Out:</small> <span class="diff-old">${oldOut}</span> → <span class="diff-new">${newOut}</span></div>`);
        }

        return parts.length ? parts.join('') : '<span class="text-muted small">—</span>';
    }

    // ── Detail Modal ──
    $(document).on('click', '.btn-detail', function() {
        const id = $(this).data('id');
        $('#detailBody').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
        $('#detailFooter').html('');
        const modal = new bootstrap.Modal(document.getElementById('detailModal'));
        modal.show();

        $.get(`/api/admin/attendance-edit-requests/${id}/detail`, function(res) {
            if (!res.success) { $('#detailBody').html('<p class="text-danger">Gagal memuat data.</p>'); return; }
            const r = res.data;
            const emp = r.attendance?.employee ?? {};
            const isManager = {{ Auth::user()->role == 'manager' ? 'true' : 'false' }};

            let body = `
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="fw-bold text-muted mb-3"><i class='bx bx-user me-1'></i>Info Karyawan</h6>
                            <p class="mb-1"><strong>Nama:</strong> ${emp.name ?? '-'}</p>
                            <p class="mb-1"><strong>NIP:</strong> ${emp.employee_code ?? '-'}</p>
                            <p class="mb-0"><strong>Divisi:</strong> ${emp.department?.name ?? '-'}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="fw-bold text-muted mb-3"><i class='bx bx-info-circle me-1'></i>Info Request</h6>
                            <p class="mb-1"><strong>Diajukan oleh:</strong> ${r.requester?.name ?? '-'}</p>
                            <p class="mb-1"><strong>Tanggal Ajukan:</strong> ${formatDate(r.created_at)}</p>
                            <p class="mb-0"><strong>Status:</strong> ${statusBadge(r.status)}</p>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <h6 class="fw-bold text-muted mb-3"><i class='bx bx-calendar-edit me-1'></i>Detail Perubahan</h6>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead><tr><th>Field</th><th>Sebelum</th><th>Sesudah</th></tr></thead>
                                    <tbody>
                                        <tr><td>Tanggal</td>
                                            <td><span class="${r.old_attendance_date !== r.new_attendance_date ? 'diff-old' : ''}">${formatDate(r.old_attendance_date)}</span></td>
                                            <td><span class="${r.old_attendance_date !== r.new_attendance_date ? 'diff-new' : ''}">${formatDate(r.new_attendance_date)}</span></td>
                                        </tr>
                                        <tr><td>Status</td>
                                            <td><span class="${r.old_status !== r.new_status ? 'diff-old' : ''}">${attendanceStatusLabel(r.old_status)}</span></td>
                                            <td><span class="${r.old_status !== r.new_status ? 'diff-new' : ''}">${attendanceStatusLabel(r.new_status)}</span></td>
                                        </tr>
                                        <tr><td>Check In</td>
                                            <td><span class="${formatTime(r.old_check_in) !== formatTime(r.new_check_in) ? 'diff-old' : ''}">${formatTime(r.old_check_in)}</span></td>
                                            <td><span class="${formatTime(r.old_check_in) !== formatTime(r.new_check_in) ? 'diff-new' : ''}">${formatTime(r.new_check_in)}</span></td>
                                        </tr>
                                        <tr><td>Check Out</td>
                                            <td><span class="${formatTime(r.old_check_out) !== formatTime(r.new_check_out) ? 'diff-old' : ''}">${formatTime(r.old_check_out)}</span></td>
                                            <td><span class="${formatTime(r.old_check_out) !== formatTime(r.new_check_out) ? 'diff-new' : ''}">${formatTime(r.new_check_out)}</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <h6 class="fw-bold text-muted mb-2"><i class='bx bx-message-detail me-1'></i>Alasan Perubahan</h6>
                            <p class="mb-0">${r.reason}</p>
                        </div>
                    </div>`;

            if (r.status !== 'pending') {
                body += `<div class="col-12">
                    <div class="border rounded p-3 ${r.status === 'approved' ? 'border-success' : 'border-danger'}">
                        <h6 class="fw-bold text-muted mb-2"><i class='bx bx-check-shield me-1'></i>Hasil Review</h6>
                        <p class="mb-1"><strong>Direview oleh:</strong> ${r.reviewer?.name ?? '-'}</p>
                        <p class="mb-1"><strong>Waktu Review:</strong> ${r.reviewed_at ? formatDate(r.reviewed_at) : '-'}</p>
                        ${r.review_notes ? `<p class="mb-0"><strong>Catatan:</strong> ${r.review_notes}</p>` : ''}
                    </div>
                </div>`;
            }

            body += '</div>';
            $('#detailBody').html(body);

            // Footer actions (manager & pending only)
            if (isManager && r.status === 'pending') {
                $('#detailFooter').html(`
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-danger btn-reject" data-id="${r.id}" data-bs-dismiss="modal">
                        <i class='bx bx-x-circle me-1'></i> Tolak
                    </button>
                    <button type="button" class="btn btn-success btn-approve-confirm" data-id="${r.id}">
                        <i class='bx bx-check-circle me-1'></i> Setujui
                    </button>`);
            } else {
                $('#detailFooter').html(`<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>`);
            }
        });
    });

    // ── Approve langsung dari baris tabel ──
    $(document).on('click', '.btn-approve', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Setujui Request?',
            text: 'Data absensi akan diubah sesuai permintaan.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Setujui!',
            cancelButtonText: 'Batal',
        }).then(result => {
            if (result.isConfirmed) doApprove(id);
        });
    });

    // ── Approve dari modal detail ──
    $(document).on('click', '.btn-approve-confirm', function() {
        const id = $(this).data('id');
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Memproses...');
        doApprove(id, true);
    });

    function doApprove(id, closeModal) {
        $.ajax({
            url: `/api/admin/attendance-edit-requests/${id}/approve`,
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
            data: JSON.stringify({}),
            success: function(res) {
                if (closeModal) bootstrap.Modal.getInstance(document.getElementById('detailModal'))?.hide();
                Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, showConfirmButton: false, timer: 2000 })
                    .then(() => { loadRequests(currentPage); loadStats(); });
            },
            error: function(xhr) {
                Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan', 'error');
                $('.btn-approve-confirm').prop('disabled', false).html('<i class=\'bx bx-check-circle me-1\'></i> Setujui');
            }
        });
    }

    // ── Buka modal tolak ──
    $(document).on('click', '.btn-reject', function() {
        const id = $(this).data('id');
        $('#reject_id').val(id);
        $('#reject_notes').val('').removeClass('is-invalid');
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
    });

    // ── Confirm tolak ──
    $('#btnConfirmReject').on('click', function() {
        const id    = $('#reject_id').val();
        const notes = $('#reject_notes').val().trim();
        if (!notes) {
            $('#reject_notes').addClass('is-invalid');
            $('#reject_notes_error').text('Catatan penolakan wajib diisi.');
            return;
        }
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menolak...');

        $.ajax({
            url: `/api/admin/attendance-edit-requests/${id}/reject`,
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
            data: JSON.stringify({ review_notes: notes }),
            success: function(res) {
                bootstrap.Modal.getInstance(document.getElementById('rejectModal'))?.hide();
                Swal.fire({ icon: 'info', title: 'Request Ditolak', text: res.message, showConfirmButton: false, timer: 2000 })
                    .then(() => { loadRequests(currentPage); loadStats(); });
            },
            error: function(xhr) {
                Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan', 'error');
            },
            complete: function() {
                $('#btnConfirmReject').prop('disabled', false).html('<i class=\'bx bx-x-circle me-1\'></i> Tolak');
            }
        });
    });

    // ── Filter & refresh ──
    $('#filter-status').on('change', function() { loadRequests(1); });
    $('#btnRefresh').on('click', function() { loadRequests(currentPage); loadStats(); });
    $(document).on('click', '.btn-page', function() { loadRequests(parseInt($(this).data('page'))); });

    // ── Init ──
    loadRequests(1);
    loadStats();
    </script>
    @endpush
@endsection
