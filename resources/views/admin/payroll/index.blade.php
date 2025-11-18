@extends('layouts.app')

@section('title', 'Payroll Karyawan')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row mb-4">
            <div class="col-12 col-md-6 mb-3 mb-md-0">
                <h4 class="fw-bold mb-0">Payroll Karyawan</h4>
            </div>
            <div class="col-12 col-md-6">
                <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-end">
                    <button type="button" class="btn btn-success" id="btnExport">
                        <i class='bx bxs-file-export me-1'></i> Export Excel
                    </button>
                    <button type="button" class="btn btn-primary" id="btnTambahPayroll">
                        <i class='bx bx-plus me-1'></i> Tambah Payroll
                    </button>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label">Periode</label>
                        <input type="month" class="form-control" id="filterPeriod">
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="filterStatus">
                            <option value="">Semua Status</option>
                            <option value="draft">Draft</option>
                            <option value="sent">Terkirim</option>
                            <option value="paid">Dibayar</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label">Cari</label>
                        <input type="text" class="form-control" id="searchInput"
                            placeholder="Cari kode/nama karyawan...">
                    </div>
                    <div class="col-12 col-md-6 col-lg-2 d-flex align-items-end">
                        <button class="btn btn-secondary w-100" id="btnResetFilter">
                            <i class='bx bx-reset'></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Desktop Table View -->
        <div class="card d-none d-lg-block">
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Karyawan</th>
                            <th>Periode</th>
                            <th>Tgl Bayar</th>
                            <th class="text-end">Gaji Bersih</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="payrollTableBody">
                        <tr>
                            <td colspan="7" class="text-center">Memuat data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <div id="pagination"></div>
            </div>
        </div>

        <!-- Mobile Card View -->
        <div class="d-lg-none" id="mobileView"></div>

        <!-- Mobile Pagination -->
        <div class="d-lg-none mt-3" id="mobilePagination"></div>
    </div>

    <!-- Create/Edit Modal -->
    <div class="modal fade" id="payrollModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Payroll</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="payrollForm">
                    @csrf
                    <input type="hidden" id="payrollId">
                    <div class="modal-body">
                        <!-- Basic Info -->
                        <div class="row mb-3">
                            <div class="col-12 col-md-6 mb-3 mb-md-0">
                                <label class="form-label">Karyawan <span class="text-danger">*</span></label>
                                <select class="form-select" id="employee_id" name="employee_id" required>
                                    <option value="">Pilih Karyawan</option>
                                </select>
                                <div class="invalid-feedback" id="employee_idError"></div>
                            </div>
                            <div class="col-6 col-md-3 mb-3 mb-md-0">
                                <label class="form-label">Periode <span class="text-danger">*</span></label>
                                <input type="month" class="form-control" id="period_month" name="period_month" required>
                                <div class="invalid-feedback" id="period_monthError"></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">Tgl Bayar <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="payment_date" name="payment_date" required>
                                <div class="invalid-feedback" id="payment_dateError"></div>
                            </div>
                        </div>

                        <!-- Tabs -->
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#earningsTab">💰
                                    Pendapatan</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#deductionsTab">➖
                                    Potongan</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#summaryTab">📊
                                    Ringkasan</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#proofTab">📄
                                    Bukti Transfer</a></li>
                        </ul>

                        <div class="tab-content mt-3">
                            <!-- Earnings Tab -->
                            <div class="tab-pane fade show active" id="earningsTab">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label>Gaji Pokok <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control earnings-field" id="basic_salary"
                                            name="basic_salary" min="0" step="0.01" required>
                                        <div class="invalid-feedback" id="basic_salaryError"></div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label>Tunjangan Transport</label>
                                        <input type="number" class="form-control earnings-field"
                                            id="allowance_transport" name="allowance_transport" min="0"
                                            step="0.01" value="0">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label>Tunjangan Makan</label>
                                        <input type="number" class="form-control earnings-field" id="allowance_meal"
                                            name="allowance_meal" min="0" step="0.01" value="0">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label>Tunjangan Jabatan</label>
                                        <input type="number" class="form-control earnings-field" id="allowance_position"
                                            name="allowance_position" min="0" step="0.01" value="0">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label>Tunjangan Lainnya</label>
                                        <input type="number" class="form-control earnings-field" id="allowance_others"
                                            name="allowance_others" min="0" step="0.01" value="0">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label>Uang Lembur</label>
                                        <input type="number" class="form-control earnings-field" id="overtime_pay"
                                            name="overtime_pay" min="0" step="0.01" value="0">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label>Bonus</label>
                                        <input type="number" class="form-control earnings-field" id="bonus"
                                            name="bonus" min="0" step="0.01" value="0">
                                    </div>
                                </div>
                            </div>

                            <!-- Deductions Tab -->
                            <div class="tab-pane fade" id="deductionsTab">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label>Potongan Terlambat</label>
                                        <input type="number" class="form-control deduction-field" id="deduction_late"
                                            name="deduction_late" min="0" step="0.01" value="0">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label>Potongan Alpha</label>
                                        <input type="number" class="form-control deduction-field" id="deduction_absent"
                                            name="deduction_absent" min="0" step="0.01" value="0">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label>Potongan Pinjaman</label>
                                        <input type="number" class="form-control deduction-field" id="deduction_loan"
                                            name="deduction_loan" min="0" step="0.01" value="0">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label>Potongan BPJS</label>
                                        <input type="number" class="form-control deduction-field" id="deduction_bpjs"
                                            name="deduction_bpjs" min="0" step="0.01" value="0">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label>Potongan Pajak</label>
                                        <input type="number" class="form-control deduction-field" id="deduction_tax"
                                            name="deduction_tax" min="0" step="0.01" value="0">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label>Potongan Lainnya</label>
                                        <input type="number" class="form-control deduction-field" id="deduction_others"
                                            name="deduction_others" min="0" step="0.01" value="0">
                                    </div>
                                </div>
                            </div>

                            <!-- Summary Tab -->
                            <div class="tab-pane fade" id="summaryTab">
                                <div class="card bg-light mb-3">
                                    <div class="card-body">
                                        <h6 class="text-success">💰 Total Pendapatan</h6>
                                        <h4 class="text-success mb-0" id="totalEarningsDisplay">Rp 0</h4>
                                    </div>
                                </div>
                                <div class="card bg-light mb-3">
                                    <div class="card-body">
                                        <h6 class="text-danger">➖ Total Potongan</h6>
                                        <h4 class="text-danger mb-0" id="totalDeductionsDisplay">Rp 0</h4>
                                    </div>
                                </div>
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h6>✅ Gaji Bersih</h6>
                                        <h3 class="mb-0" id="netSalaryDisplay">Rp 0</h3>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label>Catatan</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"
                                        placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                                </div>
                            </div>

                            <!-- Proof Tab -->
                            <div class="tab-pane fade" id="proofTab">
                                <div class="alert alert-info">
                                    <i class='bx bx-info-circle'></i> Upload bukti transfer untuk melengkapi pembayaran
                                    gaji (opsional)
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">File Bukti Transfer</label>
                                    <input type="file" class="form-control" id="payment_proof_file"
                                        name="payment_proof_file" accept="image/*,application/pdf">
                                    <div class="form-text">Format: JPG, PNG, atau PDF (Maksimal 5MB)</div>
                                    <div class="invalid-feedback" id="payment_proof_fileError"></div>
                                </div>
                                <div id="proofPreviewInForm" class="mt-3" style="display:none;">
                                    <label class="form-label">Preview:</label>
                                    <div class="border rounded p-3 text-center bg-light">
                                        <img id="proofPreviewImageInForm" src="" alt="Preview"
                                            class="img-fluid rounded" style="max-height: 300px;">
                                    </div>
                                    <button type="button" class="btn btn-sm btn-danger mt-2" id="btnRemoveProof">
                                        <i class='bx bx-trash'></i> Hapus File
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-save me-1'></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Upload Proof Modal -->
    <div class="modal fade" id="uploadProofModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Bukti Transfer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="uploadProofForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="upload_payroll_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">File Bukti Transfer <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="payment_proof" name="payment_proof"
                                accept="image/*,application/pdf" required>
                            <div class="form-text">Format: JPG, PNG, atau PDF (Maks. 5MB)</div>
                            <div class="invalid-feedback" id="payment_proofError"></div>
                        </div>
                        <div id="proofPreview" class="mt-3" style="display:none;">
                            <label class="form-label">Preview:</label>
                            <img id="proofPreviewImage" src="" alt="Preview" class="img-fluid rounded"
                                style="max-height: 300px;">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-upload me-1'></i> Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Proof Modal -->
    <div class="modal fade" id="viewProofModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bukti Transfer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="proofViewContent"></div>
                    <div id="paidAtInfo" class="mt-3 text-muted"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Mobile responsive improvements */
        @media (max-width: 767.98px) {
            .container-xxl {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .card-body {
                padding: 1rem;
            }

            .modal-dialog {
                margin: 0.5rem;
            }

            .modal-body {
                padding: 1rem;
            }

            .nav-tabs .nav-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
            }

            /* Make buttons more touch-friendly */
            .btn-sm {
                padding: 0.375rem 0.75rem;
            }

            /* Improve form inputs on mobile */
            .form-control,
            .form-select {
                font-size: 1rem;
            }

            /* Better spacing for mobile cards */
            .card {
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            }

            /* Pagination on mobile */
            .pagination {
                flex-wrap: wrap;
            }

            .pagination .page-item {
                margin: 0.125rem;
            }

            .pagination .page-link {
                padding: 0.375rem 0.75rem;
            }
        }

        @media (max-width: 575.98px) {

            /* Extra small devices */
            .modal-dialog {
                max-width: 100%;
                margin: 0;
            }

            .modal-content {
                border-radius: 0;
                min-height: 100vh;
            }

            /* Stack summary cards vertically on very small screens */
            .card.bg-light,
            .card.bg-primary {
                margin-bottom: 0.75rem !important;
            }
        }

        /* Improve button group on mobile */
        .btn-group {
            display: flex;
            width: 100%;
        }

        .btn-group>.btn {
            flex: 1;
        }

        /* Better touch targets */
        @media (hover: none) and (pointer: coarse) {
            .btn {
                min-height: 44px;
            }

            .form-control,
            .form-select {
                min-height: 44px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            console.log('🚀 Payroll page initialized');
            let currentPage = 1;
            let employees = [];

            // Load initial data
            console.log('📥 Loading initial data...');
            loadEmployees();
            loadPayrolls();

            // Event Listeners menggunakan jQuery event delegation
            console.log('🎯 Setting up event listeners...');
            $(document).on('click', '#btnTambahPayroll', function() {
                console.log('🔘 Tambah Payroll button clicked');
                openCreateModal();
            });
            $(document).on('click', '#btnExport', function() {
                console.log('🔘 Export button clicked');
                exportPayroll();
            });
            $(document).on('click', '#btnResetFilter', function() {
                console.log('🔘 Reset Filter button clicked');
                resetFilters();
            });
            $(document).on('click', '.btn-view-detail', function() {
                let id = $(this).data('id');
                console.log('🔘 View Detail clicked for ID:', id);
                viewDetail(id);
            });
            $(document).on('click', '.btn-edit-payroll', function() {
                let id = $(this).data('id');
                console.log('🔘 Edit Payroll clicked for ID:', id);
                editPayroll(id);
            });
            $(document).on('click', '.btn-send-notification', function() {
                let id = $(this).data('id');
                console.log('🔘 Send Notification clicked for ID:', id);
                sendNotification(id);
            });
            $(document).on('click', '.btn-delete-payroll', function() {
                let id = $(this).data('id');
                console.log('🔘 Delete Payroll clicked for ID:', id);
                deletePayroll(id);
            });
            $(document).on('click', '.btn-upload-proof', function() {
                let id = $(this).data('id');
                console.log('🔘 Upload Proof clicked for ID:', id);
                openUploadProofModal(id);
            });
            $(document).on('click', '.btn-view-proof', function() {
                let id = $(this).data('id');
                console.log('🔘 View Proof clicked for ID:', id);
                viewProof(id);
            });
            $(document).on('click', '.btn-delete-proof', function() {
                let id = $(this).data('id');
                console.log('🔘 Delete Proof clicked for ID:', id);
                deleteProof(id);
            });
            $(document).on('click', '.pagination-link', function(e) {
                e.preventDefault();
                let page = $(this).data('page');
                console.log('🔘 Pagination clicked, going to page:', page);
                loadPayrolls(page);
            });

            $('#searchInput').on('keyup', debounce(function() {
                let searchTerm = $(this).val();
                console.log('🔍 Search triggered:', searchTerm);
                loadPayrolls();
            }, 500));
            $('#filterPeriod, #filterStatus').on('change', function() {
                let period = $('#filterPeriod').val();
                let status = $('#filterStatus').val();
                console.log('🔍 Filter changed:', {
                    period,
                    status
                });
                loadPayrolls();
            });
            $('.earnings-field, .deduction-field').on('input', calculateTotals);

            $('#employee_id').on('change', function() {
                let empId = $(this).val();
                let salary = $(this).find(':selected').data('salary');
                console.log('👤 Employee changed:', {
                    id: empId,
                    salary: salary
                });
                if (salary) {
                    $('#basic_salary').val(salary);
                    calculateTotals();
                    console.log('✅ Basic salary auto-filled:', salary);
                }
            });

            // File input preview in form
            $('#payment_proof_file').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Check file size (5MB max)
                    if (file.size > 5 * 1024 * 1024) {
                        toastr.error('Ukuran file maksimal 5MB');
                        $(this).val('');
                        $('#proofPreviewInForm').hide();
                        return;
                    }

                    const fileType = file.type;
                    if (fileType.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            $('#proofPreviewImageInForm').attr('src', e.target.result);
                            $('#proofPreviewInForm').show();
                        };
                        reader.readAsDataURL(file);
                    } else if (fileType === 'application/pdf') {
                        $('#proofPreviewImageInForm').attr('src', '').hide();
                        $('#proofPreviewInForm').show();
                        $('#proofPreviewInForm .border').html(
                            '<i class="bx bx-file-blank bx-lg text-danger"></i><p class="mb-0 mt-2">PDF: ' +
                            file.name + '</p>');
                    }
                }
            });

            // Remove proof file
            $('#btnRemoveProof').on('click', function() {
                $('#payment_proof_file').val('');
                $('#proofPreviewInForm').hide();
                toastr.info('File dihapus');
            });

            // Form Submit
            $('#payrollForm').on('submit', function(e) {
                e.preventDefault();
                submitPayroll();
            });

            // Functions
            function loadEmployees() {
                console.log('👥 Loading employees from API...');
                $.get('/api/payroll/employees')
                    .done(function(res) {
                        console.log('✅ Employees loaded:', res);
                        if (res.success) {
                            employees = res.data;
                            console.log(`📊 Total employees: ${res.data.length}`);
                            let options = '<option value="">Pilih Karyawan</option>';
                            res.data.forEach(function(emp) {
                                let salary = emp.salary_base || 0;
                                console.log(
                                    `  - ${emp.employee_code}: ${emp.name} (Salary: ${salary})`);
                                options +=
                                    `<option value="${emp.id}" data-salary="${salary}">${emp.employee_code} - ${emp.name}</option>`;
                            });
                            $('#employee_id').html(options);
                            console.log('✅ Employee dropdown populated');
                        } else {
                            console.warn('⚠️ API returned success=false:', res);
                        }
                    })
                    .fail(function(xhr) {
                        console.error('❌ Error loading employees:', xhr);
                        console.error('Status:', xhr.status);
                        console.error('Response:', xhr.responseJSON);
                        toastr.error('Gagal memuat data karyawan');
                    });
            }

            function loadPayrolls(page = 1) {
                console.log(`📋 Loading payrolls - Page ${page}`);
                currentPage = page;
                let params = {
                    page: page,
                    per_page: 10,
                    search: $('#searchInput').val(),
                    period: $('#filterPeriod').val(),
                    status: $('#filterStatus').val()
                };
                console.log('🔍 Filter params:', params);

                $('#payrollTableBody').html(
                    '<tr><td colspan="7" class="text-center"><i class="bx bx-loader bx-spin"></i> Memuat data...</td></tr>'
                );

                $.get('/api/payroll', params)
                    .done(function(res) {
                        console.log('✅ Payrolls loaded:', res);
                        if (res.success) {
                            console.log(`📊 Total payrolls: ${res.data.data.length} of ${res.data.total}`);
                            console.log('Data structure:', {
                                current_page: res.data.current_page,
                                last_page: res.data.last_page,
                                per_page: res.data.per_page,
                                total: res.data.total
                            });
                            renderTable(res.data.data);
                            renderPagination(res.data);
                            renderMobileCards(res.data.data);
                        } else {
                            console.warn('⚠️ API returned success=false:', res);
                            $('#payrollTableBody').html(
                                '<tr><td colspan="7" class="text-center text-warning">Tidak ada data</td></tr>'
                            );
                        }
                    })
                    .fail(function(xhr) {
                        console.error('❌ Error loading payrolls:', xhr);
                        console.error('Status:', xhr.status);
                        console.error('Status Text:', xhr.statusText);
                        console.error('Response:', xhr.responseJSON);
                        console.error('Response Text:', xhr.responseText);
                        $('#payrollTableBody').html(
                            '<tr><td colspan="7" class="text-center text-danger">Error: ' + (xhr
                                .responseJSON?.message || xhr.statusText || 'Gagal memuat data') +
                            '</td></tr>');
                        toastr.error('Gagal memuat data payroll: ' + (xhr.responseJSON?.message || xhr
                            .statusText));
                    });
            }

            function renderTable(payrolls) {
                console.log('🎨 Rendering table with', payrolls.length, 'payrolls');
                if (payrolls.length === 0) {
                    console.log('📭 No payroll data to display');
                    $('#payrollTableBody').html(
                        '<tr><td colspan="7" class="text-center">Tidak ada data payroll</td></tr>');
                    return;
                }

                let html = '';
                payrolls.forEach(function(p, index) {
                    console.log(`  Row ${index + 1}:`, {
                        code: p.payroll_code,
                        employee: p.employee?.name,
                        period: p.period_month,
                        net_salary: p.net_salary,
                        status: p.status
                    });

                    let statusBadge = {
                        draft: '<span class="badge bg-secondary">Draft</span>',
                        sent: '<span class="badge bg-success">Terkirim</span>',
                        paid: '<span class="badge bg-primary">Dibayar</span>'
                    };

                    let actions =
                        `<button class="btn btn-sm btn-info btn-view-detail" data-id="${p.id}" title="Lihat Detail"><i class='bx bx-show'></i></button>`;

                    if (p.status === 'draft') {
                        actions +=
                            ` <button class="btn btn-sm btn-warning btn-edit-payroll" data-id="${p.id}" title="Edit"><i class='bx bx-edit'></i></button>`;
                        actions +=
                            ` <button class="btn btn-sm btn-success btn-send-notification" data-id="${p.id}" title="Kirim WA"><i class='bx bxl-whatsapp'></i></button>`;
                        actions +=
                            ` <button class="btn btn-sm btn-danger btn-delete-payroll" data-id="${p.id}" title="Hapus"><i class='bx bx-trash'></i></button>`;
                    } else if (p.status === 'sent') {
                        actions +=
                            ` <button class="btn btn-sm btn-primary btn-upload-proof" data-id="${p.id}" title="Upload Bukti"><i class='bx bx-upload'></i> Upload</button>`;
                    } else if (p.status === 'paid') {
                        actions +=
                            ` <button class="btn btn-sm btn-success btn-view-proof" data-id="${p.id}" title="Lihat Bukti"><i class='bx bx-image'></i></button>`;
                        actions +=
                            ` <button class="btn btn-sm btn-danger btn-delete-proof" data-id="${p.id}" title="Hapus Bukti"><i class='bx bx-trash'></i></button>`;
                    }

                    html += `<tr>
                <td>${p.payroll_code}</td>
                <td><div class="fw-bold">${p.employee.name}</div><small class="text-muted">${p.employee.employee_code}</small></td>
                <td>${formatPeriod(p.period_month)}</td>
                <td>${formatDate(p.payment_date)}</td>
                <td class="text-end fw-bold text-primary">${formatCurrency(p.net_salary)}</td>
                <td>${statusBadge[p.status]}</td>
                <td>${actions}</td>
            </tr>`;
                });
                $('#payrollTableBody').html(html);
                console.log('✅ Table rendered successfully');
            }

            function renderMobileCards(payrolls) {
                if (payrolls.length === 0) {
                    $('#mobileView').html('<div class="alert alert-info">Tidak ada data payroll</div>');
                    return;
                }

                let html = '';
                payrolls.forEach(function(p) {
                    let statusBadge = {
                        draft: '<span class="badge bg-secondary">Draft</span>',
                        sent: '<span class="badge bg-success">Terkirim</span>',
                        paid: '<span class="badge bg-primary">Dibayar</span>'
                    };

                    let actions = '';
                    if (p.status === 'draft') {
                        actions = `
                            <div class="btn-group w-100" role="group">
                                <button class="btn btn-sm btn-info btn-view-detail" data-id="${p.id}" title="Lihat">
                                    <i class='bx bx-show'></i>
                                </button>
                                <button class="btn btn-sm btn-warning btn-edit-payroll" data-id="${p.id}" title="Edit">
                                    <i class='bx bx-edit'></i>
                                </button>
                                <button class="btn btn-sm btn-success btn-send-notification" data-id="${p.id}" title="Kirim WA">
                                    <i class='bx bxl-whatsapp'></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-delete-payroll" data-id="${p.id}" title="Hapus">
                                    <i class='bx bx-trash'></i>
                                </button>
                            </div>
                        `;
                    } else if (p.status === 'sent') {
                        actions = `
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-info btn-view-detail flex-fill" data-id="${p.id}">
                                    <i class='bx bx-show'></i> Lihat Detail
                                </button>
                                <button class="btn btn-sm btn-primary btn-upload-proof flex-fill" data-id="${p.id}">
                                    <i class='bx bx-upload'></i> Upload Bukti
                                </button>
                            </div>
                        `;
                    } else if (p.status === 'paid') {
                        actions = `
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-info btn-view-detail flex-fill" data-id="${p.id}">
                                    <i class='bx bx-show'></i> Lihat Detail
                                </button>
                                <button class="btn btn-sm btn-success btn-view-proof flex-fill" data-id="${p.id}">
                                    <i class='bx bx-image'></i> Lihat Bukti
                                </button>
                                <button class="btn btn-sm btn-danger btn-delete-proof" data-id="${p.id}" title="Hapus Bukti">
                                    <i class='bx bx-trash'></i>
                                </button>
                            </div>
                        `;
                    }

                    html += `
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-bold">${p.employee.name}</h6>
                                        <small class="text-muted">${p.employee.employee_code}</small>
                                    </div>
                                    <div>${statusBadge[p.status]}</div>
                                </div>
                                <div class="mb-2">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <small class="text-muted d-block">Kode</small>
                                            <span class="fw-semibold">${p.payroll_code}</span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Periode</small>
                                            <span class="fw-semibold">${formatPeriod(p.period_month)}</span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Tgl Bayar</small>
                                            <span class="fw-semibold">${formatDate(p.payment_date)}</span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Gaji Bersih</small>
                                            <span class="fw-bold text-primary">${formatCurrency(p.net_salary)}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    ${actions}
                                </div>
                            </div>
                        </div>
                    `;
                });
                $('#mobileView').html(html);
            }

            function renderPagination(data) {
                if (data.last_page <= 1) {
                    $('#pagination').html('');
                    $('#mobilePagination').html('');
                    return;
                }

                let html = '<nav><ul class="pagination justify-content-center">';

                // Previous button
                if (data.current_page > 1) {
                    html += `<li class="page-item">
                        <a class="page-link pagination-link" href="#" data-page="${data.current_page - 1}">
                            <i class='bx bx-chevron-left'></i>
                        </a>
                    </li>`;
                }

                // Page numbers
                for (let i = 1; i <= data.last_page; i++) {
                    // Show limited pages on mobile
                    if (data.last_page > 5) {
                        if (i === 1 || i === data.last_page ||
                            (i >= data.current_page - 1 && i <= data.current_page + 1)) {
                            let active = i === data.current_page ? 'active' : '';
                            html += `<li class="page-item ${active}">
                                <a class="page-link pagination-link" href="#" data-page="${i}">${i}</a>
                            </li>`;
                        } else if (i === data.current_page - 2 || i === data.current_page + 2) {
                            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                        }
                    } else {
                        let active = i === data.current_page ? 'active' : '';
                        html += `<li class="page-item ${active}">
                            <a class="page-link pagination-link" href="#" data-page="${i}">${i}</a>
                        </li>`;
                    }
                }

                // Next button
                if (data.current_page < data.last_page) {
                    html += `<li class="page-item">
                        <a class="page-link pagination-link" href="#" data-page="${data.current_page + 1}">
                            <i class='bx bx-chevron-right'></i>
                        </a>
                    </li>`;
                }

                html += '</ul></nav>';

                // Info text for mobile
                let infoText = `<div class="text-center text-muted small mb-2">
                    Halaman ${data.current_page} dari ${data.last_page} (Total: ${data.total} data)
                </div>`;

                $('#pagination').html(html);
                $('#mobilePagination').html(infoText + html);
            }

            function openCreateModal() {
                console.log('➕ Opening create modal');
                $('#modalTitle').text('Tambah Payroll');
                $('#payrollForm')[0].reset();
                $('#payrollId').val('');
                $('#employee_id').prop('disabled', false);
                $('#period_month').prop('disabled', false);
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');
                $('#proofPreviewInForm').hide();
                $('#payment_proof_file').val('');
                calculateTotals();
                $('#payrollModal').modal('show');
                console.log('✅ Modal opened');
            }

            function resetFilters() {
                console.log('🔄 Resetting filters');
                $('#filterPeriod, #filterStatus, #searchInput').val('');
                loadPayrolls();
            }

            function exportPayroll() {
                console.log('📤 Exporting payroll data...');

                // Build URL with current filters
                let params = new URLSearchParams({
                    search: $('#searchInput').val() || '',
                    period: $('#filterPeriod').val() || '',
                    status: $('#filterStatus').val() || ''
                });

                let url = '/admin/payroll/export?' + params.toString();
                console.log('Export URL:', url);

                // Show loading toast
                toastr.info('Mempersiapkan file Excel...', 'Export', {
                    timeOut: 2000
                });

                // Trigger download
                window.location.href = url;

                // Show success message after short delay
                setTimeout(() => {
                    toastr.success('File Excel berhasil diunduh!');
                }, 1000);
            }

            function calculateTotals() {
                let earnings = 0;
                $('.earnings-field').each(function() {
                    earnings += parseFloat($(this).val() || 0);
                });

                let deductions = 0;
                $('.deduction-field').each(function() {
                    deductions += parseFloat($(this).val() || 0);
                });

                let net = earnings - deductions;
                console.log('💰 Calculated:', {
                    earnings: earnings,
                    deductions: deductions,
                    net: net
                });
                $('#totalEarningsDisplay').text(formatCurrency(earnings));
                $('#totalDeductionsDisplay').text(formatCurrency(deductions));
                $('#netSalaryDisplay').text(formatCurrency(net));
            }

            function submitPayroll() {
                console.log('💾 Submitting payroll form...');
                let id = $('#payrollId').val();
                let url = id ? `/api/payroll/${id}` : '/api/payroll';
                let method = id ? 'PUT' : 'POST';

                // Prepare FormData to handle file upload
                let formData = new FormData($('#payrollForm')[0]);

                // Add file if exists
                const fileInput = document.getElementById('payment_proof_file');
                if (fileInput.files.length > 0) {
                    formData.append('payment_proof_file', fileInput.files[0]);
                }

                console.log('Form data prepared with file:', fileInput.files.length > 0 ? 'Yes' : 'No');

                // Reset error states
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');

                $.ajax({
                    url: url,
                    method: method,
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        console.log('✅ Payroll saved:', res);
                        $('#payrollModal').modal('hide');
                        toastr.success(res.message);
                        loadPayrolls(currentPage);
                    },
                    error: function(xhr) {
                        console.error('❌ Error saving payroll:', xhr);
                        console.error('Status:', xhr.status);
                        console.error('Response:', xhr.responseJSON);

                        if (xhr.status === 422) {
                            // Check if this is a duplicate payroll error
                            let responseMessage = xhr.responseJSON?.message || '';
                            if (responseMessage.includes('sudah ada') || responseMessage.includes(
                                    'already exists')) {
                                // This is a duplicate payroll, not a form validation error
                                console.log('Duplicate payroll detected:', responseMessage);

                                // Show SweetAlert for better UX
                                Swal.fire({
                                    title: 'Payroll Sudah Ada',
                                    html: `
                                        <div class="text-start">
                                            <p><i class='bx bx-info-circle text-warning'></i> <strong>${responseMessage}</strong></p>
                                            <hr>
                                            <p class="mb-2">Silakan:</p>
                                            <ul class="text-muted">
                                                <li>Pilih karyawan yang berbeda, atau</li>
                                                <li>Pilih periode bulan yang berbeda</li>
                                            </ul>
                                        </div>
                                    `,
                                    icon: 'warning',
                                    confirmButtonText: 'Mengerti',
                                    confirmButtonColor: '#ffc107'
                                });

                                // Highlight the period and employee fields
                                $('#employee_id, #period_month').addClass('is-invalid');
                                $('#employee_idError').text(
                                    'Payroll untuk karyawan ini di periode tersebut sudah dibuat');
                                $('#period_monthError').text('Pilih periode yang berbeda');
                            } else {
                                // Regular validation errors
                                let errors = xhr.responseJSON.errors;
                                console.log('Validation errors:', errors);
                                $.each(errors, function(field, messages) {
                                    console.log(`  - ${field}: ${messages[0]}`);
                                    $(`#${field}`).addClass('is-invalid');
                                    $(`#${field}Error`).text(messages[0]);
                                });
                                toastr.error('Terdapat kesalahan pada form. Silakan periksa kembali.');
                            }
                        } else if (xhr.status === 400 || xhr.status === 409) {
                            // Handle duplicate payroll (400 Bad Request or 409 Conflict)
                            let message = xhr.responseJSON?.message || 'Payroll sudah ada';
                            console.log('Duplicate payroll error:', message);

                            // Show SweetAlert for better UX
                            Swal.fire({
                                title: 'Payroll Sudah Ada',
                                html: `
                                    <div class="text-start">
                                        <p><i class='bx bx-info-circle text-warning'></i> <strong>${message}</strong></p>
                                        <hr>
                                        <p class="mb-2">Silakan:</p>
                                        <ul class="text-muted">
                                            <li>Pilih karyawan yang berbeda, atau</li>
                                            <li>Pilih periode bulan yang berbeda</li>
                                        </ul>
                                    </div>
                                `,
                                icon: 'warning',
                                confirmButtonText: 'Mengerti',
                                confirmButtonColor: '#ffc107'
                            });

                            // Highlight the period and employee fields
                            $('#employee_id, #period_month').addClass('is-invalid');
                            $('#employee_idError').text(
                                'Payroll untuk karyawan ini di periode tersebut sudah dibuat');
                            $('#period_monthError').text('Silakan pilih periode yang berbeda');
                        } else {
                            // Handle other errors
                            let message = xhr.responseJSON?.message ||
                                'Terjadi kesalahan saat menyimpan payroll';
                            toastr.error(message);
                        }
                    }
                });
            }

            function viewDetail(id) {
                $.get(`/api/payroll/${id}`)
                    .done(function(res) {
                        if (res.success) {
                            let p = res.data;

                            // Bank info section
                            let bankInfo = '';
                            if (p.employee.bank && p.employee.nomor_rekening) {
                                bankInfo = `
                                    <hr>
                                    <h6 class="text-primary"><i class='bx bxs-bank'></i> Informasi Bank</h6>
                                    <p><strong>Bank:</strong> ${p.employee.bank}</p>
                                    <p><strong>No. Rekening:</strong> ${p.employee.nomor_rekening}</p>
                                    <p><strong>Atas Nama:</strong> ${p.employee.name}</p>
                                `;
                            }

                            Swal.fire({
                                title: 'Detail Payroll',
                                html: `
                            <div class="text-start">
                                <h6 class="text-primary"><i class='bx bx-user'></i> Data Karyawan</h6>
                                <p><strong>Kode Payroll:</strong> ${p.payroll_code}</p>
                                <p><strong>Nama:</strong> ${p.employee.name}</p>
                                <p><strong>NIP:</strong> ${p.employee.employee_code}</p>
                                <p><strong>Periode:</strong> ${formatPeriod(p.period_month)}</p>
                                <p><strong>Tanggal Bayar:</strong> ${formatDate(p.payment_date)}</p>

                                <hr>
                                <h6 class="text-success"><i class='bx bx-money'></i> Rincian Gaji</h6>
                                <p><strong>Total Pendapatan:</strong> <span class="text-success">${formatCurrency(p.total_earnings)}</span></p>
                                <p><strong>Total Potongan:</strong> <span class="text-danger">${formatCurrency(p.total_deductions)}</span></p>
                                <p><strong>Gaji Bersih:</strong> <span class="text-primary fw-bold fs-5">${formatCurrency(p.net_salary)}</span></p>

                                ${bankInfo}

                                <hr>
                                <p><strong>Status:</strong> <span class="badge bg-${p.status === 'draft' ? 'secondary' : p.status === 'sent' ? 'success' : 'primary'}">${p.status === 'draft' ? 'Draft' : p.status === 'sent' ? 'Terkirim' : 'Dibayar'}</span></p>
                                ${p.notes ? `<p><strong>Catatan:</strong> ${p.notes}</p>` : ''}
                            </div>
                        `,
                                icon: 'info',
                                width: 600,
                                confirmButtonText: 'Tutup'
                            });
                        }
                    })
                    .fail(function() {
                        toastr.error('Gagal memuat detail payroll');
                    });
            }

            function editPayroll(id) {
                $.get(`/api/payroll/${id}`)
                    .done(function(res) {
                        if (res.success) {
                            let p = res.data;
                            $('#modalTitle').text('Edit Payroll');
                            $('#payrollId').val(p.id);
                            $('#employee_id').val(p.employee_id).prop('disabled', true);
                            $('#period_month').val(p.period_month).prop('disabled', true);
                            $('#payment_date').val(p.payment_date);
                            $('#basic_salary').val(p.basic_salary);
                            $('#allowance_transport').val(p.allowance_transport);
                            $('#allowance_meal').val(p.allowance_meal);
                            $('#allowance_position').val(p.allowance_position);
                            $('#allowance_others').val(p.allowance_others);
                            $('#overtime_pay').val(p.overtime_pay);
                            $('#bonus').val(p.bonus);
                            $('#deduction_late').val(p.deduction_late);
                            $('#deduction_absent').val(p.deduction_absent);
                            $('#deduction_loan').val(p.deduction_loan);
                            $('#deduction_bpjs').val(p.deduction_bpjs);
                            $('#deduction_tax').val(p.deduction_tax);
                            $('#deduction_others').val(p.deduction_others);
                            $('#notes').val(p.notes);
                            calculateTotals();
                            $('#payrollModal').modal('show');
                        }
                    })
                    .fail(function() {
                        toastr.error('Gagal memuat data payroll');
                    });
            }

            function sendNotification(id) {
                Swal.fire({
                    title: 'Kirim Slip Gaji?',
                    text: 'Slip gaji akan dikirim ke WhatsApp karyawan',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Kirim!',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#28a745'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post(`/api/payroll/${id}/send`, {
                                _token: '{{ csrf_token() }}'
                            })
                            .done(function(res) {
                                toastr.success(res.message);
                                loadPayrolls(currentPage);
                            })
                            .fail(function(xhr) {
                                toastr.error(xhr.responseJSON?.message || 'Gagal mengirim notifikasi');
                            });
                    }
                });
            }

            function deletePayroll(id) {
                Swal.fire({
                    title: 'Hapus Payroll?',
                    text: 'Data yang dihapus tidak dapat dikembalikan!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#d33'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/api/payroll/${id}`,
                            method: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(res) {
                                toastr.success(res.message);
                                loadPayrolls(currentPage);
                            },
                            error: function(xhr) {
                                toastr.error(xhr.responseJSON?.message ||
                                    'Gagal menghapus payroll');
                            }
                        });
                    }
                });
            }

            function openUploadProofModal(id) {
                console.log('📤 Opening upload proof modal for ID:', id);
                $('#upload_payroll_id').val(id);
                $('#uploadProofForm')[0].reset();
                $('#payment_proof').removeClass('is-invalid');
                $('#payment_proofError').text('');
                $('#proofPreview').hide();
                new bootstrap.Modal(document.getElementById('uploadProofModal')).show();
            }

            // File input preview
            $('#payment_proof').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const fileType = file.type;
                    if (fileType.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            $('#proofPreviewImage').attr('src', e.target.result);
                            $('#proofPreview').show();
                        };
                        reader.readAsDataURL(file);
                    } else {
                        $('#proofPreview').hide();
                    }
                }
            });

            // Upload proof form submission
            $('#uploadProofForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#upload_payroll_id').val();
                const formData = new FormData(this);

                console.log('📤 Uploading proof for payroll ID:', id);

                $.ajax({
                    url: `/api/payroll/${id}/upload-proof`,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        console.log('✅ Proof uploaded:', res);
                        Swal.close();
                        setTimeout(function() {
                            toastr.success(res.message);
                        }, 100);
                        bootstrap.Modal.getInstance(document.getElementById('uploadProofModal'))
                            .hide();
                        loadPayrolls(currentPage);
                    },
                    error: function(xhr) {
                        console.error('❌ Upload failed:', xhr);
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            for (let field in errors) {
                                $(`#${field}`).addClass('is-invalid');
                                $(`#${field}Error`).text(errors[field][0]);
                            }
                        } else {
                            toastr.error(xhr.responseJSON?.message ||
                                'Gagal mengupload bukti transfer');
                        }
                    }
                });
            });

            function viewProof(id) {
                console.log('👁️ Viewing proof for ID:', id);
                $.get(`/api/payroll/${id}`)
                    .done(function(res) {
                        if (res.success && res.data.payment_proof) {
                            const proofPath = res.data.payment_proof;
                            const proofUrl = `/storage/${proofPath}`;
                            const fileExt = proofPath.split('.').pop().toLowerCase();

                            let content = '';
                            if (fileExt === 'pdf') {
                                content =
                                    `<embed src="${proofUrl}" type="application/pdf" width="100%" height="600px" />`;
                            } else {
                                content = `<img src="${proofUrl}" class="img-fluid" alt="Bukti Transfer">`;
                            }

                            $('#proofViewContent').html(content);

                            if (res.data.paid_at) {
                                const paidDate = formatDate(res.data.paid_at);
                                $('#paidAtInfo').html(
                                    `<small><i class='bx bx-time-five'></i> Dibayar pada: ${paidDate}</small>`
                                );
                            }

                            new bootstrap.Modal(document.getElementById('viewProofModal')).show();
                        } else {
                            toastr.error('Bukti transfer tidak ditemukan');
                        }
                    })
                    .fail(function(xhr) {
                        console.error('❌ Error loading proof:', xhr);
                        toastr.error('Gagal memuat bukti transfer');
                    });
            }

            function deleteProof(id) {
                Swal.fire({
                    title: 'Hapus Bukti Transfer?',
                    text: 'Bukti transfer akan dihapus dan status akan kembali ke Terkirim',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#d33'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/api/payroll/${id}/delete-proof`,
                            method: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(res) {
                                Swal.close();
                                setTimeout(function() {
                                    toastr.success(res.message);
                                }, 100);
                                loadPayrolls(currentPage);
                            },
                            error: function(xhr) {
                                toastr.error(xhr.responseJSON?.message ||
                                    'Gagal menghapus bukti transfer');
                            }
                        });
                    }
                });
            }

            function formatCurrency(value) {
                return 'Rp ' + parseFloat(value || 0).toLocaleString('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                });
            }

            function formatDate(date) {
                return new Date(date).toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
            }

            function formatPeriod(period) {
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                let [year, month] = period.split('-');
                return `${months[parseInt(month)-1]} ${year}`;
            }

            function debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func(...args), wait);
                };
            }
        });
    </script>
@endpush
