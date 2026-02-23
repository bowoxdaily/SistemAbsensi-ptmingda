@extends('layouts.app')

@section('title', 'Rekapitulasi Absensi')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
@endpush

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">
                <span class="text-muted fw-light">Laporan /</span> Rekapitulasi Absensi
            </h4>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4" id="summary-cards">
            <div class="col-lg-3 col-md-6 col-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between">
                            <div class="avatar flex-shrink-0">
                                <i class='bx bx-group bx-sm text-primary'></i>
                            </div>
                        </div>
                        <span class="fw-semibold d-block mb-1">Total Karyawan</span>
                        <h3 class="card-title mb-2" id="total-karyawan">0</h3>
                        <small class="text-muted">Karyawan aktif</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between">
                            <div class="avatar flex-shrink-0">
                                <i class='bx bx-check-circle bx-sm text-success'></i>
                            </div>
                        </div>
                        <span class="fw-semibold d-block mb-1">Total Hadir</span>
                        <h3 class="card-title mb-2" id="total-hadir">0</h3>
                        <small class="text-muted">Keseluruhan hari hadir</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between">
                            <div class="avatar flex-shrink-0">
                                <i class='bx bx-x-circle bx-sm text-danger'></i>
                            </div>
                        </div>
                        <span class="fw-semibold d-block mb-1">Total Alpha</span>
                        <h3 class="card-title mb-2" id="total-alpha">0</h3>
                        <small class="text-muted">Keseluruhan hari alpha</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between">
                            <div class="avatar flex-shrink-0">
                                <i class='bx bx-trending-up bx-sm text-info'></i>
                            </div>
                        </div>
                        <span class="fw-semibold d-block mb-1">Rata-rata Kehadiran</span>
                        <h3 class="card-title mb-2" id="avg-attendance">0%</h3>
                        <small class="text-muted">Persentase kehadiran</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Rekapitulasi Absensi</h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-success" id="exportExcel">
                        <i class='bx bxs-file-export'></i> Excel
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" id="exportPdf">
                        <i class='bx bxs-printer'></i> Print / PDF
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-2">
                        <label class="form-label">Periode</label>
                        <select class="form-select" id="filter-period-type">
                            <option value="monthly" selected>Bulanan</option>
                            <option value="quarterly">Per 3 Bulan (Kuartal)</option>
                            <option value="range">Range Bulan</option>
                        </select>
                    </div>
                    <div class="col-md-2" id="filter-month-container">
                        <label class="form-label">Bulan</label>
                        <select class="form-select" id="filter-month">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $m == now()->month ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2" id="filter-quarter-container" style="display: none;">
                        <label class="form-label">Kuartal</label>
                        <select class="form-select" id="filter-quarter">
                            @php
                                $currentQuarter = ceil(now()->month / 3);
                            @endphp
                            <option value="1" {{ $currentQuarter == 1 ? 'selected' : '' }}>Q1 (Jan - Mar)</option>
                            <option value="2" {{ $currentQuarter == 2 ? 'selected' : '' }}>Q2 (Apr - Jun)</option>
                            <option value="3" {{ $currentQuarter == 3 ? 'selected' : '' }}>Q3 (Jul - Sep)</option>
                            <option value="4" {{ $currentQuarter == 4 ? 'selected' : '' }}>Q4 (Okt - Des)</option>
                        </select>
                    </div>
                    <div class="col-md-2" id="filter-range-from-month-container" style="display: none;">
                        <label class="form-label">Dari Bulan</label>
                        <select class="form-select" id="filter-range-from-month">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $m == 12 ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2" id="filter-range-from-year-container" style="display: none;">
                        <label class="form-label">Dari Tahun</label>
                        <select class="form-select" id="filter-range-from-year">
                            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                                <option value="{{ $y }}" {{ $y == now()->year - 1 ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2" id="filter-range-to-month-container" style="display: none;">
                        <label class="form-label">Sampai Bulan</label>
                        <select class="form-select" id="filter-range-to-month">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $m == 3 ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2" id="filter-range-to-year-container" style="display: none;">
                        <label class="form-label">Sampai Tahun</label>
                        <select class="form-select" id="filter-range-to-year">
                            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                                <option value="{{ $y }}" {{ $y == now()->year ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2" id="filter-year-container">
                        <label class="form-label">Tahun</label>
                        <select class="form-select" id="filter-year">
                            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                                <option value="{{ $y }}" {{ $y == now()->year ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Department</label>
                        <select class="form-select" id="filter-department">
                            <option value="">Semua Department</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jabatan</label>
                        <select class="form-select" id="filter-position">
                            <option value="">Semua Jabatan</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Karyawan</label>
                        <select class="form-select" id="filter-employee">
                            <option value="">Semua Karyawan</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Bergabung (Dari)</label>
                        <input type="date" class="form-control" id="filter-join-from" placeholder="Dari tanggal...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Bergabung (Sampai)</label>
                        <input type="date" class="form-control" id="filter-join-to" placeholder="Sampai tanggal...">
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-hover table-sm" id="rekapitulasi-table">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama Karyawan</th>
                                <th>Department</th>
                                <th>Jabatan</th>
                                <th class="text-center">Hadir</th>
                                <th class="text-center">Terlambat</th>
                                <th class="text-center">Izin</th>
                                <th class="text-center">Sakit</th>
                                <th class="text-center">Cuti</th>
                                <th class="text-center">Alpha</th>
                                <th class="text-center">Total Masuk</th>
                                <th class="text-center">Hari Kerja</th>
                                <th class="text-center">%</th>
                            </tr>
                        </thead>
                        <tbody id="rekapitulasi-tbody">
                            <tr>
                                <td colspan="14" class="text-center text-muted">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            let currentData = [];

            // Load filter options
            loadFilterOptions();

            // Load initial data
            loadData();

            // Period type change handler (toggle between month, quarter, and range)
            $('#filter-period-type').on('change', function() {
                const periodType = $(this).val();
                // Hide all period-specific filters first
                $('#filter-month-container, #filter-quarter-container, #filter-year-container').hide();
                $('#filter-range-from-month-container, #filter-range-from-year-container').hide();
                $('#filter-range-to-month-container, #filter-range-to-year-container').hide();
                
                // Show appropriate filters based on selection
                if (periodType === 'quarterly') {
                    $('#filter-quarter-container').show();
                    $('#filter-year-container').show();
                } else if (periodType === 'range') {
                    $('#filter-range-from-month-container').show();
                    $('#filter-range-from-year-container').show();
                    $('#filter-range-to-month-container').show();
                    $('#filter-range-to-year-container').show();
                } else {
                    // monthly
                    $('#filter-month-container').show();
                    $('#filter-year-container').show();
                }
                loadData();
            });

            // Filter change handlers
            $('#filter-month, #filter-quarter, #filter-year, #filter-department, #filter-position').on('change', function() {
                console.log('Filter changed:', $(this).attr('id'), '=', $(this).val());
                loadData();
            });

            // Range filters change handlers
            $('#filter-range-from-month, #filter-range-from-year, #filter-range-to-month, #filter-range-to-year').on('change', function() {
                console.log('Range filter changed:', $(this).attr('id'), '=', $(this).val());
                loadData();
            });

            // Join date filter change handlers
            $('#filter-join-from, #filter-join-to').on('change', function() {
                console.log('Join date filter changed:', $(this).attr('id'), '=', $(this).val());
                loadData();
            });

            // Select2 change handler (khusus untuk employee filter)
            $('#filter-employee').on('select2:select select2:clear', function() {
                console.log('Employee filter changed:', $(this).val());
                loadData();
            });

            // Export buttons
            $('#exportExcel').on('click', function() {
                exportData('excel');
            });

            $('#exportPdf').on('click', function() {
                exportData('pdf');
            });

            // Load filter options
            function loadFilterOptions() {
                $.ajax({
                    url: '/api/admin/rekapitulasi/filter-options',
                    method: 'GET',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    success: function(response) {
                        console.log('Filter Options Response:', response);
                        if (response.success) {
                            // Populate departments
                            response.departments.forEach(dept => {
                                $('#filter-department').append(
                                    `<option value="${dept.id}">${dept.name}</option>`
                                );
                            });

                            // Populate positions
                            response.positions.forEach(pos => {
                                $('#filter-position').append(
                                    `<option value="${pos.id}">${pos.name}</option>`
                                );
                            });

                            // Populate employees
                            response.employees.forEach(emp => {
                                $('#filter-employee').append(
                                    `<option value="${emp.id}">${emp.employee_code} - ${emp.name}</option>`
                                );
                            });

                            // Initialize Select2 after populating options
                            $('#filter-employee').select2({
                                theme: 'bootstrap-5',
                                placeholder: 'Pilih Karyawan...',
                                allowClear: true,
                                width: '100%'
                            });
                        }
                    },
                    error: function(xhr) {
                        console.error('Filter Options Error:', xhr.responseJSON || xhr.responseText);
                    }
                });
            }

            // Load rekapitulasi data
            function loadData() {
                const filters = {
                    period_type: $('#filter-period-type').val(),
                    month: $('#filter-month').val(),
                    quarter: $('#filter-quarter').val(),
                    year: $('#filter-year').val(),
                    range_from_month: $('#filter-range-from-month').val(),
                    range_from_year: $('#filter-range-from-year').val(),
                    range_to_month: $('#filter-range-to-month').val(),
                    range_to_year: $('#filter-range-to-year').val(),
                    department_id: $('#filter-department').val(),
                    position_id: $('#filter-position').val(),
                    employee_id: $('#filter-employee').val(),
                    join_date_from: $('#filter-join-from').val(),
                    join_date_to: $('#filter-join-to').val(),
                };

                console.log('Loading data with filters:', filters);

                $.ajax({
                    url: '/api/admin/rekapitulasi/data',
                    method: 'GET',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    data: filters,
                    success: function(response) {
                        console.log('API Response:', response);
                        if (response.success) {
                            currentData = response.data;
                            renderTable(response.data);
                            updateSummary(response.summary);
                        } else {
                            console.error('API returned success=false');
                        }
                    },
                    error: function(xhr) {
                        console.error('API Error:', xhr.responseJSON || xhr.responseText);
                        Swal.fire('Error', 'Gagal memuat data rekapitulasi', 'error');
                    }
                });
            }

            // Render table
            function renderTable(data) {
                console.log('Rendering table with', data.length, 'rows');
                
                // Destroy existing DataTable first
                if ($.fn.dataTable && $.fn.dataTable.isDataTable('#rekapitulasi-table')) {
                    console.log('Destroying existing DataTable');
                    $('#rekapitulasi-table').DataTable().destroy();
                }
                
                if (data.length === 0) {
                    $('#rekapitulasi-tbody').html(
                        '<tr><td colspan="14" class="text-center text-muted">Tidak ada data</td></tr>'
                    );
                    return;
                }

                let html = '';
                data.forEach((row, index) => {
                    const percentageClass = row.percentage >= 90 ? 'text-success' : 
                                          row.percentage >= 75 ? 'text-warning' : 'text-danger';
                    
                    html += `
                        <tr>
                            <td>${index + 1}</td>
                            <td><code class="small">${row.employee_code}</code></td>
                            <td>${row.name}</td>
                            <td><small>${row.department}</small></td>
                            <td><small>${row.position}</small></td>
                            <td class="text-center"><span class="badge bg-success">${row.hadir}</span></td>
                            <td class="text-center"><span class="badge bg-warning">${row.terlambat}</span></td>
                            <td class="text-center"><span class="badge bg-info">${row.izin}</span></td>
                            <td class="text-center"><span class="badge bg-primary">${row.sakit}</span></td>
                            <td class="text-center"><span class="badge bg-secondary">${row.cuti}</span></td>
                            <td class="text-center"><span class="badge bg-danger">${row.alpha}</span></td>
                            <td class="text-center fw-semibold">${row.total_present}</td>
                            <td class="text-center">${row.working_days}</td>
                            <td class="text-center fw-bold ${percentageClass}">${row.percentage}%</td>
                        </tr>
                    `;
                });

                console.log('Setting HTML for table body');
                $('#rekapitulasi-tbody').html(html);

                // Initialize new DataTable
                console.log('Initializing new DataTable');
                $('#rekapitulasi-table').DataTable({
                    pageLength: 25,
                    order: [[1, 'asc']], // Sort by Kode (employee_code) ascending
                    language: {
                        search: "Cari:",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                        paginate: {
                            first: "Pertama",
                            last: "Terakhir",
                            next: "Selanjutnya",
                            previous: "Sebelumnya"
                        }
                    }
                });
                console.log('DataTable initialized successfully');
            }

            // Update summary cards
            function updateSummary(summary) {
                console.log('Updating summary with data:', summary);
                $('#total-karyawan').text(summary.total_karyawan || 0);
                $('#total-hadir').text(summary.total_hadir || 0);
                $('#total-alpha').text(summary.total_alpha || 0);
                $('#avg-attendance').text((summary.avg_attendance || 0) + '%');
            }

            // Export data
            function exportData(format) {
                const filters = {
                    period_type: $('#filter-period-type').val(),
                    month: $('#filter-month').val(),
                    quarter: $('#filter-quarter').val(),
                    year: $('#filter-year').val(),
                    range_from_month: $('#filter-range-from-month').val(),
                    range_from_year: $('#filter-range-from-year').val(),
                    range_to_month: $('#filter-range-to-month').val(),
                    range_to_year: $('#filter-range-to-year').val(),
                    department_id: $('#filter-department').val(),
                    position_id: $('#filter-position').val(),
                    employee_id: $('#filter-employee').val(),
                    join_date_from: $('#filter-join-from').val(),
                    join_date_to: $('#filter-join-to').val(),
                };

                const queryString = $.param(filters);
                
                if (format === 'excel') {
                    window.location.href = `/admin/rekapitulasi/export-excel?${queryString}`;
                } else {
                    // Open printable view in new window
                    window.open(`/admin/rekapitulasi/export-pdf?${queryString}`, '_blank');
                }
            }
        });
    </script>
@endpush
