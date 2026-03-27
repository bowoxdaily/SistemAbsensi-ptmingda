@extends('layouts.app')

@section('title', 'Analytics Dashboard')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h4 class="mb-1">
                <i class="bx bxs-bar-chart-alt-2 me-2"></i> Analytics Dashboard
            </h4>
            <p class="text-muted mb-0">Real-time attendance insights and performance metrics</p>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-primary" id="btnExportReport">
                <i class="bx bxs-download me-2"></i> Export Report
            </button>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="startDate" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" id="endDate" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-info w-100" id="btnApplyFilter">
                                <i class="bx bx-filter-alt me-2"></i> Apply Filter
                            </button>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-outline-secondary w-100" id="btnResetFilter">
                                <i class="bx bx-reset me-2"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="row mb-4" id="overviewCards">
        <div class="col-md-2">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="bx bxs-check-circle text-success" style="font-size: 2rem;"></i>
                    <p class="text-muted small mt-2">Present</p>
                    <h4 class="mb-0" id="card-hadir">-</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="bx bxs-time text-warning" style="font-size: 2rem;"></i>
                    <p class="text-muted small mt-2">Late</p>
                    <h4 class="mb-0" id="card-terlambat">-</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="bx bxs-info-circle text-info" style="font-size: 2rem;"></i>
                    <p class="text-muted small mt-2">Permission</p>
                    <h4 class="mb-0" id="card-izin">-</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="bx bxs-health text-danger" style="font-size: 2rem;"></i>
                    <p class="text-muted small mt-2">Sick Leave</p>
                    <h4 class="mb-0" id="card-sakit">-</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="bx bxs-x-circle text-dark" style="font-size: 2rem;"></i>
                    <p class="text-muted small mt-2">Absent</p>
                    <h4 class="mb-0" id="card-alpha">-</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="bx bxs-calendar-check text-purple" style="font-size: 2rem;"></i>
                    <p class="text-muted small mt-2">Leave</p>
                    <h4 class="mb-0" id="card-cuti">-</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Attendance Rate</p>
                            <h4 class="mb-0" id="metric-present-rate">-</h4>
                        </div>
                        <span class="badge bg-success">
                            <i class="bx bx-trending-up"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">On-Time Rate</p>
                            <h4 class="mb-0" id="metric-on-time-rate">-</h4>
                        </div>
                        <span class="badge bg-info">
                            <i class="bx bx-check"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Absence Rate</p>
                            <h4 class="mb-0" id="metric-absence-rate">-</h4>
                        </div>
                        <span class="badge bg-danger">
                            <i class="bx bx-x"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Total Overtime</p>
                            <h4 class="mb-0" id="metric-total-ot">-</h4>
                        </div>
                        <span class="badge bg-warning">
                            <i class="bx bx-time-five"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Attendance Trend</h5>
                    <small class="text-muted">Last 30 days</small>
                </div>
                <div class="card-body">
                    <canvas id="chartAttendanceTrend" height="250" width="800"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Status Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartStatusDist" height="250" width="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top Late Employees</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartTopLate" height="250" width="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top Absent Employees</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartTopAbsent" height="250" width="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Performance -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Attendance by Department</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="tableDepartment">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th class="text-center">Present</th>
                                    <th class="text-center">Late</th>
                                    <th class="text-center">Permission</th>
                                    <th class="text-center">Sick Leave</th>
                                    <th class="text-center">Absent</th>
                                    <th class="text-center">Leave</th>
                                    <th class="text-center">Total</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Supervisor Performance -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Supervisor Performance</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="tableSupervisor">
                            <thead>
                                <tr>
                                    <th>Supervisor</th>
                                    <th class="text-center">Employees</th>
                                    <th class="text-center">Present Rate</th>
                                    <th class="text-center">On-Time</th>
                                    <th class="text-center">Late</th>
                                    <th class="text-center">Absent</th>
                                    <th class="text-center">Sick</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    const APP_URL = '{{ config('app.url') }}';
    const CSRF_TOKEN = '{{ csrf_token() }}';

    // Set default dates (current month)
    function setDefaultDates() {
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        
        document.getElementById('startDate').value = firstDay.toISOString().split('T')[0];
        document.getElementById('endDate').value = today.toISOString().split('T')[0];
    }

    // Get current date range
    function getDateRange() {
        return {
            start_date: document.getElementById('startDate').value,
            end_date: document.getElementById('endDate').value,
        };
    }

    // Load Overview Data
    async function loadOverview() {
        try {
            const params = getDateRange();
            const response = await fetch(`${APP_URL}/api/analytics/overview?${new URLSearchParams(params)}`);
            const result = await response.json();

            if (result.success) {
                const data = result.data;
                document.getElementById('card-hadir').textContent = data.hadir;
                document.getElementById('card-terlambat').textContent = data.terlambat;
                document.getElementById('card-izin').textContent = data.izin;
                document.getElementById('card-sakit').textContent = data.sakit;
                document.getElementById('card-alpha').textContent = data.alpha;
                document.getElementById('card-cuti').textContent = data.cuti;

                // Update status distribution chart
                updateStatusDistChart(data);
            }
        } catch (error) {
            console.error('Error loading overview:', error);
        }
    }

    // Load Attendance Rate
    async function loadAttendanceRate() {
        try {
            const params = getDateRange();
            const response = await fetch(`${APP_URL}/api/analytics/attendance-rate?${new URLSearchParams(params)}`);
            const result = await response.json();

            if (result.success) {
                const data = result.data;
                document.getElementById('metric-present-rate').textContent = data.present_rate + '%';
                document.getElementById('metric-on-time-rate').textContent = data.on_time_rate + '%';
                document.getElementById('metric-absence-rate').textContent = data.absence_rate + '%';
            }
        } catch (error) {
            console.error('Error loading attendance rate:', error);
        }
    }

    // Load Overtime Stats
    async function loadOvertimeStats() {
        try {
            const params = getDateRange();
            const response = await fetch(`${APP_URL}/api/analytics/overtime?${new URLSearchParams(params)}`);
            const result = await response.json();

            if (result.success) {
                const data = result.data;
                document.getElementById('metric-total-ot').textContent = data.total_ot_hours + ' hrs';
            }
        } catch (error) {
            console.error('Error loading overtime stats:', error);
        }
    }

    // Load Attendance Trend Chart
    async function loadAttendanceTrend() {
        try {
            const params = getDateRange();
            const response = await fetch(`${APP_URL}/api/analytics/trend?${new URLSearchParams(params)}`);
            const result = await response.json();

            if (result.success) {
                updateTrendChart(result.data);
            }
        } catch (error) {
            console.error('Error loading trend:', error);
        }
    }

    // Load Top Late Employees
    async function loadTopLateEmployees() {
        try {
            const params = new URLSearchParams({
                ...getDateRange(),
                limit: 10,
            });
            const response = await fetch(`${APP_URL}/api/analytics/top-late-employees?${params}`);
            const result = await response.json();

            if (result.success) {
                updateTopLateChart(result.data);
            }
        } catch (error) {
            console.error('Error loading top late:', error);
        }
    }

    // Load Top Absent Employees
    async function loadTopAbsentEmployees() {
        try {
            const params = new URLSearchParams({
                ...getDateRange(),
                limit: 10,
            });
            const response = await fetch(`${APP_URL}/api/analytics/top-absent-employees?${params}`);
            const result = await response.json();

            if (result.success) {
                updateTopAbsentChart(result.data);
            }
        } catch (error) {
            console.error('Error loading top absent:', error);
        }
    }

    // Load Department Performance
    async function loadDepartmentPerformance() {
        try {
            const params = getDateRange();
            const response = await fetch(`${APP_URL}/api/analytics/by-department?${new URLSearchParams(params)}`);
            const result = await response.json();

            if (result.success) {
                updateDepartmentTable(result.data);
            }
        } catch (error) {
            console.error('Error loading department:', error);
        }
    }

    // Load Supervisor Performance
    async function loadSupervisorPerformance() {
        try {
            const params = getDateRange();
            const response = await fetch(`${APP_URL}/api/analytics/supervisor-performance?${new URLSearchParams(params)}`);
            const result = await response.json();

            if (result.success) {
                updateSupervisorTable(result.data);
            }
        } catch (error) {
            console.error('Error loading supervisor perf:', error);
        }
    }

    // Chart variables
    let chartTrend, chartStatusDist, chartTopLate, chartTopAbsent;

    // Update Trend Chart
    function updateTrendChart(data) {
        const ctx = document.getElementById('chartAttendanceTrend').getContext('2d');
        
        if (chartTrend) chartTrend.destroy();

        chartTrend = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: data.datasets,
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top' },
                },
                scales: {
                    y: { beginAtZero: true },
                },
            },
        });
    }

    // Update Status Distribution Chart
    function updateStatusDistChart(data) {
        const ctx = document.getElementById('chartStatusDist').getContext('2d');
        
        if (chartStatusDist) chartStatusDist.destroy();

        chartStatusDist = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Late', 'Permission', 'Sick', 'Absent', 'Leave'],
                datasets: [{
                    data: [
                        data.hadir,
                        data.terlambat,
                        data.izin,
                        data.sakit,
                        data.alpha,
                        data.cuti,
                    ],
                    backgroundColor: [
                        '#10B981', // green
                        '#F59E0B', // amber
                        '#3B82F6', // blue
                        '#F87171', // red
                        '#1F2937', // gray
                        '#8B5CF6', // purple
                    ],
                }],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                },
            },
        });
    }

    // Update Top Late Chart
    function updateTopLateChart(employees) {
        const ctx = document.getElementById('chartTopLate').getContext('2d');
        
        if (chartTopLate) chartTopLate.destroy();

        const names = employees.map(e => e.employee_name);
        const counts = employees.map(e => e.late_count);

        chartTopLate = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: names,
                datasets: [{
                    label: 'Late Count',
                    data: counts,
                    backgroundColor: '#F59E0B',
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true } },
            },
        });
    }

    // Update Top Absent Chart
    function updateTopAbsentChart(employees) {
        const ctx = document.getElementById('chartTopAbsent').getContext('2d');
        
        if (chartTopAbsent) chartTopAbsent.destroy();

        const names = employees.map(e => e.employee_name);
        const counts = employees.map(e => e.alpha_count);

        chartTopAbsent = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: names,
                datasets: [{
                    label: 'Absent Count',
                    data: counts,
                    backgroundColor: '#EF4444',
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true } },
            },
        });
    }

    // Update Department Table
    function updateDepartmentTable(departments) {
        const tbody = document.querySelector('#tableDepartment tbody');
        tbody.innerHTML = '';

        Object.entries(departments).forEach(([dept, stats]) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><strong>${dept}</strong></td>
                <td class="text-center"><span class="badge bg-success">${stats.hadir}</span></td>
                <td class="text-center"><span class="badge bg-warning">${stats.terlambat}</span></td>
                <td class="text-center"><span class="badge bg-info">${stats.izin}</span></td>
                <td class="text-center"><span class="badge bg-danger">${stats.sakit}</span></td>
                <td class="text-center"><span class="badge bg-dark">${stats.alpha}</span></td>
                <td class="text-center"><span class="badge bg-purple">${stats.cuti}</span></td>
                <td class="text-center"><strong>${stats.total}</strong></td>
            `;
            tbody.appendChild(row);
        });
    }

    // Update Supervisor Table
    function updateSupervisorTable(supervisors) {
        const tbody = document.querySelector('#tableSupervisor tbody');
        tbody.innerHTML = '';

        supervisors.forEach(sup => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><strong>${sup.supervisor_name}</strong></td>
                <td class="text-center">${sup.employee_count}</td>
                <td class="text-center">
                    <span class="badge bg-success">${sup.present_rate}%</span>
                </td>
                <td class="text-center">${sup.on_time_count}</td>
                <td class="text-center"><span class="badge bg-warning">${sup.late_count}</span></td>
                <td class="text-center"><span class="badge bg-dark">${sup.absent_count}</span></td>
                <td class="text-center"><span class="badge bg-danger">${sup.sick_count}</span></td>
            `;
            tbody.appendChild(row);
        });
    }

    // Refresh all data
    async function refreshAllData() {
        await Promise.all([
            loadOverview(),
            loadAttendanceRate(),
            loadOvertimeStats(),
            loadAttendanceTrend(),
            loadTopLateEmployees(),
            loadTopAbsentEmployees(),
            loadDepartmentPerformance(),
            loadSupervisorPerformance(),
        ]);
    }

    // Event Listeners
    document.getElementById('btnApplyFilter').addEventListener('click', refreshAllData);
    document.getElementById('btnResetFilter').addEventListener('click', () => {
        setDefaultDates();
        refreshAllData();
    });

    // Initial load
    document.addEventListener('DOMContentLoaded', () => {
        setDefaultDates();
        refreshAllData();
    });
</script>
@endsection
