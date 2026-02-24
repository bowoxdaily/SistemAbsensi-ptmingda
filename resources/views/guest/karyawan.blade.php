@extends('layouts.guest')
@section('title', 'Monitoring Karyawan')

@section('styles')
<style>
.status-badge { min-width: 100px; display: inline-block; text-align: center; }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Data Karyawan</h4>
            <p class="text-muted mb-0 d-none d-md-block">Monitoring data seluruh karyawan</p>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label small mb-1">Cari</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class='bx bx-search'></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="Nama / Kode...">
                    </div>
                </div>
                <div class="col-6 col-md-3">
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
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Departemen</label>
                    <select class="form-select form-select-sm" id="filterDept">
                        <option value="">Semua Dept.</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <button class="btn btn-primary btn-sm w-100" onclick="loadData(1)"><i class='bx bx-filter-alt me-1'></i>Filter</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Daftar Karyawan</h6>
            <div class="d-flex gap-2 align-items-center">
                <select class="form-select form-select-sm" id="perPage" style="width:auto" onchange="loadData(1)">
                    <option value="25">25</option><option value="50">50</option><option value="100">100</option>
                </select>
                <small class="text-muted" id="totalLabel">Total: 0</small>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th><th>Kode</th><th>Nama</th><th>Departemen</th>
                            <th>Jabatan</th><th>Tipe</th><th>Tgl. Bergabung</th><th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
                    </tbody>
                </table>
            </div>
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
const STATUS = {
    active:          { label: 'Aktif',           cls: 'bg-success' },
    inactive:        { label: 'Tidak Aktif',      cls: 'bg-secondary' },
    resign:          { label: 'Resign',           cls: 'bg-danger' },
    mangkir:         { label: 'Mangkir',          cls: 'bg-warning text-dark' },
    gagal_probation: { label: 'Gagal Probation',  cls: 'bg-info text-dark' },
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
});

function loadData(page) {
    const params = {
        page, per_page: $('#perPage').val(),
        search: $('#searchInput').val().trim(),
        status: $('#filterStatus').val(),
        department_id: $('#filterDept').val(),
    };
    $('#tableBody').html('<tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>');
    $.get('/api/guest/karyawan', params).done(function(r) {
        if (!r.success) return;
        renderTable(r.data, r.meta);
        renderPagination(r.meta);
        $('#totalLabel').text('Total: ' + r.meta.total);
    });
}

function renderTable(rows, meta) {
    const offset = (meta.current_page - 1) * meta.per_page;
    if (!rows.length) { $('#tableBody').html('<tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data</td></tr>'); return; }
    let html = '';
    rows.forEach(function(k, i) {
        const s = STATUS[k.status] || { label: k.status, cls: 'bg-secondary' };
        const join = k.join_date ? k.join_date.substring(0, 10).split('-').reverse().join('/') : '-';
        html += `<tr>
            <td>${offset + i + 1}</td>
            <td><code>${k.employee_code}</code></td>
            <td>${k.name}</td>
            <td>${k.department ? k.department.name : '-'}</td>
            <td>${k.position ? k.position.name : '-'}</td>
            <td><span class="badge bg-label-secondary">${k.employment_status || '-'}</span></td>
            <td>${join}</td>
            <td class="text-center"><span class="badge ${s.cls} status-badge">${s.label}</span></td>
        </tr>`;
    });
    $('#tableBody').html(html);
}

function renderPagination(meta) {
    if (!meta || meta.last_page <= 1) { $('#paginationBox').hide(); return; }
    $('#paginationBox').show();
    $('#pageInfo').text(`${meta.from ?? 0}–${meta.to ?? 0} dari ${meta.total}`);
    let links = '<ul class="pagination pagination-sm mb-0">';
    links += `<li class="page-item ${meta.current_page == 1 ? 'disabled' : ''}"><a class="page-link" href="#" onclick="loadData(${meta.current_page - 1});return false">&#8249;</a></li>`;
    for (let p = Math.max(1, meta.current_page - 2); p <= Math.min(meta.last_page, meta.current_page + 2); p++) {
        links += `<li class="page-item ${p == meta.current_page ? 'active' : ''}"><a class="page-link" href="#" onclick="loadData(${p});return false">${p}</a></li>`;
    }
    links += `<li class="page-item ${meta.current_page == meta.last_page ? 'disabled' : ''}"><a class="page-link" href="#" onclick="loadData(${meta.current_page + 1});return false">&#8250;</a></li></ul>`;
    $('#pageLinks').html(links);
}
</script>
@endsection
