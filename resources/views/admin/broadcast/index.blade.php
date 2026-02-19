@extends('layouts.app')

@section('title', 'Broadcast Pesan')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Komunikasi /</span> Broadcast Pesan
        </h4>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class='bx bx-check-circle'></i>
                                </span>
                            </div>
                            <div>
                                <small>Total Terkirim</small>
                                <h5 class="mb-0" id="totalSent">0</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-warning">
                                    <i class='bx bx-time'></i>
                                </span>
                            </div>
                            <div>
                                <small>Draft</small>
                                <h5 class="mb-0" id="totalDraft">0</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-danger">
                                    <i class='bx bx-error-circle'></i>
                                </span>
                            </div>
                            <div>
                                <small>Gagal</small>
                                <h5 class="mb-0" id="totalFailed">0</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class='bx bx-user'></i>
                                </span>
                            </div>
                            <div>
                                <small>Total Penerima</small>
                                <h5 class="mb-0" id="totalRecipients">0</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Broadcast Form Card -->
            <div class="col-md-5 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class='bx bx-broadcast'></i> Kirim Broadcast
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="broadcastForm" enctype="multipart/form-data">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label" for="title">Judul Broadcast <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required
                                    placeholder="Misal: Pengumuman Libur Nasional">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="message">Pesan <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="message" name="message" rows="5" required
                                    placeholder="Tulis pesan yang akan dikirim ke karyawan..."></textarea>
                                <div class="form-text">Pesan akan otomatis menambahkan nama karyawan di awal</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="image">Gambar (Opsional)</label>
                                <input type="file" class="form-control" id="image" name="image"
                                    accept="image/jpeg,image/png,image/jpg">
                                <div class="form-text">Format: JPG, PNG. Maksimal 2MB</div>
                            </div>

                            <hr class="my-4">

                            <div class="mb-3">
                                <label class="form-label">Filter Penerima <span class="text-danger">*</span></label>
                                <select class="form-select" id="filterType" name="filter_type" required>
                                    <option value="">Pilih Filter</option>
                                    <option value="all">Semua Karyawan Aktif</option>
                                    <option value="position">Berdasarkan Jabatan</option>
                                    <option value="department">Berdasarkan Department</option>
                                    <option value="employee">Karyawan Tertentu</option>
                                </select>
                            </div>

                            <div class="mb-3 d-none" id="positionFilterDiv">
                                <label class="form-label">Pilih Jabatan</label>
                                <div class="border rounded p-3" style="max-height: 250px; overflow-y: auto;">
                                    <div id="positionCheckboxList">
                                        <div class="text-muted">Loading...</div>
                                    </div>
                                </div>
                                <div class="form-text">
                                    <button type="button" class="btn btn-sm btn-link p-0" onclick="selectAllPositions()">Pilih Semua</button> | 
                                    <button type="button" class="btn btn-sm btn-link p-0" onclick="deselectAllPositions()">Batal Semua</button>
                                </div>
                            </div>

                            <div class="mb-3 d-none" id="departmentFilterDiv">
                                <label class="form-label">Pilih Department</label>
                                <div class="border rounded p-3" style="max-height: 250px; overflow-y: auto;">
                                    <div id="departmentCheckboxList">
                                        <div class="text-muted">Loading...</div>
                                    </div>
                                </div>
                                <div class="form-text">
                                    <button type="button" class="btn btn-sm btn-link p-0" onclick="selectAllDepartments()">Pilih Semua</button> | 
                                    <button type="button" class="btn btn-sm btn-link p-0" onclick="deselectAllDepartments()">Batal Semua</button>
                                </div>
                            </div>

                            <div class="mb-3 d-none" id="employeeFilterDiv">
                                <label class="form-label">Pilih Karyawan</label>
                                <div class="mb-2">
                                    <input type="text" class="form-control form-control-sm" id="employeeSearch" 
                                           placeholder="Cari nama/NIP karyawan...">
                                </div>
                                <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                    <div id="employeeCheckboxList">
                                        <div class="text-muted">Loading...</div>
                                    </div>
                                </div>
                                <div class="form-text">
                                    <button type="button" class="btn btn-sm btn-link p-0" onclick="selectAllEmployees()">Pilih Semua</button> | 
                                    <button type="button" class="btn btn-sm btn-link p-0" onclick="deselectAllEmployees()">Batal Semua</button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class='bx bx-shield-check text-success'></i> Delay Antar Pesan (Anti-Ban)
                                </label>
                                <select name="delay_per_message" id="delayPerMessage" class="form-select">
                                    <option value="3">3 detik — Cepat (maks. ~20 penerima)</option>
                                    <option value="5" selected>5 detik — Disarankan</option>
                                    <option value="10">10 detik — Aman</option>
                                    <option value="15">15 detik — Sangat Aman</option>
                                    <option value="30">30 detik — Ekstra Aman (penerima banyak)</option>
                                </select>
                                <div class="form-text text-muted">
                                    Pesan dikirim antre di background — halaman tidak perlu tetap terbuka.
                                </div>
                            </div>

                            <div class="mb-3">
                                <button type="button" class="btn btn-outline-info w-100" id="previewBtn">
                                    <i class='bx bx-search'></i> Preview Penerima
                                </button>
                            </div>

                            <div id="previewResult" class="alert alert-info d-none mb-3">
                                <strong>Preview Penerima:</strong><br>
                                <span id="previewText"></span>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" id="sendBtn">
                                    <i class='bx bx-send'></i> Kirim Broadcast
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Broadcast History Card -->
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Riwayat Broadcast</h5>
                        <div>
                            <select class="form-select form-select-sm" id="statusFilter" style="width: 150px;">
                                <option value="">Semua Status</option>
                                <option value="completed">Selesai</option>
                                <option value="sending">Mengirim</option>
                                <option value="draft">Draft</option>
                                <option value="failed">Gagal</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Judul</th>
                                        <th>Filter</th>
                                        <th>Penerima</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="broadcastTableBody">
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <div class="spinner-border spinner-border-sm" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            Loading...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <nav aria-label="Page navigation" id="paginationNav" class="d-none">
                            <ul class="pagination justify-content-end" id="pagination">
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class='bx bx-detail'></i> Detail Broadcast
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <div class="text-center">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        Loading...
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let currentPage = 1;
        let positions = [];
        let departments = [];
        let employees = [];
        let allEmployees = []; // Store all for search filtering

        $(document).ready(function() {
            loadBroadcasts();
            loadPositions();
            loadDepartments();
            loadEmployees();
            updateStatistics();

            // Filter type change
            $('#filterType').change(function() {
                const filterType = $(this).val();
                $('#positionFilterDiv').addClass('d-none');
                $('#departmentFilterDiv').addClass('d-none');
                $('#employeeFilterDiv').addClass('d-none');

                if (filterType === 'position') {
                    $('#positionFilterDiv').removeClass('d-none');
                } else if (filterType === 'department') {
                    $('#departmentFilterDiv').removeClass('d-none');
                } else if (filterType === 'employee') {
                    $('#employeeFilterDiv').removeClass('d-none');
                }
            });

            // Status filter change
            $('#statusFilter').change(function() {
                currentPage = 1;
                loadBroadcasts();
            });

            // Preview button
            $('#previewBtn').click(function() {
                previewRecipients();
            });

            // Form submit
            $('#broadcastForm').submit(function(e) {
                e.preventDefault();
                sendBroadcast();
            });

            // Employee search
            $('#employeeSearch').on('keyup', function() {
                const searchTerm = $(this).val().toLowerCase();
                filterEmployeeList(searchTerm);
            });
        });

        function loadPositions() {
            $.get('/api/admin/broadcast/positions')
                .done(function(res) {
                    if (res.success) {
                        positions = res.data;
                        let checkboxes = '';
                        positions.forEach(pos => {
                            checkboxes += `
                                <div class="form-check mb-2">
                                    <input class="form-check-input position-checkbox" type="checkbox" 
                                           value="${pos.id}" id="pos_${pos.id}">
                                    <label class="form-check-label" for="pos_${pos.id}">
                                        ${pos.name}
                                    </label>
                                </div>
                            `;
                        });
                        $('#positionCheckboxList').html(checkboxes);
                    }
                })
                .fail(function() {
                    $('#positionCheckboxList').html('<div class="text-danger">Gagal memuat jabatan</div>');
                });
        }

        function loadDepartments() {
            $.get('/api/admin/broadcast/departments')
                .done(function(res) {
                    if (res.success) {
                        departments = res.data;
                        let checkboxes = '';
                        departments.forEach(dept => {
                            checkboxes += `
                                <div class="form-check mb-2">
                                    <input class="form-check-input department-checkbox" type="checkbox" 
                                           value="${dept.id}" id="dept_${dept.id}">
                                    <label class="form-check-label" for="dept_${dept.id}">
                                        ${dept.name}
                                    </label>
                                </div>
                            `;
                        });
                        $('#departmentCheckboxList').html(checkboxes);
                    }
                })
                .fail(function() {
                    $('#departmentCheckboxList').html('<div class="text-danger">Gagal memuat department</div>');
                });
        }

        function loadEmployees() {
            $.get('/api/admin/broadcast/employees')
                .done(function(res) {
                    if (res.success) {
                        employees = res.data;
                        allEmployees = res.data; // Store for search
                        console.log('Loaded employees:', employees.length);
                        renderEmployeeList(employees);
                    }
                })
                .fail(function(xhr) {
                    console.error('Failed to load employees:', xhr);
                    $('#employeeCheckboxList').html('<div class="text-danger">Gagal memuat karyawan</div>');
                });
        }

        function renderEmployeeList(employeeList) {
            // Simpan ID yang sudah dicentang sebelum re-render
            const checkedIds = new Set();
            $('.employee-checkbox:checked').each(function() {
                checkedIds.add($(this).val());
            });

            let checkboxes = '';
            employeeList.forEach(emp => {
                checkboxes += `
                    <div class="form-check mb-2 employee-item" data-name="${(emp.name || '').toLowerCase()}" data-code="${(emp.employee_code || '').toLowerCase()}">
                        <input class="form-check-input employee-checkbox" type="checkbox" 
                               value="${emp.id}" id="emp_${emp.id}">
                        <label class="form-check-label" for="emp_${emp.id}">
                            <strong>${emp.name}</strong> <small class="text-muted">(${emp.employee_code})</small><br>
                            <small class="text-muted">${emp.position} - ${emp.department}</small>
                        </label>
                    </div>
                `;
            });
            $('#employeeCheckboxList').html(checkboxes || '<div class="text-muted">Tidak ada karyawan</div>');

            // Restore centang sebelumnya
            checkedIds.forEach(id => {
                $(`#emp_${id}`).prop('checked', true);
            });

            // Terapkan filter search yang aktif (jika ada)
            const searchTerm = $('#employeeSearch').val().toLowerCase();
            if (searchTerm) {
                filterEmployeeList(searchTerm);
            }
        }

        function filterEmployeeList(searchTerm) {
            // Show/hide saja — TIDAK re-render, sehingga centang tetap tersimpan
            if (!searchTerm || searchTerm === '') {
                $('.employee-item').show();
                return;
            }

            $('.employee-item').each(function() {
                const name = $(this).data('name') || '';
                const code = $(this).data('code') || '';
                if (name.includes(searchTerm) || code.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }

        function loadBroadcasts(page = 1) {
            const status = $('#statusFilter').val();

            $.get('/api/admin/broadcast', {
                    page: page,
                    status: status,
                    per_page: 10
                })
                .done(function(res) {
                    if (res.success) {
                        renderBroadcastTable(res.data);
                        renderPagination(res.data);
                    }
                })
                .fail(function() {
                    $('#broadcastTableBody').html(
                        '<tr><td colspan="6" class="text-center text-danger">Gagal memuat data</td></tr>'
                    );
                });
        }

        function renderBroadcastTable(data) {
            let html = '';

            if (data.data.length === 0) {
                html =
                    '<tr><td colspan="6" class="text-center text-muted">Belum ada broadcast yang dikirim</td></tr>';
            } else {
                data.data.forEach(broadcast => {
                    const date = new Date(broadcast.created_at).toLocaleDateString('id-ID');
                    const time = new Date(broadcast.created_at).toLocaleTimeString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    html += `
                        <tr>
                            <td><small>${date}<br>${time}</small></td>
                            <td>
                                <strong>${broadcast.title}</strong><br>
                                <small class="text-muted">${broadcast.message.substring(0, 50)}...</small>
                            </td>
                            <td><span class="badge bg-label-info">${broadcast.filter_label}</span></td>
                            <td>
                                <small>
                                    <i class='bx bx-check-circle text-success'></i> ${broadcast.sent_count}<br>
                                    ${broadcast.failed_count > 0 ? `<i class='bx bx-error-circle text-danger'></i> ${broadcast.failed_count}` : ''}
                                </small>
                            </td>
                            <td><span class="badge ${broadcast.status_badge}">${broadcast.status_label}</span></td>
                            <td>
                                <button class="btn btn-sm btn-icon btn-outline-info" onclick="viewDetail(${broadcast.id})" title="Lihat Detail">
                                    <i class='bx bx-show'></i>
                                </button>
                                <button class="btn btn-sm btn-icon btn-outline-danger" onclick="deleteBroadcast(${broadcast.id})" title="Hapus">
                                    <i class='bx bx-trash'></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            }

            $('#broadcastTableBody').html(html);
        }

        function renderPagination(data) {
            if (data.last_page <= 1) {
                $('#paginationNav').addClass('d-none');
                return;
            }

            $('#paginationNav').removeClass('d-none');
            let html = '';

            // Previous
            html += `
                <li class="page-item ${data.current_page === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadBroadcasts(${data.current_page - 1}); return false;">
                        <i class="tf-icon bx bx-chevron-left"></i>
                    </a>
                </li>
            `;

            // Pages
            for (let i = 1; i <= data.last_page; i++) {
                if (i === 1 || i === data.last_page || (i >= data.current_page - 1 && i <= data.current_page + 1)) {
                    html += `
                        <li class="page-item ${i === data.current_page ? 'active' : ''}">
                            <a class="page-link" href="#" onclick="loadBroadcasts(${i}); return false;">${i}</a>
                        </li>
                    `;
                } else if (i === data.current_page - 2 || i === data.current_page + 2) {
                    html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            // Next
            html += `
                <li class="page-item ${data.current_page === data.last_page ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadBroadcasts(${data.current_page + 1}); return false;">
                        <i class="tf-icon bx bx-chevron-right"></i>
                    </a>
                </li>
            `;

            $('#pagination').html(html);
        }

        function previewRecipients() {
            const filterType = $('#filterType').val();
            
            let filterValues = [];
            if (filterType === 'position') {
                $('.position-checkbox:checked').each(function() {
                    filterValues.push($(this).val());
                });
            } else if (filterType === 'department') {
                $('.department-checkbox:checked').each(function() {
                    filterValues.push($(this).val());
                });
            } else if (filterType === 'employee') {
                $('.employee-checkbox:checked').each(function() {
                    filterValues.push($(this).val());
                });
            }

            if (!filterType) {
                Swal.fire('Peringatan', 'Silakan pilih filter penerima', 'warning');
                return;
            }

            if (filterType !== 'all' && (!filterValues || filterValues.length === 0)) {
                Swal.fire('Peringatan', 'Silakan pilih minimal satu pilihan filter', 'warning');
                return;
            }

            Swal.fire({
                title: 'Loading...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.post('/api/admin/broadcast/preview', {
                    _token: '{{ csrf_token() }}',
                    filter_type: filterType,
                    filter_values: filterValues
                })
                .done(function(res) {
                    Swal.close();
                    if (res.success) {
                        const data = res.data;
                        let previewText = `
                            <div class="mb-2">
                                <i class='bx bx-user'></i> <strong>${data.valid_phone}</strong> karyawan akan menerima pesan
                            </div>
                        `;

                        if (data.without_phone > 0) {
                            previewText += `
                                <div class="text-warning">
                                    <i class='bx bx-error-circle'></i> ${data.without_phone} karyawan tidak memiliki nomor WhatsApp
                                </div>
                            `;
                        }

                        $('#previewText').html(previewText);
                        $('#previewResult').removeClass('d-none');

                        // Show recipients in modal
                        let recipientsHtml = '<div class="table-responsive"><table class="table table-sm">';
                        recipientsHtml += '<thead><tr><th>Nama</th><th>NIP</th><th>Jabatan</th><th>WhatsApp</th></tr></thead><tbody>';

                        data.recipients.forEach(emp => {
                            recipientsHtml += `
                                <tr>
                                    <td>${emp.name}</td>
                                    <td>${emp.employee_code}</td>
                                    <td>${emp.position}</td>
                                    <td>${emp.phone}</td>
                                </tr>
                            `;
                        });

                        recipientsHtml += '</tbody></table></div>';

                        Swal.fire({
                            title: `Preview Penerima (${data.valid_phone} orang)`,
                            html: recipientsHtml,
                            icon: 'info',
                            width: 800,
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .fail(function(xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Gagal memuat preview', 'error');
                });
        }

        function sendBroadcast() {
            const formData = new FormData($('#broadcastForm')[0]);

            const filterType = $('#filterType').val();
            if (!filterType) {
                Swal.fire('Peringatan', 'Silakan pilih filter penerima', 'warning');
                return;
            }

            // Add checkbox values to formData
            if (filterType === 'position') {
                formData.delete('filter_values[]'); // Remove default
                $('.position-checkbox:checked').each(function() {
                    formData.append('filter_values[]', $(this).val());
                });
            } else if (filterType === 'department') {
                formData.delete('filter_values[]'); // Remove default
                $('.department-checkbox:checked').each(function() {
                    formData.append('filter_values[]', $(this).val());
                });
            } else if (filterType === 'employee') {
                formData.delete('filter_values[]'); // Remove default
                $('.employee-checkbox:checked').each(function() {
                    formData.append('filter_values[]', $(this).val());
                });
            }

            Swal.fire({
                title: 'Kirim Broadcast?',
                text: 'Pesan akan dikirim ke semua karyawan sesuai filter yang dipilih',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Kirim!',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#28a745'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Menjadwalkan Broadcast...',
                        html: 'Mohon tunggu, pesan sedang dimasukkan ke antrian',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: '/api/admin/broadcast/send',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    }).done(function(res) {
                        const eta = res.data?.eta_minutes ?? '?';
                        const delay = res.data?.delay ?? 5;
                        Swal.fire({
                            icon: 'success',
                            title: 'Broadcast Dijadwalkan!',
                            html: res.message +
                                  `<br><br><small class="text-muted"><i class='bx bx-time'></i> Pesan dikirim otomatis oleh background worker.<br>Pastikan <strong>Queue Worker</strong> berjalan: <code>php artisan queue:work</code></small>`,
                            showConfirmButton: true
                        });

                        // Reset form
                        $('#broadcastForm')[0].reset();
                        $('#previewResult').addClass('d-none');
                        $('#positionFilterDiv').addClass('d-none');
                        $('#departmentFilterDiv').addClass('d-none');
                        $('#employeeFilterDiv').addClass('d-none');
                        
                        // Uncheck all checkboxes
                        $('.position-checkbox').prop('checked', false);
                        $('.department-checkbox').prop('checked', false);
                        $('.employee-checkbox').prop('checked', false);
                        $('#employeeSearch').val('');

                        // Reload data
                        loadBroadcasts();
                        updateStatistics();
                    }).fail(function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message ||
                            'Gagal mengirim broadcast', 'error');
                    });
                }
            });
        }

        function viewDetail(id) {
            Swal.fire({
                title: 'Loading...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.get(`/api/admin/broadcast/${id}`)
                .done(function(res) {
                    Swal.close();
                    if (res.success) {
                        const data = res.data;
                        const b = data.broadcast;
                        
                        // Format dates
                        const createdDate = new Date(b.created_at).toLocaleString('id-ID');
                        const sentDate = b.sent_at ? new Date(b.sent_at).toLocaleString('id-ID') : '-';
                        
                        // Build filter info
                        let filterInfo = '<span class="badge bg-label-info">' + b.filter_label + '</span>';
                        if (data.filter_details && data.filter_details.length > 0) {
                            filterInfo += '<div class="mt-2"><small>' + data.filter_details.join(', ') + '</small></div>';
                        }

                        // Build HTML for detail modal
                        let html = `
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Judul:</strong><br>
                                    <span>${b.title}</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Status:</strong><br>
                                    <span class="badge ${b.status_badge}">${b.status_label}</span>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Dikirim oleh:</strong><br>
                                    <span>${b.sender ? b.sender.name : '-'}</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Waktu Dibuat:</strong><br>
                                    <span>${createdDate}</span>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Waktu Dikirim:</strong><br>
                                    <span>${sentDate}</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Filter Penerima:</strong><br>
                                    ${filterInfo}
                                </div>
                            </div>

                            <hr>

                            <div class="mb-3">
                                <strong>Pesan:</strong><br>
                                <div class="border rounded p-3 bg-light mt-2" style="white-space: pre-wrap;">${b.message}</div>
                            </div>

                            ${data.image_url ? `
                            <div class="mb-3">
                                <strong>Gambar:</strong><br>
                                <img src="${data.image_url}" class="img-fluid rounded mt-2" style="max-width: 400px;">
                            </div>
                            ` : ''}

                            <hr>

                            <div class="row text-center mb-3">
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h3 class="text-primary mb-1">${b.total_recipients}</h3>
                                            <small class="text-muted">Total Penerima</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h3 class="text-success mb-1">${b.sent_count}</h3>
                                            <small class="text-muted">Berhasil Terkirim</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h3 class="text-danger mb-1">${b.failed_count}</h3>
                                            <small class="text-muted">Gagal</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            ${data.recipients && data.recipients.length > 0 ? `
                            <hr>
                            <div class="mb-2">
                                <strong>Daftar Penerima (${data.recipients.length}):</strong>
                            </div>
                            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Nama</th>
                                            <th>NIP</th>
                                            <th>Jabatan</th>
                                            <th>WhatsApp</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.recipients.map(emp => `
                                            <tr>
                                                <td>${emp.name}</td>
                                                <td>${emp.employee_code}</td>
                                                <td>${emp.position}</td>
                                                <td>${emp.phone || '-'}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                            ` : ''}
                        `;

                        $('#detailContent').html(html);
                        $('#detailModal').modal('show');
                    }
                })
                .fail(function(xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Gagal memuat detail', 'error');
                });
        }

        function deleteBroadcast(id) {
            Swal.fire({
                title: 'Hapus Broadcast?',
                text: 'Data broadcast akan dihapus secara permanen',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/api/admin/broadcast/${id}`,
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    }).done(function(res) {
                        Swal.fire('Berhasil!', res.message, 'success');
                        loadBroadcasts(currentPage);
                        updateStatistics();
                    }).fail(function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Gagal menghapus broadcast',
                            'error');
                    });
                }
            });
        }

        function updateStatistics() {
            // Simple statistics - you can enhance this with actual API call
            $.get('/api/admin/broadcast', {
                    per_page: 1000
                })
                .done(function(res) {
                    if (res.success) {
                        const broadcasts = res.data.data;
                        let totalSent = 0;
                        let totalDraft = 0;
                        let totalFailed = 0;
                        let totalRecipients = 0;

                        broadcasts.forEach(b => {
                            totalSent += b.sent_count;
                            totalRecipients += b.total_recipients;
                            if (b.status === 'draft') totalDraft++;
                            if (b.status === 'failed') totalFailed++;
                        });

                        $('#totalSent').text(totalSent);
                        $('#totalDraft').text(totalDraft);
                        $('#totalFailed').text(totalFailed);
                        $('#totalRecipients').text(totalRecipients);
                    }
                });
        }

        // Helper functions for select/deselect all
        function selectAllPositions() {
            $('.position-checkbox').prop('checked', true);
        }

        function deselectAllPositions() {
            $('.position-checkbox').prop('checked', false);
        }

        function selectAllDepartments() {
            $('.department-checkbox').prop('checked', true);
        }

        function deselectAllDepartments() {
            $('.department-checkbox').prop('checked', false);
        }

        function selectAllEmployees() {
            $('.employee-checkbox:visible').prop('checked', true);
        }

        function deselectAllEmployees() {
            $('.employee-checkbox').prop('checked', false);
        }
    </script>
@endpush
