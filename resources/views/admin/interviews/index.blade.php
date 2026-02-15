@extends('layouts.app')

@section('title', 'Manajemen Interview')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">
                <span class="text-muted fw-light">Rekrutmen /</span> Interview
            </h4>
            <button type="button" class="btn btn-primary" id="addInterviewBtn">
                <i class='bx bx-plus me-1'></i> Jadwalkan Interview
            </button>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class='bx bx-calendar bx-sm'></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Total Interview</small>
                                <h4 class="mb-0">{{ $stats['total'] }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-warning">
                                    <i class='bx bx-time bx-sm'></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Terjadwal</small>
                                <h4 class="mb-0">{{ $stats['scheduled'] }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class='bx bx-check-circle bx-sm'></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Dikonfirmasi</small>
                                <h4 class="mb-0">{{ $stats['confirmed'] }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class='bx bx-badge-check bx-sm'></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Selesai</small>
                                <h4 class="mb-0">{{ $stats['completed'] }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Table Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Filter & Pencarian</h5>
            </div>
            <div class="card-body">
                <form method="GET" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Dari</label>
                            <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Sampai</label>
                            <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">Semua Status</option>
                                <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Terjadwal</option>
                                <option value="notified" {{ request('status') == 'notified' ? 'selected' : '' }}>Ternotifikasi</option>
                                <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Dikonfirmasi</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Posisi</label>
                            <select class="form-select" name="position_id">
                                <option value="">Semua Posisi</option>
                                @foreach ($positions as $position)
                                    <option value="{{ $position->id }}" {{ request('position_id') == $position->id ? 'selected' : '' }}>
                                        {{ $position->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Cari</label>
                            <input type="text" class="form-control" name="search" placeholder="Nama/No. HP" value="{{ request('search') }}">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class='bx bx-filter-alt me-1'></i> Filter
                            </button>
                            <a href="{{ route('admin.interviews.index') }}" class="btn btn-secondary">
                                <i class='bx bx-reset me-1'></i> Reset
                            </a>
                            <button type="button" class="btn btn-success" id="bulkBlastBtn" style="display: none;">
                                <i class='bx bxl-whatsapp me-1'></i> Blast WA (<span id="selectedCount">0</span>)
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Interview Table -->
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Daftar Kandidat Interview</h5>
                <div class="d-flex align-items-center">
                    <label class="me-2 mb-0 text-muted small">Tampilkan:</label>
                    <select class="form-select form-select-sm" id="perPageSelect" style="width: auto; min-width: 80px;">
                        <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                        <option value="all" {{ request('per_page') == 'all' ? 'selected' : '' }}>Semua</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <!-- Desktop Table View -->
                <div class="table-responsive d-none d-lg-block">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="40">
                                    <input type="checkbox" class="form-check-input" id="selectAll">
                                </th>
                                <th>No</th>
                                <th>Nama Kandidat</th>
                                <th>No. HP</th>
                                <th>Email</th>
                                <th>Posisi</th>
                                <th>Tanggal Interview</th>
                                <th>Waktu</th>
                                <th>Status</th>
                                <th>WA Terkirim</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($interviews as $index => $interview)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input interview-checkbox" 
                                               value="{{ $interview->id }}"
                                               data-name="{{ $interview->candidate_name }}">
                                    </td>
                                    <td>{{ $interviews->firstItem() + $index }}</td>
                                    <td><strong>{{ $interview->candidate_name }}</strong></td>
                                    <td>{{ $interview->phone }}</td>
                                    <td>{{ $interview->email ?? '-' }}</td>
                                    <td><span class="badge bg-label-primary">{{ $interview->position->name }}</span></td>
                                    <td>{{ \Carbon\Carbon::parse($interview->interview_date)->locale('id')->translatedFormat('d M Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($interview->interview_time)->format('H:i') }} WIB</td>
                                    <td>
                                        @if ($interview->status == 'scheduled')
                                            <span class="badge bg-warning">Terjadwal</span>
                                        @elseif($interview->status == 'notified')
                                            <span class="badge bg-info">Ternotifikasi</span>
                                        @elseif($interview->status == 'confirmed')
                                            <span class="badge bg-success">Dikonfirmasi</span>
                                        @elseif($interview->status == 'completed')
                                            <span class="badge bg-primary">Selesai</span>
                                        @else
                                            <span class="badge bg-danger">Dibatalkan</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($interview->wa_sent_at)
                                            <i class='bx bx-check-circle text-success'></i>
                                            <small>{{ \Carbon\Carbon::parse($interview->wa_sent_at)->diffForHumans() }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                <i class="bx bx-dots-vertical-rounded"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item detail-btn" href="javascript:void(0);" data-id="{{ $interview->id }}">
                                                    <i class="bx bx-detail me-1"></i> Detail
                                                </a>
                                                <a class="dropdown-item edit-btn" href="javascript:void(0);" data-id="{{ $interview->id }}">
                                                    <i class="bx bx-edit me-1"></i> Edit
                                                </a>
                                                <a class="dropdown-item send-wa-btn" href="javascript:void(0);" data-id="{{ $interview->id }}">
                                                    <i class="bx bxl-whatsapp me-1"></i> Kirim WA
                                                </a>
                                                <a class="dropdown-item text-danger delete-btn" href="javascript:void(0);" 
                                                   data-id="{{ $interview->id }}" data-name="{{ $interview->candidate_name }}">
                                                    <i class="bx bx-trash me-1"></i> Hapus
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center py-4">
                                        <i class='bx bx-info-circle bx-lg text-muted'></i>
                                        <p class="mt-2 mb-0 text-muted">Belum ada jadwal interview</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="d-lg-none">
                    @forelse($interviews as $interview)
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="d-flex align-items-start gap-2 flex-grow-1">
                                        <input type="checkbox" class="form-check-input interview-checkbox mt-1" 
                                               value="{{ $interview->id }}" data-name="{{ $interview->candidate_name }}">
                                        <div>
                                            <h6 class="mb-1">{{ $interview->candidate_name }}</h6>
                                            <small class="text-muted">
                                                <i class='bx bx-briefcase'></i> {{ $interview->position->name }}
                                            </small>
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                            <i class="bx bx-dots-vertical-rounded"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item detail-btn" href="javascript:void(0);" data-id="{{ $interview->id }}">
                                                <i class="bx bx-detail me-1"></i> Detail
                                            </a>
                                            <a class="dropdown-item edit-btn" href="javascript:void(0);" data-id="{{ $interview->id }}">
                                                <i class="bx bx-edit me-1"></i> Edit
                                            </a>
                                            <a class="dropdown-item send-wa-btn" href="javascript:void(0);" data-id="{{ $interview->id }}">
                                                <i class="bx bxl-whatsapp me-1"></i> Kirim WA
                                            </a>
                                            <a class="dropdown-item text-danger delete-btn" href="javascript:void(0);" 
                                               data-id="{{ $interview->id }}" data-name="{{ $interview->candidate_name }}">
                                                <i class="bx bx-trash me-1"></i> Hapus
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <small class="text-muted d-block mb-1">
                                        <i class='bx bxs-phone'></i> {{ $interview->phone }}
                                    </small>
                                    <small class="text-muted d-block mb-1">
                                        <i class='bx bx-envelope'></i> {{ $interview->email ?? '-' }}
                                    </small>
                                    <small class="text-muted d-block mb-1">
                                        <i class='bx bx-calendar'></i> {{ \Carbon\Carbon::parse($interview->interview_date)->locale('id')->translatedFormat('d M Y') }}
                                    </small>
                                    <small class="text-muted d-block">
                                        <i class='bx bx-time'></i> {{ \Carbon\Carbon::parse($interview->interview_time)->format('H:i') }} WIB
                                    </small>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        @if ($interview->status == 'scheduled')
                                            <span class="badge bg-warning">Terjadwal</span>
                                        @elseif($interview->status == 'notified')
                                            <span class="badge bg-info">Ternotifikasi</span>
                                        @elseif($interview->status == 'confirmed')
                                            <span class="badge bg-success">Dikonfirmasi</span>
                                        @elseif($interview->status == 'completed')
                                            <span class="badge bg-primary">Selesai</span>
                                        @else
                                            <span class="badge bg-danger">Dibatalkan</span>
                                        @endif
                                    </div>
                                    <div>
                                        @if($interview->wa_sent_at)
                                            <i class='bx bx-check-circle text-success'></i>
                                            <small class="text-muted">WA Terkirim</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <i class='bx bx-info-circle' style="font-size: 48px; color: #ccc;"></i>
                            <p class="mt-2 mb-0 text-muted">Belum ada jadwal interview</p>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap">
                    <div class="mb-2 mb-md-0">
                        <span class="text-muted">
                            Menampilkan {{ $interviews->firstItem() ?? 0 }} - {{ $interviews->lastItem() ?? 0 }}
                            dari {{ $interviews->total() }} data
                        </span>
                    </div>
                    <div>
                        {{ $interviews->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="interviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Jadwalkan Interview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="interviewForm">
                    <div class="modal-body">
                        <input type="hidden" id="interview_id" name="id">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Kandidat <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="candidate_name" name="candidate_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nomor HP/WhatsApp <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="phone" name="phone" placeholder="08xxxxxxxxxx" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Posisi yang Dilamar <span class="text-danger">*</span></label>
                                <select class="form-select" id="position_id" name="position_id" required>
                                    <option value="">Pilih Posisi</option>
                                    @foreach ($positions as $position)
                                        <option value="{{ $position->id }}">{{ $position->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Interview <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="interview_date" name="interview_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Waktu Interview <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="interview_time" name="interview_time" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Lokasi Interview</label>
                            <input type="text" class="form-control" id="location" name="location" value="Kantor PT Mingda">
                        </div>

                        <div class="mb-3" id="statusGroup" style="display: none;">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="scheduled">Terjadwal</option>
                                <option value="notified">Ternotifikasi</option>
                                <option value="confirmed">Dikonfirmasi</option>
                                <option value="completed">Selesai</option>
                                <option value="cancelled">Dibatalkan</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Catatan tambahan (opsional)"></textarea>
                        </div>

                        <!-- Custom Message Template -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Template Pesan WhatsApp</label>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="manageTemplatesBtn">
                                        <i class='bx bx-folder'></i> Kelola Template
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Template Selector -->
                            <select class="form-select mb-2" id="templateSelector">
                                <option value="">-- Pilih Template atau Tulis Manual --</option>
                            </select>

                            <textarea class="form-control" id="custom_message_template" name="custom_message_template" rows="8" 
                                      placeholder="Pilih template dari dropdown atau tulis pesan custom Anda..."></textarea>
                            
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <small class="text-muted">
                                    <strong>Placeholder:</strong> 
                                    <code>{nama}</code>, <code>{posisi}</code>, <code>{tanggal}</code>, 
                                    <code>{waktu}</code>, <code>{lokasi}</code>, <code>{catatan}</code>
                                </small>
                                <small class="text-muted">
                                    <span id="charCount">0</span> karakter
                                </small>
                            </div>

                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-info" id="previewMessageBtn">
                                    <i class='bx bx-show'></i> Preview
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success" id="saveAsTemplateBtn">
                                    <i class='bx bx-save'></i> Simpan sebagai Template
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="saveBtn">
                            <i class="bx bx-save me-1"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Interview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manage Templates Modal -->
    <div class="modal fade" id="manageTemplatesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kelola Template Pesan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nama Template</th>
                                    <th width="100">Pemakaian</th>
                                    <th width="80">Default</th>
                                    <th width="160">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="templatesTableBody">
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Template Modal -->
    <div class="modal fade" id="editTemplateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Template Pesan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editTemplateForm">
                    <div class="modal-body">
                        <input type="hidden" id="edit_template_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Template <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_template_name" required>
                            <small class="text-muted" id="defaultTemplateWarning" style="display: none;">
                                <i class='bx bx-info-circle'></i> Nama template default tidak dapat diubah
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Isi Template <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="edit_template_message" rows="10" required></textarea>
                            <small class="text-muted d-block mt-1">
                                <strong>Gunakan placeholder:</strong> 
                                <code>{nama}</code>, <code>{posisi}</code>, <code>{tanggal}</code>, 
                                <code>{waktu}</code>, <code>{lokasi}</code>, <code>{catatan}</code>
                            </small>
                            <small class="text-muted">
                                <span id="editCharCount">0</span> karakter
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="saveEditTemplateBtn">
                            <i class="bx bx-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            let messageTemplates = [];

            // Load Templates on Page Load
            function loadTemplates() {
                $.ajax({
                    url: '/api/admin/interviews/templates/list',
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            messageTemplates = response.data;
                            populateTemplateSelector();
                        }
                    },
                    error: function() {
                        console.error('Failed to load templates');
                    }
                });
            }

            // Populate Template Selector
            function populateTemplateSelector() {
                const selector = $('#templateSelector');
                selector.html('<option value="">-- Pilih Template atau Tulis Manual --</option>');
                
                messageTemplates.forEach(template => {
                    const defaultLabel = template.is_default ? ' (Default)' : '';
                    selector.append(`<option value="${template.id}" data-template="${template.message_template}">${template.name}${defaultLabel}</option>`);
                });
            }

            // Template Selector Change
            $('#templateSelector').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const template = selectedOption.data('template');
                
                if (template) {
                    $('#custom_message_template').val(template);
                    updateCharCount();
                }
            });

            // Load templates on page load
            $(document).ready(function() {
                loadTemplates();
            });

            // Per page selector
            $('#perPageSelect').on('change', function() {
                const perPage = $(this).val();
                const url = new URL(window.location.href);
                url.searchParams.set('per_page', perPage);
                url.searchParams.delete('page');
                window.location.href = url.toString();
            });

            // Add Interview
            $('#addInterviewBtn').on('click', function() {
                $('#modalTitle').text('Jadwalkan Interview');
                $('#interviewForm')[0].reset();
                $('#interview_id').val('');
                $('#custom_message_template').val('');
                $('#statusGroup').hide();
                updateCharCount();
                const modal = new bootstrap.Modal(document.getElementById('interviewModal'));
                modal.show();
            });

            // Save Interview (Add/Edit)
            $('#interviewForm').on('submit', function(e) {
                e.preventDefault();

                const id = $('#interview_id').val();
                const url = id ? `/api/admin/interviews/${id}` : '/api/admin/interviews';
                const method = id ? 'PUT' : 'POST';

                const formData = {
                    candidate_name: $('#candidate_name').val(),
                    phone: $('#phone').val(),
                    email: $('#email').val() || null,
                    position_id: $('#position_id').val(),
                    interview_date: $('#interview_date').val(),
                    interview_time: $('#interview_time').val(),
                    location: $('#location').val(),
                    notes: $('#notes').val() || null,
                    custom_message_template: $('#custom_message_template').val() || null,
                };

                if (id) {
                    formData.status = $('#status').val();
                }

                $('#saveBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');

                $.ajax({
                    url: url,
                    method: method,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    data: JSON.stringify(formData),
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 2000
                        }).then(() => {
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        let errorMessage = 'Gagal menyimpan data';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage += '<br><br><div class="text-start">';
                            for (const [field, messages] of Object.entries(errors)) {
                                errorMessage += `<small>• ${messages.join(', ')}</small><br>`;
                            }
                            errorMessage += '</div>';
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            html: errorMessage
                        });
                        $('#saveBtn').prop('disabled', false).html('<i class="bx bx-save me-1"></i> Simpan');
                    }
                });
            });

            // Edit Interview
            $(document).on('click', '.edit-btn', function() {
                const id = $(this).data('id');

                $.ajax({
                    url: `/api/admin/interviews/${id}`,
                    method: 'GET',
                    success: function(response) {
                        const data = response.data;

                        $('#modalTitle').text('Edit Interview');
                        $('#interview_id').val(data.id);
                        $('#candidate_name').val(data.candidate_name);
                        $('#phone').val(data.phone);
                        $('#email').val(data.email);
                        $('#position_id').val(data.position_id);
                        
                        // Parse date
                        const dateStr = String(data.interview_date);
                        const match = dateStr.match(/(\d{4})-(\d{2})-(\d{2})/);
                        if (match) {
                            $('#interview_date').val(`${match[1]}-${match[2]}-${match[3]}`);
                        }

                        $('#interview_time').val(data.interview_time ? data.interview_time.substring(0, 5) : '');
                        $('#location').val(data.location);
                        $('#notes').val(data.notes);
                        $('#custom_message_template').val(data.custom_message_template || '');
                        $('#status').val(data.status);
                        $('#statusGroup').show();
                        
                        // Update character count
                        updateCharCount();

                        const modal = new bootstrap.Modal(document.getElementById('interviewModal'));
                        modal.show();
                    },
                    error: function() {
                        Swal.fire('Error', 'Gagal memuat data interview', 'error');
                    }
                });
            });

            // Detail Interview
            $(document).on('click', '.detail-btn', function() {
                const id = $(this).data('id');
                const modal = new bootstrap.Modal(document.getElementById('detailModal'));

                $('#detailContent').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
                modal.show();

                $.ajax({
                    url: `/api/admin/interviews/${id}`,
                    method: 'GET',
                    success: function(response) {
                        const data = response.data;
                        const date = new Date(data.interview_date).toLocaleDateString('id-ID', {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'});
                        
                        let statusBadge = '';
                        if (data.status === 'scheduled') statusBadge = '<span class="badge bg-warning">Terjadwal</span>';
                        else if (data.status === 'notified') statusBadge = '<span class="badge bg-info">Ternotifikasi</span>';
                        else if (data.status === 'confirmed') statusBadge = '<span class="badge bg-success">Dikonfirmasi</span>';
                        else if (data.status === 'completed') statusBadge = '<span class="badge bg-primary">Selesai</span>';
                        else statusBadge = '<span class="badge bg-danger">Dibatalkan</span>';

                        $('#detailContent').html(`
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="mb-3">Informasi Kandidat</h6>
                                    <table class="table table-sm">
                                        <tr><th width="40%">Nama</th><td>${data.candidate_name}</td></tr>
                                        <tr><th>No. HP</th><td>${data.phone}</td></tr>
                                        <tr><th>Email</th><td>${data.email || '-'}</td></tr>
                                        <tr><th>Posisi</th><td>${data.position.name}</td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="mb-3">Detail Interview</h6>
                                    <table class="table table-sm">
                                        <tr><th width="40%">Tanggal</th><td>${date}</td></tr>
                                        <tr><th>Waktu</th><td>${data.interview_time.substring(0, 5)} WIB</td></tr>
                                        <tr><th>Lokasi</th><td>${data.location}</td></tr>
                                        <tr><th>Status</th><td>${statusBadge}</td></tr>
                                    </table>
                                </div>
                            </div>
                            ${data.notes ? `<hr><h6>Catatan</h6><p>${data.notes}</p>` : ''}
                            ${data.wa_sent_at ? `<hr><div class="alert alert-info mb-0"><strong>WhatsApp Terkirim:</strong> ${new Date(data.wa_sent_at).toLocaleString('id-ID')}</div>` : ''}
                        `);
                    },
                    error: function() {
                        $('#detailContent').html('<div class="alert alert-danger">Gagal memuat detail interview</div>');
                    }
                });
            });

            // Delete Interview
            $(document).on('click', '.delete-btn', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');

                Swal.fire({
                    title: 'Hapus Interview?',
                    html: `Apakah Anda yakin ingin menghapus interview untuk:<br><strong>${name}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/api/admin/interviews/${id}`,
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil!',
                                    text: response.message,
                                    showConfirmButton: false,
                                    timer: 2000
                                }).then(() => {
                                    window.location.reload();
                                });
                            },
                            error: function() {
                                Swal.fire('Error', 'Gagal menghapus interview', 'error');
                            }
                        });
                    }
                });
            });

            // Send WhatsApp Single
            $(document).on('click', '.send-wa-btn', function() {
                const id = $(this).data('id');

                Swal.fire({
                    title: 'Kirim Notifikasi WhatsApp?',
                    text: 'Pesan undangan interview akan dikirim ke kandidat',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Kirim!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Mengirim...',
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); }
                        });

                        $.ajax({
                            url: `/api/admin/interviews/${id}/send-notification`,
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil!',
                                    text: response.message,
                                    showConfirmButton: false,
                                    timer: 2000
                                }).then(() => {
                                    window.location.reload();
                                });
                            },
                            error: function(xhr) {
                                Swal.fire('Error', xhr.responseJSON?.message || 'Gagal mengirim WhatsApp', 'error');
                            }
                        });
                    }
                });
            });

            // Select All Checkbox
            $('#selectAll').on('change', function() {
                $('.interview-checkbox').prop('checked', $(this).prop('checked'));
                updateBulkButton();
            });

            $(document).on('change', '.interview-checkbox', function() {
                updateBulkButton();
            });

            function updateBulkButton() {
                const count = $('.interview-checkbox:checked').length;
                if (count > 0) {
                    $('#bulkBlastBtn').show();
                    $('#selectedCount').text(count);
                } else {
                    $('#bulkBlastBtn').hide();
                }
            }

            // Bulk Blast WhatsApp
            $('#bulkBlastBtn').on('click', function() {
                const ids = [];
                const names = [];

                $('.interview-checkbox:checked').each(function() {
                    ids.push($(this).val());
                    names.push($(this).data('name'));
                });

                if (ids.length === 0) return;

                let namesList = '<div class="text-start mt-3" style="max-height: 200px; overflow-y: auto;">';
                names.forEach((name, idx) => {
                    namesList += `<small>${idx + 1}. ${name}</small><br>`;
                });
                namesList += '</div>';

                Swal.fire({
                    title: `Blast WhatsApp ke ${ids.length} Kandidat?`,
                    html: `Pesan undangan interview akan dikirim ke:${namesList}`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Blast Sekarang!',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#25D366'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Mengirim Blast WhatsApp...',
                            html: `Sedang mengirim ke ${ids.length} kandidat...`,
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); }
                        });

                        $.ajax({
                            url: '/api/admin/interviews/bulk-send-notification',
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            data: JSON.stringify({ ids: ids }),
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Selesai!',
                                    html: `
                                        <p>${response.message}</p>
                                        <table class="table table-sm mt-2">
                                            <tr><td>Berhasil</td><td class="text-end"><strong class="text-success">${response.data.sent}</strong></td></tr>
                                            <tr><td>Gagal</td><td class="text-end"><strong class="text-danger">${response.data.failed}</strong></td></tr>
                                        </table>
                                    `
                                }).then(() => {
                                    window.location.reload();
                                });
                            },
                            error: function(xhr) {
                                Swal.fire('Error', xhr.responseJSON?.message || 'Gagal melakukan blast WhatsApp', 'error');
                            }
                        });
                    }
                });
            });

            // Character Counter
            function updateCharCount() {
                const count = $('#custom_message_template').val().length;
                $('#charCount').text(count);
            }

            $('#custom_message_template').on('input', function() {
                updateCharCount();
            });

            // Save As Template Button
            $('#saveAsTemplateBtn').on('click', function() {
                const template = $('#custom_message_template').val().trim();
                
                if (!template) {
                    Swal.fire('Error', 'Template pesan tidak boleh kosong', 'error');
                    return;
                }

                Swal.fire({
                    title: 'Simpan Template',
                    input: 'text',
                    inputLabel: 'Nama Template',
                    inputPlaceholder: 'Contoh: Template Friendly, Template Formal, dll',
                    showCancelButton: true,
                    confirmButtonText: 'Simpan',
                    cancelButtonText: 'Batal',
                    inputValidator: (value) => {
                        if (!value) {
                            return 'Nama template harus diisi!';
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/api/admin/interviews/templates/save',
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            data: JSON.stringify({
                                name: result.value,
                                message_template: template
                            }),
                            success: function(response) {
                                Swal.fire('Berhasil!', 'Template berhasil disimpan', 'success');
                                loadTemplates(); // Reload templates
                            },
                            error: function(xhr) {
                                Swal.fire('Error', xhr.responseJSON?.message || 'Gagal menyimpan template', 'error');
                            }
                        });
                    }
                });
            });

            // Manage Templates Button
            $('#manageTemplatesBtn').on('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('manageTemplatesModal'));
                modal.show();
                loadTemplatesTable();
            });

            // Load Templates Table
            function loadTemplatesTable() {
                const tbody = $('#templatesTableBody');
                tbody.html('<tr><td colspan="4" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>');

                $.ajax({
                    url: '/api/admin/interviews/templates/list',
                    method: 'GET',
                    success: function(response) {
                        if (response.success && response.data.length > 0) {
                            let html = '';
                            response.data.forEach(template => {
                                const defaultBadge = template.is_default ? '<span class="badge bg-primary">Ya</span>' : '<span class="badge bg-secondary">Tidak</span>'; 
                                const deleteBtn = !template.is_default ? `<button class="btn btn-sm btn-danger delete-template-btn" data-id="${template.id}"><i class='bx bx-trash'></i></button>` : '';
                                
                                html += `
                                    <tr>
                                        <td><strong>${template.name}</strong></td>
                                        <td>${template.usage_count}x</td>
                                        <td>${defaultBadge}</td>
                                        <td>
                                            <button class="btn btn-sm btn-info preview-template-btn" data-message="${template.message_template.replace(/"/g, '&quot;')}">
                                                <i class='bx bx-show'></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning edit-template-btn" 
                                                data-id="${template.id}" 
                                                data-name="${template.name.replace(/"/g, '&quot;')}" 
                                                data-message="${template.message_template.replace(/"/g, '&quot;')}"
                                                data-is-default="${template.is_default ? 1 : 0}">
                                                <i class='bx bx-edit'></i>
                                            </button>
                                            ${deleteBtn}
                                        </td>
                                    </tr>
                                `;
                            });
                            tbody.html(html);
                        } else {
                            tbody.html('<tr><td colspan="4" class="text-center py-4 text-muted">Belum ada template tersimpan</td></tr>');
                        }
                    },
                    error: function() {
                        tbody.html('<tr><td colspan="4" class="text-center py-4 text-danger">Gagal memuat template</td></tr>');
                    }
                });
            }

            // Preview Template in Manage Modal
            $(document).on('click', '.preview-template-btn', function() {
                const message = $(this).data('message');
                Swal.fire({
                    title: 'Preview Template',
                    html: `<div class="text-start" style="white-space: pre-wrap; background: #f5f5f5; padding: 15px; border-radius: 8px;">${message.replace(/\n/g, '<br>')}</div>`,
                    width: '600px'
                });
            });

            // Delete Template
            $(document).on('click', '.delete-template-btn', function() {
                const id = $(this).data('id');
                
                Swal.fire({
                    title: 'Hapus Template?',
                    text: 'Template yang dihapus tidak dapat dikembalikan',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/api/admin/interviews/templates/${id}`,
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            success: function(response) {
                                Swal.fire('Berhasil!', response.message, 'success');
                                loadTemplatesTable();
                                loadTemplates(); // Reload selector
                            },
                            error: function(xhr) {
                                Swal.fire('Error', xhr.responseJSON?.message || 'Gagal menghapus template', 'error');
                            }
                        });
                    }
                });
            });

            // Edit Template
            $(document).on('click', '.edit-template-btn', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                const message = $(this).data('message');
                const isDefault = $(this).data('is-default') == 1;

                // Populate form
                $('#edit_template_id').val(id);
                $('#edit_template_name').val(name);
                $('#edit_template_message').val(message);
                
                // Update character count
                $('#editCharCount').text(message.length);
                
                // Show/hide warning for default templates
                if (isDefault) {
                    $('#edit_template_name').prop('readonly', true);
                    $('#defaultTemplateWarning').show();
                } else {
                    $('#edit_template_name').prop('readonly', false);
                    $('#defaultTemplateWarning').hide();
                }

                // Show modal
                const editModal = new bootstrap.Modal(document.getElementById('editTemplateModal'));
                editModal.show();
            });

            // Character counter for edit template
            $('#edit_template_message').on('input', function() {
                $('#editCharCount').text($(this).val().length);
            });

            // Submit Edit Template Form
            $('#editTemplateForm').on('submit', function(e) {
                e.preventDefault();
                
                const id = $('#edit_template_id').val();
                const name = $('#edit_template_name').val().trim();
                const message = $('#edit_template_message').val().trim();

                if (!name || !message) {
                    Swal.fire('Error', 'Nama dan isi template harus diisi', 'error');
                    return;
                }

                const saveBtn = $('#saveEditTemplateBtn');
                saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');

                $.ajax({
                    url: `/api/admin/interviews/templates/${id}`,
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    data: JSON.stringify({
                        name: name,
                        message_template: message
                    }),
                    success: function(response) {
                        Swal.fire('Berhasil!', response.message, 'success');
                        
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editTemplateModal'));
                        modal.hide();
                        
                        // Reload templates
                        loadTemplatesTable();
                        loadTemplates();
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Gagal mengupdate template', 'error');
                    },
                    complete: function() {
                        saveBtn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Simpan Perubahan');
                    }
                });
            });

            // Preview Message Button
            $('#previewMessageBtn').on('click', function() {
                const template = $('#custom_message_template').val();
                const candidateName = $('#candidate_name').val() || '[Nama Kandidat]';
                const positionId = $('#position_id').val();
                const positionName = positionId ? $('#position_id option:selected').text() : '[Posisi]';
                const interviewDate = $('#interview_date').val();
                const interviewTime = $('#interview_time').val();
                const location = $('#location').val() || 'Kantor PT Mingda';
                const notes = $('#notes').val() || '';

                // Format date
                let formattedDate = '[Tanggal]';
                if (interviewDate) {
                    const date = new Date(interviewDate);
                    formattedDate = date.toLocaleDateString('id-ID', { 
                        weekday: 'long', 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    });
                }

                // Format time
                const formattedTime = interviewTime || '[Waktu]';

                // Use template if provided, otherwise use default
                let message = template || `*Undangan Interview - PT Mingda*

Kepada Yth,
*{nama}*

Berdasarkan hasil seleksi berkas Anda, kami mengundang Anda untuk mengikuti sesi interview untuk posisi *{posisi}*.

📅 *Tanggal:* {tanggal}
🕐 *Waktu:* {waktu} WIB
📍 *Lokasi:* {lokasi}

📝 *Catatan:*
{catatan}

Mohon konfirmasi kehadiran Anda dengan membalas pesan ini.

Terima kasih dan sampai jumpa di hari interview.

*HRD PT Mingda*`;

                // Replace placeholders
                message = message.replace(/{nama}/g, candidateName);
                message = message.replace(/{posisi}/g, positionName);
                message = message.replace(/{tanggal}/g, formattedDate);
                message = message.replace(/{waktu}/g, formattedTime);
                message = message.replace(/{lokasi}/g, location);
                message = message.replace(/{catatan}/g, notes);

                // Show preview in modal
                Swal.fire({
                    title: 'Preview Pesan WhatsApp',
                    html: `
                        <div class="text-start" style="white-space: pre-wrap; background: #f5f5f5; padding: 15px; border-radius: 8px; font-family: system-ui;">
                            ${message.replace(/\n/g, '<br>')}
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">Total: <strong>${message.length}</strong> karakter</small>
                        </div>
                    `,
                    width: '600px',
                    confirmButtonText: 'OK'
                });
            });
        </script>
    @endpush
@endsection
