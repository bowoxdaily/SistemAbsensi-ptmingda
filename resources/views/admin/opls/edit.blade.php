@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Edit OPL</h4>

    <div class="card">
        <div class="card-body">
            <form id="form-opl" enctype="multipart/form-data">
                <input type="hidden" name="id" id="opl-id">
                <div class="mb-3">
                    <label class="form-label">Judul</label>
                    <input name="title" id="title" class="form-control" required>
                </div>
                <!-- Content removed: OPL now only uses Title and Image -->
                <div class="mb-3">
                    <label class="form-label">Gambar OPL (jpg/png)</label>
                    <input type="file" name="attachment" id="attachment" accept="image/*" class="form-control">
                    <div id="attachment-preview" class="mt-2"></div>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="show_popup" id="show_popup" class="form-check-input">
                    <label class="form-check-label" for="show_popup">Tampilkan sebagai popup</label>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="is_active" id="is_active" class="form-check-input">
                    <label class="form-check-label" for="is_active">Aktif</label>
                </div>
                <button class="btn btn-primary">Simpan Perubahan</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Extract the OPL id from the URL (/admin/opls/{id}/edit)
    const _segments = location.pathname.split('/').filter(s => s.length > 0);
    const id = _segments[_segments.length - 2] || _segments.pop();

    document.getElementById('form-opl').addEventListener('submit', async function(e){ e.preventDefault(); submitUpdate(); });

    async function submitUpdate() {
        const form = document.getElementById('form-opl');
        const fd = new FormData(form);
        fd.set('_method', 'PUT');
        fd.set('show_popup', document.getElementById('show_popup').checked ? 1 : 0);
        fd.set('is_active', document.getElementById('is_active').checked ? 1 : 0);

        const res = await fetch('/api/admin/opls/' + id, { method: 'POST', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept':'application/json'}, body: fd});
        const j = await res.json();
        if (j.success) window.location.href = '{{ route('admin.opls.index') }}';
        else alert(j.message || 'Gagal memperbarui');
    }

    // load data into form
    async function load(){
        const res = await fetch('/api/admin/opls/' + id, { headers: {'Accept':'application/json'} });
        const j = await res.json();
        if (!j.success) { alert('OPL tidak ditemukan'); return; }
        const o = j.data;
        document.getElementById('opl-id').value = o.id;
        document.getElementById('title').value = o.title;
        // content field removed; nothing to load here
        document.getElementById('show_popup').checked = !!o.show_popup;
        document.getElementById('is_active').checked = !!o.is_active;
        if (o.attachment_url) {
            document.getElementById('attachment-preview').innerHTML = `<img src="${o.attachment_url}" style="max-width:220px;max-height:160px;border-radius:8px;">`;
        }
    }

    load();
</script>
@endpush

@endsection
