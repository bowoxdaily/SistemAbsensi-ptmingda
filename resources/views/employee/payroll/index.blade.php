@extends('layouts.app')

@section('title', 'Slip Gaji')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold">💰 Slip Gaji</h4>
            <button class="btn btn-sm btn-outline-primary" id="btnRefresh">
                <i class='bx bx-refresh'></i> Refresh
            </button>
        </div>

        <!-- Info Banner -->
        <div class="alert alert-info d-flex align-items-center mb-4">
            <i class='bx bx-cloud-download bx-sm me-2'></i>
            <div>Data slip gaji diambil dari sistem HRIS. Anda dapat mengunduh slip gaji dalam format PDF.</div>
        </div>

        <!-- Desktop Table View -->
        <div class="card d-none d-md-block">
            <div class="card-header">
                <h5 class="mb-0"><i class='bx bx-receipt me-1'></i> Daftar Slip Gaji</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Periode</th>
                            <th>Keterangan</th>
                            <th class="text-end">Gaji Bersih</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="payslipTableBody">
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <i class="bx bx-loader bx-spin bx-lg"></i>
                                <p class="mb-0 mt-2">Memuat data slip gaji...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile Card View -->
        <div class="d-md-none" id="mobileView">
            <div class="text-center py-4">
                <i class="bx bx-loader bx-spin bx-lg"></i>
                <p>Memuat data slip gaji...</p>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .btn-download-pdf {
            transition: all 0.2s ease;
        }
        .btn-download-pdf:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(105, 108, 255, 0.3);
        }
        @media (max-width: 767.98px) {
            .container-xxl { padding-left: 1rem; padding-right: 1rem; }
            .card-body { padding: 1rem; }
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            console.log('🚀 Payslip page initialized (HRIS)');

            // Load data on page load
            loadPayslips();

            // Refresh button
            $(document).on('click', '#btnRefresh', function() {
                loadPayslips();
            });

            function loadPayslips() {
                console.log('🌐 Loading payslips from HRIS...');

                $('#payslipTableBody').html(
                    '<tr><td colspan="5" class="text-center py-4"><i class="bx bx-loader bx-spin bx-lg"></i><p class="mb-0 mt-2">Memuat data slip gaji...</p></td></tr>'
                );
                $('#mobileView').html(
                    '<div class="text-center py-4"><i class="bx bx-loader bx-spin bx-lg"></i><p>Memuat data slip gaji...</p></div>'
                );

                $.get('/api/employee/payslip')
                    .done(function(res) {
                        console.log('✅ Payslips loaded:', res);
                        if (res.success) {
                            renderPayslips(res.data);
                        } else {
                            let msg = res.message || 'Gagal memuat data';
                            showError(msg);
                        }
                    })
                    .fail(function(xhr) {
                        console.error('❌ Error loading payslips:', xhr);
                        let msg = xhr.responseJSON?.message || 'Gagal terhubung ke server';
                        showError(msg);
                        toastr.error(msg);
                    });
            }

            function showError(msg) {
                $('#payslipTableBody').html(
                    `<tr><td colspan="5" class="text-center py-4 text-danger"><i class="bx bx-error-circle me-1"></i> ${msg}</td></tr>`
                );
                $('#mobileView').html(`<div class="alert alert-danger"><i class="bx bx-error-circle me-1"></i> ${msg}</div>`);
            }

            function renderPayslips(apiData) {
                // apiData will now be: { employee_id: "...", employee_name: "...", total: 2, payslips: [...] }
                let employeeName = apiData.employee_name || '';
                let employeeId = apiData.employee_id || '';
                let payslips = apiData.payslips || [];
                
                if (!Array.isArray(payslips)) {
                    payslips = [payslips];
                }

                // Show employee info if available
                if (employeeName) {
                    console.log(`👤 Employee: ${employeeName} (${employeeId})`);
                }

                if (payslips.length === 0) {
                    $('#payslipTableBody').html(
                        '<tr><td colspan="5" class="text-center py-4"><i class="bx bx-folder-open bx-lg text-muted"></i><p class="mb-0 mt-2 text-muted">Belum ada data slip gaji</p></td></tr>'
                    );
                    $('#mobileView').html('<div class="alert alert-secondary text-center">Belum ada data slip gaji</div>');
                    return;
                }

                // Desktop table
                let html = '';
                payslips.forEach(function(slip, index) {
                    let month = slip.salary_month || slip.month || slip.period || '-';
                    let netSalary = slip.net_salary || 0;
                    let isPaid = slip.is_paid;
                    let createdAt = slip.created_at || '-';
                    let statusBadge = isPaid === true || isPaid === 'true' || isPaid === 1
                        ? '<span class="badge bg-success">Sudah Dibayar</span>'
                        : '<span class="badge bg-warning">Belum Dibayar</span>';

                    html += `<tr>
                        <td>${index + 1}</td>
                        <td><strong>${formatPeriod(month)}</strong><br><small class="text-muted">${month}</small></td>
                        <td>${statusBadge}<br><small class="text-muted">${createdAt}</small></td>
                        <td class="text-end fw-bold text-primary">${formatCurrency(netSalary)}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-primary btn-download-pdf" data-month="${month}" title="Download PDF">
                                <i class='bx bx-download me-1'></i> PDF
                            </button>
                        </td>
                    </tr>`;
                });
                $('#payslipTableBody').html(html);

                // Mobile cards
                let mobileHtml = '';
                payslips.forEach(function(slip, index) {
                    let month = slip.salary_month || slip.month || slip.period || '-';
                    let netSalary = slip.net_salary || 0;
                    let isPaid = slip.is_paid;
                    let statusBadge = isPaid === true || isPaid === 'true' || isPaid === 1
                        ? '<span class="badge bg-success">Sudah Dibayar</span>'
                        : '<span class="badge bg-warning">Belum Dibayar</span>';

                    mobileHtml += `<div class="card mb-2">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong>${formatPeriod(month)}</strong>
                                    <br>${statusBadge}
                                </div>
                                <span class="badge bg-label-primary">#${index + 1}</span>
                            </div>
                            <div class="fw-bold text-primary mb-2">${formatCurrency(netSalary)}</div>
                            <button class="btn btn-sm btn-primary btn-download-pdf w-100" data-month="${month}">
                                <i class='bx bx-download me-1'></i> Download PDF
                            </button>
                        </div>
                    </div>`;
                });
                $('#mobileView').html(mobileHtml);
            }

            // Download PDF (Native Browser Download)
            $(document).on('click', '.btn-download-pdf', function() {
                let month = $(this).data('month');
                let btn = $(this);
                let originalHtml = btn.html();

                // Ubah state tombol sementara
                btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i> Mengunduh...');
                toastr.info(`Memulai unduhan payslip ${month}...`);

                // Gunakan native browser download karena route ini menggunakan web middleware (session cookie)
                window.location.href = `/api/employee/payslip/download?month=${month}`;

                // Kembalikan state tombol setelah beberapa detik (asumsi download sudah di-handle browser/IDM)
                setTimeout(function() {
                    btn.prop('disabled', false).html(originalHtml);
                }, 3000);
            });

            function formatPeriod(period) {
                if (!period || period === '-') return '-';
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                let parts = period.split('-');
                if (parts.length === 2) {
                    let year = parts[0];
                    let month = parseInt(parts[1]) - 1;
                    if (month >= 0 && month < 12) {
                        return `${months[month]} ${year}`;
                    }
                }
                return period;
            }

            function formatCurrency(value) {
                return 'Rp ' + parseFloat(value || 0).toLocaleString('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                });
            }
        });
    </script>
@endpush
