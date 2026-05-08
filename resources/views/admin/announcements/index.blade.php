@extends('layouts.app')
@section('title', 'Manajemen Pengumuman')

@push('styles')
<style>
.announcement-card { transition: all .2s ease; border-left: 4px solid; }
.announcement-card:hover { transform: translateY(-2px); box-shadow: 0 4px 20px rgba(0,0,0,.1); }
.announcement-card.info    { border-color: #17a2b8; }
.announcement-card.warning { border-color: #ffc107; }
.announcement-card.success { border-color: #28a745; }
.announcement-card.danger  { border-color: #dc3545; }
.priority-badge { font-size: .7rem; letter-spacing:.5px; text-transform:uppercase; }
.stat-card { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; border-radius: 12px; }
.stat-card.green  { background: linear-gradient(135deg, #11998e, #38ef7d); }
.stat-card.orange { background: linear-gradient(135deg, #f7971e, #ffd200); }
.stat-card.red    { background: linear-gradient(135deg, #cb2d3e, #ef473a); }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-1"><i class="bx bx-bell me-2 text-primary"></i>Manajemen Pengumuman</h4>
            <p class="text-muted mb-0">Kelola pengumuman in-app untuk karyawan aktif</p>
        </div>
        <button class="btn btn-primary" onclick="openCreateModal()">
            <i class="bx bx-plus me-1"></i>Buat Pengumuman
        </button>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4" id="stats-row">
    <div class="col-6 col-md-3"><div class="stat-card p-3 text-center"><div class="fs-3 fw-bold" id="stat-total">-</div><small>Total</small></div></div>
    <div class="col-6 col-md-3"><div class="stat-card green p-3 text-center"><div class="fs-3 fw-bold" id="stat-active">-</div><small>Aktif</small></div></div>
    <div class="col-6 col-md-3"><div class="stat-card orange p-3 text-center"><div class="fs-3 fw-bold" id="stat-expired">-</div><small>Kadaluarsa</small></div></div>
    <div class="col-6 col-md-3"><div class="stat-card red p-3 text-center"><div class="fs-3 fw-bold" id="stat-urgent">-</div><small>Mendesak</small></div></div>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body p-3">
        <div class="row g-2">
            <div class="col-md-4"><input type="text" id="search" class="form-control" placeholder="Cari judul/isi..."></div>
            <div class="col-md-2">
                <select id="filter-status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                    <option value="expired">Kadaluarsa</option>
                </select>
            </div>
            <div class="col-md-2">
                <select id="filter-type" class="form-select">
                    <option value="">Semua Tipe</option>
                    <option value="info">Informasi</option>
                    <option value="warning">Peringatan</option>
                    <option value="success">Baik</option>
                    <option value="danger">Penting</option>
                </select>
            </div>
            <div class="col-md-2">
                <select id="filter-priority" class="form-select">
                    <option value="">Semua Prioritas</option>
                    <option value="urgent">Mendesak</option>
                    <option value="high">Tinggi</option>
                    <option value="normal">Normal</option>
                    <option value="low">Rendah</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100" onclick="loadList()"><i class="bx bx-search me-1"></i>Filter</button>
            </div>
        </div>
    </div>
</div>

<!-- List -->
<div class="card">
    <div class="card-body p-0">
        <div id="announcement-list" class="p-3">
            <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
        </div>
        <div id="pagination-wrap" class="d-flex justify-content-between align-items-center px-3 pb-3"></div>
    </div>
</div>

<!-- ─── Modal Create/Edit ─── -->
<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formModalTitle">Buat Pengumuman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-id">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Judul <span class="text-danger">*</span></label>
                        <input type="text" id="f-title" class="form-control" placeholder="Judul pengumuman">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tipe <span class="text-danger">*</span></label>
                        <select id="f-type" class="form-select">
                            <option value="info">ℹ️ Informasi</option>
                            <option value="warning">⚠️ Peringatan</option>
                            <option value="success">✅ Baik</option>
                            <option value="danger">🔔 Penting</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Prioritas <span class="text-danger">*</span></label>
                        <select id="f-priority" class="form-select">
                            <option value="normal">Normal</option>
                            <option value="low">Rendah</option>
                            <option value="high">Tinggi</option>
                            <option value="urgent">🚨 Mendesak</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Isi Pengumuman <span class="text-danger">*</span></label>
                        <textarea id="f-content" class="form-control" rows="4" placeholder="Tulis isi pengumuman..."></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Target Penerima <span class="text-danger">*</span></label>
                        <select id="f-filter-type" class="form-select" onchange="onFilterTypeChange()">
                            <option value="all">Semua Karyawan Aktif</option>
                            <option value="department">Berdasarkan Departemen</option>
                            <option value="position">Berdasarkan Jabatan</option>
                            <option value="employee">Karyawan Tertentu</option>
                        </select>
                    </div>
                    <div class="col-12" id="filter-values-wrap" style="display:none">
                        <label class="form-label fw-semibold">Pilih Target <span class="text-danger">*</span></label>
                        <select id="f-filter-values" class="form-select" multiple style="height:120px"></select>
                    </div>
                    <div class="col-12">
                        <button type="button" class="btn btn-sm btn-outline-info" onclick="previewRecipients()">
                            <i class="bx bx-group me-1"></i>Preview Penerima
                        </button>
                        <span id="preview-count" class="ms-2 text-muted small"></span>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Mulai Tampil</label>
                        <input type="datetime-local" id="f-start-date" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Berakhir</label>
                        <input type="datetime-local" id="f-end-date" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="f-is-active" checked>
                            <label class="form-check-label" for="f-is-active">Aktifkan sekarang</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="f-show-popup">
                            <label class="form-check-label" for="f-show-popup">Tampil sebagai Popup saat login</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-save" onclick="saveAnnouncement()">
                    <i class="bx bx-save me-1"></i>Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ─── Modal Detail ─── -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Pengumuman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detail-body">
                <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btn-export-readers" class="btn btn-success d-none" onclick="exportReaders()">
                    <i class="bx bx-download me-1"></i>Export CSV
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
let currentPage = 1;
let positionsData = [], departmentsData = [], employeesData = [];

// Strip semua tag HTML, untuk tampilan preview plain-text
function stripHtml(html) {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
}

async function apiFetch(url, opts = {}) {
    const res = await fetch(url, {
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json', ...opts.headers },
        ...opts
    });
    return res.json();
}

// ── Load Stats ────────────────────────────────────────────
async function loadStats() {
    const r = await apiFetch('/api/admin/announcements/stats');
    if (r.success) {
        document.getElementById('stat-total').textContent   = r.data.total;
        document.getElementById('stat-active').textContent  = r.data.active;
        document.getElementById('stat-expired').textContent = r.data.expired;
        document.getElementById('stat-urgent').textContent  = r.data.urgent;
    }
}

// ── Load List ─────────────────────────────────────────────
async function loadList(page = 1) {
    currentPage = page;
    const search   = document.getElementById('search').value;
    const status   = document.getElementById('filter-status').value;
    const type     = document.getElementById('filter-type').value;
    const priority = document.getElementById('filter-priority').value;
    const params   = new URLSearchParams({ page, per_page: 10, search, status, type, priority });

    document.getElementById('announcement-list').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';

    const r = await apiFetch(`/api/admin/announcements?${params}`);
    if (!r.success) return;

    const items = r.data.data;
    if (items.length === 0) {
        document.getElementById('announcement-list').innerHTML = '<div class="text-center py-5 text-muted"><i class="bx bx-inbox fs-1 d-block mb-2"></i>Belum ada pengumuman</div>';
        document.getElementById('pagination-wrap').innerHTML = '';
        return;
    }

    const typeColor = { info: 'info', warning: 'warning', success: 'success', danger: 'danger' };
    const html = items.map(a => `
        <div class="card announcement-card ${a.type} mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge ${a.type_badge}">${a.type_label}</span>
                            <span class="badge priority-badge ${a.priority_badge}">${a.priority_label}</span>
                            <span class="badge ${a.is_active ? 'bg-label-success' : 'bg-label-secondary'}">${a.is_active ? 'Aktif' : 'Nonaktif'}</span>
                            ${a.show_popup ? '<span class="badge bg-label-primary"><i class="bx bx-window-alt"></i> Popup</span>' : ''}
                        </div>
                        <h6 class="fw-bold mb-1">${a.title}</h6>
                        <p class="text-muted small mb-1">${stripHtml(a.content).substring(0, 120)}${stripHtml(a.content).length > 120 ? '...' : ''}</p>
                        <small class="text-muted">
                            <i class="bx bx-group me-1"></i>${a.filter_label} &nbsp;|&nbsp;
                            <i class="bx bx-user-check me-1"></i>${a.total_recipients} penerima &nbsp;|&nbsp;
                            <i class="bx bx-check-double me-1"></i>${a.reads_count} sudah baca &nbsp;|&nbsp;
                            <i class="bx bx-calendar me-1"></i>${a.created_at ? new Date(a.created_at).toLocaleDateString('id-ID') : '-'}
                        </small>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <button class="btn btn-sm btn-outline-info" onclick="showDetail(${a.id})" title="Detail"><i class="bx bx-show"></i></button>
                        <button class="btn btn-sm btn-outline-warning" onclick="openEditModal(${a.id})" title="Edit"><i class="bx bx-edit"></i></button>
                        <button class="btn btn-sm btn-outline-${a.is_active ? 'secondary' : 'success'}" onclick="toggleActive(${a.id})" title="${a.is_active ? 'Nonaktifkan' : 'Aktifkan'}">
                            <i class="bx bx-${a.is_active ? 'pause' : 'play'}"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteAnnouncement(${a.id})" title="Hapus"><i class="bx bx-trash"></i></button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');

    document.getElementById('announcement-list').innerHTML = html;

    // Pagination
    const p = r.data;
    let paginHTML = `<small class="text-muted">Menampilkan ${p.from}-${p.to} dari ${p.total}</small><div class="d-flex gap-1">`;
    for (let i = 1; i <= p.last_page; i++) {
        paginHTML += `<button class="btn btn-sm ${i === p.current_page ? 'btn-primary' : 'btn-outline-secondary'}" onclick="loadList(${i})">${i}</button>`;
    }
    paginHTML += '</div>';
    document.getElementById('pagination-wrap').innerHTML = paginHTML;
}

// ── Modal Create ──────────────────────────────────────────
function openCreateModal() {
    document.getElementById('formModalTitle').textContent = 'Buat Pengumuman Baru';
    document.getElementById('edit-id').value = '';
    document.getElementById('f-title').value = '';
    document.getElementById('f-content').value = '';
    document.getElementById('f-type').value = 'info';
    document.getElementById('f-priority').value = 'normal';
    document.getElementById('f-filter-type').value = 'all';
    document.getElementById('f-start-date').value = '';
    document.getElementById('f-end-date').value = '';
    document.getElementById('f-is-active').checked = true;
    document.getElementById('f-show-popup').checked = false;
    document.getElementById('preview-count').textContent = '';
    onFilterTypeChange();
    new bootstrap.Modal(document.getElementById('formModal')).show();
}

// ── Modal Edit ────────────────────────────────────────────
async function openEditModal(id) {
    const r = await apiFetch(`/api/admin/announcements/${id}`);
    if (!r.success) return;
    const a = r.data.announcement;

    document.getElementById('formModalTitle').textContent = 'Edit Pengumuman';
    document.getElementById('edit-id').value = a.id;
    document.getElementById('f-title').value = a.title;
    document.getElementById('f-content').value = a.content;
    document.getElementById('f-type').value = a.type;
    document.getElementById('f-priority').value = a.priority;
    document.getElementById('f-filter-type').value = a.filter_type;
    document.getElementById('f-is-active').checked = a.is_active;
    document.getElementById('f-show-popup').checked = a.show_popup;
    document.getElementById('f-start-date').value = a.start_date ? a.start_date.substring(0, 16) : '';
    document.getElementById('f-end-date').value = a.end_date ? a.end_date.substring(0, 16) : '';
    document.getElementById('preview-count').textContent = '';

    await onFilterTypeChange();

    if (a.filter_values) {
        const sel = document.getElementById('f-filter-values');
        Array.from(sel.options).forEach(opt => {
            opt.selected = a.filter_values.includes(parseInt(opt.value));
        });
    }

    new bootstrap.Modal(document.getElementById('formModal')).show();
}

// ── Filter Type Change ────────────────────────────────────
async function onFilterTypeChange() {
    const type = document.getElementById('f-filter-type').value;
    const wrap = document.getElementById('filter-values-wrap');
    const sel  = document.getElementById('f-filter-values');

    if (type === 'all') { wrap.style.display = 'none'; return; }
    wrap.style.display = 'block';
    sel.innerHTML = '<option disabled>Memuat...</option>';

    let data = [], label = '';
    if (type === 'department') {
        if (!departmentsData.length) departmentsData = (await apiFetch('/api/admin/announcements/departments')).data;
        data = departmentsData; label = 'dept';
    } else if (type === 'position') {
        if (!positionsData.length) positionsData = (await apiFetch('/api/admin/announcements/positions')).data;
        data = positionsData;
    } else if (type === 'employee') {
        if (!employeesData.length) employeesData = (await apiFetch('/api/admin/announcements/employees')).data;
        data = employeesData;
    }

    sel.innerHTML = data.map(d => `<option value="${d.id}">${d.name}${d.employee_code ? ' ('+d.employee_code+')' : ''}</option>`).join('');
}

// ── Preview Penerima ──────────────────────────────────────
async function previewRecipients() {
    const filterType   = document.getElementById('f-filter-type').value;
    const sel          = document.getElementById('f-filter-values');
    const filterValues = Array.from(sel.selectedOptions).map(o => parseInt(o.value));

    const body = { filter_type: filterType, filter_values: filterValues };
    const r = await apiFetch('/api/admin/announcements/preview-recipients', { method: 'POST', body: JSON.stringify(body) });

    if (r.success) {
        document.getElementById('preview-count').innerHTML = `<strong class="text-primary">${r.data.count} karyawan</strong> akan menerima pengumuman ini`;
    }
}

// ── Save ──────────────────────────────────────────────────
async function saveAnnouncement() {
    const id        = document.getElementById('edit-id').value;
    const filterType   = document.getElementById('f-filter-type').value;
    const sel          = document.getElementById('f-filter-values');
    const filterValues = Array.from(sel.selectedOptions).map(o => parseInt(o.value));

    const body = {
        title:         document.getElementById('f-title').value,
        content:       document.getElementById('f-content').value,
        type:          document.getElementById('f-type').value,
        priority:      document.getElementById('f-priority').value,
        filter_type:   filterType,
        filter_values: filterValues,
        is_active:     document.getElementById('f-is-active').checked,
        show_popup:    document.getElementById('f-show-popup').checked,
        start_date:    document.getElementById('f-start-date').value || null,
        end_date:      document.getElementById('f-end-date').value || null,
    };

    const btn = document.getElementById('btn-save');
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';

    const url    = id ? `/api/admin/announcements/${id}` : '/api/admin/announcements';
    const method = id ? 'PUT' : 'POST';
    const r      = await apiFetch(url, { method, body: JSON.stringify(body) });

    btn.disabled = false; btn.innerHTML = '<i class="bx bx-save me-1"></i>Simpan';

    if (r.success) {
        toastr.success(r.message);
        bootstrap.Modal.getInstance(document.getElementById('formModal')).hide();
        loadList(); loadStats();
    } else {
        const errs = r.errors ? Object.values(r.errors).flat().join('<br>') : r.message;
        toastr.error(errs);
    }
}

// ── Toggle Active ─────────────────────────────────────────
async function toggleActive(id) {
    const r = await apiFetch(`/api/admin/announcements/${id}/toggle-active`, { method: 'POST' });
    if (r.success) { toastr.success(r.message); loadList(currentPage); loadStats(); }
    else toastr.error(r.message);
}

// ── Delete ────────────────────────────────────────────────
async function deleteAnnouncement(id) {
    const result = await Swal.fire({ title: 'Hapus Pengumuman?', text: 'Data tidak bisa dikembalikan.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Ya, Hapus!' });
    if (!result.isConfirmed) return;
    const r = await apiFetch(`/api/admin/announcements/${id}`, { method: 'DELETE' });
    if (r.success) { toastr.success(r.message); loadList(currentPage); loadStats(); }
    else toastr.error(r.message);
}

// ── Detail ────────────────────────────────────────────────
async function showDetail(id) {
    document.getElementById('detail-body').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
    new bootstrap.Modal(document.getElementById('detailModal')).show();
    const r = await apiFetch(`/api/admin/announcements/${id}`);
    if (!r.success) { document.getElementById('detail-body').innerHTML = '<p class="text-danger">Gagal memuat data</p>'; return; }

    const { announcement: a, filter_details, readers } = r.data;
    const readersHtml = readers.length ? readers.map(rd => `
        <tr><td>${rd.name}</td><td>${rd.employee_code}</td><td>${rd.read_at}</td></tr>
    `).join('') : '<tr><td colspan="3" class="text-center text-muted">Belum ada yang membaca</td></tr>';

    const detailHtml = `
        <div class="row g-3">
            <div class="col-12">
                <span class="badge ${a.type_badge} me-1">${a.type_label}</span>
                <span class="badge ${a.priority_badge} me-1">${a.priority_label}</span>
                <span class="badge ${a.is_active ? 'bg-success' : 'bg-secondary'}">${a.is_active ? 'Aktif' : 'Nonaktif'}</span>
            </div>
            <div class="col-12"><h5>${a.title}</h5><div id="detail-content" style="font-size:.95rem;line-height:1.7;color:#444;">${a.content}</div></div>
            <div class="col-md-6"><strong>Target:</strong> ${a.filter_label}${filter_details.length ? ': ' + filter_details.join(', ') : ''}</div>
            <div class="col-md-6"><strong>Total Penerima:</strong> ${a.total_recipients}</div>
            <div class="col-md-6"><strong>Mulai:</strong> ${a.start_date ? new Date(a.start_date).toLocaleString('id-ID') : 'Sekarang'}</div>
            <div class="col-md-6"><strong>Berakhir:</strong> ${a.end_date ? new Date(a.end_date).toLocaleString('id-ID') : 'Tidak terbatas'}</div>
            <div class="col-12">
                <h6 class="mt-2">Sudah Dibaca (${readers.length} orang)</h6>
                <table class="table table-sm table-bordered">
                    <thead><tr><th>Nama</th><th>Kode</th><th>Waktu Baca</th></tr></thead>
                    <tbody>${readersHtml}</tbody>
                </table>
            </div>
        </div>
    `;
    document.getElementById('detail-body').innerHTML = detailHtml;

    // Tombol export
    const btnExport = document.getElementById('btn-export-readers');
    btnExport.classList.remove('d-none');
    btnExport.dataset.announcementId = id;

    // Buka semua link di detail modal di tab baru
    document.querySelectorAll('#detail-content a').forEach(el => {
        el.setAttribute('target', '_blank');
        el.setAttribute('rel', 'noopener noreferrer');
        el.style.cssText += 'color:#4361ee;font-weight:600;text-decoration:underline;';
    });
}

// ── Export Readers ───────────────────────────────────────
function exportReaders() {
    const id = document.getElementById('btn-export-readers').dataset.announcementId;
    window.location.href = `/api/admin/announcements/${id}/export-readers`;
}

// ── Init ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadStats();
    loadList();
    let searchTimer;
    document.getElementById('search').addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadList(), 400);
    });
});
</script>
@endpush
