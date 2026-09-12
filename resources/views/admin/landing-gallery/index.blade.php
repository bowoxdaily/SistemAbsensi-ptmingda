@extends('layouts.app')
@section('title', 'Galeri Landing Page')

@push('styles')
<style>
.gallery-admin-card {
    transition: transform .2s ease, box-shadow .2s ease;
    border: 1px solid #e7e7e8;
    overflow: hidden;
}
.gallery-admin-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(67, 89, 113, 0.12);
}
.gallery-thumb-container {
    position: relative;
    width: 100%;
    height: 190px;
    background-color: #f5f5f9;
    overflow: hidden;
}
.gallery-thumb {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .3s ease;
}
.gallery-badge {
    position: absolute;
    top: 10px;
    left: 10px;
}
.gallery-order-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(0,0,0,0.65);
    color: #fff;
    font-size: 0.75rem;
    padding: 2px 8px;
    border-radius: 20px;
}
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1"><i class="bx bx-images text-primary me-2"></i>Galeri Fasilitas Pabrik</h4>
            <p class="text-muted mb-0">Kelola foto fasilitas pabrik sepatu PT Mingda untuk Landing Page</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createGalleryModal">
            <i class="bx bx-plus me-1"></i> Tambah Foto Galeri
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(isset($errors) && $errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-1"></i>
            @foreach($errors->all() as $err) <div>{{ $err }}</div> @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.landing-gallery.index') }}" class="row g-2">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Cari judul..." value="{{ request('search') }}">
                </div>
                <div class="col-md-4">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
                    @if(request('search') || request('category'))
                        <a href="{{ route('admin.landing-gallery.index') }}" class="btn btn-outline-secondary"><i class="bx bx-reset"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if($galleries->isEmpty())
        <div class="card text-center p-5">
            <div class="card-body">
                <i class="bx bx-image-alt text-muted mb-3" style="font-size: 3rem;"></i>
                <h5 class="text-muted">Belum ada foto galeri</h5>
                <p class="text-muted small">Klik tombol "Tambah Foto Galeri" untuk menambahkan.</p>
            </div>
        </div>
    @else
        <div class="row g-4 mb-4">
            @foreach($galleries as $item)
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="card h-100 gallery-admin-card">
                        <div class="gallery-thumb-container">
                            <img src="{{ $item->image_url }}" alt="{{ $item->title }}" class="gallery-thumb" loading="lazy">
                            <span class="badge bg-primary gallery-badge">{{ $categories[$item->category] ?? $item->category }}</span>
                            <span class="gallery-order-badge">Urutan: {{ $item->sort_order }}</span>
                        </div>
                        <div class="card-body d-flex flex-column justify-content-between p-3">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title fw-bold mb-0 text-truncate" title="{{ $item->title }}">{{ $item->title }}</h6>
                                    <form action="{{ route('admin.landing-gallery.toggle', $item) }}" method="POST" class="ms-2">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="badge {{ $item->is_active ? 'bg-success' : 'bg-secondary' }} border-0" title="Klik ubah status">
                                            {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </button>
                                    </form>
                                </div>
                                <p class="card-text text-muted small mb-3" style="min-height: 38px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    {{ $item->description ?: 'Tidak ada deskripsi' }}
                                </p>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <small class="text-muted">{{ $item->created_at ? $item->created_at->format('d/m/Y') : '-' }}</small>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary btn-edit-gallery" data-item="{{ json_encode($item) }}" title="Edit">
                                        <i class="bx bx-edit-alt"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-delete-gallery" data-action="{{ route('admin.landing-gallery.destroy', $item) }}" data-title="{{ $item->title }}" title="Hapus">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="d-flex justify-content-center">{{ $galleries->links() }}</div>
    @endif
<!-- Modal Tambah Galeri -->
<div class="modal fade" id="createGalleryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" action="{{ route('admin.landing-gallery.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bx bx-plus-circle text-primary me-2"></i>Tambah Foto Galeri</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label required">Judul Fasilitas / Kegiatan</label>
                    <input type="text" name="title" class="form-control" placeholder="Contoh: Lini Produksi Sewing" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Kategori</label>
                        <select name="category" class="form-select" required>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Urutan Tampil</label>
                        <input type="number" name="sort_order" class="form-control" value="0" min="0">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label required">File Foto (Maks 5MB)</label>
                    <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" required onchange="previewUpload(this, 'create-preview')">
                    <div class="mt-2 text-center d-none" id="create-preview-wrapper">
                        <img id="create-preview" class="img-thumbnail" style="max-height: 160px;">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Keterangan singkat kegiatan/fasilitas..."></textarea>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="create_is_active" value="1" checked>
                    <label class="form-check-label" for="create_is_active">Tampilkan di Landing Page</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
<!-- Modal Edit Galeri -->
<div class="modal fade" id="editGalleryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="editGalleryForm" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bx bx-edit text-primary me-2"></i>Edit Foto Galeri</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label required">Judul Fasilitas / Kegiatan</label>
                    <input type="text" name="title" id="edit_title" class="form-control" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Kategori</label>
                        <select name="category" id="edit_category" class="form-select" required>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Urutan Tampil</label>
                        <input type="number" name="sort_order" id="edit_sort_order" class="form-control" min="0">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Ganti Foto (Opsional)</label>
                    <input type="file" name="image" id="edit_image" class="form-control" accept="image/jpeg,image/png,image/webp" onchange="previewUpload(this, 'edit-preview')">
                    <div class="mt-2 text-center" id="edit-preview-wrapper">
                        <img id="edit-preview" class="img-thumbnail" style="max-height: 160px;">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1">
                    <label class="form-check-label" for="edit_is_active">Tampilkan di Landing Page</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Perbarui</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Hapus -->
<div class="modal fade" id="deleteGalleryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form class="modal-content" id="deleteGalleryForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="modal-body text-center p-4">
                <i class="bx bx-trash text-danger mb-3" style="font-size: 3rem;"></i>
                <h5 class="fw-bold mb-2">Hapus Foto?</h5>
                <p class="text-muted small mb-0" id="deleteGalleryTitle"></p>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
function previewUpload(input, targetImgId) {
    const wrapper = document.getElementById(targetImgId + '-wrapper');
    const targetImg = document.getElementById(targetImgId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            targetImg.src = e.target.result;
            if (wrapper) wrapper.classList.remove('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function openEditModal(item) {
    const form = document.getElementById('editGalleryForm');
    form.action = "{{ url('/admin/landing-gallery') }}/" + item.id;

    document.getElementById('edit_title').value = item.title;
    document.getElementById('edit_category').value = item.category;
    document.getElementById('edit_sort_order').value = item.sort_order;
    document.getElementById('edit_description').value = item.description || '';
    document.getElementById('edit_is_active').checked = item.is_active == 1 || item.is_active === true;

    const fileInput = document.getElementById('edit_image');
    if (fileInput) fileInput.value = '';

    const preview = document.getElementById('edit-preview');
    preview.src = item.image_url || (item.image_path && item.image_path.startsWith('http') ? item.image_path : "{{ asset('storage') }}/" + item.image_path);

    const modal = new bootstrap.Modal(document.getElementById('editGalleryModal'));
    modal.show();
}

function confirmDelete(actionUrl, title) {
    const form = document.getElementById('deleteGalleryForm');
    form.action = actionUrl;
    document.getElementById('deleteGalleryTitle').innerText = '"' + title + '"';
    const modal = new bootstrap.Modal(document.getElementById('deleteGalleryModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-edit-gallery').forEach(btn => {
        btn.addEventListener('click', function() {
            try {
                const item = JSON.parse(this.getAttribute('data-item'));
                openEditModal(item);
            } catch (e) {
                console.error('Failed to parse gallery item JSON', e);
            }
        });
    });

    document.querySelectorAll('.btn-delete-gallery').forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.getAttribute('data-action');
            const title = this.getAttribute('data-title');
            confirmDelete(action, title);
        });
    });
});
</script>
@endpush
</div>
@endsection
