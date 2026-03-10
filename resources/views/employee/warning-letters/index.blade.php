@extends('layouts.app')

@section('title', 'Riwayat Surat Peringatan')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Page Header -->
    <h4 class="fw-bold mb-4">
        <span class="text-muted fw-light">Absensi /</span> Riwayat Surat Peringatan
    </h4>

    <!-- Statistics Cards -->
    <div class="row mb-4" id="statsCards">
        <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text">SP Aktif</p>
                            <h4 class="mb-0 text-danger" id="aktifCount">0</h4>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-danger rounded p-2">
                                <i class='bx bx-error-circle bx-sm'></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text">Total SP Diterima</p>
                            <h4 class="mb-0" id="totalCount">0</h4>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-secondary rounded p-2">
                                <i class='bx bx-list-ul bx-sm'></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text">SP Selesai</p>
                            <h4 class="mb-0 text-success" id="selesaiCount">0</h4>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-success rounded p-2">
                                <i class='bx bx-check-circle bx-sm'></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filter</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Jenis SP</label>
                    <select class="form-select" id="filterSpType">
                        <option value="">Semua Jenis</option>
                        <option value="SP1">SP 1</option>
                        <option value="SP2">SP 2</option>
                        <option value="SP3" >SP 3</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="filterStatus">
                        <option value="">Semua Status</option>
                        <option value="aktif">Aktif</option>
                        <option value="selesai">Selesai</option>
                        <option value="dibatalkan">Dibatalkan</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="button" class="btn btn-primary me-2" onclick="loadWarningLetters()">
                        <i class='bx bx-search'></i> Cari
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetFilters()">
                        <i class='bx bx-reset'></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Data List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Daftar Surat Peringatan</h5>
        </div>
        <div class="card-body">
            <div id="spList">
                <!-- Data will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Surat Peringatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <!-- Content loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    loadStatistics();
    loadWarningLetters();
});

// Load statistics
function loadStatistics() {
    $.get('/api/employee/warning-letters/statistics', function(response) {
        if (response.success) {
            $('#aktifCount').text(response.data.aktif);
            $('#totalCount').text(response.data.total);
            $('#selesaiCount').text(response.data.selesai);
        }
    });
}

// Load warning letters
function loadWarningLetters() {
    let params = {
        sp_type: $('#filterSpType').val(),
        status: $('#filterStatus').val()
    };

    $.get('/api/employee/warning-letters', params, function(response) {
        if (response.success) {
            renderList(response.data.data);
        }
    });
}

// Render list
function renderList(data) {
    let html = '';

    if (data.length === 0) {
        html = `
            <div class="text-center py-5">
                <i class='bx bx-check-shield bx-lg text-success'></i>
                <p class="text-muted mt-3">Anda belum pernah menerima surat peringatan</p>
            </div>
        `;
    } else {
        data.forEach(function(sp) {
            let spTypeBadge = '';
            let spTypeClass = '';
            if (sp.sp_type === 'SP1') {
                spTypeBadge = '<span class="badge bg-warning">SP 1</span>';
                spTypeClass = 'border-warning';
            } else if (sp.sp_type === 'SP2') {
                spTypeBadge = '<span class="badge" style="background-color: #ff9800;">SP 2</span>';
                spTypeClass = 'border-warning';
            } else if (sp.sp_type === 'SP3') {
                spTypeBadge = '<span class="badge bg-danger">SP 3</span>';
                spTypeClass = 'border-danger';
            }

            let statusBadge = '';
            if (sp.status === 'draft') statusBadge = '<span class="badge bg-warning text-dark">Draft</span>';
            else if (sp.status === 'aktif') statusBadge = '<span class="badge bg-danger">Aktif</span>';
            else if (sp.status === 'selesai') statusBadge = '<span class="badge bg-success">Selesai</span>';
            else if (sp.status === 'dibatalkan') statusBadge = '<span class="badge bg-secondary">Dibatalkan</span>';

            let violation = sp.violation.length > 100 ? sp.violation.substring(0, 100) + '...' : sp.violation;

            html += `
                <div class="card mb-3 ${spTypeClass}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="mb-1">${spTypeBadge} ${sp.sp_number}</h5>
                                <small class="text-muted">
                                    <i class='bx bx-calendar'></i> Diterbitkan: ${formatDate(sp.issue_date)}
                                </small>
                            </div>
                            <div>
                                ${statusBadge}
                            </div>
                        </div>
                        <div class="mb-3">
                            <strong>Pelanggaran:</strong>
                            <p class="mb-0">${violation}</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" onclick="viewDetail(${sp.id})">
                                <i class='bx bx-show'></i> Lihat Detail
                            </button>
                            ${sp.document_path ? `
                            <a href="/api/employee/warning-letters/${sp.id}/download" class="btn btn-sm btn-outline-primary">
                                <i class='bx bx-download'></i> Download Dokumen
                            </a>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        });
    }

    $('#spList').html(html);
}

// View detail
function viewDetail(id) {
    $.get(`/api/employee/warning-letters/${id}`, function(response) {
        if (response.success) {
            let sp = response.data;

            let spTypeLabel = {
                'SP1': 'SP 1 - Peringatan Pertama',
                'SP2': 'SP 2 - Peringatan Kedua',
                'SP3': 'SP 3 - Peringatan Terakhir'
            };

            let statusBadge = {
                'aktif': '<span class="badge bg-danger">Aktif</span>',
                'selesai': '<span class="badge bg-success">Selesai</span>',
                'dibatalkan': '<span class="badge bg-secondary">Dibatalkan</span>'
            };

            let html = `
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>Nomor SP:</strong><br>
                        <span class="text-primary">${sp.sp_number}</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Jenis SP:</strong><br>
                        ${spTypeLabel[sp.sp_type] || sp.sp_type}
                    </div>
                    <div class="col-md-6">
                        <strong>Tanggal Terbit:</strong><br>
                        ${formatDate(sp.issue_date)}
                    </div>
                    <div class="col-md-6">
                        <strong>Tanggal Berlaku:</strong><br>
                        ${formatDate(sp.effective_date)}
                    </div>
                    <div class="col-md-6">
                        <strong>Status:</strong><br>
                        ${statusBadge[sp.status]}
                    </div>
                    ${sp.completion_date ? `
                    <div class="col-md-6">
                        <strong>Tanggal Selesai:</strong><br>
                        ${formatDate(sp.completion_date)}
                    </div>
                    ` : ''}
                    <div class="col-12">
                        <strong>Pelanggaran:</strong><br>
                        <p class="mb-0">${sp.violation}</p>
                    </div>
                    ${sp.description ? `
                    <div class="col-12">
                        <strong>Deskripsi Tambahan:</strong><br>
                        <p class="mb-0">${sp.description}</p>
                    </div>
                    ` : ''}
                    <div class="col-md-6">
                        <strong>Diterbitkan oleh:</strong><br>
                        ${sp.issuer.name}
                    </div>
                    <div class="col-md-6">
                        <strong>Waktu Penerbitan:</strong><br>
                        ${formatDateTime(sp.issued_at)}
                    </div>
                    ${sp.cancellation_reason ? `
                    <div class="col-12">
                        <div class="alert alert-info">
                            <strong><i class='bx bx-info-circle'></i> Catatan:</strong><br>
                            SP ini telah dibatalkan dengan alasan: ${sp.cancellation_reason}
                        </div>
                    </div>
                    ` : ''}
                    ${sp.document_path ? `
                    <div class="col-12">
                        <a href="/api/employee/warning-letters/${sp.id}/download" class="btn btn-primary">
                            <i class='bx bx-download'></i> Download Dokumen SP
                        </a>
                    </div>
                    ` : ''}
                </div>
            `;

            $('#detailContent').html(html);
            $('#detailModal').modal('show');
        }
    }).fail(function() {
        toastr.error('Gagal memuat detail SP');
    });
}

// Reset filters
function resetFilters() {
    $('#filterSpType').val('');
    $('#filterStatus').val('');
    loadWarningLetters();
}

// Utility functions
function formatDate(dateString) {
    if (!dateString) return '-';
    let date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
}

function formatDateTime(dateString) {
    if (!dateString) return '-';
    let date = new Date(dateString);
    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
</script>
@endpush
