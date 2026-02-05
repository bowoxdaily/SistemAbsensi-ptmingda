@extends('layouts.app')

@section('title', 'Pengaturan Fingerspot')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Pengaturan /</span> Fingerspot
        </h4>

        <div class="row">
            <!-- Fingerspot Settings List -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Daftar Device Fingerspot</h5>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                            data-bs-target="#addSettingModal">
                            <i class="bx bx-plus"></i> Tambah Device
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="settings-container">
                            <div class="text-center py-5" id="empty-state">
                                <i class="bx bx-fingerprint" style="font-size: 4rem; color: #ddd;"></i>
                                <p class="text-muted mt-3">Belum ada device yang dikonfigurasi</p>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#addSettingModal">
                                    <i class="bx bx-plus"></i> Tambah Device Pertama
                                </button>
                            </div>
                            <div class="table-responsive d-none" id="settings-table">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Nama Device</th>
                                            <th>Serial Number</th>
                                            <th>Mode</th>
                                            <th>Status</th>
                                            <th>Last Sync</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="settings-tbody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Webhook Logs -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Log Webhook</h5>
                        <div>
                            <select class="form-select form-select-sm d-inline-block w-auto me-2" id="log-status-filter">
                                <option value="">Semua Status</option>
                                <option value="success">Success</option>
                                <option value="failed">Failed</option>
                                <option value="skipped">Skipped</option>
                                <option value="pending">Pending</option>
                            </select>
                            <button type="button" class="btn btn-outline-warning btn-sm me-1" id="reprocess-pending"
                                title="Reprocess Pending & Failed">
                                <i class="bx bx-revision"></i> Reprocess
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="refresh-logs">
                                <i class="bx bx-refresh"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm" id="logs-table">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>PIN</th>
                                        <th>Karyawan</th>
                                        <th>Scan Time</th>
                                        <th>Status</th>
                                        <th>Message</th>
                                    </tr>
                                </thead>
                                <tbody id="logs-tbody">
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="logs-pagination" class="mt-3"></div>
                    </div>
                </div>
            </div>

            <!-- Information Card -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Informasi Webhook</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="mb-2">
                                <i class="bx bx-link text-primary"></i> Webhook URL
                            </h6>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="webhook-url" readonly
                                    value="{{ url('/api/fingerspot/webhook') }}">
                                <button class="btn btn-outline-secondary" type="button" id="copy-webhook-url">
                                    <i class="bx bx-copy"></i>
                                </button>
                            </div>
                            <small class="text-muted">Copy URL ini ke konfigurasi Fingerspot Cloud</small>
                        </div>

                        <div class="mb-3">
                            <h6 class="mb-2">
                                <i class="bx bx-key text-warning"></i> Authentication
                            </h6>
                            <p class="text-muted small mb-2">
                                Kirim token via header atau query parameter:
                            </p>
                            <code class="d-block small mb-1">X-Fingerspot-Token: YOUR_TOKEN</code>
                            <code class="d-block small mb-1">Authorization: Bearer YOUR_TOKEN</code>
                            <code class="d-block small">?token=YOUR_TOKEN</code>
                        </div>

                        <div class="mb-3">
                            <h6 class="mb-2">
                                <i class="bx bx-code-alt text-info"></i> Expected Payload
                            </h6>
                            <pre class="bg-light p-2 rounded small mb-0"><code>{
  "attlog": [
    {
      "pin": "123",
      "datetime": "2026-02-03 08:00:00",
      "status_scan": "0",
      "verify_mode": "1"
    }
  ]
}</code></pre>
                        </div>

                        <div class="mb-0">
                            <h6 class="mb-2">
                                <i class="bx bx-time-five text-success"></i> Scan Mode
                            </h6>
                            <p class="text-muted small mb-1">
                                <strong>First/Last:</strong> Scan pertama = Check-in, scan terakhir = Check-out
                            </p>
                            <p class="text-muted small mb-0">
                                <strong>All:</strong> Log semua scan, selalu update check-out ke scan terbaru
                            </p>
                        </div>
                    </div>
                </div>

                <!-- PIN Mapping Info -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">PIN Mapping</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">
                            Sistem akan mencocokkan PIN dari mesin fingerprint dengan:
                        </p>
                        <ol class="small text-muted mb-0">
                            <li><code>fingerspot_pin</code> pada data karyawan</li>
                            <li><code>employee_code</code> jika PIN tidak ditemukan</li>
                        </ol>
                        <hr>
                        <p class="text-muted small mb-0">
                            <i class="bx bx-info-circle"></i> Pastikan PIN di mesin sesuai dengan kode karyawan atau set
                            fingerspot_pin di data karyawan.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Setting Modal -->
    <div class="modal fade" id="addSettingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Device Fingerspot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addSettingForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="add_name">Nama Device <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_name" name="name"
                                placeholder="Contoh: Fingerspot Lantai 1" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="add_sn">Serial Number</label>
                            <input type="text" class="form-control" id="add_sn" name="sn"
                                placeholder="Serial number mesin (opsional)">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="add_api_url">API URL (Pull Mode)</label>
                            <input type="url" class="form-control" id="add_api_url" name="api_url"
                                placeholder="https://api.mingda.my.id/get_webhook.php">
                            <small class="text-muted">URL untuk fetch data attlog (opsional, untuk mode pull)</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="add_auto_sync" name="auto_sync">
                                <label class="form-check-label" for="add_auto_sync">Auto Sync (setiap 5 menit)</label>
                            </div>
                            <small class="text-muted">Otomatis ambil data dari API URL secara berkala</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="add_scan_mode">Scan Mode</label>
                            <select class="form-select" id="add_scan_mode" name="scan_mode">
                                <option value="first_last">First/Last (Recommended)</option>
                                <option value="all">All Scans</option>
                            </select>
                            <small class="text-muted">Mode penentuan check-in dan check-out</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="add_is_active" name="is_active"
                                    checked>
                                <label class="form-check-label" for="add_is_active">Aktif</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="add_notes">Catatan</label>
                            <textarea class="form-control" id="add_notes" name="notes" rows="2"
                                placeholder="Catatan tambahan (opsional)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Setting Modal -->
    <div class="modal fade" id="editSettingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Device Fingerspot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editSettingForm">
                    @csrf
                    <input type="hidden" id="edit_id" name="id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="edit_name">Nama Device <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="edit_sn">Serial Number</label>
                            <input type="text" class="form-control" id="edit_sn" name="sn">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="edit_api_url">API URL (Pull Mode)</label>
                            <div class="input-group">
                                <input type="url" class="form-control" id="edit_api_url" name="api_url"
                                    placeholder="https://api.mingda.my.id/get_webhook.php">
                                <button class="btn btn-success" type="button" id="sync-from-api">
                                    <i class="bx bx-sync"></i> Sync Now
                                </button>
                            </div>
                            <small class="text-muted">URL untuk fetch data attlog (mode pull)</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="edit_auto_sync" name="auto_sync">
                                <label class="form-check-label" for="edit_auto_sync">Auto Sync (setiap 5 menit)</label>
                            </div>
                            <small class="text-muted">Otomatis ambil data dari API URL secara berkala. Pastikan
                                cron/scheduler berjalan.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Webhook Token</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="edit_token" readonly>
                                <button class="btn btn-outline-warning" type="button" id="regenerate-token">
                                    <i class="bx bx-refresh"></i> Regenerate
                                </button>
                            </div>
                            <small class="text-muted">Token untuk autentikasi webhook</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="edit_scan_mode">Scan Mode</label>
                            <select class="form-select" id="edit_scan_mode" name="scan_mode">
                                <option value="first_last">First/Last (Recommended)</option>
                                <option value="all">All Scans</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                <label class="form-check-label" for="edit_is_active">Aktif</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="edit_notes">Catatan</label>
                            <textarea class="form-control" id="edit_notes" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger me-auto" id="delete-setting">
                            <i class="bx bx-trash"></i> Hapus
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            const csrfToken = $('meta[name="csrf-token"]').attr('content');

            // Load settings on page load
            loadSettings();
            loadLogs();

            // Copy webhook URL
            $('#copy-webhook-url').on('click', function() {
                const url = $('#webhook-url').val();
                navigator.clipboard.writeText(url).then(function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: 'Webhook URL berhasil disalin',
                        timer: 1500,
                        showConfirmButton: false
                    });
                });
            });

            // Load settings
            function loadSettings() {
                $.ajax({
                    url: '/api/settings/fingerspot',
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(response) {
                        if (response.success && response.data.length > 0) {
                            renderSettings(response.data);
                        } else {
                            $('#empty-state').removeClass('d-none');
                            $('#settings-table').addClass('d-none');
                        }
                    },
                    error: function(xhr) {
                        console.error('Error loading settings:', xhr);
                    }
                });
            }

            // Render settings table
            function renderSettings(settings) {
                $('#empty-state').addClass('d-none');
                $('#settings-table').removeClass('d-none');

                let html = '';
                settings.forEach((setting, index) => {
                    const lastSync = setting.last_sync_at ? new Date(setting.last_sync_at).toLocaleString(
                        'id-ID') : 'Never';
                    const autoSyncBadge = setting.auto_sync ?
                        '<span class="badge bg-label-success ms-1" title="Auto Sync Aktif"><i class="bx bx-sync"></i></span>' :
                        '';
                    html += `
                <tr>
                    <td>${index + 1}</td>
                    <td><strong>${setting.name}</strong>${autoSyncBadge}</td>
                    <td><code>${setting.sn || '-'}</code></td>
                    <td><span class="badge bg-label-info">${setting.scan_mode === 'first_last' ? 'First/Last' : 'All'}</span></td>
                    <td>
                        <span class="badge ${setting.is_active ? 'bg-success' : 'bg-secondary'}">
                            ${setting.is_active ? 'Aktif' : 'Nonaktif'}
                        </span>
                    </td>
                    <td><small class="text-muted">${lastSync}</small></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary edit-setting" data-id="${setting.id}">
                            <i class="bx bx-edit-alt"></i>
                        </button>
                    </td>
                </tr>
            `;
                });
                $('#settings-tbody').html(html);
            }

            // Load logs
            function loadLogs(page = 1) {
                const status = $('#log-status-filter').val();
                $.ajax({
                    url: '/api/settings/fingerspot/logs',
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    data: {
                        page: page,
                        status: status,
                        per_page: 20
                    },
                    success: function(response) {
                        if (response.success) {
                            renderLogs(response.data);
                        }
                    },
                    error: function(xhr) {
                        $('#logs-tbody').html(
                            '<tr><td colspan="6" class="text-center text-danger">Error loading logs</td></tr>'
                        );
                    }
                });
            }

            // Render logs table
            function renderLogs(paginatedData) {
                const logs = paginatedData.data;
                if (logs.length === 0) {
                    $('#logs-tbody').html(
                        '<tr><td colspan="6" class="text-center text-muted">Tidak ada log</td></tr>');
                    $('#logs-pagination').html('');
                    return;
                }

                let html = '';
                logs.forEach(log => {
                    const statusClass = {
                        'success': 'bg-success',
                        'failed': 'bg-danger',
                        'skipped': 'bg-warning',
                        'pending': 'bg-secondary'
                    };
                    html += `
                <tr>
                    <td><small>${new Date(log.created_at).toLocaleString('id-ID')}</small></td>
                    <td><code>${log.pin}</code></td>
                    <td>${log.employee ? log.employee.name : '<span class="text-muted">-</span>'}</td>
                    <td><small>${new Date(log.scan_time).toLocaleString('id-ID')}</small></td>
                    <td><span class="badge ${statusClass[log.process_status] || 'bg-secondary'}">${log.process_status}</span></td>
                    <td><small class="text-muted">${log.process_message || '-'}</small></td>
                </tr>
            `;
                });
                $('#logs-tbody').html(html);

                // Pagination with info
                renderPagination(paginatedData);
            }

            // Render pagination
            function renderPagination(data) {
                const currentPage = data.current_page;
                const lastPage = data.last_page;
                const total = data.total;
                const from = data.from || 0;
                const to = data.to || 0;

                if (lastPage <= 1) {
                    $('#logs-pagination').html(`
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Menampilkan ${from}-${to} dari ${total} data</small>
                        </div>
                    `);
                    return;
                }

                let pagesHtml = '';

                // Previous button
                pagesHtml += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link log-page" href="#" data-page="${currentPage - 1}" ${currentPage === 1 ? 'tabindex="-1"' : ''}>
                        <i class="bx bx-chevron-left"></i>
                    </a>
                </li>`;

                // Page numbers with ellipsis
                const maxVisible = 5;
                let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
                let endPage = Math.min(lastPage, startPage + maxVisible - 1);

                if (endPage - startPage < maxVisible - 1) {
                    startPage = Math.max(1, endPage - maxVisible + 1);
                }

                // First page
                if (startPage > 1) {
                    pagesHtml += `<li class="page-item">
                        <a class="page-link log-page" href="#" data-page="1">1</a>
                    </li>`;
                    if (startPage > 2) {
                        pagesHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                }

                // Visible pages
                for (let i = startPage; i <= endPage; i++) {
                    pagesHtml += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link log-page" href="#" data-page="${i}">${i}</a>
                    </li>`;
                }

                // Last page
                if (endPage < lastPage) {
                    if (endPage < lastPage - 1) {
                        pagesHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                    pagesHtml += `<li class="page-item">
                        <a class="page-link log-page" href="#" data-page="${lastPage}">${lastPage}</a>
                    </li>`;
                }

                // Next button
                pagesHtml += `<li class="page-item ${currentPage === lastPage ? 'disabled' : ''}">
                    <a class="page-link log-page" href="#" data-page="${currentPage + 1}" ${currentPage === lastPage ? 'tabindex="-1"' : ''}>
                        <i class="bx bx-chevron-right"></i>
                    </a>
                </li>`;

                $('#logs-pagination').html(`
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">Menampilkan ${from}-${to} dari ${total} data</small>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                ${pagesHtml}
                            </ul>
                        </nav>
                    </div>
                `);
            }

            // Refresh logs
            $('#refresh-logs, #log-status-filter').on('click change', function() {
                loadLogs();
            });

            // Log pagination
            $(document).on('click', '.log-page', function(e) {
                e.preventDefault();
                loadLogs($(this).data('page'));
            });

            // Add setting form submit
            $('#addSettingForm').on('submit', function(e) {
                e.preventDefault();
                const formData = {
                    name: $('#add_name').val(),
                    sn: $('#add_sn').val(),
                    api_url: $('#add_api_url').val(),
                    auto_sync: $('#add_auto_sync').is(':checked') ? 1 : 0,
                    scan_mode: $('#add_scan_mode').val(),
                    is_active: $('#add_is_active').is(':checked') ? 1 : 0,
                    notes: $('#add_notes').val()
                };

                $.ajax({
                    url: '/api/settings/fingerspot',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            $('#addSettingModal').modal('hide');
                            $('#addSettingForm')[0].reset();
                            loadSettings();
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Terjadi kesalahan',
                            'error');
                    }
                });
            });

            // Edit setting
            $(document).on('click', '.edit-setting', function() {
                const id = $(this).data('id');
                $.ajax({
                    url: '/api/settings/fingerspot',
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(response) {
                        const setting = response.data.find(s => s.id == id);
                        if (setting) {
                            $('#edit_id').val(setting.id);
                            $('#edit_name').val(setting.name);
                            $('#edit_sn').val(setting.sn);
                            $('#edit_api_url').val(setting.api_url);
                            $('#edit_auto_sync').prop('checked', setting.auto_sync);
                            $('#edit_token').val(setting.webhook_token);
                            $('#edit_scan_mode').val(setting.scan_mode);
                            $('#edit_is_active').prop('checked', setting.is_active);
                            $('#edit_notes').val(setting.notes);
                            $('#editSettingModal').modal('show');
                        }
                    }
                });
            });

            // Edit setting form submit
            $('#editSettingForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#edit_id').val();
                const formData = {
                    name: $('#edit_name').val(),
                    sn: $('#edit_sn').val(),
                    api_url: $('#edit_api_url').val(),
                    auto_sync: $('#edit_auto_sync').is(':checked') ? 1 : 0,
                    scan_mode: $('#edit_scan_mode').val(),
                    is_active: $('#edit_is_active').is(':checked') ? 1 : 0,
                    notes: $('#edit_notes').val()
                };

                $.ajax({
                    url: '/api/settings/fingerspot/' + id,
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            $('#editSettingModal').modal('hide');
                            loadSettings();
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Terjadi kesalahan',
                            'error');
                    }
                });
            });

            // Sync from API (Pull mode)
            $('#sync-from-api').on('click', function() {
                const apiUrl = $('#edit_api_url').val();
                if (!apiUrl) {
                    Swal.fire('Error', 'API URL belum diisi', 'error');
                    return;
                }

                Swal.fire({
                    title: 'Sync dari API?',
                    text: 'Sistem akan mengambil data attlog dari API dan memprosesnya.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Sync Sekarang',
                    cancelButtonText: 'Batal',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return $.ajax({
                            url: '/api/settings/fingerspot/fetch',
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken
                            },
                            data: {
                                api_url: apiUrl
                            }
                        }).then(response => {
                            return response;
                        }).catch(error => {
                            Swal.showValidationMessage(
                                error.responseJSON?.message || 'Gagal sync dari API'
                            );
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        const data = result.value.data;
                        Swal.fire({
                            icon: 'success',
                            title: 'Sync Berhasil',
                            html: `
                                <div class="text-start">
                                    <p><strong>Total Records:</strong> ${data.total}</p>
                                    <p><strong>Berhasil:</strong> ${data.processed}</p>
                                    <p><strong>Gagal:</strong> ${data.failed}</p>
                                    <p><strong>Duplikat (Skip):</strong> ${data.skipped}</p>
                                </div>
                            `
                        });
                        loadLogs();
                        loadSettings();
                    }
                });
            });

            // Regenerate token
            $('#regenerate-token').on('click', function() {
                const id = $('#edit_id').val();
                Swal.fire({
                    title: 'Regenerate Token?',
                    text: 'Token lama tidak akan berfungsi lagi. Anda perlu update token di Fingerspot Cloud.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Regenerate',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/api/settings/fingerspot/' + id + '/regenerate-token',
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken
                            },
                            success: function(response) {
                                if (response.success) {
                                    $('#edit_token').val(response.data.webhook_token);
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil',
                                        text: 'Token berhasil di-regenerate. Jangan lupa update di Fingerspot Cloud.',
                                        timer: 3000
                                    });
                                }
                            },
                            error: function(xhr) {
                                Swal.fire('Error', xhr.responseJSON?.message ||
                                    'Terjadi kesalahan', 'error');
                            }
                        });
                    }
                });
            });

            // Delete setting
            $('#delete-setting').on('click', function() {
                const id = $('#edit_id').val();
                Swal.fire({
                    title: 'Hapus Device?',
                    text: 'Device ini akan dihapus dari sistem.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#d33'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/api/settings/fingerspot/' + id,
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil',
                                        text: response.message,
                                        timer: 1500,
                                        showConfirmButton: false
                                    });
                                    $('#editSettingModal').modal('hide');
                                    loadSettings();
                                }
                            },
                            error: function(xhr) {
                                Swal.fire('Error', xhr.responseJSON?.message ||
                                    'Terjadi kesalahan', 'error');
                            }
                        });
                    }
                });
            });

            // Reprocess pending and failed logs
            $('#reprocess-pending').on('click', function() {
                Swal.fire({
                    title: 'Reprocess Log?',
                    text: 'Sistem akan memproses ulang semua log dengan status pending dan failed.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Proses Ulang',
                    cancelButtonText: 'Batal',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return $.ajax({
                            url: '/api/settings/fingerspot/logs/reprocess-all',
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken
                            }
                        }).then(response => {
                            return response;
                        }).catch(error => {
                            Swal.showValidationMessage(
                                error.responseJSON?.message ||
                                'Gagal memproses ulang'
                            );
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        const data = result.value.data;
                        Swal.fire({
                            icon: 'success',
                            title: 'Reprocess Selesai',
                            html: `
                                <div class="text-start">
                                    <p><strong>Total Diproses:</strong> ${data.total}</p>
                                    <p><strong>Berhasil:</strong> ${data.processed}</p>
                                    <p><strong>Gagal:</strong> ${data.failed}</p>
                                </div>
                            `
                        });
                        loadLogs();
                    }
                });
            });
        });
    </script>
@endpush
