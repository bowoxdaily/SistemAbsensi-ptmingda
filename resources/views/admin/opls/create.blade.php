@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Buat OPL</h4>

    <div class="card">
        <div class="card-body">
            <form id="form-opl" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Judul</label>
                    <input name="title" class="form-control" required>
                </div>
                <!-- Content removed: OPL now only uses Title and Image -->
                <div class="mb-3">
                    <label class="form-label">Gambar OPL (jpg/png)</label>
                    <input type="file" name="attachment" accept="image/*" class="form-control">
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="show_popup" class="form-check-input" id="showPopup">
                    <label class="form-check-label" for="showPopup">Tampilkan sebagai popup</label>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="is_active" class="form-check-input" id="isActive" checked>
                    <label class="form-check-label" for="isActive">Aktif</label>
                </div>
                <button class="btn btn-primary">Simpan</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('form-opl').addEventListener('submit', async function(e){
        e.preventDefault();
        const form = this;
        const fd = new FormData(form);
        fd.set('show_popup', form.show_popup?.checked ? 1 : 0);
        fd.set('is_active', form.is_active?.checked ? 1 : 0);

        const res = await fetch('/api/admin/opls', { method: 'POST', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept':'application/json'}, body: fd});
        const j = await res.json();
        if (j.success) {
            window.location.href = '{{ route('admin.opls.index') }}';
        } else {
            alert(j.message || 'Gagal');
        }
    });
</script>
@endpush

@endsection
