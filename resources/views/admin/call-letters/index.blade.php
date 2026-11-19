@extends('layouts.app')
@section('title', 'Surat Panggilan')
@section('content')
<div class="container-xxl container-p-y">
    <div class="d-flex justify-content-between mb-4"><h4>SDM & Disiplin / Surat Panggilan</h4><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">Buat Surat Panggilan</button></div>
    <div class="card"><div class="card-body">
        <input id="search" class="form-control mb-3" placeholder="Cari nomor surat atau karyawan">
        <div class="table-responsive"><table class="table"><thead><tr><th>Nomor</th><th>Karyawan</th><th>Tanggal Surat</th><th>Jadwal Klarifikasi</th><th>Dokumen</th><th>Aksi</th></tr></thead><tbody id="letters"></tbody></table></div>
    </div></div>
</div>
<div class="modal fade" id="createModal" tabindex="-1"><div class="modal-dialog modal-lg"><form id="createForm" class="modal-content" enctype="multipart/form-data">
    <div class="modal-header"><h5 class="modal-title">Buat Surat Panggilan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="employee_search">Cari karyawan berdasarkan kode atau nama</label><input type="search" class="form-control mb-2" id="employee_search" placeholder="Ketik kode atau nama karyawan" autocomplete="off"><select class="form-select" id="employee_id" name="employee_id" required><option value="">Cari lalu pilih karyawan</option></select></div>
        <div class="col-md-6"><label class="form-label" for="letter_number">Nomor surat</label><input class="form-control" id="letter_number" name="letter_number" maxlength="100" required></div>
        <div class="col-md-6"><label class="form-label" for="issue_date">Tanggal surat</label><input type="date" class="form-control" id="issue_date" name="issue_date" required></div>
        <div class="col-md-6"><label class="form-label" for="meeting_at">Jadwal klarifikasi</label><input type="datetime-local" class="form-control" id="meeting_at" name="meeting_at" required></div>
        <div class="col-12"><label class="form-label" for="location">Tempat</label><input class="form-control" id="location" name="location" maxlength="255" required></div>
        <div class="col-12"><label class="form-label" for="purpose">Keperluan/hal yang diklarifikasi</label><textarea class="form-control" id="purpose" name="purpose" rows="3" maxlength="2000" required></textarea></div>
        <div class="col-12"><label class="form-label" for="description">Keterangan tambahan</label><textarea class="form-control" id="description" name="description" rows="2" maxlength="2000"></textarea></div>
        <div class="col-12"><label class="form-label" for="document">File surat resmi fisik (PDF, maks. 5 MB) <span class="text-danger">*</span></label><input type="file" class="form-control" id="document" name="document" accept="application/pdf,.pdf" required></div>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
</form></div></div>
@endsection
@push('scripts')
<script>
$(function () {
    function loadLetters() {
        $.get('/api/admin/call-letters', {search: $('#search').val()}).done(function (response) {
            $('#letters').html(response.data.data.map(letter => `<tr><td>${$('<div>').text(letter.letter_number).html()}</td><td>${$('<div>').text(letter.employee?.name || '-').html()}</td><td>${letter.issue_date}</td><td>${letter.meeting_at}</td><td><a class="btn btn-sm btn-outline-secondary" href="/admin/call-letters/${letter.id}/document">Surat fisik</a></td><td><a class="btn btn-sm btn-outline-primary" target="_blank" href="/admin/call-letters/${letter.id}/print">Lihat / Cetak</a> <a class="btn btn-sm btn-primary" href="/admin/call-letters/${letter.id}/pdf">PDF digital</a></td></tr>`).join('') || '<tr><td colspan="5" class="text-center">Belum ada surat panggilan</td></tr>');
        });
    }
    let employeeSearchRequest;
    $('#employee_search').on('input', function () {
        clearTimeout(employeeSearchRequest);
        const term = this.value.trim();
        const $employee = $('#employee_id');
        $employee.html('<option value="">Memuat karyawan...</option>');
        if (!term) {
            $employee.html('<option value="">Ketik kode atau nama karyawan</option>');
            return;
        }
        employeeSearchRequest = setTimeout(() => {
            $.get('/api/admin/call-letters/employees', {q: term, page: 1})
                .done(response => {
                    const employees = response.results || [];
                    $employee.empty().append(new Option(
                        employees.length ? 'Pilih karyawan' : 'Karyawan tidak ditemukan', ''
                    ));
                    employees.forEach(employee => $employee.append(new Option(employee.text, employee.id)));
                })
                .fail(() => $employee.html('<option value="">Gagal memuat karyawan</option>'));
        }, 250);
    });
    $('#createModal').on('hidden.bs.modal', function () {
        $('#employee_search').val('');
        $('#employee_id').html('<option value="">Ketik kode atau nama karyawan</option>');
    });
    loadLetters();
    $('#search').on('input', loadLetters);
    $('#createForm').on('submit', function (event) {
        event.preventDefault();
        const form = this;
        $.ajax({url: '/api/admin/call-letters', method: 'POST', data: new FormData(form), processData: false, contentType: false})
            .done(() => { form.reset(); bootstrap.Modal.getOrCreateInstance('#createModal').hide(); loadLetters(); })
            .fail(xhr => alert(xhr.responseJSON?.message || 'Gagal menyimpan surat panggilan.'));
    });
});
</script>
@endpush

