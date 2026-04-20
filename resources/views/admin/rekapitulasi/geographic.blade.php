@extends('layouts.app')

@section('title', 'Rekap Karyawan Berdasarkan Wilayah')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
@endpush

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">
                <span class="text-muted fw-light">Laporan /</span> Rekap Karyawan Berdasarkan Wilayah
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
                                <i class='bx bx-map-pin bx-sm text-success'></i>
                            </div>
                        </div>
                        <span class="fw-semibold d-block mb-1">Total Wilayah</span>
                        <h3 class="card-title mb-2" id="total-wilayah">0</h3>
                        <small class="text-muted">Kelompok lokasi</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between">
                            <div class="avatar flex-shrink-0">
                                <i class='bx bx-check-circle bx-sm text-info'></i>
                            </div>
                        </div>
                        <span class="fw-semibold d-block mb-1">Karyawan Aktif</span>
                        <h3 class="card-title mb-2" id="total-active">0</h3>
                        <small class="text-muted">Status aktif</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between">
                            <div class="avatar flex-shrink-0">
                                <i class='bx bx-trending-up bx-sm text-danger'></i>
                            </div>
                        </div>
                        <span class="fw-semibold d-block mb-1">Karyawan Resign</span>
                        <h3 class="card-title mb-2" id="total-resign">0</h3>
                        <small class="text-muted">Status resign</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Rekap Karyawan Berdasarkan Wilayah</h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-success" id="exportExcel">
                        <i class='bx bxs-file-export'></i> Excel
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" id="btnRefresh">
                        <i class='bx bx-refresh'></i> Refresh
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Kelompok Berdasarkan</label>
                        <select class="form-select" id="filter-group-level">
                            <option value="provinsi">Provinsi</option>
                            <option value="kabupaten" selected>Kabupaten/Kota</option>
                            <option value="kecamatan">Kecamatan</option>
                            <option value="desa">Desa/Kelurahan</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Provinsi</label>
                        <select class="form-select select2-single" id="filter-province" data-placeholder="Pilih Provinsi">
                            <option></option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Kabupaten/Kota</label>
                        <select class="form-select select2-single" id="filter-kabupaten" data-placeholder="Pilih Kabupaten">
                            <option></option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Kecamatan</label>
                        <select class="form-select select2-single" id="filter-kecamatan" data-placeholder="Pilih Kecamatan">
                            <option></option>
                        </select>
                    </div>
                </div>

                <!-- Chart Visualization -->
                <div class="row mb-4">
                    <div class="col-lg-6 mb-3">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Distribusi Karyawan per Wilayah</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="distributionChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Status Karyawan per Wilayah</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="statusChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="table-responsive">
                    <table class="table table-hover" id="rekapTable">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Wilayah</th>
                                <th>Total Karyawan</th>
                                <th>Aktif</th>
                                <th>Tidak Aktif</th>
                                <th>Resign</th>
                                <th>Departemen</th>
                                <th>Posisi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <span class="spinner-border spinner-border-sm me-2"></span>
                                    Memuat data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Collapsible Employee List Modal -->
        <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="detailModalLabel">Detail Karyawan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="detailContent" style="max-height: 600px; overflow-y: auto;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

        <script>
            let allData = [];
            let locationsData = {};
            let distributionChart = null;
            let statusChart = null;

            $(document).ready(function() {
                initializeSelects();
                loadData();

                $('#filter-group-level').on('change', loadData);
                $('#filter-province').on('change', loadData);
                $('#filter-kabupaten').on('change', loadData);
                $('#filter-kecamatan').on('change', loadData);
                $('#btnRefresh').on('click', loadData);
                $('#exportExcel').on('click', exportToExcel);
            });

            function initializeSelects() {
                $('.select2-single').select2({
                    theme: 'bootstrap-5',
                    allowClear: true,
                    width: '100%'
                });
            }

            function loadData() {
                const groupLevel = $('#filter-group-level').val();
                const province = $('#filter-province').val();
                const kabupaten = $('#filter-kabupaten').val();
                const kecamatan = $('#filter-kecamatan').val();

                $.ajax({
                    url: '/api/admin/rekapitulasi/geographic-data',
                    method: 'GET',
                    data: {
                        group_level: groupLevel,
                        province: province,
                        kabupaten: kabupaten,
                        kecamatan: kecamatan,
                    },
                    success: function(response) {
                        if (response.success) {
                            allData = response.data;
                            locationsData = response.locations;

                            // Update filters
                            updateFilterOptions(response.locations);

                            // Update summary
                            updateSummary(response.summary);

                            // Update charts
                            updateCharts(response.data);

                            // Update table
                            updateTable(response.data);
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Gagal memuat data', 'error');
                        console.error(xhr);
                    }
                });
            }

            function updateFilterOptions(locations) {
                // Update Province filter
                const provinceSelect = $('#filter-province');
                const currentProvince = provinceSelect.val();
                provinceSelect.empty().append('<option></option>');
                locations.provinces.forEach(prov => {
                    provinceSelect.append(`<option value="${prov}">${prov}</option>`);
                });
                if (currentProvince) {
                    provinceSelect.val(currentProvince).trigger('change.select2');
                }

                // Update Kabupaten filter
                const kabupatenSelect = $('#filter-kabupaten');
                const currentKabupaten = kabupatenSelect.val();
                kabupatenSelect.empty().append('<option></option>');
                locations.kabupatens.forEach(kab => {
                    kabupatenSelect.append(`<option value="${kab}">${kab}</option>`);
                });
                if (currentKabupaten) {
                    kabupatenSelect.val(currentKabupaten).trigger('change.select2');
                }

                // Update Kecamatan filter
                const kecamatanSelect = $('#filter-kecamatan');
                const currentKecamatan = kecamatanSelect.val();
                kecamatanSelect.empty().append('<option></option>');
                locations.kecamatans.forEach(kec => {
                    kecamatanSelect.append(`<option value="${kec}">${kec}</option>`);
                });
                if (currentKecamatan) {
                    kecamatanSelect.val(currentKecamatan).trigger('change.select2');
                }
            }

            function updateSummary(summary) {
                $('#total-karyawan').text(summary.total_karyawan);
                $('#total-wilayah').text(summary.group_count);
                $('#total-active').text(summary.total_active);
                $('#total-resign').text(summary.total_resign);
            }

            function updateCharts(data) {
                // Prepare chart data
                const labels = data.map(item => item.location);
                const activeData = data.map(item => item.active_count);
                const inactiveData = data.map(item => item.inactive_count);
                const resignData = data.map(item => item.resign_count);
                const totalData = data.map(item => item.total_karyawan);

                // Generate colors for locations
                const colors = generateColors(labels.length);

                // Destroy existing charts if they exist
                if (distributionChart) {
                    distributionChart.destroy();
                }
                if (statusChart) {
                    statusChart.destroy();
                }

                // Distribution Chart (Bar with aligned Data Labels)
                const distCtx = document.getElementById('distributionChart').getContext('2d');
                distributionChart = new Chart(distCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Total Karyawan',
                            data: totalData,
                            backgroundColor: colors,
                            borderColor: colors.map(c => adjustBrightness(c, -20)),
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'x',
                        layout: {
                            padding: {
                                top: 22
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    afterLabel: function(context) {
                                        const index = context.dataIndex;
                                        const item = data[index];
                                        return `Aktif: ${item.active_count}, Tidak Aktif: ${item.inactive_count}, Resign: ${item.resign_count}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grace: '15%',
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    },
                    plugins: [{
                        afterDraw: function(chart) {
                            const ctx = chart.ctx;
                            chart.data.datasets.forEach((dataset, i) => {
                                const meta = chart.getDatasetMeta(i);
                                meta.data.forEach((bar, index) => {
                                    const value = dataset.data[index];
                                    if (!value) return;

                                    const x = bar.x;
                                    const y = bar.y;

                                    ctx.fillStyle = '#333';
                                    ctx.font = 'bold 12px Arial';
                                    ctx.textAlign = 'center';
                                    ctx.textBaseline = 'bottom';
                                    ctx.fillText(value, x, y - 8);
                                });
                            });
                        }
                    }]
                });

                // Status Chart (Stacked Bar with aligned Data Labels)
                const statusCtx = document.getElementById('statusChart').getContext('2d');
                statusChart = new Chart(statusCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Aktif',
                                data: activeData,
                                backgroundColor: 'rgba(75, 192, 75, 0.8)',
                                borderColor: 'rgba(75, 192, 75, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Tidak Aktif',
                                data: inactiveData,
                                backgroundColor: 'rgba(255, 193, 7, 0.8)',
                                borderColor: 'rgba(255, 193, 7, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Resign',
                                data: resignData,
                                backgroundColor: 'rgba(244, 67, 54, 0.8)',
                                borderColor: 'rgba(244, 67, 54, 1)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'x',
                        plugins: {
                            legend: {
                                position: 'bottom',
                            }
                        },
                        scales: {
                            x: {
                                stacked: true,
                                ticks: {
                                    stepSize: 1
                                }
                            },
                            y: {
                                stacked: true,
                                beginAtZero: true
                            }
                        }
                    },
                    plugins: [{
                        afterDraw: function(chart) {
                            const ctx = chart.ctx;

                            // Draw value labels per segment with adaptive contrast and position.
                            chart.data.datasets.forEach((dataset, i) => {
                                const meta = chart.getDatasetMeta(i);
                                meta.data.forEach((bar, index) => {
                                    const value = dataset.data[index];
                                    if (value === 0) return;

                                    const x = bar.x;
                                    const y = bar.y;
                                    const segmentHeight = Math.abs(bar.base - bar.y);

                                    ctx.font = 'bold 11px Arial';
                                    ctx.textAlign = 'center';

                                    if (segmentHeight < 16) {
                                        ctx.fillStyle = '#1f2937';
                                        ctx.textBaseline = 'bottom';
                                        ctx.fillText(value, x, y - 2);
                                    } else {
                                        ctx.fillStyle = i === 1 ? '#111' : '#fff';
                                        ctx.textBaseline = 'middle';
                                        ctx.fillText(value, x, y + segmentHeight / 2);
                                    }
                                });
                            });

                            // Draw total stacked value on top of each location bar.
                            const totals = chart.data.labels.map((_, index) => {
                                return chart.data.datasets.reduce((sum, dataset) => {
                                    return sum + (Number(dataset.data[index]) || 0);
                                }, 0);
                            });

                            totals.forEach((total, index) => {
                                if (!total) return;

                                let topY = Number.POSITIVE_INFINITY;
                                chart.data.datasets.forEach((_, datasetIndex) => {
                                    const element = chart.getDatasetMeta(datasetIndex).data[index];
                                    if (element) {
                                        topY = Math.min(topY, element.y);
                                    }
                                });

                                if (Number.isFinite(topY)) {
                                    const x = chart.getDatasetMeta(0).data[index]?.x;
                                    if (x !== undefined) {
                                        ctx.fillStyle = '#111';
                                        ctx.font = 'bold 12px Arial';
                                        ctx.textAlign = 'center';
                                        ctx.textBaseline = 'bottom';
                                        ctx.fillText(total, x, topY - 6);
                                    }
                                }
                            });
                        }
                    }]
                });
            }

            function updateTable(data) {
                const tbody = $('#tableBody');
                tbody.empty();

                if (data.length === 0) {
                    tbody.append(`
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class='bx bx-inbox'></i> Tidak ada data
                            </td>
                        </tr>
                    `);
                    return;
                }

                data.forEach((item, index) => {
                    const deptList = Object.entries(item.departments)
                        .map(([dept, count]) => `${dept} (${count})`)
                        .join(', ');
                    const posList = Object.entries(item.positions)
                        .map(([pos, count]) => `${pos} (${count})`)
                        .join(', ');

                    const groupLevel = $('#filter-group-level').val();

                    tbody.append(`
                        <tr>
                            <td>${index + 1}</td>
                            <td>
                                <strong>${item.location}</strong>
                            </td>
                            <td><span class="badge bg-primary">${item.total_karyawan}</span></td>
                            <td><span class="badge bg-success">${item.active_count}</span></td>
                            <td><span class="badge bg-warning">${item.inactive_count}</span></td>
                            <td><span class="badge bg-danger">${item.resign_count}</span></td>
                            <td><small>${deptList || '-'}</small></td>
                            <td><small>${posList || '-'}</small></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info" onclick="showDetails('${item.location}', '${groupLevel}')">
                                    <i class='bx bx-show'></i> Detail
                                </button>
                            </td>
                        </tr>
                    `);
                });
            }

            function showDetails(location, groupLevel) {
                // Show loading state
                const modal = new bootstrap.Modal(document.getElementById('detailModal'));
                document.getElementById('detailModalLabel').textContent = 'Detail Karyawan - ' + location;
                document.getElementById('detailContent').innerHTML = `
                    <div class="text-center py-4">
                        <span class="spinner-border spinner-border-sm me-2"></span>
                        Memuat detail karyawan...
                    </div>
                `;
                modal.show();

                // Fetch employee details from API
                $.ajax({
                    url: '/api/admin/rekapitulasi/geographic-location-detail',
                    method: 'GET',
                    data: {
                        location: location,
                        group_level: groupLevel,
                    },
                    success: function(response) {
                        if (response.success) {
                            renderEmployeeDetails(response.employees);
                        } else {
                            document.getElementById('detailContent').innerHTML = `
                                <div class="alert alert-danger" role="alert">
                                    Error: ${response.message}
                                </div>
                            `;
                        }
                    },
                    error: function(xhr) {
                        document.getElementById('detailContent').innerHTML = `
                            <div class="alert alert-danger" role="alert">
                                Gagal memuat detail karyawan
                            </div>
                        `;
                        console.error(xhr);
                    }
                });
            }

            function renderEmployeeDetails(employees) {
                if (!employees || employees.length === 0) {
                    document.getElementById('detailContent').innerHTML = `
                        <div class="alert alert-info" role="alert">
                            Tidak ada data karyawan untuk wilayah ini
                        </div>
                    `;
                    return;
                }

                let html = '<table class="table table-sm"><thead class="table-light"><tr>' +
                    '<th>Kode</th><th>Nama</th><th>Departemen</th><th>Posisi</th>' +
                    '<th>Bergabung</th><th>Lokasi</th></tr></thead><tbody>';

                employees.forEach(emp => {
                    const location = `${emp.desa ? emp.desa + ', ' : ''}${emp.kecamatan ? emp.kecamatan + ', ' : ''}${emp.kabupaten || ''} (${emp.province || ''})`;
                    html += `<tr>
                        <td><small><strong>${emp.code}</strong></small></td>
                        <td><small>${emp.name}</small></td>
                        <td><small>${emp.department}</small></td>
                        <td><small>${emp.position}</small></td>
                        <td><small>${emp.join_date || '-'}</small></td>
                        <td><small>${location}</small></td>
                    </tr>`;
                });

                html += '</tbody></table>';
                document.getElementById('detailContent').innerHTML = html;
            }

            function exportToExcel() {
                const groupLevel = $('#filter-group-level').val();
                const province = $('#filter-province').val();
                const kabupaten = $('#filter-kabupaten').val();
                const kecamatan = $('#filter-kecamatan').val();

                const params = new URLSearchParams({
                    group_level: groupLevel,
                    province: province || '',
                    kabupaten: kabupaten || '',
                    kecamatan: kecamatan || ''
                });

                window.location.href = `/api/admin/rekapitulasi/geographic-export-excel?${params.toString()}`;
            }

            /**
             * Generate random colors for chart
             */
            function generateColors(count) {
                const baseColors = [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(75, 192, 75, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 159, 64, 0.8)',
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(201, 203, 207, 0.8)',
                ];

                const colors = [];
                for (let i = 0; i < count; i++) {
                    colors.push(baseColors[i % baseColors.length]);
                }
                return colors;
            }

            /**
             * Adjust color brightness
             */
            function adjustBrightness(color, percent) {
                const rgba = color.match(/\d+/g);
                if (!rgba) return color;
                
                const r = Math.min(255, Math.max(0, parseInt(rgba[0]) + percent));
                const g = Math.min(255, Math.max(0, parseInt(rgba[1]) + percent));
                const b = Math.min(255, Math.max(0, parseInt(rgba[2]) + percent));
                
                return `rgba(${r}, ${g}, ${b}, 1)`;
            }
        </script>
    @endpush
@endsection
