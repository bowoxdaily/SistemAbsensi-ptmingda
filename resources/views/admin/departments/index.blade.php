@extends('layouts.app')
@section('title', 'Daftar Departemen')

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
@endpush

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Daftar Departemen</h4>
                <p class="text-muted mb-0 d-none d-md-block">Kelola data departemen perusahaan</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#departmentModal"
                    onclick="openCreateModal()">
                    <i class='bx bx-plus me-1'></i> <span class="d-none d-sm-inline">Tambah</span>
                </button>
            </div>
        </div>

        <!-- Department List Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Data Departemen</h5>
                <small class="text-muted" id="totalDepartments">Total: 0</small>
            </div>
            <div class="card-body p-0">
                <!-- Desktop View: Table -->
                <div class="d-none d-md-block">
                    <div class="table-responsive text-nowrap" style="overflow: visible;">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th style="width: 25%;">Nama Departemen</th>
                                    <th style="width: 40%;">Deskripsi</th>
                                    <th style="width: 120px;" class="text-center">Karyawan</th>
                                    <th style="width: 60px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0" id="departmentTableBody">
                                <tr id="loadingRow">
                                    <td colspan="5" class="text-center py-4">
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
                <div class="d-md-none" id="departmentCardList">
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
    <div class="modal fade" id="departmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="departmentForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Tambah Departemen</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="departmentId">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Departemen <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback" id="nameError"></div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            <div class="invalid-feedback" id="descriptionError"></div>
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
                    <h5 class="modal-title">Detail Departemen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th width="40%">Nama Departemen</th>
                            <td id="detailName">-</td>
                        </tr>
                        <tr>
                            <th>Deskripsi</th>
                            <td id="detailDescription">-</td>
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
        let departmentModal, detailModal;

        $(document).ready(function() {
            // Initialize modals
            departmentModal = new bootstrap.Modal(document.getElementById('departmentModal'));
            detailModal = new bootstrap.Modal(document.getElementById('detailModal'));

            // Load departments on page load
            loadDepartments();

            // Form submit
            $('#departmentForm').on('submit', function(e) {
                e.preventDefault();
                saveDepartment();
            });
        });

        // Load departments
        function loadDepartments(page = 1) {
            $.ajax({
                url: '/api/departments?page=' + page,
                method: 'GET',
                beforeSend: function() {
                    $('#loadingRow').show();
                    $('#loadingRowMobile').show();
                },
                success: function(response) {
                    renderDepartments(response.data);
                    currentPage = page;
                },
                error: function() {
                    toastr.error('Gagal memuat data departemen', 'Error');
                }
            });
        }

        // Render departments table and cards
        function renderDepartments(data) {
            const tbody = $('#departmentTableBody');
            const cardList = $('#departmentCardList');

            tbody.empty();
            cardList.empty();

            if (data.data.length === 0) {
                // Empty state for desktop
                tbody.append(`
                    <tr>
                        <td colspan="5" class="text-center py-4">
                            <div class="mb-3">
                                <i class='bx bx-folder-open' style="font-size: 48px; color: #ddd;"></i>
                            </div>
                            <p class="text-muted mb-2">Belum ada data departemen</p>
                            <button class="btn btn-sm btn-primary" onclick="openCreateModal()" data-bs-toggle="modal" data-bs-target="#departmentModal">
                                <i class='bx bx-plus me-1'></i> Tambah Departemen
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
                        <p class="text-muted mb-2">Belum ada data departemen</p>
                        <button class="btn btn-sm btn-primary" onclick="openCreateModal()" data-bs-toggle="modal" data-bs-target="#departmentModal">
                            <i class='bx bx-plus me-1'></i> Tambah Departemen
                        </button>
                    </div>
                `);

                $('#paginationContainer').hide();
            } else {
                data.data.forEach((dept, index) => {
                    const rowNumber = data.from + index;

                    // Desktop table row
                    tbody.append(`
                        <tr>
                            <td style="white-space: nowrap;">${rowNumber}</td>
                            <td style="white-space: nowrap;"><strong>${dept.name}</strong></td>
                            <td><span class="text-muted">${dept.description || '-'}</span></td>
                            <td class="text-center" style="white-space: nowrap;">
                                <span class="badge bg-label-primary">${dept.employees_count}</span>
                            </td>
                            <td class="text-center" style="white-space: nowrap; position: relative;">
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="true"
                                            data-bs-offset="0,8">
                                        <i class='bx bx-dots-vertical-rounded'></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" style="position: absolute; z-index: 1050;">
                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0);" onclick="showDetail(${dept.id})">
                                                <i class='bx bx-show me-2'></i> Detail
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0);" onclick="editDepartment(${dept.id})">
                                                <i class='bx bx-edit me-2'></i> Edit
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteDepartment(${dept.id})">
                                                <i class='bx bx-trash me-2'></i> Hapus
                                            </a>
                                        </li>
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
                                        <h6 class="mb-1 fw-bold">${dept.name}</h6>
                                        <p class="text-muted small mb-2">${dept.description || '-'}</p>
                                        <span class="badge bg-label-primary">${dept.employees_count} Karyawan</span>
                                    </div>
                                    <div class="dropdown" style="position: relative;">
                                        <button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow"
                                                data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="true"
                                                data-bs-offset="0,8">
                                            <i class='bx bx-dots-vertical-rounded'></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" style="position: absolute; z-index: 1050;">
                                            <li>
                                                <a class="dropdown-item" href="javascript:void(0);" onclick="showDetail(${dept.id})">
                                                    <i class='bx bx-show me-2'></i> Detail
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="javascript:void(0);" onclick="editDepartment(${dept.id})">
                                                    <i class='bx bx-edit me-2'></i> Edit
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteDepartment(${dept.id})">
                                                    <i class='bx bx-trash me-2'></i> Hapus
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);
                });

                // Update pagination
                $('#totalDepartments').text(`Total: ${data.total}`);
                $('#paginationInfo').text(
                    `${data.from} - ${data.to} dari ${data.total}`
                );

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
                    <a class="page-link" href="#" onclick="loadDepartments(${data.current_page - 1}); return false;">
                        <i class='bx bx-chevron-left'></i>
                    </a>
                </li>
            `;

            // Page numbers (simplified for mobile)
            const maxVisiblePages = window.innerWidth < 768 ? 3 : 5;
            const halfVisible = Math.floor(maxVisiblePages / 2);
            let startPage = Math.max(1, data.current_page - halfVisible);
            let endPage = Math.min(data.last_page, startPage + maxVisiblePages - 1);

            if (endPage - startPage < maxVisiblePages - 1) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }

            if (startPage > 1) {
                html +=
                    `<li class="page-item"><a class="page-link" href="#" onclick="loadDepartments(1); return false;">1</a></li>`;
                if (startPage > 2) {
                    html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                html += `
                    <li class="page-item ${i === data.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="loadDepartments(${i}); return false;">${i}</a>
                    </li>
                `;
            }

            if (endPage < data.last_page) {
                if (endPage < data.last_page - 1) {
                    html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                html +=
                    `<li class="page-item"><a class="page-link" href="#" onclick="loadDepartments(${data.last_page}); return false;">${data.last_page}</a></li>`;
            }

            // Next
            html += `
                <li class="page-item ${data.current_page === data.last_page ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadDepartments(${data.current_page + 1}); return false;">
                        <i class='bx bx-chevron-right'></i>
                    </a>
                </li>
            `;

            html += '</ul></nav>';
            container.html(html);
        }

        // Open create modal
        function openCreateModal() {
            $('#modalTitle').text('Tambah Departemen');
            $('#departmentId').val('');
            $('#departmentForm')[0].reset();
            $('.form-control').removeClass('is-invalid');
        }

        // Edit department
        function editDepartment(id) {
            $.ajax({
                url: `/api/departments/${id}`,
                method: 'GET',
                success: function(response) {
                    $('#modalTitle').text('Edit Departemen');
                    $('#departmentId').val(response.data.id);
                    $('#name').val(response.data.name);
                    $('#description').val(response.data.description);
                    $('.form-control').removeClass('is-invalid');
                    departmentModal.show();
                },
                error: function() {
                    toastr.error('Gagal memuat data departemen', 'Error');
                }
            });
        }

        // Save department
        function saveDepartment() {
            const id = $('#departmentId').val();
            const url = id ? `/api/departments/${id}` : '/api/departments';
            const method = id ? 'PUT' : 'POST';
            const data = {
                name: $('#name').val(),
                description: $('#description').val()
            };

            // Clear previous errors
            $('.form-control').removeClass('is-invalid');
            $('.invalid-feedback').text('');

            // Show loading
            $('#submitBtn').prop('disabled', true);
            $('#submitSpinner').removeClass('d-none');

            $.ajax({
                url: url,
                method: method,
                data: data,
                success: function(response) {
                    departmentModal.hide();
                    toastr.success(response.message, 'Berhasil');
                    loadDepartments(currentPage);
                    $('#departmentForm')[0].reset();
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
                url: `/api/departments/${id}`,
                method: 'GET',
                success: function(response) {
                    const dept = response.data;
                    $('#detailName').text(dept.name);
                    $('#detailDescription').text(dept.description || '-');
                    $('#detailEmployees').html(
                        `<span class="badge bg-label-primary">${dept.employees_count} Karyawan</span>`
                    );
                    detailModal.show();
                },
                error: function() {
                    toastr.error('Gagal memuat detail departemen', 'Error');
                }
            });
        }

        // Delete department
        function deleteDepartment(id) {
            Swal.fire({
                title: 'Hapus Departemen?',
                html: 'Apakah Anda yakin ingin menghapus departemen ini?<br><br><small class="text-danger">Peringatan: Departemen yang masih memiliki karyawan tidak dapat dihapus.</small>',
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
                        url: `/api/departments/${id}`,
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
                            loadDepartments(currentPage);
                        },
                        error: function(xhr) {
                            Swal.fire({
                                title: 'Gagal!',
                                text: xhr.responseJSON.message || 'Gagal menghapus departemen',
                                icon: 'error',
                                confirmButtonText: 'OK',
                                allowOutsideClick: false
                            });
                        }
                    });
                }
            });
        }
    </script>
@endpush
