@extends('layouts.app')

@section('title', 'Daftar Jabatan')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Daftar Jabatan</h4>
                <p class="text-muted mb-0 d-none d-md-block">Kelola data jabatan/posisi di perusahaan</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#positionModal"
                    onclick="openCreateModal()">
                    <i class='bx bx-plus me-1'></i> <span class="d-none d-sm-inline">Tambah</span>
                </button>
            </div>
        </div>

        <!-- Alert Messages -->
        <div id="alertContainer"></div>

        <!-- Position List Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Data Jabatan</h5>
                <small class="text-muted" id="totalPositions">Total: 0</small>
            </div>
            <div class="card-body p-0">
                <!-- Desktop View: Table -->
                <div class="d-none d-md-block">
                    <div class="table-responsive text-nowrap" style="overflow: visible;">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th style="width: 15%;">Kode</th>
                                    <th style="width: 25%;">Nama Jabatan</th>
                                    <th style="width: 40%;">Deskripsi</th>
                                    <th style="width: 10%;" class="text-center">Status</th>
                                    <th style="width: 60px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0" id="positionTableBody">
                                <tr id="loadingRow">
                                    <td colspan="6" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile View: Card List -->
                <div class="d-md-none" id="positionCardList">
                    <div class="text-center py-4" id="loadingRowMobile">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="card-footer" id="paginationContainer" style="display: none;">
                <div
                    class="d-flex justify-content-center justify-content-md-between align-items-center flex-column flex-md-row gap-2">
                    <div class="text-muted small d-none d-md-block" id="paginationInfo"></div>
                    <div id="paginationLinks"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="positionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="positionForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Tambah Jabatan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="positionId">

                        <div class="mb-3">
                            <label for="code" class="form-label">Kode Jabatan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="code" name="code" required
                                placeholder="Contoh: MGR, SPV, STAFF">
                            <div class="invalid-feedback" id="codeError"></div>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Jabatan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required
                                placeholder="Contoh: Manager, Supervisor">
                            <div class="invalid-feedback" id="nameError"></div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                placeholder="Deskripsi jabatan (opsional)"></textarea>
                            <div class="invalid-feedback" id="descriptionError"></div>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="">Pilih Status...</option>
                                <option value="active">Aktif</option>
                                <option value="inactive">Tidak Aktif</option>
                            </select>
                            <div class="invalid-feedback" id="statusError"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="submitSpinner"></span>
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Jabatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="35%">Kode</th>
                            <td id="detailCode">-</td>
                        </tr>
                        <tr>
                            <th>Nama Jabatan</th>
                            <td id="detailName">-</td>
                        </tr>
                        <tr>
                            <th>Deskripsi</th>
                            <td id="detailDescription">-</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td id="detailStatus">-</td>
                        </tr>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let currentPage = 1;
        let positionModal, detailModal;

        $(document).ready(function() {
            positionModal = new bootstrap.Modal(document.getElementById('positionModal'));
            detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
            loadPositions();
            $('#positionForm').on('submit', function(e) {
                e.preventDefault();
                savePosition();
            });
        });

        function loadPositions(page = 1) {
            $.ajax({
                url: '/api/positions?page=' + page + '&per_page=10',
                method: 'GET',
                beforeSend: function() {
                    $('#loadingRow').show();
                    $('#loadingRowMobile').show();
                },
                success: function(response) {
                    renderPositions(response.data);
                    currentPage = page;
                },
                error: function() {
                    showAlert('Gagal memuat data jabatan', 'danger');
                }
            });
        }

        function renderPositions(data) {
            const tbody = $('#positionTableBody');
            const cardList = $('#positionCardList');
            tbody.empty();
            cardList.empty();

            if (data.data.length === 0) {
                tbody.append(`
                    <tr><td colspan="6" class="text-center py-4">
                        <div class="mb-3"><i class='bx bx-briefcase' style="font-size: 48px; color: #ddd;"></i></div>
                        <p class="text-muted mb-2">Belum ada data jabatan</p>
                        <button class="btn btn-sm btn-primary" onclick="openCreateModal()" data-bs-toggle="modal" data-bs-target="#positionModal">
                            <i class='bx bx-plus me-1'></i> Tambah Jabatan
                        </button>
                    </td></tr>
                `);
                cardList.append(`
                    <div class="text-center py-4">
                        <div class="mb-3"><i class='bx bx-briefcase' style="font-size: 48px; color: #ddd;"></i></div>
                        <p class="text-muted mb-2">Belum ada data jabatan</p>
                        <button class="btn btn-sm btn-primary" onclick="openCreateModal()" data-bs-toggle="modal" data-bs-target="#positionModal">
                            <i class='bx bx-plus me-1'></i> Tambah Jabatan
                        </button>
                    </div>
                `);
                $('#paginationContainer').hide();
            } else {
                data.data.forEach((p, index) => {
                    const rowNumber = data.from + index;
                    const statusBadge = getStatusBadge(p.status);
                    const description = p.description ? (p.description.length > 80 ? p.description.substring(0,
                        80) + '...' : p.description) : '-';

                    tbody.append(`
                        <tr>
                            <td style="white-space: nowrap;">${rowNumber}</td>
                            <td style="white-space: nowrap;"><strong>${p.code}</strong></td>
                            <td><strong>${p.name}</strong></td>
                            <td><small class="text-muted">${description}</small></td>
                            <td class="text-center" style="white-space: nowrap;">${statusBadge}</td>
                            <td class="text-center" style="white-space: nowrap; position: relative;">
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class='bx bx-dots-vertical-rounded'></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="javascript:void(0);" onclick="showDetail(${p.id})">
                                            <i class='bx bx-show me-2'></i> Detail</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0);" onclick="editPosition(${p.id})">
                                            <i class='bx bx-edit me-2'></i> Edit</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deletePosition(${p.id})">
                                            <i class='bx bx-trash me-2'></i> Hapus</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    `);

                    cardList.append(`
                        <div class="card mb-2 mx-3 border">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-bold">${p.name}</h6>
                                        <p class="text-muted small mb-1">Kode: ${p.code}</p>
                                        <p class="text-muted small mb-2">${description}</p>
                                        ${statusBadge}
                                    </div>
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class='bx bx-dots-vertical-rounded'></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="showDetail(${p.id})">
                                                <i class='bx bx-show me-2'></i> Detail</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="editPosition(${p.id})">
                                                <i class='bx bx-edit me-2'></i> Edit</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deletePosition(${p.id})">
                                                <i class='bx bx-trash me-2'></i> Hapus</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);
                });

                $('#totalPositions').text(`Total: ${data.total}`);
                $('#paginationInfo').text(`${data.from} - ${data.to} dari ${data.total}`);

                if (data.last_page > 1) {
                    renderPagination(data);
                    $('#paginationContainer').show();
                } else {
                    $('#paginationContainer').hide();
                }
            }
        }

        function getStatusBadge(status) {
            const badges = {
                'active': '<span class="badge bg-label-success">Aktif</span>',
                'inactive': '<span class="badge bg-label-warning">Tidak Aktif</span>'
            };
            return badges[status] || status;
        }

        function renderPagination(data) {
            const container = $('#paginationLinks');
            container.empty();
            let html = '<nav><ul class="pagination pagination-sm mb-0">';
            html += `<li class="page-item ${data.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="loadPositions(${data.current_page - 1}); return false;">
                    <i class='bx bx-chevron-left'></i></a></li>`;

            const maxVisiblePages = window.innerWidth < 768 ? 3 : 5;
            const halfVisible = Math.floor(maxVisiblePages / 2);
            let startPage = Math.max(1, data.current_page - halfVisible);
            let endPage = Math.min(data.last_page, startPage + maxVisiblePages - 1);

            if (endPage - startPage < maxVisiblePages - 1) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }

            if (startPage > 1) {
                html +=
                    `<li class="page-item"><a class="page-link" href="#" onclick="loadPositions(1); return false;">1</a></li>`;
                if (startPage > 2) html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }

            for (let i = startPage; i <= endPage; i++) {
                html += `<li class="page-item ${i === data.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadPositions(${i}); return false;">${i}</a></li>`;
            }

            if (endPage < data.last_page) {
                if (endPage < data.last_page - 1) html +=
                    '<li class="page-item disabled"><span class="page-link">...</span></li>';
                html +=
                    `<li class="page-item"><a class="page-link" href="#" onclick="loadPositions(${data.last_page}); return false;">${data.last_page}</a></li>`;
            }

            html += `<li class="page-item ${data.current_page === data.last_page ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="loadPositions(${data.current_page + 1}); return false;">
                    <i class='bx bx-chevron-right'></i></a></li>`;
            html += '</ul></nav>';
            container.html(html);
        }

        function openCreateModal() {
            $('#modalTitle').text('Tambah Jabatan');
            $('#positionId').val('');
            $('#positionForm')[0].reset();
            $('.form-control, .form-select').removeClass('is-invalid');
        }

        function editPosition(id) {
            $.ajax({
                url: `/api/positions/${id}`,
                method: 'GET',
                success: function(response) {
                    const p = response.data;
                    $('#modalTitle').text('Edit Jabatan');
                    $('#positionId').val(p.id);
                    $('#code').val(p.code);
                    $('#name').val(p.name);
                    $('#description').val(p.description);
                    $('#status').val(p.status);
                    $('.form-control, .form-select').removeClass('is-invalid');
                    positionModal.show();
                }
            });
        }

        function savePosition() {
            const id = $('#positionId').val();
            const url = id ? `/api/positions/${id}` : '/api/positions';
            const method = id ? 'PUT' : 'POST';

            const data = {
                code: $('#code').val(),
                name: $('#name').val(),
                description: $('#description').val(),
                status: $('#status').val()
            };

            $('.form-control, .form-select').removeClass('is-invalid');
            $('.invalid-feedback').text('');
            $('#submitBtn').prop('disabled', true);
            $('#submitSpinner').removeClass('d-none');

            $.ajax({
                url: url,
                method: method,
                data: data,
                success: function(response) {
                    positionModal.hide();
                    showAlert(response.message, 'success');
                    loadPositions(currentPage);
                    $('#positionForm')[0].reset();
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        for (let field in errors) {
                            $(`#${field}`).addClass('is-invalid');
                            $(`#${field}Error`).text(errors[field][0]);
                        }
                    } else {
                        showAlert(xhr.responseJSON.message || 'Terjadi kesalahan', 'danger');
                    }
                },
                complete: function() {
                    $('#submitBtn').prop('disabled', false);
                    $('#submitSpinner').addClass('d-none');
                }
            });
        }

        function showDetail(id) {
            $.ajax({
                url: `/api/positions/${id}`,
                method: 'GET',
                success: function(response) {
                    const p = response.data;
                    $('#detailCode').text(p.code);
                    $('#detailName').text(p.name);
                    $('#detailDescription').text(p.description || '-');
                    $('#detailStatus').html(getStatusBadge(p.status));
                    detailModal.show();
                }
            });
        }

        function deletePosition(id) {
            Swal.fire({
                title: 'Hapus Jabatan?',
                html: 'Apakah Anda yakin ingin menghapus jabatan ini?<br><br><small class="text-danger">Peringatan: Jabatan yang masih digunakan oleh karyawan tidak dapat dihapus.</small>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '<i class="bx bx-trash"></i> Ya, Hapus!',
                cancelButtonText: '<i class="bx bx-x"></i> Batal',
                buttonsStyling: true,
                reverseButtons: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                backdrop: true,
                focusConfirm: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/api/positions/${id}`,
                        method: 'DELETE',
                        success: function(response) {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false,
                                allowOutsideClick: false
                            });
                            loadPositions(currentPage);
                        },
                        error: function(xhr) {
                            Swal.fire({
                                title: 'Gagal!',
                                text: xhr.responseJSON.message || 'Gagal menghapus jabatan',
                                icon: 'error',
                                confirmButtonText: 'OK',
                                allowOutsideClick: false
                            });
                        }
                    });
                }
            });
        }

        function showAlert(message, type) {
            const icon = type === 'success' ? 'bx-check-circle' : 'bx-error-circle';
            const alert = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    <i class='bx ${icon} me-2'></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            $('#alertContainer').html(alert);
            setTimeout(function() {
                $('.alert').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    </script>
@endpush
