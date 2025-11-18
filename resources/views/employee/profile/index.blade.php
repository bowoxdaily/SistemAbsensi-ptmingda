@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold mb-2">
                        <span class="text-muted fw-light">Karyawan /</span> Profil Saya
                    </h4>
                    <p class="text-muted mb-0">Kelola informasi profil dan akun Anda</p>
                </div>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    <i class='bx bx-home'></i>
                    <span class="d-none d-sm-inline">Kembali ke Dashboard</span>
                    <span class="d-sm-none">Dashboard</span>
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Profil Card -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-4">
                            <div class="position-relative d-inline-block mb-3">
                                @if ($employee->profile_photo)
                                    <img src="{{ asset('storage/' . $employee->profile_photo) }}" alt="Avatar"
                                        class="rounded-circle"
                                        style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #f0f0f0;">
                                @else
                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-label-primary mx-auto"
                                        style="width: 120px; height: 120px; font-size: 48px; border: 3px solid #f0f0f0;">
                                        {{ strtoupper(substr($employee->name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#uploadPhotoModal">
                                    <i class='bx bx-camera'></i> Ubah Foto
                                </button>
                            </div>
                        </div>
                        <h5 class="mb-1">{{ $employee->name }}</h5>
                        <p class="text-muted mb-3">{{ $employee->position->name ?? '-' }}</p>
                        <div class="d-flex justify-content-center gap-2 mb-3">
                            <span class="badge bg-label-primary">{{ $employee->employee_code }}</span>
                            <span
                                class="badge bg-label-success">{{ $employee->status == 'active' ? 'Aktif' : 'Tidak Aktif' }}</span>
                        </div>
                        <hr>
                        <div class="text-start">
                            <small class="text-muted d-block mb-2">
                                <i class='bx bx-buildings'></i> {{ $employee->department->name ?? '-' }}
                            </small>
                            <small class="text-muted d-block mb-2">
                                <i class='bx bx-envelope'></i> {{ $employee->email ?? '-' }}
                            </small>
                            <small class="text-muted d-block">
                                <i class='bx bx-phone'></i> {{ $employee->phone ?? '-' }}
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Statistik Bulan Ini</h6>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-2">
                                    <span class="avatar-initial rounded bg-label-success">
                                        <i class='bx bx-check'></i>
                                    </span>
                                </div>
                                <span>Hadir</span>
                            </div>
                            <strong class="text-success">{{ $stats['hadir'] ?? 0 }}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-2">
                                    <span class="avatar-initial rounded bg-label-warning">
                                        <i class='bx bx-time'></i>
                                    </span>
                                </div>
                                <span>Terlambat</span>
                            </div>
                            <strong class="text-warning">{{ $stats['terlambat'] ?? 0 }}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-2">
                                    <span class="avatar-initial rounded bg-label-danger">
                                        <i class='bx bx-x'></i>
                                    </span>
                                </div>
                                <span>Alpha</span>
                            </div>
                            <strong class="text-danger">{{ $stats['alpha'] ?? 0 }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Edit Profil -->
            <div class="col-lg-8">
                <!-- Informasi Pribadi -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Informasi Pribadi</h5>
                    </div>
                    <div class="card-body">
                        <form id="profileForm">
                            @csrf

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        name="name" value="{{ old('name', $employee->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                        name="email" value="{{ old('email', $employee->email) }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">No. Telepon</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                        name="phone" value="{{ old('phone', $employee->phone) }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tanggal Lahir</label>
                                    <input type="date" class="form-control @error('birth_date') is-invalid @enderror"
                                        name="birth_date"
                                        value="{{ old('birth_date', $employee->birth_date ? $employee->birth_date->format('Y-m-d') : '') }}">
                                    @error('birth_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Jenis Kelamin</label>
                                    <select class="form-select @error('gender') is-invalid @enderror" name="gender">
                                        <option value="">Pilih Jenis Kelamin</option>
                                        <option value="L"
                                            {{ old('gender', $employee->gender) == 'L' ? 'selected' : '' }}>Laki-laki
                                        </option>
                                        <option value="P"
                                            {{ old('gender', $employee->gender) == 'P' ? 'selected' : '' }}>Perempuan
                                        </option>
                                    </select>
                                    @error('gender')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">NIK</label>
                                    <input type="text" class="form-control" name="nik"
                                        value="{{ $employee->nik }}" readonly>
                                    <small class="text-muted">
                                        <i class='bx bx-info-circle'></i> Tidak dapat diubah. Hubungi admin jika ada
                                        kesalahan data.
                                    </small>
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label">Alamat</label>
                                    <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="3">{{ old('address', $employee->address) }}</textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                                <i class='bx bx-info-circle me-2' style="font-size: 20px;"></i>
                                <div>
                                    <strong>Perhatian:</strong> Data NIK, Kode Karyawan, Departemen, dan Posisi hanya dapat
                                    diubah oleh Admin.
                                    Jika terdapat kesalahan data, silakan hubungi Admin untuk melakukan perubahan.
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="reset" class="btn btn-outline-secondary">Reset</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class='bx bx-save'></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Ubah Password -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Ubah Password</h5>
                    </div>
                    <div class="card-body">
                        <form id="passwordForm">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Password Lama <span class="text-danger">*</span></label>
                                <input type="password"
                                    class="form-control @error('current_password') is-invalid @enderror"
                                    name="current_password" required>
                                @error('current_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password Baru <span class="text-danger">*</span></label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                    name="password" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Minimal 8 karakter</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Konfirmasi Password Baru <span
                                        class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="password_confirmation" required>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-warning">
                                    <i class='bx bx-lock'></i> Ubah Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Upload Photo -->
    <div class="modal fade" id="uploadPhotoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Foto Profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="photoForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3 text-center">
                            <img id="preview" src="#" alt="Preview"
                                style="max-width: 200px; max-height: 200px; display: none;" class="rounded-circle mb-3">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pilih Foto <span class="text-danger">*</span></label>
                            <input type="file" class="form-control @error('profile_photo') is-invalid @enderror"
                                name="profile_photo" accept="image/*" required id="photoInput">
                            @error('profile_photo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Format: JPG, PNG, JPEG, WebP. Maksimal 10MB. Gambar akan otomatis
                                dikompres ke 500x500px dan dikonversi ke WebP dengan kualitas optimal.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-upload'></i> Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Preview image before upload
        document.getElementById('photoInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('preview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        // Profile Form Submit
        $('#profileForm').on('submit', function(e) {
            e.preventDefault();

            const submitBtn = $(this).find('button[type="submit"]');
            const originalHtml = submitBtn.html();

            // Disable button and show loading
            submitBtn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm"></span> Menyimpan...');

            // Add _method field for Laravel method spoofing
            const formData = $(this).serialize() + '&_method=PUT';

            $.ajax({
                url: '/api/employee/profile',
                type: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    submitBtn.prop('disabled', false).html(originalHtml);

                    if (response.success) {
                        toastr.success(response.message || 'Profil berhasil diperbarui');
                        setTimeout(() => location.reload(), 1500);
                    }
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).html(originalHtml);

                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON?.errors;
                        if (errors) {
                            Object.keys(errors).forEach(key => {
                                toastr.error(errors[key][0]);
                            });
                        }
                    } else {
                        toastr.error(xhr.responseJSON?.message ||
                            'Terjadi kesalahan saat memperbarui profil');
                    }
                }
            });
        });

        // Password Form Submit
        $('#passwordForm').on('submit', function(e) {
            e.preventDefault();

            const submitBtn = $(this).find('button[type="submit"]');
            const originalHtml = submitBtn.html();

            // Disable button and show loading
            submitBtn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm"></span> Menyimpan...');

            // Add _method field for Laravel method spoofing
            const formData = $(this).serialize() + '&_method=PUT';

            $.ajax({
                url: '/api/employee/profile/password',
                type: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    submitBtn.prop('disabled', false).html(originalHtml);

                    if (response.success) {
                        toastr.success(response.message || 'Password berhasil diubah');
                        $('#passwordForm')[0].reset();
                    }
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).html(originalHtml);

                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON?.errors;
                        if (errors) {
                            Object.keys(errors).forEach(key => {
                                toastr.error(errors[key][0]);
                            });
                        }
                    } else {
                        toastr.error(xhr.responseJSON?.message ||
                            'Terjadi kesalahan saat memperbarui password');
                    }
                }
            });
        });

        // Photo Form Submit
        $('#photoForm').on('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = $(this).find('button[type="submit"]');
            const originalHtml = submitBtn.html();

            // Disable button and show loading
            submitBtn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm"></span> Uploading...');

            $.ajax({
                url: '/api/employee/profile/photo',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    submitBtn.prop('disabled', false).html(originalHtml);

                    if (response.success) {
                        toastr.success(response.message || 'Foto profil berhasil diperbarui');
                        $('#uploadPhotoModal').modal('hide');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        toastr.error(response.message || 'Gagal mengupload foto');
                    }
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).html(originalHtml);

                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON?.errors;
                        if (errors) {
                            Object.keys(errors).forEach(key => {
                                toastr.error(errors[key][0]);
                            });
                        } else {
                            toastr.error('Validasi gagal');
                        }
                    } else {
                        toastr.error(xhr.responseJSON?.message ||
                            'Terjadi kesalahan saat mengupload foto');
                    }
                }
            });
        });

        // Show toastr notifications
        @if (session('success'))
            toastr.success('{{ session('success') }}');
        @endif

        @if (session('error'))
            toastr.error('{{ session('error') }}');
        @endif

        @if ($errors->any())
            @foreach ($errors->all() as $error)
                toastr.error('{{ $error }}');
            @endforeach
        @endif
    </script>
@endpush
