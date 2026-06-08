@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Manajemen OPL (One Point Lesson)</h4>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-3">
                <div>
                    <input id="search" class="form-control" placeholder="Cari OPL...">
                </div>
                <div>
                    <a href="{{ route('admin.opls.create') }}" class="btn btn-primary">Buat OPL</a>
                </div>
            </div>

                <div id="table-wrap">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Gambar</th>
                                <th>Judul</th>
                                <th>Tanggal</th>
                                <th>Popup</th>
                                <th>Aktif</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="opl-list">
                            <tr><td colspan="6">Memuat...</td></tr>
                        </tbody>
                    </table>
                </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    async function loadOpls() {
        const q = document.getElementById('search').value;
        const res = await fetch('/api/admin/opls?per_page=20&search=' + encodeURIComponent(q), { headers: {'Accept':'application/json'} });
        const json = await res.json();
        const tbody = document.getElementById('opl-list');
        if (!json.success) { tbody.innerHTML = '<tr><td colspan="5">Gagal memuat</td></tr>'; return; }
        const items = json.data.data || json.data;
        if (items.length === 0) { tbody.innerHTML = '<tr><td colspan="6">Tidak ada OPL</td></tr>'; return; }
        tbody.innerHTML = items.map(i => `
            <tr>
                <td>${i.attachment_url ? `<img src="${i.attachment_url}" style="max-width:120px;max-height:72px;border-radius:6px;">` : '-'}</td>
                <td>${i.title}</td>
                <td>${new Date(i.created_at).toLocaleString()}</td>
                <td>${i.show_popup ? 'Ya' : 'Tidak'}</td>
                <td>${i.is_active ? 'Ya' : 'Tidak'}</td>
                <td>
                    <a href="/admin/opls/${i.id}/edit" class="btn btn-sm btn-outline-primary">Edit</a>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteOpl(${i.id})">Hapus</button>
                </td>
            </tr>
        `).join('');
    }

    async function deleteOpl(id) {
        if (!confirm('Hapus OPL ini?')) return;
        const res = await fetch('/api/admin/opls/' + id, { method: 'DELETE', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept':'application/json'} });
        const j = await res.json();
        alert(j.message || (j.success ? 'Berhasil' : 'Gagal'));
        loadOpls();
    }

    document.getElementById('search').addEventListener('input', () => setTimeout(loadOpls, 300));
    loadOpls();
</script>
@endpush

@endsection
