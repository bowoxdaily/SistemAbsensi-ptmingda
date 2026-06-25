@extends('layouts.app')

@section('title', 'Status Request Edit Absensi')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">
                <span class="text-muted fw-light">Karyawan /</span> Status Request Edit Absensi
            </h4>
            <div>
                <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">
                    <i class='bx bx-arrow-back'></i> Kembali
                </a>
            </div>
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
                <h5 class="mb-0">Daftar Request Edit Absensi Saya</h5>
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm" id="filter-status" style="width:auto;">
                        <option value="">Semua Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Disetujui</option>
                        <option value="rejected">Ditolak</option>
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
                                <th>Tanggal Absensi</th>
                                <th>Perubahan</th>
                                <th>Alasan</th>
                                <th>Diajukan Oleh</th>
                                <th>Diajukan Pada</th>
                                <th>Status</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <tr>
                                <td colspan="8" class="text-center py-4">
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

    {{-- ───── Modal Detail (Read-Only) ───── --}}
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
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .card-header { background-color: #fff; border-bottom: 1px solid #d9dee3; }
        .diff-old { background: #fff3cd; padding: 2px 6px; border-radius: 4px; text-decoration: line-through; color: #856404; }
        .diff-new { background: #d1e7dd; padding: 2px 6px; border-radius: 4px; color: #0f5132; font-weight: 600; }
        .badge-info-box {
            background: #e7f1ff;
            border-left: 3px solid #0d6efd;
            padding: 12px;
            border-radius: 4px;
            margin-top: 10px;
        }
    </style>
    @endpush

    @push('scripts')
    <script>
    let currentPage = 1;
    const tableColspan = 8;

    function statusBadge(status) {
        const map = {
            pending:  '<span class="badge bg-warning text-dark"><i class="bx bx-time-five"></i> Pending</span>',
            approved: '<span class="badge bg-success"><i class="bx bx-check-circle"></i> Disetujui</span>',
            rejected: '<span class="badge bg-danger"><i class="bx bx-x-circle"></i> Ditolak</span>',
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

    function loadStats() {
        $.get('/api/employee/attendance-edit-requests/stats', function(response) {
            if (response.success) {
                $('#stat-pending').text(response.data.pending);
                $('#stat-approved').text(response.data.approved);
                $('#stat-rejected').text(response.data.rejected);
            }
        });
    }

    function loadData(page = 1, status = '') {
        const params = { page: page, per_page: 15 };
        if (status) params.status = status;

        $.ajax({
            url: '/api/employee/attendance-edit-requests/list',
            data: params,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (!response.success) return;

                const requests = response.data.data;
                const tbody = $('#tableBody');
                tbody.empty();

                if (requests.length === 0) {
                    tbody.html(`<tr><td colspan="${tableColspan}" class="text-center py-4 text-muted">Tidak ada data request edit absensi</td></tr>`);
                    $('#mobileCards').html('<div class="text-center py-4 text-muted"><i class="bx bx-inbox"></i><p>Tidak ada data request edit absensi</p></div>');
                    return;
                }

                let mobileHtml = '';
                requests.forEach((req, idx) => {
                    const rowNum = (response.data.current_page - 1) * 15 + idx + 1;
                    const oldTime = req.old_check_in ? req.old_check_in.substring(0, 5) : '-';
                    const newTime = req.new_check_in ? req.new_check_in.substring(0, 5) : '-';
                    const oldStatus = req.old_status ? attendanceStatusLabel(req.old_status) : '-';
                    const newStatus = req.new_status ? attendanceStatusLabel(req.new_status) : '-';
                    
                    const rowHtml = `<tr>
                        <td class="fw-semibold">${rowNum}</td>
                        <td><small>${req.new_attendance_date || '-'}</small></td>
                        <td>
                            <small class="d-block mb-1">
                                <span class="diff-old">${oldTime} ${oldStatus}</span> →
                                <span class="diff-new">${newTime} ${newStatus}</span>
                            </small>
                        </td>
                        <td><small>${req.reason || '-'}</small></td>
                        <td><small>${req.requester?.name || '-'}</small></td>
                        <td><small>${new Date(req.created_at).toLocaleDateString('id-ID')}</small></td>
                        <td>${statusBadge(req.status)}</td>
                        <td>
                            <small>
                                ${req.status === 'rejected' ? (req.review_notes ? `<span class="text-danger">${req.review_notes}</span>` : '-') : 
                                  req.status === 'approved' ? '<span class="text-success">Sudah disetujui</span>' : '-'}
                            </small>
                            <button class="btn btn-link btn-sm p-0 ms-2" onclick="showDetail(${req.id})">
                                <i class="bx bx-info-circle"></i>
                            </button>
                        </td>
                    </tr>`;
                    tbody.append(rowHtml);

                    // Mobile card
                    const mobileCard = `<div class="card-body border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-1">Request #${rowNum}</h6>
                                <small class="text-muted">${req.new_attendance_date || '-'}</small>
                            </div>
                            ${statusBadge(req.status)}
                        </div>
                        <div class="mb-2">
                            <small class="text-muted d-block mb-1">Perubahan:</small>
                            <small>
                                <span class="diff-old">${oldTime} ${oldStatus}</span> → 
                                <span class="diff-new">${newTime} ${newStatus}</span>
                            </small>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Alasan: ${req.reason || '-'}</small>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Diajukan: ${req.requester?.name || '-'} (${new Date(req.created_at).toLocaleDateString('id-ID')})</small>
                        </div>
                        ${req.status === 'rejected' ? `<div class="badge-info-box">
                            <small class="text-danger"><strong>Alasan Penolakan:</strong><br>${req.review_notes || 'Tidak ada catatan'}</small>
                        </div>` : ''}
                        <button class="btn btn-link btn-sm p-0 mt-2" onclick="showDetail(${req.id})">
                            <i class="bx bx-info-circle"></i> Lihat Detail
                        </button>
                    </div>`;
                    mobileHtml += mobileCard;
                });

                $('#mobileCards').html(mobileHtml);

                // Pagination
                renderPagination(response.data);
            }
        });
    }

    function renderPagination(pagination) {
        const paginationArea = $('#paginationArea');
        paginationArea.empty();

        const totalPages = pagination.last_page;
        const currentPage = pagination.current_page;
        const links = pagination.links;

        if (totalPages <= 1) return;

        let paginationHtml = '<nav aria-label="Page navigation"><ul class="pagination mb-0">';

        links.forEach(link => {
            const isActive = link.active ? 'active' : '';
            const isDisabled = link.url === null ? 'disabled' : '';
            const page = new URL(link.url || '#', window.location.origin).searchParams.get('page') || 1;
            const label = link.label.replace('&laquo;', '«').replace('&raquo;', '»');

            if (link.url === null) {
                paginationHtml += `<li class="page-item ${isDisabled}"><span class="page-link">${label}</span></li>`;
            } else {
                paginationHtml += `<li class="page-item ${isActive}"><a class="page-link" href="javascript:void(0)" onclick="loadData(${page}, $('#filter-status').val())">${label}</a></li>`;
            }
        });

        paginationHtml += '</ul></nav>';
        paginationArea.html(paginationHtml);
    }

    function showDetail(id) {
        const modal = new bootstrap.Modal(document.getElementById('detailModal'));
        const detailBody = $('#detailBody');
        detailBody.html(`<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>`);
        modal.show();

        $.get(`/api/employee/attendance-edit-requests/detail/${id}`, function(response) {
            if (!response.success) {
                detailBody.html('<div class="alert alert-danger">Gagal memuat detail</div>');
                return;
            }

            const data = response.data;
            const oldTime = data.old_check_in ? data.old_check_in.substring(0, 5) : '-';
            const newTime = data.new_check_in ? data.new_check_in.substring(0, 5) : '-';
            const oldStatus = data.old_status ? attendanceStatusLabel(data.old_status) : '-';
            const newStatus = data.new_status ? attendanceStatusLabel(data.new_status) : '-';

            let html = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block mb-1">Tanggal Absensi</small>
                        <strong>${data.new_attendance_date || '-'}</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block mb-1">Status</small>
                        ${statusBadge(data.status)}
                    </div>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Alasan Permintaan</small>
                    <p>${data.reason || '-'}</p>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block mb-1">Jam Masuk (Sebelum)</small>
                        <span class="diff-old">${oldTime} (${oldStatus})</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block mb-1">Jam Masuk (Sesudah)</small>
                        <span class="diff-new">${newTime} (${newStatus})</span>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block mb-1">Diajukan Oleh</small>
                        <strong>${data.requester?.name || '-'}</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block mb-1">Tanggal Pengajuan</small>
                        <strong>${new Date(data.created_at).toLocaleDateString('id-ID')} ${new Date(data.created_at).toLocaleTimeString('id-ID')}</strong>
                    </div>
                </div>
            `;

            if (data.status === 'approved') {
                html += `
                    <div class="alert alert-success" role="alert">
                        <i class="bx bx-check-circle"></i> <strong>Disetujui</strong>
                        <br><small>Oleh: ${data.reviewer?.name || '-'}</small>
                        <br><small>Pada: ${data.approved_at ? new Date(data.approved_at).toLocaleDateString('id-ID') : '-'}</small>
                    </div>
                `;
            } else if (data.status === 'rejected') {
                html += `
                    <div class="alert alert-danger" role="alert">
                        <i class="bx bx-x-circle"></i> <strong>Ditolak</strong>
                        <br><strong>Alasan:</strong> ${data.review_notes || 'Tidak ada catatan'}
                        <br><small>Oleh: ${data.reviewer?.name || '-'}</small>
                        <br><small>Pada: ${data.approved_at ? new Date(data.approved_at).toLocaleDateString('id-ID') : '-'}</small>
                    </div>
                `;
            }

            detailBody.html(html);
        }).fail(function() {
            detailBody.html('<div class="alert alert-danger">Gagal memuat detail</div>');
        });
    }

    // Event listeners
    $('#filter-status').on('change', function() {
        loadData(1, $(this).val());
    });

    $('#btnRefresh').on('click', function() {
        loadData(1, $('#filter-status').val());
    });

    // Initial load
    loadStats();
    loadData(1, $('#filter-status').val());
    </script>
    @endpush
@endsection
