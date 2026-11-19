@extends('layouts.app')
@section('title', 'Surat Panggilan')
@section('content')
<div class="container-xxl container-p-y">
    <div class="d-flex justify-content-between mb-4"><h4>SDM & Disiplin / Surat Panggilan</h4><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">Buat Surat Panggilan</button></div>
    <div class="card"><div class="card-body">
        <input id="search" class="form-control mb-3" placeholder="Cari nomor surat atau karyawan">
        <div class="table-responsive"><table class="table"><thead><tr><th>Nomor</th><th>Karyawan</th><th>Tanggal Surat</th><th>Jadwal Klarifikasi</th><th>Status Email</th><th>Dokumen</th><th>Aksi</th></tr></thead><tbody id="letters"></tbody></table></div>
        <div class="d-flex justify-content-between align-items-center mt-3"><small id="paginationInfo" class="text-muted"></small><nav aria-label="Pagination surat panggilan"><ul class="pagination mb-0" id="paginationLinks"></ul></nav></div>
    </div></div>
</div>
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Surat Panggilan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body" id="detailContent"></div>
            <div class="modal-footer"><a class="btn btn-outline-primary" id="detailDownload" href="#">Unduh PDF</a><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
        </div>
    </div>
</div>
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buat Surat Panggilan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required" for="employee_search">Karyawan</label>
                            <select class="form-select" id="employee_id" name="employee_id" required>
                                <option value="">Cari karyawan berdasarkan kode atau nama</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required" for="letter_number">Nomor Surat</label>
                            <input type="text" class="form-control" id="letter_number" name="letter_number" maxlength="100" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required" for="issue_date">Tanggal Terbit</label>
                            <input type="date" class="form-control" id="issue_date" name="issue_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required" for="meeting_at">Jadwal Klarifikasi</label>
                            <input type="datetime-local" class="form-control" id="meeting_at" name="meeting_at" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label required" for="location">Tempat</label>
                            <input type="text" class="form-control" id="location" name="location" maxlength="255" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label required" for="purpose">Keperluan / Hal yang Diklarifikasi</label>
                            <textarea class="form-control" id="purpose" name="purpose" rows="3" maxlength="2000" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description">Keterangan Tambahan</label>
                            <textarea class="form-control" id="description" name="description" rows="2" maxlength="2000"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label required" for="document">Dokumen Surat Resmi (PDF)</label>
                            <input type="file" class="form-control" id="document" name="document" accept="application/pdf,.pdf" required>
                            <small class="text-muted">Wajib diunggah. Format PDF, maksimal 5 MB.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class='bx bx-save'></i> Simpan Surat Panggilan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(function () {
    function formatDate(value) {
        const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);
        return match ? `${match[3]}-${match[2]}-${match[1]}` : value;
    }
    function formatDateTime(value) {
        const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);
        return match ? `${match[3]}-${match[2]}-${match[1]} ${match[4]}:${match[5]}` : value;
    }
    let currentPage = 1;
    function loadLetters(page = 1) {
        currentPage = page;
        $.get('/api/admin/call-letters', {search: $('#search').val(), page}).done(function (response) {
            const result = response.data;
            $('#letters').html(result.data.map(letter => `<tr><td>${$('<div>').text(letter.letter_number).html()}</td><td>${$('<div>').text(letter.employee?.name || '-').html()}</td><td>${formatDate(letter.issue_date)}</td><td>${formatDateTime(letter.meeting_at)}</td><td><span class="badge ${letter.email_status === 'sent' ? 'bg-label-success' : letter.email_status === 'failed' ? 'bg-label-danger' : 'bg-label-warning'}">${letter.email_status === 'sent' ? 'Terkirim' : letter.email_status === 'failed' ? 'Gagal' : letter.email_status === 'pending' ? 'Dalam antrean' : 'Belum dikirim'}</span>${letter.email_sent_at ? `<small class="d-block text-muted">${formatDateTime(letter.email_sent_at)}</small>` : ''}</td><td><a class="btn btn-sm btn-outline-secondary" href="/admin/call-letters/${letter.id}/document">Unduh PDF resmi</a></td><td><button class="btn btn-sm btn-info detail-letter" data-id="${letter.id}">Detail</button> <button class="btn btn-sm btn-primary send-email" data-id="${letter.id}" data-name="${$('<div>').text(letter.employee?.name || 'karyawan').html()}"><i class="bx bx-envelope"></i> Kirim Email</button> <button class="btn btn-sm btn-danger delete-letter" data-id="${letter.id}" data-name="${$('<div>').text(letter.letter_number).html()}">Hapus</button></td></tr>`).join('') || '<tr><td colspan="6" class="text-center">Belum ada surat panggilan</td></tr>');
            $('#paginationInfo').text(result.total ? `Menampilkan ${result.from}–${result.to} dari ${result.total} surat` : 'Tidak ada surat panggilan');
            const pages = [];
            for (let number = Math.max(1, result.current_page - 2); number <= Math.min(result.last_page, result.current_page + 2); number++) {
                pages.push(`<li class="page-item ${number === result.current_page ? 'active' : ''}"><button class="page-link" type="button" data-page="${number}" ${number === result.current_page ? 'aria-current="page"' : ''}>${number}</button></li>`);
            }
            $('#paginationLinks').html(`<li class="page-item ${result.current_page <= 1 ? 'disabled' : ''}"><button class="page-link" type="button" data-page="${result.current_page - 1}" ${result.current_page <= 1 ? 'disabled' : ''}>Sebelumnya</button></li>${pages.join('')}<li class="page-item ${result.current_page >= result.last_page ? 'disabled' : ''}"><button class="page-link" type="button" data-page="${result.current_page + 1}" ${result.current_page >= result.last_page ? 'disabled' : ''}>Berikutnya</button></li>`);
        });
    }
    $('#employee_id').select2({
        width: '100%',
        dropdownParent: $('#createModal .modal-content'),
        placeholder: 'Cari karyawan berdasarkan kode atau nama',
        allowClear: true,
        minimumInputLength: 1,
        ajax: {
            url: '/api/admin/call-letters/employees',
            dataType: 'json',
            delay: 250,
            data: params => ({q: params.term, page: params.page || 1}),
            processResults: response => ({
                results: response.results || [],
                pagination: response.pagination || {more: false}
            })
        }
    });
    $('#createModal').on('hidden.bs.modal', function () {
        $('#employee_id').val(null).trigger('change');
    });
    loadLetters();
    $('#search').on('input', function () { loadLetters(1); });
    $('#paginationLinks').on('click', 'button[data-page]:not(:disabled)', function () { loadLetters(Number($(this).data('page'))); });
    $('#letters').on('click', '.detail-letter', function () {
        $.get(`/api/admin/call-letters/${$(this).data('id')}`).done(response => {
            const letter = response.data;
            const escape = value => $('<div>').text(value || '-').html();
            $('#detailContent').html(`<dl class="row mb-0"><dt class="col-sm-4">Nomor Surat</dt><dd class="col-sm-8">${escape(letter.letter_number)}</dd><dt class="col-sm-4">Karyawan</dt><dd class="col-sm-8">${escape(letter.employee?.name)}</dd><dt class="col-sm-4">Tanggal Surat</dt><dd class="col-sm-8">${formatDate(letter.issue_date)}</dd><dt class="col-sm-4">Jadwal Klarifikasi</dt><dd class="col-sm-8">${formatDateTime(letter.meeting_at)}</dd><dt class="col-sm-4">Tempat</dt><dd class="col-sm-8">${escape(letter.location)}</dd><dt class="col-sm-4">Keperluan</dt><dd class="col-sm-8 text-break">${escape(letter.purpose)}</dd><dt class="col-sm-4">Keterangan</dt><dd class="col-sm-8 text-break">${escape(letter.description)}</dd><dt class="col-sm-4">Status Email</dt><dd class="col-sm-8">${letter.email_status === 'sent' ? 'Terkirim' : letter.email_status === 'failed' ? 'Gagal' : letter.email_status === 'pending' ? 'Dalam antrean' : 'Belum dikirim'}${letter.email_sent_at ? ` — ${formatDateTime(letter.email_sent_at)}` : ''}${letter.email_error ? `<div class="text-danger small">${escape(letter.email_error)}</div>` : ''}</dd></dl>`);
            $('#detailDownload').attr('href', `/admin/call-letters/${letter.id}/document`);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('detailModal')).show();
        }).fail(xhr => Swal.fire({icon: 'error', title: 'Gagal memuat detail', text: xhr.responseJSON?.message || 'Gagal memuat detail surat.'}));
    });
    $('#letters').on('click', '.delete-letter', function () {
        const button = $(this);
        Swal.fire({
            icon: 'warning',
            title: 'Hapus surat panggilan?',
            text: `Surat ${button.data('name')} dan PDF-nya akan dihapus permanen.`,
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Tidak',
            confirmButtonColor: '#dc3545',
            reverseButtons: true
        }).then(result => {
            if (!result.isConfirmed) return;
            button.prop('disabled', true);
            $.ajax({
                url: `/api/admin/call-letters/${button.data('id')}`,
                method: 'DELETE',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
            }).done(response => {
                Swal.fire({icon: 'success', title: 'Berhasil dihapus', text: response.message, timer: 1800, showConfirmButton: false});
                loadLetters(currentPage);
            }).fail(xhr => {
                Swal.fire({icon: 'error', title: 'Gagal menghapus', text: xhr.responseJSON?.message || 'Gagal menghapus surat panggilan.'});
                button.prop('disabled', false);
            });
        });
    });
    $('#letters').on('click', '.send-email', function () {
        const button = $(this);
        Swal.fire({
            icon: 'question',
            title: 'Kirim ulang email?',
            text: `Notifikasi Surat Panggilan akan dikirim ke ${button.data('name')}.`,
            showCancelButton: true,
            confirmButtonText: 'Ya, kirim',
            cancelButtonText: 'Tidak',
            confirmButtonColor: '#696cff',
            reverseButtons: true
        }).then(result => {
            if (!result.isConfirmed) return;
            button.prop('disabled', true);
            $.ajax({
                url: `/api/admin/call-letters/${button.data('id')}/send-notification`,
                method: 'POST',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
            }).done(response => Swal.fire({icon: 'success', title: 'Email terkirim', text: response.message, timer: 1800, showConfirmButton: false}))
                .fail(xhr => Swal.fire({icon: 'error', title: 'Gagal mengirim email', text: xhr.responseJSON?.message || 'Gagal mengirim email Surat Panggilan.'}))
                .always(() => button.prop('disabled', false));
        });
    });
    $('#createForm').on('submit', function (event) {
        event.preventDefault();
        const form = this;
        $.ajax({url: '/api/admin/call-letters', method: 'POST', data: new FormData(form), processData: false, contentType: false})
            .done(response => {
                form.reset();
                bootstrap.Modal.getOrCreateInstance(document.getElementById('createModal')).hide();
                loadLetters();
                Swal.fire({icon: 'success', title: 'Surat tersimpan', text: response.message, timer: 2000, showConfirmButton: false});
            })
            .fail(xhr => Swal.fire({icon: 'error', title: 'Gagal menyimpan surat', text: xhr.responseJSON?.message || 'Gagal menyimpan surat panggilan.'}));
    });
});
</script>
@endpush

