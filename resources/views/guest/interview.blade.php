@extends('layouts.guest')
@section('title', 'Monitoring Interview')

@section('styles')
<style>
.badge-intv { min-width: 85px; display: inline-block; text-align: center; }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Monitoring Interview</h4>
            <p class="text-muted mb-0 d-none d-md-block">Data jadwal dan status interview kandidat</p>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label small mb-1">Cari Kandidat</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class='bx bx-search'></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="Nama / Posisi...">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Dari Tanggal</label>
                    <input type="date" class="form-control form-control-sm" id="dateFrom">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Sampai Tanggal</label>
                    <input type="date" class="form-control form-control-sm" id="dateTo">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select class="form-select form-select-sm" id="filterStatus">
                        <option value="">Semua Status</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <button class="btn btn-primary btn-sm w-100" onclick="loadData(1)">
                        <i class='bx bx-filter-alt me-1'></i>Filter
                    </button>
                </div>
            </div>
            <!-- Quick shortcuts -->
            <div class="mt-2 d-flex gap-2 flex-wrap">
                <small class="text-muted align-self-center">Cepat:</small>
                <button class="btn btn-sm btn-outline-secondary btn-xs py-0 px-2" onclick="setDateRange(0)">Hari Ini</button>
                <button class="btn btn-sm btn-outline-secondary btn-xs py-0 px-2" onclick="setDateRange(6)">7 Hari</button>
                <button class="btn btn-sm btn-outline-secondary btn-xs py-0 px-2" onclick="setMonthRange()">Bulan Ini</button>
                <button class="btn btn-sm btn-outline-secondary btn-xs py-0 px-2" onclick="clearDate()">Semua</button>
            </div>
        </div>
    </div>

    <!-- Status Summary Cards -->
    <div class="row g-3 mb-3" id="summaryCards">
        <div class="col-6 col-md-3">
            <div class="card text-center h-100">
                <div class="card-body p-3">
                    <div class="text-secondary mb-1 small">Scheduled</div>
                    <div class="fw-bold fs-5" id="cntScheduled">-</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center h-100">
                <div class="card-body p-3">
                    <div class="text-primary mb-1 small">Confirmed</div>
                    <div class="fw-bold fs-5 text-primary" id="cntConfirmed">-</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center h-100">
                <div class="card-body p-3">
                    <div class="text-success mb-1 small">Completed</div>
                    <div class="fw-bold fs-5 text-success" id="cntCompleted">-</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center h-100">
                <div class="card-body p-3">
                    <div class="text-danger mb-1 small">Cancelled</div>
                    <div class="fw-bold fs-5 text-danger" id="cntCancelled">-</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Daftar Interview</h6>
            <div class="d-flex gap-2 align-items-center">
                <select class="form-select form-select-sm" id="perPage" style="width:auto" onchange="loadData(1)">
                    <option value="25">25</option><option value="50">50</option><option value="100">100</option>
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
                                <th>#</th><th>Nama Kandidat</th><th>Posisi</th>
                                <th>Tanggal Interview</th><th>Waktu</th><th>Lokasi</th>
                                <th>No. Telp</th><th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
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
const INTV_STATUS = {
    scheduled:  { label: 'Scheduled',  cls: 'bg-secondary' },
    confirmed:  { label: 'Confirmed',  cls: 'bg-primary' },
    completed:  { label: 'Completed',  cls: 'bg-success' },
    cancelled:  { label: 'Cancelled',  cls: 'bg-danger' },
};

$(function() {
    loadData(1);
    loadSummary();
    let t;
    $('#searchInput').on('input', function() { clearTimeout(t); t = setTimeout(() => loadData(1), 400); });
    $('#filterStatus').on('change', () => loadData(1));
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

function clearDate() {
    $('#dateFrom').val('');
    $('#dateTo').val('');
    loadData(1);
}

function loadSummary() {
    $.get('/api/guest/stats', function(r) {
        if (!r.success) return;
        const iv = r.interview || {};
        $('#cntScheduled').text(iv.scheduled ?? 0);
        $('#cntConfirmed').text(iv.confirmed  ?? 0);
        $('#cntCompleted').text(iv.completed  ?? 0);
        $('#cntCancelled').text(iv.cancelled  ?? 0);
    });
}

function loadData(page) {
    const params = {
        page, per_page: $('#perPage').val(),
        search: $('#searchInput').val().trim(),
        status: $('#filterStatus').val(),
        date_from: $('#dateFrom').val(),
        date_to: $('#dateTo').val(),
    };
    $('#tableBody').html('<tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>');
    $('#mobileCards').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>');
    $.get('/api/guest/interview', params).done(function(r) {
        if (!r.success) return;
        renderTable(r.data, r.meta);
        renderPagination(r.meta);
        $('#totalLabel').text('Total: ' + r.meta.total);
    });
}

function renderTable(rows, meta) {
    const offset = (meta.current_page - 1) * meta.per_page;
    if (!rows.length) {
        $('#tableBody').html('<tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data interview</td></tr>');
        $('#mobileCards').html('<div class="p-3 text-center text-muted">Tidak ada data interview</div>');
        return;
    }
    let html = '', mobile = '';
    rows.forEach(function(iv, i) {
        const s = INTV_STATUS[iv.status] || { label: iv.status, cls: 'bg-secondary' };
        const pos   = iv.position ? iv.position.name : (iv.position_name || '-');
        const tgl   = iv.interview_date ? iv.interview_date.substring(0, 10).split('-').reverse().join('/') : '-';
        const time  = iv.interview_time ? iv.interview_time.substring(0, 5) : '-';
        const phone = iv.phone || '-';
        const loc   = iv.location || '-';

        html += `<tr>
            <td>${offset + i + 1}</td>
            <td><strong>${iv.candidate_name || '-'}</strong></td>
            <td>${pos}</td>
            <td>${tgl}</td>
            <td>${time}</td>
            <td>${loc}</td>
            <td>${phone}</td>
            <td class="text-center"><span class="badge ${s.cls} badge-intv">${s.label}</span></td>
        </tr>`;

        mobile += `<div class="p-3 border-bottom">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="fw-semibold">${iv.candidate_name || '-'}</div>
                    <small class="text-muted">${pos}</small>
                </div>
                <span class="badge ${s.cls}">${s.label}</span>
            </div>
            <div class="mt-1 small text-muted">
                ${tgl} ${time} &nbsp;|&nbsp; ${loc}
            </div>
            <div class="small text-muted">${phone}</div>
        </div>`;
    });
    $('#tableBody').html(html);
    $('#mobileCards').html(mobile);
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
