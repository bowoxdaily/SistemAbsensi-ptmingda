@extends('layouts.app')

@section('title', 'Status Karyawan')

@section('styles')
<style>
    .stat-card {
        border-left: 4px solid;
        transition: transform .15s;
        cursor: default;
    }
    .stat-card:hover { transform: translateY(-2px); }
    .stat-card.active-card  { border-color: #28a745; }
    .stat-card.inactive-card { border-color: #6c757d; }
    .stat-card.resign-card  { border-color: #dc3545; }
    .stat-card.mangkir-card { border-color: #fd7e14; }
    .stat-card.gagal-card   { border-color: #ffc107; }
    .stat-card.total-card   { border-color: #4e73df; }

    .stat-card .stat-number { font-size: 1.9rem; font-weight: 700; line-height: 1; }
    .stat-card .stat-label  { font-size: 0.78rem; text-transform: uppercase; letter-spacing: .05em; }

    .filter-active-badge .badge { font-size: .75rem; }

    .table-status-badge { min-width: 105px; display: inline-block; text-align: center; }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Status Karyawan</h4>
            <p class="text-muted mb-0 d-none d-md-block">Laporan status aktif, resign, gagal probation, dll</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.karyawan.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class='bx bx-arrow-back me-1'></i> Data Karyawan
            </a>
            <button id="exportBtn" class="btn btn-success btn-sm" onclick="exportReport()">
                <i class='bx bx-export me-1'></i> <span class="d-none d-sm-inline">Export Excel</span>
            </button>
        </div>
    </div>

   

    <!-- Filter Card -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <!-- Search -->
                <div class="col-12 col-md-4">
                    <label class="form-label small mb-1">Cari</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class='bx bx-search'></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="Nama / Kode / NIK...">
                    </div>
                </div>
                <!-- Status -->
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select class="form-select form-select-sm" id="filterStatus">
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Tidak Aktif</option>
                        <option value="resign">Resign</option>
                        <option value="mangkir">Mangkir</option>
                        <option value="gagal_probation">Gagal Probation</option>
                    </select>
                </div>
                <!-- Department -->
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Departemen</label>
                    <select class="form-select form-select-sm" id="filterDept">
                        <option value="">Semua Dept.</option>
                    </select>
                </div>
                <!-- Join From -->
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Join dari</label>
                    <input type="date" class="form-control form-control-sm" id="joinFrom">
                </div>
                <!-- Join To -->
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Join sampai</label>
                    <input type="date" class="form-control form-control-sm" id="joinTo">
                </div>
            </div>
            <div class="row g-2 mt-2">
                <div class="col-auto">
                    <button class="btn btn-primary btn-sm" onclick="applyFilter()">
                        <i class='bx bx-filter-alt me-1'></i> Filter
                    </button>
                    <button class="btn btn-outline-secondary btn-sm ms-1" onclick="resetFilter()">
                        <i class='bx bx-reset me-1'></i> Reset
                    </button>
                </div>
                <div class="col-auto d-flex align-items-center filter-active-badge" id="activeBadges"></div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Karyawan</h5>
            <div class="d-flex align-items-center gap-2">
                <small class="text-muted d-none d-md-inline">Tampilkan:</small>
                <select class="form-select form-select-sm" id="perPageSelect" style="width:auto;" onchange="loadData(1)">
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="all">Semua</option>
                </select>
                <small class="text-muted" id="totalLabel">Total: 0</small>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Desktop -->
            <div class="d-none d-md-block">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px">#</th>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Departemen</th>
                                <th>Jabatan</th>
                                <th>Tipe</th>
                                <th>Tgl. Bergabung</th>
                                <th>Tgl. Nonaktif</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <tr><td colspan="9" class="text-center py-4">
                                <div class="spinner-border text-primary" role="status"></div>
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Mobile Cards -->
            <div class="d-md-none" id="mobileCards">
                <div class="text-center py-4" id="mobileLoading">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
        </div>
        <!-- Pagination -->
        <div class="card-footer" id="paginationContainer" style="display:none">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small" id="paginationInfo"></div>
                <div id="paginationLinks"></div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
let currentPage = 1;
let isLoading   = false;

const STATUS_INFO = {
    active:          { label: 'Aktif',           class: 'bg-success' },
    inactive:        { label: 'Tidak Aktif',      class: 'bg-secondary' },
    resign:          { label: 'Resign',           class: 'bg-danger' },
    mangkir:         { label: 'Mangkir',          class: 'bg-warning text-dark' },
    gagal_probation: { label: 'Gagal Probation',  class: 'bg-info text-dark' },
};

/* --- Department options --- */
function loadDepartments() {
    $.get('/api/karyawan/master-data', function(res) {
        if (!res.success) return;
        res.data.departments.forEach(d => {
            $('#filterDept').append(`<option value="${d.id}">${d.name}</option>`);
        });
    });
}

/* --- Main load --- */
function loadData(page) {
    if (isLoading) return;
    isLoading = true;
    currentPage = page || currentPage;

    const params = {
        page:          currentPage,
        per_page:      $('#perPageSelect').val(),
        search:        $('#searchInput').val().trim(),
        status:        $('#filterStatus').val(),
        department_id: $('#filterDept').val(),
        join_from:     $('#joinFrom').val(),
        join_to:       $('#joinTo').val(),
    };

    $.get('/api/karyawan/status-report', params)
        .done(function(res) {
            if (!res.success) return;
            renderSummary(res.summary);
            renderTable(res.data, res.meta);
            renderPagination(res.meta);
        })
        .fail(function() {
            $('#tableBody').html('<tr><td colspan="9" class="text-center text-danger py-3">Gagal memuat data</td></tr>');
        })
        .always(function() { isLoading = false; });
}

/* --- Summary cards --- */
function renderSummary(s) {
    $('#sumTotal').text(s.total);
    $('#sumActive').text(s.active);
    $('#sumInactive').text(s.inactive);
    $('#sumResign').text(s.resign);
    $('#sumMangkir').text(s.mangkir);
    $('#sumGagal').text(s.gagal_probation);
    $('#totalLabel').text('Total: ' + s.total);
}

/* --- Table --- */
function renderTable(rows, meta) {
    const offset = meta.per_page === 'all' ? 0 : (meta.current_page - 1) * meta.per_page;

    if (!rows.length) {
        $('#tableBody').html('<tr><td colspan="9" class="text-center text-muted py-4">Tidak ada data</td></tr>');
        $('#mobileCards').html('<div class="p-3 text-center text-muted">Tidak ada data</div>');
        return;
    }

    let html   = '';
    let mobile = '';

    rows.forEach(function(k, idx) {
        const si     = STATUS_INFO[k.status] || { label: k.status, class: 'bg-secondary' };
        const dept   = k.department ? k.department.name : '-';
        const pos    = k.position   ? k.position.name   : '-';
        const join   = k.join_date  ? formatDate(k.join_date)   : '-';
        const no     = offset + idx + 1;

        // Determine inactive date based on status
        let inactiveDate = '-';
        let inactiveDateRaw = null;
        let inactiveDateLabel = '';
        if (k.status === 'resign' && k.tanggal_resign) {
            inactiveDateRaw = k.tanggal_resign;
            inactiveDateLabel = 'Resign';
        } else if (k.status === 'mangkir' && k.tanggal_mangkir) {
            inactiveDateRaw = k.tanggal_mangkir;
            inactiveDateLabel = 'Mangkir';
        } else if (k.status === 'gagal_probation' && k.tanggal_gagal_probation) {
            inactiveDateRaw = k.tanggal_gagal_probation;
            inactiveDateLabel = 'Gagal Prob';
        }
        if (inactiveDateRaw) inactiveDate = formatDate(inactiveDateRaw);

        html += `<tr>
            <td>${no}</td>
            <td><code>${k.employee_code}</code></td>
            <td>${k.name}</td>
            <td>${dept}</td>
            <td>${pos}</td>
            <td><span class="badge bg-label-secondary">${k.employment_status || '-'}</span></td>
            <td>${join}</td>
            <td>${inactiveDateRaw ? `<span class="text-danger">${inactiveDate}</span>` : '-'}</td>
            <td class="text-center"><span class="badge ${si.class} table-status-badge">${si.label}</span></td>
        </tr>`;

        mobile += `<div class="p-3 border-bottom">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="fw-semibold">${k.name}</div>
                    <small class="text-muted">${k.employee_code} &bull; ${dept}</small><br>
                    <small class="text-muted">${pos} &bull; ${k.employment_status || '-'}</small>
                </div>
                <span class="badge ${si.class} ms-2">${si.label}</span>
            </div>
            <div class="mt-1 small text-muted">
                Join: ${join}
                ${inactiveDateRaw ? ` &bull; ${inactiveDateLabel}: <span class="text-danger">${inactiveDate}</span>` : ''}
            </div>
        </div>`;
    });

    $('#tableBody').html(html);
    $('#mobileCards').html(mobile);
    $('#mobileLoading').hide();
}

/* --- Pagination --- */
function renderPagination(meta) {
    if (!meta || meta.per_page === 'all' || meta.last_page <= 1) {
        $('#paginationContainer').hide();
        return;
    }

    $('#paginationContainer').show();
    $('#paginationInfo').text(`Menampilkan ${meta.from ?? 0}–${meta.to ?? 0} dari ${meta.total} data`);

    let links = '<ul class="pagination pagination-sm mb-0">';
    links += `<li class="page-item ${meta.current_page == 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="loadData(${meta.current_page - 1}); return false;">&#8249;</a></li>`;

    const range = pageRange(meta.current_page, meta.last_page);
    range.forEach(p => {
        if (p === '...') {
            links += '<li class="page-item disabled"><span class="page-link">…</span></li>';
        } else {
            links += `<li class="page-item ${p == meta.current_page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadData(${p}); return false;">${p}</a></li>`;
        }
    });

    links += `<li class="page-item ${meta.current_page == meta.last_page ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="loadData(${meta.current_page + 1}); return false;">&#8250;</a></li>`;
    links += '</ul>';

    $('#paginationLinks').html(links);
}

function pageRange(current, last) {
    const pages = [];
    if (last <= 7) {
        for (let i = 1; i <= last; i++) pages.push(i);
    } else {
        pages.push(1);
        if (current > 3) pages.push('...');
        for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) pages.push(i);
        if (current < last - 2) pages.push('...');
        pages.push(last);
    }
    return pages;
}

/* --- Helpers --- */
function formatDate(str) {
    if (!str) return '-';
    const match = String(str).match(/(\d{4})-(\d{2})-(\d{2})/);
    if (!match) return str;
    return `${match[3]}/${match[2]}/${match[1]}`;
}

/* --- Filter actions --- */
function applyFilter() { loadData(1); renderActiveBadges(); }

function resetFilter() {
    $('#searchInput').val('');
    $('#filterStatus').val('');
    $('#filterDept').val('');
    $('#joinFrom').val('');
    $('#joinTo').val('');
    $('#activeBadges').html('');
    loadData(1);
}

function filterByStatus(val) {
    $('#filterStatus').val(val);
    applyFilter();
    $('html, body').animate({ scrollTop: $('.card:last').offset().top - 80 }, 300);
}

function renderActiveBadges() {
    const badges = [];
    const s = $('#filterStatus').val();
    const d = $('#filterDept option:selected').text();
    const f = $('#joinFrom').val();
    const t = $('#joinTo').val();
    const q = $('#searchInput').val().trim();

    if (s) badges.push(`<span class="badge bg-label-primary">Status: ${STATUS_INFO[s]?.label || s}</span>`);
    if ($('#filterDept').val()) badges.push(`<span class="badge bg-label-info">Dept: ${d}</span>`);
    if (f) badges.push(`<span class="badge bg-label-secondary">Join dari: ${formatDate(f)}</span>`);
    if (t) badges.push(`<span class="badge bg-label-secondary">Join s/d: ${formatDate(t)}</span>`);
    if (q) badges.push(`<span class="badge bg-label-dark">Cari: ${q}</span>`);

    $('#activeBadges').html(badges.join(' '));
}

/* --- Export --- */
function exportReport() {
    const params = new URLSearchParams({
        search:        $('#searchInput').val().trim(),
        status:        $('#filterStatus').val(),
        department_id: $('#filterDept').val(),
        join_from:     $('#joinFrom').val(),
        join_to:       $('#joinTo').val(),
    });
    window.location.href = '{{ route("admin.karyawan.status-report.export") }}?' + params.toString();
}

/* --- Init --- */
$(function() {
    loadDepartments();
    loadData(1);

    let searchTimer;
    $('#searchInput').on('input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadData(1), 400);
    });
    $('#filterStatus, #filterDept').on('change', () => applyFilter());
    $('#joinFrom, #joinTo').on('change', () => applyFilter());
});
</script>
@endpush
