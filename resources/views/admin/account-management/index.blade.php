@extends('layouts.app')

@section('title', 'Management Akun')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">
                <span class="text-muted fw-light">Sistem /</span> Management Akun
            </h4>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class='bx bx-plus'></i> Buat Akun Baru
            </button>
        </div>

        <!-- Info Alert -->
        <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
            <i class='bx bx-info-circle me-2'></i>
            <strong>Informasi:</strong> Superadmin dapat mengelola akun dengan role <strong>Admin</strong>, <strong>Security</strong>, dan <strong>Viewer</strong>. Akun superadmin lain tidak dapat dikelola melalui interface ini.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <!-- Statistik -->
        <div class="row mb-4" id="statsRow">
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="fw-semibold d-block mb-1">Total Akun</span>
                                <h3 class="card-title mb-0" id="stat-total">—</h3>
                            </div>
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class='bx bx-user bx-sm'></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="fw-semibold d-block mb-1">Akun Aktif</span>
                                <h3 class="card-title mb-0" id="stat-aktif">—</h3>
                            </div>
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class='bx bx-check-circle bx-sm'></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="fw-semibold d-block mb-1">Akun Nonaktif</span>
                                <h3 class="card-title mb-0" id="stat-nonaktif">—</h3>
                            </div>
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-danger">
                                    <i class='bx bx-x-circle bx-sm'></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="fw-semibold d-block mb-1">Superadmin</span>
                                <h3 class="card-title mb-0" id="stat-superadmin">—</h3>
                            </div>
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-warning">
                                    <i class='bx bx-shield bx-sm'></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Akun -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <h5 class="mb-0">Daftar Akun</h5>
                <div class="d-flex gap-2 flex-wrap">
                    <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Cari nama/email..."
                        style="width: auto; min-width: 200px;">
                    <select class="form-select form-select-sm" id="filterRole" style="width:auto;">
                        <option value="">Semua Role</option>
                    </select>
                    <select class="form-select form-select-sm" id="filterStatus" style="width:auto;">
                        <option value="">Semua Status</option>
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefresh">
                        <i class='bx bx-refresh'></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <!-- Desktop Table -->
                <div class="table-responsive d-none d-lg-block">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Terdaftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                    Memuat data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="d-lg-none p-3" id="mobileCards">
                    <div class="text-center py-4">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                        Memuat data...
                    </div>
                </div>

                <!-- Pagination -->
                <div class="p-3 d-flex justify-content-between align-items-center flex-wrap" id="paginationArea"></div>
            </div>
        </div>
    </div>

    {{-- ───── Modal Buat Akun ───── --}}
    <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Buat Akun Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" required minlength="8">
                            <small class="text-muted">Minimal 8 karakter</small>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password_confirmation" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" name="role" required id="createRole">
                                <option value="">Pilih Role</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-save me-1'></i> Buat Akun
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ───── Modal Edit Akun ───── --}}
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Akun</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editForm">
                    @csrf
                    <input type="hidden" name="id" id="editId">
                    <input type="hidden" name="role" id="editAccountRole">
                    <div class="modal-body">
                        <div class="alert alert-info alert-dismissible fade show" id="editSuperadminWarning" role="alert" style="display: none;">
                            <i class='bx bx-shield me-2'></i>
                            <strong>Informasi:</strong> Anda tidak dapat mengedit akun superadmin lain. Hanya akun Anda sendiri yang dapat diubah.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" name="name" id="editName">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="editEmail">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" id="editRole">
                                <option value="">Tidak diubah</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="editStatus">
                                <option value="">Tidak diubah</option>
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <hr>
                        <p class="small text-muted mb-2">Kosongkan untuk tidak mengubah password</p>
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" class="form-control" name="password" id="editPassword" minlength="8">
                            <small class="text-muted">Minimal 8 karakter</small>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password</label>
                            <input type="password" class="form-control" name="password_confirmation" id="editPasswordConfirm">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-save me-1'></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ───── Modal Hapus Akun ───── --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class='bx bx-trash me-1'></i>Hapus Akun</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-3">
                        <i class='bx bx-exclamation-circle me-2'></i>
                        <strong>Peringatan!</strong> Aksi ini tidak dapat dibatalkan.
                    </div>
                    <div class="alert alert-info mb-3" id="superadminWarning" style="display: none;">
                        <i class='bx bx-shield me-2'></i>
                        <strong>Catatan:</strong> Akun superadmin tidak dapat dihapus melalui interface ini.
                    </div>
                    <p>Anda yakin ingin menghapus akun:</p>
                    <h6 id="deleteAccountName" class="fw-bold"></h6>
                    <p class="text-muted mb-0" id="deleteAccountEmail"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmDelete">
                        <i class='bx bx-trash me-1'></i>Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .card-header { background-color: #fff; border-bottom: 1px solid #d9dee3; }
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .role-superadmin { background-color: #ffeaa7; color: #2d3436; }
        .role-admin { background-color: #dfe6e9; color: #2d3436; }
        .role-manager { background-color: #74b9ff; color: #fff; }
        .role-viewer { background-color: #a29bfe; color: #fff; }
        .role-security { background-color: #fd79a8; color: #fff; }
        .role-karyawan { background-color: #55efc4; color: #2d3436; }
        .role-guest { background-color: #fab1a0; color: #fff; }
    </style>
    @endpush

    @push('scripts')
    <script>
    let currentPage = 1;
    let deleteAccountId = null;

    // Load stats
    function loadStats() {
        $.get('/api/admin/account-management/stats', function(response) {
            if (response.success) {
                $('#stat-total').text(response.data.total);
                $('#stat-aktif').text(response.data.aktif);
                $('#stat-nonaktif').text(response.data.nonaktif);
                $('#stat-superadmin').text(response.data.by_role.superadmin);
            }
        });
    }

    // Load options (roles, statuses)
    function loadOptions() {
        $.get('/api/admin/account-management/options', function(response) {
            if (response.success) {
                const roleOptions = response.roles
                    .map(r => '<option value="' + r + '">' + r.charAt(0).toUpperCase() + r.slice(1).replace('_', ' ') + '</option>')
                    .join('');
                $('#createRole').html('<option value="">Pilih Role</option>' + roleOptions);
                
                let editRoleOptions = '<option value="">Tidak diubah</option>' + roleOptions;
                $('#editRole').html(editRoleOptions);
                
                let filterRoleOptions = '<option value="">Semua Role</option>' + roleOptions;
                $('#filterRole').html(filterRoleOptions);
            }
        });
    }

    // Format role badge
    function getRoleBadge(role) {
        const badge = '<span class="role-badge role-' + role + '">' + role + '</span>';
        return badge;
    }

    // Format status badge
    function getStatusBadge(status) {
        const badges = {
            'aktif': '<span class="badge bg-success">Aktif</span>',
            'nonaktif': '<span class="badge bg-danger">Nonaktif</span>',
        };
        return badges[status] || '<span class="badge bg-secondary">' + status + '</span>';
    }

    // Load data
    function loadData(page = 1) {
        const params = {
            page: page,
            per_page: 15,
            search: $('#searchInput').val() || '',
            role: $('#filterRole').val() || '',
            status: $('#filterStatus').val() || '',
        };

        $.ajax({
            url: '/api/admin/account-management/list',
            data: params,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (!response.success) return;

                const accounts = response.data.data;
                const tbody = $('#tableBody');
                tbody.empty();

                if (accounts.length === 0) {
                    tbody.html('<tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada data akun</td></tr>');
                    $('#mobileCards').html('<div class="text-center py-4 text-muted"><i class="bx bx-inbox"></i><p>Tidak ada data akun</p></div>');
                    return;
                }

                let mobileHtml = '';
                accounts.forEach((acc, idx) => {
                    const rowNum = (response.data.current_page - 1) * 15 + idx + 1;
                    const createdDate = new Date(acc.created_at).toLocaleDateString('id-ID');
                    const currentUserId = {{ Auth::id() }};
                    const isCurrentUser = acc.id === currentUserId;

                    const rowHtml = '<tr>' +
                        '<td class="fw-semibold">' + rowNum + '</td>' +
                        '<td>' +
                            '<strong>' + acc.name + '</strong><br>' +
                            '<small class="text-muted">' + acc.email + '</small>' +
                        '</td>' +
                        '<td>' + acc.email + '</td>' +
                        '<td>' + getRoleBadge(acc.role) + '</td>' +
                        '<td>' + getStatusBadge(acc.status) + '</td>' +
                        '<td><small>' + createdDate + '</small></td>' +
                        '<td>' +
                            '<div class="btn-group btn-group-sm" role="group">' +
                                '<button type="button" class="btn btn-outline-primary" onclick="editAccount(' + acc.id + ')">' +
                                    '<i class="bx bx-edit-alt"></i>' +
                                '</button>' +
                                (!isCurrentUser ? '<button type="button" class="btn btn-outline-danger" onclick="deleteAccount(' + acc.id + ', \'' + acc.name.replace(/'/g, "\\'") + '\', \'' + acc.email.replace(/'/g, "\\'") + '\', \'' + acc.role + '\')">' +
                                    '<i class="bx bx-trash"></i>' +
                                '</button>' : '') +
                            '</div>' +
                        '</td>' +
                    '</tr>';
                    tbody.append(rowHtml);

                    const mobileCard = '<div class="card-body border-bottom p-3">' +
                        '<div class="d-flex justify-content-between align-items-start mb-2">' +
                            '<div>' +
                                '<h6 class="mb-1">' + acc.name + '</h6>' +
                                '<small class="text-muted">' + acc.email + '</small>' +
                            '</div>' +
                            getStatusBadge(acc.status) +
                        '</div>' +
                        '<div class="mb-2">' +
                            '<small>' + getRoleBadge(acc.role) + '</small>' +
                        '</div>' +
                        '<small class="text-muted d-block mb-3">Terdaftar: ' + createdDate + '</small>' +
                        '<div class="d-flex gap-2">' +
                            '<button class="btn btn-sm btn-outline-primary flex-grow-1" onclick="editAccount(' + acc.id + ')">' +
                                '<i class="bx bx-edit-alt me-1"></i> Edit' +
                            '</button>' +
                            (!isCurrentUser ? '<button class="btn btn-sm btn-outline-danger" onclick="deleteAccount(' + acc.id + ', \'' + acc.name.replace(/'/g, "\\'") + '\', \'' + acc.email.replace(/'/g, "\\'") + '\', \'' + acc.role + '\')">' +
                                '<i class="bx bx-trash"></i>' +
                            '</button>' : '') +
                        '</div>' +
                    '</div>';
                    mobileHtml += mobileCard;
                });

                $('#mobileCards').html(mobileHtml);
                renderPagination(response.data);
            }
        });
    }

    function renderPagination(pagination) {
        const paginationArea = $('#paginationArea');
        paginationArea.empty();

        const totalPages = pagination.last_page;
        if (totalPages <= 1) return;

        let paginationHtml = '<nav aria-label="Page navigation"><ul class="pagination mb-0">';

        pagination.links.forEach(link => {
            const isActive = link.active ? 'active' : '';
            const isDisabled = link.url === null ? 'disabled' : '';
            const page = new URL(link.url || '#', window.location.origin).searchParams.get('page') || 1;
            const label = link.label.replace('&laquo;', '«').replace('&raquo;', '»');

            if (link.url === null) {
                paginationHtml += '<li class="page-item ' + isDisabled + '"><span class="page-link">' + label + '</span></li>';
            } else {
                paginationHtml += '<li class="page-item ' + isActive + '"><a class="page-link" href="javascript:void(0)" onclick="loadData(' + page + ')">' + label + '</a></li>';
            }
        });

        paginationHtml += '</ul></nav>';
        paginationArea.html(paginationHtml);
    }

    // Create account
    $('#createForm').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;

        $.ajax({
            url: '/api/admin/account-management/store',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: $(form).serialize(),
            success: function(response) {
                if (response.success) {
                    Swal.fire('Berhasil', response.message, 'success');
                    $('#createModal').find('button[data-bs-dismiss="modal"]').click();
                    form.reset();
                    loadData(1);
                    loadStats();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
                btn.disabled = false;
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                Object.keys(errors).forEach(key => {
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                        const feedback = input.parentElement.querySelector('.invalid-feedback');
                        if (feedback) feedback.textContent = errors[key][0];
                    }
                });
                btn.disabled = false;
            }
        });
    });

    // Edit account
    function editAccount(id) {
        $.get(`/api/admin/account-management/detail/${id}`, function(response) {
            if (response.success) {
                const acc = response.data;
                const currentUserId = {{ Auth::id() }};
                const isSuperadmin = acc.role === 'superadmin';
                const isOwnAccount = acc.id === currentUserId;
                
                $('#editId').val(acc.id);
                $('#editAccountRole').val(acc.role);
                $('#editName').val(acc.name);
                $('#editEmail').val(acc.email);
                $('#editRole').val('');
                $('#editStatus').val('');
                $('#editPassword').val('');
                $('#editPasswordConfirm').val('');
                
                // Show warning if trying to edit other superadmin account
                if (isSuperadmin && !isOwnAccount) {
                    $('#editSuperadminWarning').show();
                    $('#editForm button[type="submit"]').prop('disabled', true);
                } else {
                    $('#editSuperadminWarning').hide();
                    $('#editForm button[type="submit"]').prop('disabled', false);
                }
                
                const editModal = new bootstrap.Modal(document.getElementById('editModal'));
                editModal.show();
            }
        });
    }

    $('#editForm').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        const id = $('#editId').val();
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;

        $.ajax({
            url: `/api/admin/account-management/update/${id}`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-HTTP-Method-Override': 'PUT'
            },
            data: $(form).serialize(),
            success: function(response) {
                if (response.success) {
                    Swal.fire('Berhasil', response.message, 'success');
                    $('#editModal').find('button[data-bs-dismiss="modal"]').click();
                    loadData(currentPage);
                    loadStats();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
                btn.disabled = false;
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Gagal memperbarui akun';
                Swal.fire('Error', message, 'error');
                btn.disabled = false;
            }
        });
    });

    // Delete account
    function deleteAccount(id, name, email, role) {
        deleteAccountId = id;
        $('#deleteAccountName').text(name);
        $('#deleteAccountEmail').text(email);
        
        // Show warning if trying to delete superadmin account
        if (role === 'superadmin') {
            $('#superadminWarning').show();
            $('#btnConfirmDelete').prop('disabled', true);
        } else {
            $('#superadminWarning').hide();
            $('#btnConfirmDelete').prop('disabled', false);
        }
        
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }

    $('#btnConfirmDelete').on('click', function() {
        if (!deleteAccountId) return;
        
        const btn = this;
        btn.disabled = true;

        $.ajax({
            url: `/api/admin/account-management/destroy/${deleteAccountId}`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-HTTP-Method-Override': 'DELETE'
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire('Berhasil', response.message, 'success');
                    $('#deleteModal').find('button[data-bs-dismiss="modal"]').click();
                    loadData(1);
                    loadStats();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
                btn.disabled = false;
            },
            error: function() {
                Swal.fire('Error', 'Gagal menghapus akun', 'error');
                btn.disabled = false;
            }
        });
    });

    // Event listeners
    $('#searchInput').on('keyup', function() {
        loadData(1);
    });

    $('#filterRole, #filterStatus').on('change', function() {
        loadData(1);
    });

    $('#btnRefresh').on('click', function() {
        loadData(1);
    });

    // Initial load
    loadOptions();
    loadStats();
    loadData(1);
    </script>
    @endpush
@endsection
