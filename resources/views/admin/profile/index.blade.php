@extends('layouts.app')

@section('title', 'Profil Admin')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold mb-2">
                        <span class="text-muted fw-light">Admin /</span> Profil Saya
                    </h4>
                    <p class="text-muted mb-0">Kelola informasi profil dan akun admin Anda</p>
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
                                @if ($user->profile_photo)
                                    <img src="{{ asset('storage/' . $user->profile_photo) }}" alt="Avatar"
                                        class="rounded-circle"
                                        style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #f0f0f0;">
                                @else
                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-label-primary mx-auto"
                                        style="width: 120px; height: 120px; font-size: 48px; border: 3px solid #f0f0f0;">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
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
                        <h5 class="mb-1">{{ $user->name }}</h5>
                        <p class="text-muted mb-3">{{ ucfirst($user->role) }}</p>
                        <div class="d-flex justify-content-center gap-2 mb-3">
                            <span class="badge bg-label-primary">Administrator</span>
                        </div>
                        <hr>
                        <div class="text-start">
                            <small class="text-muted d-block mb-2">
                                <i class='bx bx-envelope'></i> {{ $user->email }}
                            </small>
                            <small class="text-muted d-block">
                                <i class='bx bx-calendar'></i> Bergabung {{ $user->created_at->format('d M Y') }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Edit Profil -->
            <div class="col-lg-8">
                <!-- Informasi Pribadi -->
                <!-- Main Profile Information -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="mb-0">Informasi Akun</h5>
                            <span class="badge bg-primary">Administrator</span>
                        </div>
                        <div class="card-body">
                            <form id="formUpdateProfile">
                                @csrf

                                <div class="row g-3">
                                    <!-- Nama Lengkap -->
                                    <div class="col-12">
                                        <label class="form-label" for="name">Nama Lengkap</label>
                                        <div class="input-group input-group-merge">
                                            <span class="input-group-text"><i class='bx bx-user'></i></span>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                id="name" name="name" value="{{ old('name', $user->name) }}"
                                                placeholder="Masukkan nama lengkap" required>
                                        </div>
                                        @error('name')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Email -->
                                    <div class="col-12">
                                        <label class="form-label" for="email">Email</label>
                                        <div class="input-group input-group-merge">
                                            <span class="input-group-text"><i class='bx bx-envelope'></i></span>
                                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                                id="email" name="email" value="{{ old('email', $user->email) }}"
                                                placeholder="Masukkan email" required>
                                        </div>
                                        @error('email')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Button Group -->
                                    <div class="col-12">
                                        <div class="d-flex gap-2 justify-content-end">
                                            <button type="reset" class="btn btn-label-secondary">
                                                <i class='bx bx-reset'></i> Reset
                                            </button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class='bx bx-save'></i> Simpan Perubahan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div> <!-- Ubah Password -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Ubah Password</h5>
                        </div>
                        <div class="card-body">
                            <form id="formUpdatePassword">
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
                    <form id="formUpdatePhoto" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3 text-center">
                                <img id="preview" src="#" alt="Preview"
                                    style="max-width: 200px; max-height: 200px; display: none;"
                                    class="rounded-circle mb-3">
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
                            <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Batal</button>
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

            // Update Profile Form
            $('#formUpdateProfile').on('submit', function(e) {
                e.preventDefault();

                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i> Menyimpan...');

                $.ajax({
                    url: '/api/admin/profile/',
                    type: 'PUT',
                    data: $(this).serialize(),
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log('Update profile response:', response);
                        submitBtn.prop('disabled', false).html(originalText);

                        if (response.success) {
                            toastr.success(response.message || 'Profil berhasil diperbarui');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            toastr.error(response.message || 'Terjadi kesalahan');
                        }
                    },
                    error: function(xhr) {
                        submitBtn.prop('disabled', false).html(originalText);

                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            Object.keys(errors).forEach(key => {
                                toastr.error(errors[key][0]);
                            });
                        } else {
                            toastr.error(xhr.responseJSON?.message ||
                                'Terjadi kesalahan saat memperbarui profil');
                        }
                    }
                });
            });

            // Update Password Form
            $('#formUpdatePassword').on('submit', function(e) {
                e.preventDefault();

                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i> Menyimpan...');

                $.ajax({
                    url: '/api/admin/profile/password',
                    type: 'PUT',
                    data: $(this).serialize(),
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log('Update password response:', response);
                        submitBtn.prop('disabled', false).html(originalText);

                        if (response.success) {
                            toastr.success(response.message || 'Password berhasil diubah', 'Berhasil!', {
                                timeOut: 3000,
                                closeButton: true,
                                progressBar: true
                            });
                            $('#formUpdatePassword')[0].reset();
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            toastr.error(response.message || 'Terjadi kesalahan');
                        }
                    },
                    error: function(xhr) {
                        submitBtn.prop('disabled', false).html(originalText);

                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            Object.keys(errors).forEach(key => {
                                toastr.error(errors[key][0]);
                            });
                        } else {
                            toastr.error(xhr.responseJSON?.message ||
                                'Terjadi kesalahan saat mengubah password');
                        }
                    }
                });
            });

            // Update Photo Form
            $('#formUpdatePhoto').on('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i> Mengunggah...');

                $.ajax({
                    url: '/api/admin/profile/photo',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log('Update photo response:', response);
                        submitBtn.prop('disabled', false).html(originalText);

                        if (response.success) {
                            toastr.success(response.message || 'Foto profil berhasil diperbarui',
                                'Berhasil!', {
                                    timeOut: 3000,
                                    closeButton: true,
                                    progressBar: true
                                });
                            $('#uploadPhotoModal').modal('hide');
                            $('#formUpdatePhoto')[0].reset();
                            $('#preview').hide();
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            toastr.error(response.message || 'Terjadi kesalahan');
                        }
                    },
                    error: function(xhr) {
                        submitBtn.prop('disabled', false).html(originalText);

                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            Object.keys(errors).forEach(key => {
                                toastr.error(errors[key][0]);
                            });
                        } else {
                            toastr.error(xhr.responseJSON?.message ||
                                'Terjadi kesalahan saat mengunggah foto');
                        }
                    }
                });
            });
        </script>
    @endpush
