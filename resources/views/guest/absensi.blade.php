@extends('layouts.guest')
@section('title', 'Monitoring Absensi')

@section('styles')
<style>
.badge-att { min-width: 80px; display: inline-block; text-align: center; }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Monitoring Absensi</h4>
            <p class="text-muted mb-0 d-none d-md-block">Data kehadiran karyawan</p>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label small mb-1">Cari Karyawan</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class='bx bx-search'></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="Nama / Kode...">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Dari Tanggal</label>
                    <input type="date" class="form-control form-control-sm" id="dateFrom" value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Sampai Tanggal</label>
                    <input type="date" class="form-control form-control-sm" id="dateTo" value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select class="form-select form-select-sm" id="filterStatus">
                        <option value="">Semua</option>
                        <option value="hadir">Hadir</option>
                        <option value="terlambat">Terlambat</option>
                        <option value="alpha">Alpha</option>
                        <option value="izin">Izin</option>
                        <option value="sakit">Sakit</option>
                        <option value="cuti">Cuti</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Departemen</label>
                    <select class="form-select form-select-sm" id="filterDept">
                        <option value="">Semua</option>
                    </select>
                </div>
                <div class="col-12 col-md-1">
                    <button class="btn btn-primary btn-sm w-100" onclick="loadData(1)"><i class='bx bx-filter-alt'></i></button>
                </div>
            </div>
            <!-- Quick Date Shortcuts -->
            <div class="mt-2 d-flex gap-2 flex-wrap">
                <small class="text-muted align-self-center">Cepat:</small>
                <button class="btn btn-sm btn-outline-secondary btn-xs py-0 px-2" onclick="setDateRange(0)">Hari Ini</button>
                <button class="btn btn-sm btn-outline-secondary btn-xs py-0 px-2" onclick="setDateRange(6)">7 Hari</button>
                <button class="btn btn-sm btn-outline-secondary btn-xs py-0 px-2" onclick="setDateRange(29)">30 Hari</button>
                <button class="btn btn-sm btn-outline-secondary btn-xs py-0 px-2" onclick="setMonthRange()">Bulan Ini</button>
            </div>
        </div>
    </div>

    <!-- Summary row -->
    <div class="row g-2 mb-3" id="summaryRow" style="display:none!important"></div>

    <!-- Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Data Absensi</h6>
            <div class="d-flex gap-2 align-items-center">
                <select class="form-select form-select-sm" id="perPage" style="width:auto" onchange="loadData(1)">
                    <option value="25">25</option><option value="50">50</option><option value="100">100</option><option value="99999">Semua</option>
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
                                <th>#</th><th>Tanggal</th><th>Karyawan</th><th>Departemen</th>
                                <th>Jabatan</th><th>Jam Masuk</th><th>Jam Keluar</th>
                                <th>Terlambat</th><th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <tr><td colspan="9" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Mobile -->
            <div class="d-md-none" id="mobileCards"></div>
        </div>
        <div class="card-footer" id="paginationBox" style="display:none">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted" id="pageInfo"></small>
                <div id="pageLinks"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const ATT_STATUS = {
    hadir:     { label: 'Hadir',     cls: 'bg-success' },
    terlambat: { label: 'Terlambat', cls: 'bg-warning text-dark' },
    alpha:     { label: 'Alpha',     cls: 'bg-danger' },
    izin:      { label: 'Izin',      cls: 'bg-info text-dark' },
    sakit:     { label: 'Sakit',     cls: 'bg-secondary' },
    cuti:      { label: 'Cuti',      cls: 'bg-primary' },
};

$(function() {
    $.get('/api/guest/master-data', function(r) {
        if (!r.success) return;
        r.data.departments.forEach(d => $('#filterDept').append(`<option value="${d.id}">${d.name}</option>`));
    });
    loadData(1);
    let t;
    $('#searchInput').on('input', function() { clearTimeout(t); t = setTimeout(() => loadData(1), 400); });
    $('#filterStatus, #filterDept').on('change', () => loadData(1));
    $('#dateFrom, #dateTo').on('change', () => loadData(1));
});

function setDateRange(days) {
    const to = new Date();
    const from = new Date();
    from.setDate(to.getDate() - days);
    $('#dateFrom').val(from.toISOString().slice(0, 10));
    $('#dateTo').val(to.toISOString().slice(0, 10));
    loadData(1);
}

function setMonthRange() {
    const now = new Date();
    $('#dateFrom').val(new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10));
    $('#dateTo').val(new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10));
    loadData(1);
}

function loadData(page) {
    const params = {
        page, per_page: $('#perPage').val(),
        search: $('#searchInput').val().trim(),
        status: $('#filterStatus').val(),
        department_id: $('#filterDept').val(),
        date_from: $('#dateFrom').val(),
        date_to: $('#dateTo').val(),
    };
    $('#tableBody').html('<tr><td colspan="9" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>');
    $('#mobileCards').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>');
    $.get('/api/guest/absensi', params).done(function(r) {
        if (!r.success) return;
        renderTable(r.data, r.meta);
        renderPagination(r.meta);
        $('#totalLabel').text('Total: ' + r.meta.total);
    });
}

function renderTable(rows, meta) {
    const offset = (meta.current_page - 1) * meta.per_page;
    if (!rows.length) {
        $('#tableBody').html('<tr><td colspan="9" class="text-center text-muted py-4">Tidak ada data</td></tr>');
        $('#mobileCards').html('<div class="p-3 text-center text-muted">Tidak ada data</div>');
        return;
    }
    let html = '', mobile = '';
    rows.forEach(function(a, i) {
        const s = ATT_STATUS[a.status] || { label: a.status, cls: 'bg-secondary' };
        const emp  = a.employee || {};
        const dept = emp.department ? emp.department.name : '-';
        const pos  = emp.position  ? emp.position.name   : '-';
        const date = a.attendance_date ? a.attendance_date.substring(0, 10).split('-').reverse().join('/') : '-';
        const cin  = a.check_in_fmt  ?? (a.check_in  ? String(a.check_in).substring(11, 16)  : '-');
        const cout = a.check_out_fmt ?? (a.check_out ? String(a.check_out).substring(11, 16) : '-');
        const late = a.late_minutes > 0 ? `<span class="text-warning">${a.late_minutes} mnt</span>` : '-';

        html += `<tr>
            <td>${offset + i + 1}</td>
            <td>${date}</td>
            <td><strong>${emp.name || '-'}</strong><br><small class="text-muted">${emp.employee_code || '-'}</small></td>
            <td>${dept}</td><td>${pos}</td>
            <td>${cin}</td><td>${cout}</td>
            <td>${late}</td>
            <td class="text-center"><span class="badge ${s.cls} badge-att">${s.label}</span></td>
        </tr>`;

        mobile += `<div class="p-3 border-bottom">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="fw-semibold">${emp.name || '-'}</div>
                    <small class="text-muted">${emp.employee_code || '-'} &bull; ${dept}</small>
                </div>
                <span class="badge ${s.cls}">${s.label}</span>
            </div>
            <div class="mt-1 small text-muted">
                ${date} &nbsp;|&nbsp; Masuk: ${cin} &nbsp;|&nbsp; Keluar: ${cout}
            </div>
        </div>`;
    });
    $('#tableBody').html(html);
    $('#mobileCards').html(mobile);
}

function renderPagination(meta) {
    const perPage = parseInt($('#perPage').val());
    if (perPage >= 99999) { $('#paginationBox').hide(); return; }
    $('#paginationBox').show();
    if (!meta) return;
    $('#pageInfo').text(`${meta.from ?? 0}–${meta.to ?? 0} dari ${meta.total}`);
    let links = '<ul class="pagination pagination-sm mb-0">';
    links += `<li class="page-item ${meta.current_page == 1 ? 'disabled' : ''}"><a class="page-link" href="#" onclick="loadData(${meta.current_page - 1});return false">&#8249;</a></li>`;
    for (let p = 1; p <= meta.last_page; p++) {
        links += `<li class="page-item ${p == meta.current_page ? 'active' : ''}"><a class="page-link" href="#" onclick="loadData(${p});return false">${p}</a></li>`;
    }
    links += `<li class="page-item ${meta.current_page == meta.last_page ? 'disabled' : ''}"><a class="page-link" href="#" onclick="loadData(${meta.current_page + 1});return false">&#8250;</a></li></ul>`;
    $('#pageLinks').html(links);
}
</script>
@endsection
