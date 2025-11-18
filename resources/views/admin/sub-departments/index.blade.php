@extends('layouts.app')
@section('title', 'Daftar Sub Departemen')

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
@endpush

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Daftar Sub Departemen</h4>
                <p class="text-muted mb-0 d-none d-md-block">Kelola data sub departemen / bagian perusahaan</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                    data-bs-target="#subDepartmentModal" onclick="openCreateModal()">
                    <i class='bx bx-plus me-1'></i> <span class="d-none d-sm-inline">Tambah</span>
                </button>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="filterDepartment" class="form-label small mb-1">Departemen</label>
                        <select class="form-select form-select-sm" id="filterDepartment">
                            <option value="">Semua Departemen</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="filterStatus" class="form-label small mb-1">Status</label>
                        <select class="form-select form-select-sm" id="filterStatus">
                            <option value="">Semua Status</option>
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-primary flex-grow-1" onclick="applyFilter()">
                                <i class='bx bx-filter-alt me-1'></i> Filter
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetFilter()"
                                title="Reset Filter">
                                <i class='bx bx-reset'></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sub Department List Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Data Sub Departemen</h5>
                <small class="text-muted" id="totalSubDepartments">Total: 0</small>
            </div>
            <div class="card-body p-0">
                <!-- Desktop View: Table -->
                <div class="d-none d-md-block">
                    <div class="table-responsive text-nowrap" style="overflow: visible;">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th style="width: 20%;">Departemen</th>
                                    <th style="width: 20%;">Nama Sub Departemen</th>
                                    <th style="width: 30%;">Deskripsi</th>
                                    <th style="width: 10%;" class="text-center">Karyawan</th>
                                    <th style="width: 10%;" class="text-center">Status</th>
                                    <th style="width: 60px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0" id="subDepartmentTableBody">
                                <tr id="loadingRow">
                                    <td colspan="7" class="text-center py-4">
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
                <div class="d-md-none" id="subDepartmentCardList">
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
    <div class="modal fade" id="subDepartmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="subDepartmentForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Tambah Sub Departemen</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="subDepartmentId">

                        <div class="mb-3">
                            <label for="department_id" class="form-label">Departemen <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="department_id" name="department_id">
                                <option value="">Pilih Departemen...</option>
                            </select>
                            <div class="invalid-feedback" id="department_idError"></div>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Sub Departemen <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name"
                                placeholder="Contoh: Marketing Digital">
                            <div class="invalid-feedback" id="nameError"></div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            <div class="invalid-feedback" id="descriptionError"></div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    Aktif
                                </label>
                            </div>
                            <small class="text-muted">Sub departemen yang tidak aktif tidak akan muncul di pilihan
                                dropdown</small>
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
                    <h5 class="modal-title">Detail Sub Departemen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th width="40%">Departemen</th>
                            <td id="detailDepartment">-</td>
                        </tr>
                        <tr>
                            <th>Nama Sub Departemen</th>
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
                        <tr>
                            <th>Jumlah Karyawan</th>
                            <td id="detailEmployees">-</td>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        // Toastr configuration
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };

        let currentPage = 1;
        let subDepartmentModal, detailModal;
        let departments = [];
        let currentFilters = {
            department_id: '',
            is_active: ''
        };

        $(document).ready(function() {
            // Initialize modals
            subDepartmentModal = new bootstrap.Modal(document.getElementById('subDepartmentModal'));
            detailModal = new bootstrap.Modal(document.getElementById('detailModal'));

            // Load departments for filter and form
            loadDepartments();

            // Load sub departments
            loadSubDepartments();

            // Form submit
            $('#subDepartmentForm').on('submit', function(e) {
                e.preventDefault();
                saveSubDepartment();
            });
        });

        // Load departments for dropdown
        function loadDepartments() {
            $.ajax({
                url: '/api/departments',
                method: 'GET',
                success: function(response) {
                    departments = response.data.data;
                    populateDepartmentDropdowns();
                }
            });
        }

        function populateDepartmentDropdowns() {
            // For filter
            const filterSelect = $('#filterDepartment');
            filterSelect.find('option:not(:first)').remove();

            // For form
            const formSelect = $('#department_id');
            formSelect.find('option:not(:first)').remove();

            departments.forEach(dept => {
                filterSelect.append(`<option value="${dept.id}">${dept.name}</option>`);
                formSelect.append(`<option value="${dept.id}">${dept.name}</option>`);
            });
        }

        function applyFilter() {
            currentFilters.department_id = $('#filterDepartment').val();
            currentFilters.is_active = $('#filterStatus').val();
            loadSubDepartments(1);
        }

        function resetFilter() {
            currentFilters = {
                department_id: '',
                is_active: ''
            };
            $('#filterDepartment').val('');
            $('#filterStatus').val('');
            loadSubDepartments(1);
        }

        // Load sub departments
        function loadSubDepartments(page = 1) {
            let url = '/api/sub-departments?page=' + page;

            if (currentFilters.department_id) {
                url += '&department_id=' + currentFilters.department_id;
            }
            if (currentFilters.is_active !== '') {
                url += '&is_active=' + currentFilters.is_active;
            }

            $.ajax({
                url: url,
                method: 'GET',
                beforeSend: function() {
                    $('#loadingRow').show();
                    $('#loadingRowMobile').show();
                },
                success: function(response) {
                    renderSubDepartments(response.data);
                    currentPage = page;
                },
                error: function() {
                    toastr.error('Gagal memuat data sub departemen', 'Error');
                }
            });
        }

        // Render sub departments table and cards
        function renderSubDepartments(data) {
            const tbody = $('#subDepartmentTableBody');
            const cardList = $('#subDepartmentCardList');

            tbody.empty();
            cardList.empty();

            if (data.data.length === 0) {
                // Empty state for desktop
                tbody.append(`
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <div class="mb-3">
                                <i class='bx bx-folder-open' style="font-size: 48px; color: #ddd;"></i>
                            </div>
                            <p class="text-muted mb-2">Belum ada data sub departemen</p>
                            <button class="btn btn-sm btn-primary" onclick="openCreateModal()" data-bs-toggle="modal" data-bs-target="#subDepartmentModal">
                                <i class='bx bx-plus me-1'></i> Tambah Sub Departemen
                            </button>
                        </td>
                    </tr>
                `);

                // Empty state for mobile
                cardList.append(`
                    <div class="text-center py-4">
                        <div class="mb-3">
                            <i class='bx bx-folder-open' style="font-size: 48px; color: #ddd;"></i>
                        </div>
                        <p class="text-muted mb-2">Belum ada data sub departemen</p>
                        <button class="btn btn-sm btn-primary" onclick="openCreateModal()" data-bs-toggle="modal" data-bs-target="#subDepartmentModal">
                            <i class='bx bx-plus me-1'></i> Tambah Sub Departemen
                        </button>
                    </div>
                `);

                $('#paginationContainer').hide();
            } else {
                data.data.forEach((subDept, index) => {
                    const rowNumber = data.from + index;
                    const statusBadge = subDept.is_active ?
                        '<span class="badge bg-label-success">Aktif</span>' :
                        '<span class="badge bg-label-secondary">Tidak Aktif</span>';

                    // Desktop table row
                    tbody.append(`
                        <tr>
                            <td style="white-space: nowrap;">${rowNumber}</td>
                            <td style="white-space: nowrap;"><span class="badge bg-label-primary">${subDept.department.name}</span></td>
                            <td style="white-space: nowrap;"><strong>${subDept.name}</strong></td>
                            <td><span class="text-muted">${subDept.description || '-'}</span></td>
                            <td class="text-center" style="white-space: nowrap;">
                                <span class="badge bg-label-info">${subDept.employees_count}</span>
                            </td>
                            <td class="text-center" style="white-space: nowrap;">${statusBadge}</td>
                            <td class="text-center" style="white-space: nowrap; position: relative;">
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class='bx bx-dots-vertical-rounded'></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="javascript:void(0);" onclick="showDetail(${subDept.id})">
                                            <i class='bx bx-show me-2'></i> Detail</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0);" onclick="editSubDepartment(${subDept.id})">
                                            <i class='bx bx-edit me-2'></i> Edit</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteSubDepartment(${subDept.id})">
                                            <i class='bx bx-trash me-2'></i> Hapus</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    `);

                    // Mobile card
                    cardList.append(`
                        <div class="card mb-2 mx-3 border">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <span class="badge bg-label-primary mb-2">${subDept.department.name}</span>
                                        <h6 class="mb-1 fw-bold">${subDept.name}</h6>
                                        <p class="text-muted small mb-2">${subDept.description || '-'}</p>
                                        <div class="d-flex gap-2">
                                            <span class="badge bg-label-info">${subDept.employees_count} Karyawan</span>
                                            ${statusBadge}
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class='bx bx-dots-vertical-rounded'></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="showDetail(${subDept.id})">
                                                <i class='bx bx-show me-2'></i> Detail</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="editSubDepartment(${subDept.id})">
                                                <i class='bx bx-edit me-2'></i> Edit</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteSubDepartment(${subDept.id})">
                                                <i class='bx bx-trash me-2'></i> Hapus</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);
                });

                // Update pagination
                $('#totalSubDepartments').text(`Total: ${data.total}`);
                $('#paginationInfo').text(`${data.from} - ${data.to} dari ${data.total}`);

                if (data.last_page > 1) {
                    renderPagination(data);
                    $('#paginationContainer').show();
                } else {
                    $('#paginationContainer').hide();
                }
            }
        }

        // Render pagination
        function renderPagination(data) {
            const container = $('#paginationLinks');
            container.empty();

            let html = '<nav><ul class="pagination pagination-sm mb-0">';

            // Previous
            html += `
                <li class="page-item ${data.current_page === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadSubDepartments(${data.current_page - 1}); return false;">
                        <i class='bx bx-chevron-left'></i>
                    </a>
                </li>
            `;

            // Page numbers
            const maxVisiblePages = window.innerWidth < 768 ? 3 : 5;
            const halfVisible = Math.floor(maxVisiblePages / 2);
            let startPage = Math.max(1, data.current_page - halfVisible);
            let endPage = Math.min(data.last_page, startPage + maxVisiblePages - 1);

            if (endPage - startPage < maxVisiblePages - 1) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }

            if (startPage > 1) {
                html +=
                    `<li class="page-item"><a class="page-link" href="#" onclick="loadSubDepartments(1); return false;">1</a></li>`;
                if (startPage > 2) {
                    html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                html += `
                    <li class="page-item ${i === data.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="loadSubDepartments(${i}); return false;">${i}</a>
                    </li>
                `;
            }

            if (endPage < data.last_page) {
                if (endPage < data.last_page - 1) {
                    html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                html +=
                    `<li class="page-item"><a class="page-link" href="#" onclick="loadSubDepartments(${data.last_page}); return false;">${data.last_page}</a></li>`;
            }

            // Next
            html += `
                <li class="page-item ${data.current_page === data.last_page ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadSubDepartments(${data.current_page + 1}); return false;">
                        <i class='bx bx-chevron-right'></i>
                    </a>
                </li>
            `;

            html += '</ul></nav>';
            container.html(html);
        }

        // Open create modal
        function openCreateModal() {
            $('#modalTitle').text('Tambah Sub Departemen');
            $('#subDepartmentId').val('');
            $('#subDepartmentForm')[0].reset();
            $('#is_active').prop('checked', true);
            $('.form-control, .form-select').removeClass('is-invalid');
        }

        // Edit sub department
        function editSubDepartment(id) {
            $.ajax({
                url: `/api/sub-departments/${id}`,
                method: 'GET',
                success: function(response) {
                    const sd = response.data;
                    $('#modalTitle').text('Edit Sub Departemen');
                    $('#subDepartmentId').val(sd.id);
                    $('#department_id').val(sd.department_id);
                    $('#name').val(sd.name);
                    $('#description').val(sd.description);
                    $('#is_active').prop('checked', sd.is_active);
                    $('.form-control, .form-select').removeClass('is-invalid');
                    subDepartmentModal.show();
                },
                error: function() {
                    toastr.error('Gagal memuat data sub departemen', 'Error');
                }
            });
        }

        // Save sub department
        function saveSubDepartment() {
            const id = $('#subDepartmentId').val();
            const url = id ? `/api/sub-departments/${id}` : '/api/sub-departments';
            const method = id ? 'PUT' : 'POST';
            const data = {
                department_id: $('#department_id').val(),
                name: $('#name').val(),
                description: $('#description').val(),
                is_active: $('#is_active').is(':checked') ? 1 : 0
            };

            // Clear previous errors
            $('.form-control, .form-select').removeClass('is-invalid');
            $('.invalid-feedback').text('');

            // Show loading
            $('#submitBtn').prop('disabled', true);
            $('#submitSpinner').removeClass('d-none');

            $.ajax({
                url: url,
                method: method,
                data: data,
                success: function(response) {
                    subDepartmentModal.hide();
                    toastr.success(response.message, 'Berhasil');
                    loadSubDepartments(currentPage);
                    $('#subDepartmentForm')[0].reset();
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        for (let field in errors) {
                            $(`#${field}`).addClass('is-invalid');
                            $(`#${field}Error`).text(errors[field][0]);
                        }
                        toastr.warning('Periksa kembali form Anda', 'Validasi Gagal');
                    } else {
                        toastr.error(xhr.responseJSON.message || 'Terjadi kesalahan', 'Error');
                    }
                },
                complete: function() {
                    $('#submitBtn').prop('disabled', false);
                    $('#submitSpinner').addClass('d-none');
                }
            });
        }

        // Show detail
        function showDetail(id) {
            $.ajax({
                url: `/api/sub-departments/${id}`,
                method: 'GET',
                success: function(response) {
                    const sd = response.data;
                    $('#detailDepartment').text(sd.department.name);
                    $('#detailName').text(sd.name);
                    $('#detailDescription').text(sd.description || '-');
                    $('#detailStatus').html(sd.is_active ?
                        '<span class="badge bg-label-success">Aktif</span>' :
                        '<span class="badge bg-label-secondary">Tidak Aktif</span>');
                    $('#detailEmployees').html(
                        `<span class="badge bg-label-primary">${sd.employees_count} Karyawan</span>`);
                    detailModal.show();
                },
                error: function() {
                    toastr.error('Gagal memuat detail sub departemen', 'Error');
                }
            });
        }

        // Delete sub department
        function deleteSubDepartment(id) {
            Swal.fire({
                title: 'Hapus Sub Departemen?',
                html: 'Apakah Anda yakin ingin menghapus sub departemen ini?<br><br><small class="text-danger">Peringatan: Sub departemen yang masih memiliki karyawan tidak dapat dihapus.</small>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '<i class="bx bx-trash"></i> Ya, Hapus!',
                cancelButtonText: '<i class="bx bx-x"></i> Batal',
                buttonsStyling: true,
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/api/sub-departments/${id}`,
                        method: 'DELETE',
                        success: function(response) {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            loadSubDepartments(currentPage);
                        },
                        error: function(xhr) {
                            Swal.fire({
                                title: 'Gagal!',
                                text: xhr.responseJSON.message ||
                                    'Gagal menghapus sub departemen',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                }
            });
        }
    </script>
@endpush
