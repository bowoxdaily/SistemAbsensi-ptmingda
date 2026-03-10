@extends('layouts.app')

@section('title', 'Manajemen Surat Peringatan')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">SDM & Disiplin /</span> Surat Peringatan
        </h4>
        @if(in_array(auth()->user()->role, ['admin', 'manager', 'viewer']))
        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class='bx bx-plus'></i> Buat SP Baru
        </button>
        @endif
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4" id="statsCards">
        <div class="col-lg-3 col-md-6 col-sm-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text">Total SP Aktif</p>
                            <h4 class="mb-0 text-danger" id="totalAktif">0</h4>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-danger rounded p-2">
                                <i class='bx bx-error-circle bx-sm'></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text">SP1 Aktif</p>
                            <h4 class="mb-0 text-warning" id="sp1Aktif">0</h4>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-warning rounded p-2">
                                <i class='bx bx-error bx-sm'></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text">SP2 Aktif</p>
                            <h4 class="mb-0" style="color: #ff9800;" id="sp2Aktif">0</h4>
                        </div>
                        <div class="card-icon">
                            <span class="badge rounded p-2" style="background-color: rgba(255, 152, 0, 0.1);">
                                <i class='bx bx-error-alt bx-sm' style="color: #ff9800;"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text">SP3 Aktif</p>
                            <h4 class="mb-0 text-danger" id="sp3Aktif">0</h4>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-danger rounded p-2">
                                <i class='bx bxs-error bx-sm'></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Filter SP</h5>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                    <i class='bx bx-filter'></i> Filter
                </button>
            </div>
        </div>
        <div class="collapse show" id="filterCollapse">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Karyawan</label>
                        <select class="form-select select2" id="filterEmployee">
                            <option value="">Semua Karyawan</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jenis SP</label>
                        <select class="form-select" id="filterSpType">
                            <option value="">Semua Jenis</option>
                            <option value="SP1">SP 1</option>
                            <option value="SP2">SP 2</option>
                            <option value="SP3">SP 3</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="filterStatus">
                            <option value="">Semua Status</option>
                            <option value="aktif">Aktif</option>
                            <option value="selesai">Selesai</option>
                            <option value="dibatalkan">Dibatalkan</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Cari</label>
                        <input type="text" class="form-control" id="filterSearch" placeholder="Nomor SP / Pelanggaran">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" class="form-control" id="filterStartDate">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" class="form-control" id="filterEndDate">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="button" class="btn btn-primary me-2" onclick="loadWarningLetters()">
                            <i class='bx bx-search'></i> Cari
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="resetFilters()">
                            <i class='bx bx-reset'></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5 class="mb-0">Daftar Surat Peringatan</h5>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table table-hover" id="spTable">
                <thead>
                    <tr>
                        <th>Nomor SP</th>
                        <th>Karyawan</th>
                        <th>Jenis SP</th>
                        <th>Pelanggaran</th>
                        <th>Tanggal Terbit</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="spTableBody">
                    <!-- Data will be loaded via AJAX -->
                </tbody>
            </table>
        </div>
        <div class="card-body">
            <div id="pagination" class="d-flex justify-content-between align-items-center">
                <div id="paginationInfo"></div>
                <nav>
                    <ul class="pagination mb-0" id="paginationLinks"></ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Create SP Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buat Surat Peringatan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Karyawan</label>
                            <select class="form-select select2-create-employee" id="createEmployeeSelect" name="employee_id" required>
                                <option value="">Pilih Karyawan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Jenis SP</label>
                            <select class="form-select" name="sp_type" id="createSpType" required>
                                <option value="">Pilih Jenis SP</option>
                                <option value="SP1">SP 1 - Peringatan Pertama</option>
                                <option value="SP2">SP 2 - Peringatan Kedua</option>
                                <option value="SP3">SP 3 - Peringatan Terakhir</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label required">Nomor Surat SP</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="sp_number" id="createSpNumber" placeholder="Ketik manual atau klik generate" required>
                                <button class="btn btn-outline-primary" type="button" onclick="generateSpNumber()">
                                    <i class='bx bx-refresh'></i> Generate Otomatis
                                </button>
                            </div>
                            <small class="text-muted">Ketik manual atau gunakan tombol generate untuk membuat nomor otomatis</small>
                        </div>
                        <div class="col-12" id="spWarningContainer" style="display: none;">
                            <div class="alert alert-warning py-2 px-3 mb-0">
                                <small id="spWarningMessage"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Tanggal Terbit</label>
                            <input type="date" class="form-control" name="issue_date" id="createIssueDate" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Tanggal Berlaku</label>
                            <input type="date" class="form-control" name="effective_date" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label required">Pelanggaran</label>
                            <textarea class="form-control" name="violation" rows="3" maxlength="1000" required></textarea>
                            <small class="text-muted">Maksimal 1000 karakter</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi Tambahan</label>
                            <textarea class="form-control" name="description" rows="3" maxlength="2000"></textarea>
                            <small class="text-muted">Maksimal 2000 karakter (opsional)</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="date" class="form-control" name="completion_date">
                            <small class="text-muted">Opsional - tanggal SP berakhir</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Dokumen SP (PDF/Gambar)</label>
                            <input type="file" class="form-control" name="document" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">
                                Optional - Max 5MB (PDF, JPG, PNG). <br>Jika tidak di-upload sekarang, SP akan berstatus <strong>draft</strong>.
                            </small>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="send_notification" value="1" id="sendNotification" checked>
                                <label class="form-check-label" for="sendNotification">
                                    Kirim notifikasi WhatsApp ke karyawan
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class='bx bx-save'></i> Simpan SP
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Surat Peringatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <!-- Content loaded via AJAX -->
            </div>

<div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Surat Peringatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editForm">
                @csrf
                <input type="hidden" id="editSpId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="editStatus">
                            <option value="aktif">Aktif</option>
                            <option value="selesai">Selesai</option>
                            <option value="dibatalkan">Dibatalkan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" class="form-control" id="editCompletionDate">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="editDescription" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Batalkan Surat Peringatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="cancelForm">
                @csrf
                <input type="hidden" id="cancelSpId">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class='bx bx-info-circle'></i> SP akan dibatalkan dan status berubah menjadi "Dibatalkan"
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Alasan Pembatalan</label>
                        <textarea class="form-control" id="cancellationReason" rows="3" maxlength="500" required></textarea>
                        <small class="text-muted">Maksimal 500 karakter</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-danger">Batalkan SP</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocumentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Dokumen & Aktivasi SP</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="uploadDocumentForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="uploadSpId">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class='bx bx-info-circle'></i> Upload dokumen SP yang sudah ditandatangani. SP akan otomatis diaktifkan setelah upload.
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Dokumen SP (PDF/Gambar)</label>
                        <input type="file" class="form-control" id="uploadDocument" name="document" accept=".pdf,.jpg,.jpeg,.png" required>
                        <small class="text-muted">Max 5MB (PDF, JPG, PNG)</small>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="send_notification" value="1" id="uploadSendNotification" checked>
                            <label class="form-check-label" for="uploadSendNotification">
                                Kirim notifikasi WhatsApp ke karyawan
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-upload'></i> Upload & Aktivasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let currentPage = 1;

// Setup AJAX for CSRF token
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

$(document).ready(function() {
    // Initialize Select2 for employees
    loadEmployees();

    // Load initial data
    loadStatistics();
    loadWarningLetters();

    // Bind modal shown event for select2 initialization
    $('#createModal').on('shown.bs.modal', function() {
        initCreateModalSelect2();
    });

    // Reset SP options when modal is hidden
    $('#createModal').on('hidden.bs.modal', function() {
        $('#createSpType option').prop('disabled', false);
        $('#spWarningContainer').hide();
    });

    // Check employee's active SPs when employee is selected
    $('#createEmployeeSelect').on('change', function() {
        checkEmployeeActiveSP();
    });

    // Create form submit
    $('#createForm').on('submit', function(e) {
        e.preventDefault();

        let formData = new FormData(this);

        $.ajax({
            url: '/api/admin/warning-letters',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#createModal').modal('hide');
                $('#createForm')[0].reset();
                // Reset SP type options
                $('#createSpType option').prop('disabled', false);
                $('#spWarningContainer').hide();
                toastr.success('Surat peringatan berhasil dibuat');
                loadStatistics();
                loadWarningLetters();
            },
            error: function(xhr) {
                let message = 'Gagal membuat surat peringatan';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }

                // Show error alert if it's a duplicate SP error
                if (xhr.status === 422 && message.includes('sudah memiliki')) {
                    Swal.fire({
                        icon: 'error',
                        title: 'SP Duplikat',
                        text: message,
                        confirmButtonColor: '#d33'
                    });
                } else {
                    toastr.error(message);
                }
            }
        });
    });

    // Edit form submit
    $('#editForm').on('submit', function(e) {
        e.preventDefault();

        let id = $('#editSpId').val();
        let data = {
            status: $('#editStatus').val(),
            completion_date: $('#editCompletionDate').val(),
            description: $('#editDescription').val()
        };

        $.ajax({
            url: `/api/admin/warning-letters/${id}`,
            type: 'PUT',
            data: data,
            success: function(response) {
                $('#editModal').modal('hide');
                toastr.success('SP berhasil diupdate');
                loadStatistics();
                loadWarningLetters();
            },
            error: function(xhr) {
                toastr.error('Gagal mengupdate SP');
            }
        });
    });

    // Cancel form submit
    $('#cancelForm').on('submit', function(e) {
        e.preventDefault();

        let id = $('#cancelSpId').val();
        let data = {
            cancellation_reason: $('#cancellationReason').val()
        };

        $.ajax({
            url: `/api/admin/warning-letters/${id}/cancel`,
            type: 'POST',
            data: data,
            success: function(response) {
                $('#cancelModal').modal('hide');
                $('#cancelForm')[0].reset();
                toastr.success('SP berhasil dibatalkan');
                loadStatistics();
                loadWarningLetters();
            },
            error: function(xhr) {
                toastr.error('Gagal membatalkan SP');
            }
        });
    });

    // Upload document form submit
    $('#uploadDocumentForm').on('submit', function(e) {
        e.preventDefault();

        let id = $('#uploadSpId').val();
        let formData = new FormData(this);

        $.ajax({
            url: `/api/admin/warning-letters/${id}/upload-document`,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#uploadDocumentModal').modal('hide');
                $('#uploadDocumentForm')[0].reset();
                toastr.success(response.message || 'Dokumen berhasil di-upload dan SP diaktifkan');
                loadStatistics();
                loadWarningLetters();
            },
            error: function(xhr) {
                let message = 'Gagal upload dokumen';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                toastr.error(message);
            }
        });
    });
});

// Load employees for select2
function loadEmployees() {
    $.ajax({
        url: '/api/karyawan?per_page=1000', // Get all employees without pagination
        type: 'GET',
        success: function(response) {
            console.log('Employees response:', response); // Debug log

            let options = '<option value="">Pilih Karyawan</option>';
            let filterOptions = '<option value="">Semua Karyawan</option>';

            // Handle paginated response
            let employees = [];
            if (response.success && response.data) {
                // If data is paginated (has .data property)
                employees = response.data.data || response.data;
            } else if (Array.isArray(response)) {
                employees = response;
            }

            console.log('Employees array:', employees); // Debug log

            if (Array.isArray(employees) && employees.length > 0) {
                employees.forEach(function(emp) {
                    let displayName = emp.employee_code ? `${emp.employee_code} - ${emp.name}` : emp.name;
                    options += `<option value="${emp.id}">${displayName}</option>`;
                    filterOptions += `<option value="${emp.id}">${displayName}</option>`;
                });

                console.log('Total employees loaded:', employees.length);
            } else {
                console.warn('No employees found or invalid data structure');
                toastr.warning('Tidak ada data karyawan');
            }

            // Update dropdowns
            $('#createEmployeeSelect').html(options);
            $('#filterEmployee').html(filterOptions);

            // Initialize filter select2 immediately
            initFilterSelect2();
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:');
            console.error('Status:', status);
            console.error('Error:', error);
            console.error('Response:', xhr.responseText);
            toastr.error('Gagal memuat data karyawan: ' + error);
        }
    });
}

// Initialize filter select2
function initFilterSelect2() {
    if ($('#filterEmployee').length && !$('#filterEmployee').hasClass('select2-hidden-accessible')) {
        $('#filterEmployee').select2({
            width: '100%',
            placeholder: 'Semua Karyawan'
        });
    }
}

// Initialize create modal select2
function initCreateModalSelect2() {
    if ($('#createEmployeeSelect').length && !$('#createEmployeeSelect').hasClass('select2-hidden-accessible')) {
        $('#createEmployeeSelect').select2({
            dropdownParent: $('#createModal'),
            width: '100%',
            placeholder: 'Pilih Karyawan'
        });
    }
}

// Load statistics
function loadStatistics() {
    $.get('/api/admin/warning-letters/statistics', function(response) {
        if (response.success) {
            $('#totalAktif').text(response.data.total_aktif);
            $('#sp1Aktif').text(response.data.sp1_aktif);
            $('#sp2Aktif').text(response.data.sp2_aktif);
            $('#sp3Aktif').text(response.data.sp3_aktif);
        }
    });
}

// Load warning letters
function loadWarningLetters(page = 1) {
    currentPage = page;

    let params = {
        page: page,
        employee_id: $('#filterEmployee').val(),
        sp_type: $('#filterSpType').val(),
        status: $('#filterStatus').val(),
        search: $('#filterSearch').val(),
        start_date: $('#filterStartDate').val(),
        end_date: $('#filterEndDate').val()
    };

    $.get('/api/admin/warning-letters', params, function(response) {
        if (response.success) {
            renderTable(response.data.data);
            renderPagination(response.data);
        }
    });
}

// Render table
function renderTable(data) {
    let html = '';

    if (data.length === 0) {
        html = `
            <tr>
                <td colspan="7" class="text-center py-4">
                    <i class='bx bx-info-circle bx-lg text-muted'></i>
                    <p class="text-muted mt-2">Tidak ada data</p>
                </td>
            </tr>
        `;
    } else {
        data.forEach(function(sp) {
            let spTypeBadge = '';
            if (sp.sp_type === 'SP1') spTypeBadge = '<span class="badge bg-warning">SP 1</span>';
            else if (sp.sp_type === 'SP2') spTypeBadge = '<span class="badge" style="background-color: #ff9800;">SP 2</span>';
            else if (sp.sp_type === 'SP3') spTypeBadge = '<span class="badge bg-danger">SP 3</span>';

            let statusBadge = '';
            if (sp.status === 'draft') statusBadge = '<span class="badge bg-secondary">Draft</span>';
            else if (sp.status === 'aktif') statusBadge = '<span class="badge bg-danger">Aktif</span>';
            else if (sp.status === 'selesai') statusBadge = '<span class="badge bg-success">Selesai</span>';
            else if (sp.status === 'dibatalkan') statusBadge = '<span class="badge bg-secondary">Dibatalkan</span>';

            let violation = sp.violation.length > 50 ? sp.violation.substring(0, 50) + '...' : sp.violation;

            html += `
                <tr>
                    <td><strong>${sp.sp_number}</strong></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div>
                                <strong>${sp.employee.name}</strong><br>
                                <small class="text-muted">${sp.employee.employee_code}</small>
                            </div>
                        </div>
                    </td>
                    <td>${spTypeBadge}</td>
                    <td>${violation}</td>
                    <td>${formatDate(sp.issue_date)}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <div class="dropdown">
                            <button type="button" class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                                <i class='bx bx-dots-vertical-rounded'></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="viewDetail(${sp.id})">
                                    <i class='bx bx-show'></i> Lihat Detail
                                </a></li>
                                ${sp.document_path ? `
                                <li><a class="dropdown-item" href="/api/admin/warning-letters/${sp.id}/download">
                                    <i class='bx bx-download'></i> Download Dokumen
                                </a></li>
                                ` : ''}
                                @if(in_array(auth()->user()->role, ['admin', 'manager', 'viewer']))
                                ${sp.status === 'draft' ? `
                                <li><a class="dropdown-item text-primary" href="#" onclick="uploadDocumentModal(${sp.id})">
                                    <i class='bx bx-upload'></i> Upload Dokumen & Aktivasi
                                </a></li>
                                ` : ''}
                                <li><a class="dropdown-item" href="#" onclick="editSp(${sp.id})">
                                    <i class='bx bx-edit'></i> Edit
                                </a></li>
                                ${sp.status === 'aktif' ? `
                                <li><a class="dropdown-item" href="#" onclick="sendNotification(${sp.id})">
                                    <i class='bx bxl-whatsapp'></i> Kirim WhatsApp
                                </a></li>
                                ` : ''}
                                <li><hr class="dropdown-divider"></li>
                                ${sp.status !== 'dibatalkan' ? `
                                <li><a class="dropdown-item" href="#" onclick="cancelSp(${sp.id})">
                                    <i class='bx bx-x-circle'></i> Batalkan SP
                                </a></li>
                                ` : ''}
                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteSp(${sp.id})">
                                    <i class='bx bx-trash'></i> Hapus
                                </a></li>
                                @endif
                            </ul>
                        </div>
                    </td>
                </tr>
            `;
        });
    }

    $('#spTableBody').html(html);
}

// Render pagination
function renderPagination(data) {
    let info = `Menampilkan ${data.from || 0} - ${data.to || 0} dari ${data.total} data`;
    $('#paginationInfo').html(info);

    let links = '';
    if (data.last_page > 1) {
        // Previous
        links += `<li class="page-item ${data.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadWarningLetters(${data.current_page - 1}); return false;">
                <i class='bx bx-chevron-left'></i>
            </a>
        </li>`;

        // Pages
        for (let i = 1; i <= data.last_page; i++) {
            if (i === 1 || i === data.last_page || Math.abs(i - data.current_page) <= 2) {
                links += `<li class="page-item ${i === data.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadWarningLetters(${i}); return false;">${i}</a>
                </li>`;
            } else if (Math.abs(i - data.current_page) === 3) {
                links += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        // Next
        links += `<li class="page-item ${data.current_page === data.last_page ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadWarningLetters(${data.current_page + 1}); return false;">
                <i class='bx bx-chevron-right'></i>
            </a>
        </li>`;
    }

    $('#paginationLinks').html(links);
}

// View detail
function viewDetail(id) {
    $.get(`/api/admin/warning-letters/${id}`, function(response) {
        if (response.success) {
            let sp = response.data;

            let spTypeLabel = {
                'SP1': 'SP 1 - Peringatan Pertama',
                'SP2': 'SP 2 - Peringatan Kedua',
                'SP3': 'SP 3 - Peringatan Terakhir'
            };

            let statusBadge = {
                'draft': '<span class="badge bg-secondary">Draft</span>',
                'aktif': '<span class="badge bg-danger">Aktif</span>',
                'selesai': '<span class="badge bg-success">Selesai</span>',
                'dibatalkan': '<span class="badge bg-secondary">Dibatalkan</span>'
            };

            let html = `
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>Nomor SP:</strong><br>
                        <span class="text-primary">${sp.sp_number}</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Jenis SP:</strong><br>
                        ${spTypeLabel[sp.sp_type] || sp.sp_type}
                    </div>
                    <div class="col-md-6">
                        <strong>Karyawan:</strong><br>
                        ${sp.employee.name} (${sp.employee.employee_code})
                    </div>
                    <div class="col-md-6">
                        <strong>Status:</strong><br>
                        ${statusBadge[sp.status]}
                    </div>
                    <div class="col-md-6">
                        <strong>Tanggal Terbit:</strong><br>
                        ${formatDate(sp.issue_date)}
                    </div>
                    <div class="col-md-6">
                        <strong>Tanggal Berlaku:</strong><br>
                        ${formatDate(sp.effective_date)}
                    </div>
                    ${sp.completion_date ? `
                    <div class="col-md-6">
                        <strong>Tanggal Selesai:</strong><br>
                        ${formatDate(sp.completion_date)}
                    </div>
                    ` : ''}
                    <div class="col-12">
                        <strong>Pelanggaran:</strong><br>
                        <p class="mb-0">${sp.violation}</p>
                    </div>
                    ${sp.description ? `
                    <div class="col-12">
                        <strong>Deskripsi:</strong><br>
                        <p class="mb-0">${sp.description}</p>
                    </div>
                    ` : ''}
                    <div class="col-md-6">
                        <strong>Diterbitkan oleh:</strong><br>
                        ${sp.issuer.name}
                    </div>
                    <div class="col-md-6">
                        <strong>Waktu Penerbitan:</strong><br>
                        ${formatDateTime(sp.issued_at)}
                    </div>
                    ${sp.wa_sent_at ? `
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class='bx bxl-whatsapp'></i> WhatsApp terkirim pada ${formatDateTime(sp.wa_sent_at)}
                        </div>
                    </div>
                    ` : ''}
                    ${sp.cancellation_reason ? `
                    <div class="col-12">
                        <div class="alert alert-warning">
                            <strong>Alasan Pembatalan:</strong><br>
                            ${sp.cancellation_reason}
                        </div>
                    </div>
                    ` : ''}
                    <div class="col-12">
                        <strong>Dokumen SP:</strong><br>
                        ${sp.document_path ? `
                        <a href="/api/admin/warning-letters/${sp.id}/download" class="btn btn-sm btn-primary mt-2">
                            <i class='bx bx-download'></i> Download Dokumen
                        </a>
                        ` : `
                        <div class="alert alert-warning mt-2 py-2">
                            <i class='bx bx-info-circle'></i> Belum ada dokumen.
                            ${sp.status === 'draft' ? '<br><small>Upload dokumen untuk mengaktifkan SP.</small>' : ''}
                        </div>
                        `}
                    </div>
                </div>
            `;

            $('#detailContent').html(html);
            $('#detailModal').modal('show');
        }
    });
}

// Edit SP
function editSp(id) {
    $.get(`/api/admin/warning-letters/${id}`, function(response) {
        if (response.success) {
            let sp = response.data;

            $('#editSpId').val(sp.id);
            $('#editStatus').val(sp.status);
            $('#editCompletionDate').val(sp.completion_date);
            $('#editDescription').val(sp.description);

            $('#editModal').modal('show');
        }
    });
}

// Cancel SP
function cancelSp(id) {
    $('#cancelSpId').val(id);
    $('#cancellationReason').val('');
    $('#cancelModal').modal('show');
}

// Upload document for draft SP
function uploadDocumentModal(id) {
    $('#uploadSpId').val(id);
    $('#uploadDocument').val('');
    $('#uploadSendNotification').prop('checked', true);
    $('#uploadDocumentModal').modal('show');
}

// Delete SP
function deleteSp(id) {
    Swal.fire({
        title: 'Hapus Surat Peringatan?',
        text: 'Data akan dihapus secara permanen',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/api/admin/warning-letters/${id}`,
                type: 'DELETE',
                success: function(response) {
                    toastr.success('SP berhasil dihapus');
                    loadStatistics();
                    loadWarningLetters();
                },
                error: function() {
                    toastr.error('Gagal menghapus SP');
                }
            });
        }
    });
}

// Send notification
function sendNotification(id) {
    Swal.fire({
        title: 'Kirim Notifikasi WhatsApp?',
        text: 'Notifikasi akan dikirim ke karyawan',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#25D366',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Kirim!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(`/api/admin/warning-letters/${id}/send-notification`, function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    loadWarningLetters();
                } else {
                    toastr.error(response.message);
                }
            }).fail(function() {
                toastr.error('Gagal mengirim notifikasi');
            });
        }
    });
}

// Reset filters
function resetFilters() {
    $('#filterEmployee').val('').trigger('change');
    $('#filterSpType').val('');
    $('#filterStatus').val('');
    $('#filterSearch').val('');
    $('#filterStartDate').val('');
    $('#filterEndDate').val('');
    loadWarningLetters();
}

// Generate SP Number
function generateSpNumber() {
    let spType = $('#createSpType').val();
    let issueDate = $('#createIssueDate').val();

    if (!spType) {
        toastr.warning('Pilih jenis SP terlebih dahulu');
        return;
    }

    if (!issueDate) {
        toastr.warning('Pilih tanggal terbit terlebih dahulu');
        return;
    }

    $.ajax({
        url: '/api/admin/warning-letters/generate-number',
        type: 'POST',
        data: {
            sp_type: spType,
            issue_date: issueDate
        },
        success: function(response) {
            if (response.success) {
                $('#createSpNumber').val(response.data.sp_number);
                toastr.success('Nomor SP berhasil di-generate');
            }
        },
        error: function(xhr) {
            toastr.error('Gagal generate nomor SP');
        }
    });
}

// Check employee's active SP types
function checkEmployeeActiveSP() {
    let employeeId = $('#createEmployeeSelect').val();

    if (!employeeId) {
        // Reset SP type options if no employee selected
        $('#createSpType option').prop('disabled', false);
        $('#spWarningContainer').hide();
        return;
    }

    $.ajax({
        url: '/api/admin/warning-letters/check-employee-sp',
        type: 'POST',
        data: {
            employee_id: employeeId
        },
        success: function(response) {
            if (response.success) {
                let data = response.data;

                // Reset all options first
                $('#createSpType option').prop('disabled', false);
                $('#spWarningContainer').hide();

                // Disable SP types that are already active
                if (!data.can_create_sp1) {
                    $('#createSpType option[value="SP1"]').prop('disabled', true);
                }
                if (!data.can_create_sp2) {
                    $('#createSpType option[value="SP2"]').prop('disabled', true);
                }
                if (!data.can_create_sp3) {
                    $('#createSpType option[value="SP3"]').prop('disabled', true);
                }

                // Show warning if there are active SPs
                if (data.active_sps.length > 0) {
                    let spList = data.active_sps.map(sp => {
                        let statusLabel = sp.status === 'draft' ? '(draft)' : '(aktif)';
                        return `<strong>${sp.sp_type}</strong> ${statusLabel} - ${sp.sp_number}`;
                    }).join(', ');

                    $('#spWarningMessage').html(
                        `<i class='bx bx-info-circle'></i> Karyawan memiliki SP: ${spList}. Opsi telah di-disable untuk mencegah duplikasi.`
                    );
                    $('#spWarningContainer').show();
                }

                // If currently selected SP type is now disabled, reset selection
                let currentSpType = $('#createSpType').val();
                if (currentSpType && $('#createSpType option[value="' + currentSpType + '"]').prop('disabled')) {
                    $('#createSpType').val('');
                    toastr.info(`${currentSpType} sudah aktif untuk karyawan ini, pilih jenis SP lain`);
                }
            }
        },
        error: function(xhr) {
            console.error('Error checking employee SP:', xhr);
        }
    });
}

// Utility functions
function formatDate(dateString) {
    if (!dateString) return '-';
    let date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatDateTime(dateString) {
    if (!dateString) return '-';
    let date = new Date(dateString);
    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
</script>
@endpush
