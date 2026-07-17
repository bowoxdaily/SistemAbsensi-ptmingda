@extends('layouts.app')

@section('title', 'Pengaturan SMTP Email')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Pengaturan /</span> SMTP Email
    </h4>

    <div class="alert alert-info">
        Gunakan kredensial SMTP terpisah untuk email notifikasi umum dan email interview.
    </div>

    @if(isset($storageReady) && !$storageReady)
        <div class="alert alert-warning">
            Tabel <strong>email_smtp_settings</strong> belum tersedia. Jalankan <code>php artisan migrate</code> agar fitur simpan/test SMTP bisa digunakan.
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">SMTP Notifikasi</h5>
                    <span class="badge {{ ($settings['notifications']['using_custom'] ?? false) ? 'bg-success' : 'bg-secondary' }}" id="badge-notifications">
                        {{ ($settings['notifications']['using_custom'] ?? false) ? 'Custom Aktif' : 'Fallback Global' }}
                    </span>
                </div>
                <div class="card-body">
                    <form class="smtp-form" data-context="notifications">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">From Name</label>
                            <input type="text" class="form-control" name="from_name" value="{{ $settings['notifications']['from_name'] ?? '' }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">From Address</label>
                            <input type="email" class="form-control" name="from_address" value="{{ $settings['notifications']['from_address'] ?? '' }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">SMTP Host</label>
                            <input type="text" class="form-control" name="smtp_host" value="{{ $settings['notifications']['smtp_host'] ?? '' }}" required>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Port</label>
                                <input type="number" class="form-control" name="smtp_port" value="{{ $settings['notifications']['smtp_port'] ?? 587 }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Encryption</label>
                                <select class="form-select" name="smtp_encryption" required>
                                    <option value="tls" {{ ($settings['notifications']['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ ($settings['notifications']['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                    <option value="none" {{ ($settings['notifications']['smtp_encryption'] ?? '') === 'none' ? 'selected' : '' }}>None</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">SMTP Username</label>
                            <input type="text" class="form-control" name="smtp_username" value="{{ $settings['notifications']['smtp_username'] ?? '' }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">SMTP Password</label>
                            <input type="password" class="form-control" name="smtp_password" placeholder="Kosongkan jika tidak diubah">
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ ($settings['notifications']['is_active'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label">Aktifkan custom credential</label>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <button type="button" class="btn btn-outline-secondary btn-test" data-context="notifications">Test</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">SMTP Interview</h5>
                    <span class="badge {{ ($settings['interview']['using_custom'] ?? false) ? 'bg-success' : 'bg-secondary' }}" id="badge-interview">
                        {{ ($settings['interview']['using_custom'] ?? false) ? 'Custom Aktif' : 'Fallback Global' }}
                    </span>
                </div>
                <div class="card-body">
                    <form class="smtp-form" data-context="interview">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">From Name</label>
                            <input type="text" class="form-control" name="from_name" value="{{ $settings['interview']['from_name'] ?? '' }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">From Address</label>
                            <input type="email" class="form-control" name="from_address" value="{{ $settings['interview']['from_address'] ?? '' }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">SMTP Host</label>
                            <input type="text" class="form-control" name="smtp_host" value="{{ $settings['interview']['smtp_host'] ?? '' }}" required>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Port</label>
                                <input type="number" class="form-control" name="smtp_port" value="{{ $settings['interview']['smtp_port'] ?? 587 }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Encryption</label>
                                <select class="form-select" name="smtp_encryption" required>
                                    <option value="tls" {{ ($settings['interview']['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ ($settings['interview']['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                    <option value="none" {{ ($settings['interview']['smtp_encryption'] ?? '') === 'none' ? 'selected' : '' }}>None</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">SMTP Username</label>
                            <input type="text" class="form-control" name="smtp_username" value="{{ $settings['interview']['smtp_username'] ?? '' }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">SMTP Password</label>
                            <input type="password" class="form-control" name="smtp_password" placeholder="Kosongkan jika tidak diubah">
                        </div>
                        <hr class="my-4">
                        <h6 class="mb-3">Template Email Interview</h6>
                        @if (!($settings['interview']['template_storage_ready'] ?? false))
                            <div class="alert alert-warning">
                                Kolom template email belum tersedia di database. Jalankan <code>php artisan migrate</code> agar perubahan template bisa disimpan.
                            </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label">Subject Interview</label>
                            <input type="text" class="form-control" name="interview_subject_template" value="{{ $settings['interview']['interview_subject_template'] ?? '' }}" maxlength="191">
                            <div class="form-text">Placeholder: <code>{nama}</code>, <code>{posisi}</code>, <code>{tanggal}</code>, <code>{waktu}</code>, <code>{lokasi}</code></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Body Interview</label>
                            <textarea class="form-control" name="interview_body_template" rows="9">{{ $settings['interview']['interview_body_template'] ?? '' }}</textarea>
                            <div class="form-text">Placeholder: <code>{nama}</code>, <code>{posisi}</code>, <code>{tanggal}</code>, <code>{waktu}</code>, <code>{lokasi}</code>, <code>{catatan}</code>, <code>{qr_url}</code></div>
                        </div>
                        <h6 class="mb-3 mt-4">Template Email Join</h6>
                        <div class="mb-3">
                            <label class="form-label">Subject Join</label>
                            <input type="text" class="form-control" name="join_call_subject_template" value="{{ $settings['interview']['join_call_subject_template'] ?? '' }}" maxlength="191">
                            <div class="form-text">Placeholder: <code>{nama}</code>, <code>{departemen}</code>, <code>{sub_departemen}</code>, <code>{sub_department}</code>, <code>{tanggal}</code>, <code>{waktu}</code>, <code>{lokasi}</code></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Body Join</label>
                            <textarea class="form-control" name="join_call_body_template" rows="9">{{ $settings['interview']['join_call_body_template'] ?? '' }}</textarea>
                            <div class="form-text">Placeholder: <code>{nama}</code>, <code>{departemen}</code>, <code>{sub_departemen}</code>, <code>{sub_department}</code>, <code>{posisi}</code>, <code>{tanggal}</code>, <code>{waktu}</code>, <code>{lokasi}</code>, <code>{catatan}</code>, <code>{qr_url}</code></div>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ ($settings['interview']['is_active'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label">Aktifkan custom credential</label>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <button type="button" class="btn btn-outline-secondary btn-test" data-context="interview">Test</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.smtp-form').forEach((form) => {
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const context = this.dataset.context;
        const formData = new FormData(this);
        if (!formData.get('is_active')) {
            formData.set('is_active', '0');
        }

        try {
            const res = await fetch(`/api/settings/email-smtp/${context}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.querySelector('[name="_token"]').value,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const result = await res.json();
            if (!result.success) {
                throw new Error(result.message || 'Gagal menyimpan pengaturan');
            }

            const badge = document.getElementById(`badge-${context}`);
            badge.className = 'badge bg-success';
            badge.textContent = 'Custom Aktif';

            Swal.fire('Berhasil', result.message, 'success');
        } catch (error) {
            Swal.fire('Error', error.message || 'Terjadi kesalahan', 'error');
        }
    });
});

document.querySelectorAll('.btn-test').forEach((btn) => {
    btn.addEventListener('click', async function () {
        const context = this.dataset.context;
        const toEmail = await Swal.fire({
            title: 'Test Email',
            input: 'email',
            inputLabel: 'Kirim test ke email',
            inputPlaceholder: 'contoh@domain.com',
            showCancelButton: true,
            confirmButtonText: 'Kirim',
            cancelButtonText: 'Batal',
            inputValidator: (value) => {
                if (!value) return 'Email wajib diisi';
                return null;
            }
        });

        if (!toEmail.isConfirmed) return;

        try {
            const form = document.querySelector(`.smtp-form[data-context="${context}"]`);
            const token = form.querySelector('[name="_token"]').value;
            const res = await fetch(`/api/settings/email-smtp/${context}/test`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ to_email: toEmail.value }),
            });

            const result = await res.json();
            if (!result.success) {
                throw new Error(result.message || 'Test gagal');
            }

            Swal.fire('Berhasil', result.message, 'success');
        } catch (error) {
            Swal.fire('Error', error.message || 'Terjadi kesalahan', 'error');
        }
    });
});
</script>
@endpush
@endsection
