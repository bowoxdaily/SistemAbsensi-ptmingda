@extends('layouts.app')

@section('title', 'Pengaturan WhatsApp')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Pengaturan /</span> WhatsApp Notification
        </h4>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <strong>Berhasil!</strong> {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <strong>Error!</strong> {{ session('error') }}
            </div>
        @endif

        <div class="row">
            <!-- Configuration Card -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Konfigurasi WhatsApp</h5>
                        <span class="badge {{ $setting->is_enabled ? 'bg-success' : 'bg-secondary' }}">
                            {{ $setting->is_enabled ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                    <div class="card-body">
                        <form id="configForm">
                            @csrf
                            <input type="hidden" name="form_type" value="config">

                            <!-- Provider (Hidden - Fixed to Fonnte) -->
                            <input type="hidden" name="provider" value="fonnte">

                            <!-- Fonnte API Key -->
                            <div class="mb-3">
                                <label class="form-label" for="api_key">Fonnte API Key</label>
                                <div class="input-group">
                                    <input type="text" class="form-control @error('api_key') is-invalid @enderror"
                                        id="api_key" name="api_key" value="{{ old('api_key', $setting->api_key) }}"
                                        placeholder="Your Fonnte API Key">
                                    <button class="btn btn-outline-secondary" type="button" onclick="testConnection()">
                                        <i class='bx bx-test-tube'></i> Test
                                    </button>
                                </div>
                                <div class="form-text">
                                    Get your API key from <a href="https://fonnte.com/dashboard" target="_blank">Fonnte
                                        Dashboard</a>. Free 100 messages/month!
                                </div>
                                @error('api_key')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Sender Number -->
                            <div class="mb-3">
                                <label class="form-label" for="sender">Nomor Pengirim (Optional)</label>
                                <input type="text" class="form-control @error('sender') is-invalid @enderror"
                                    id="sender" name="sender" value="{{ old('sender', $setting->sender) }}"
                                    placeholder="628123456789">
                                <div class="form-text">Format: 628xxx (optional, untuk tracking purposes)</div>
                                @error('sender')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Admin Phone for Leave Notifications -->
                            <div class="mb-3">
                                <label class="form-label" for="admin_phone">Nomor Admin (untuk notifikasi cuti)</label>
                                <input type="text" class="form-control @error('admin_phone') is-invalid @enderror"
                                    id="admin_phone" name="admin_phone"
                                    value="{{ old('admin_phone', $setting->admin_phone) }}" placeholder="628123456789">
                                <div class="form-text">Nomor WhatsApp admin yang akan menerima notifikasi pengajuan cuti
                                </div>
                                @error('admin_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <hr class="my-4">

                            <!-- Enable/Disable Toggles -->
                            <div class="mb-3">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled"
                                        {{ $setting->is_enabled ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_enabled">
                                        <strong>Aktifkan Notifikasi WhatsApp</strong>
                                    </label>
                                </div>

                                <h6 class="mt-3 mb-2">Notifikasi Absensi</h6>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="notify_checkin"
                                        name="notify_checkin" {{ $setting->notify_checkin ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notify_checkin">
                                        Kirim notifikasi saat Check-in
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-2 ms-4">
                                    <input class="form-check-input" type="checkbox" id="send_checkin_photo"
                                        name="send_checkin_photo" {{ $setting->send_checkin_photo ? 'checked' : '' }}>
                                    <label class="form-check-label" for="send_checkin_photo">
                                        <small class="text-muted">📷 Kirim foto check-in</small>
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="notify_checkout"
                                        name="notify_checkout" {{ $setting->notify_checkout ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notify_checkout">
                                        Kirim notifikasi saat Check-out
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-2 ms-4">
                                    <input class="form-check-input" type="checkbox" id="send_checkout_photo"
                                        name="send_checkout_photo" {{ $setting->send_checkout_photo ? 'checked' : '' }}>
                                    <label class="form-check-label" for="send_checkout_photo">
                                        <small class="text-muted">📷 Kirim foto check-out</small>
                                    </label>
                                </div>

                                <h6 class="mt-3 mb-2">Notifikasi Cuti/Izin</h6>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="notify_leave_request"
                                        name="notify_leave_request" {{ $setting->notify_leave_request ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notify_leave_request">
                                        Kirim ke admin saat karyawan ajukan cuti/izin
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="notify_leave_approved"
                                        name="notify_leave_approved"
                                        {{ $setting->notify_leave_approved ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notify_leave_approved">
                                        Kirim ke karyawan saat cuti disetujui
                                    </label>
                                </div>

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="notify_leave_rejected"
                                        name="notify_leave_rejected"
                                        {{ $setting->notify_leave_rejected ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notify_leave_rejected">
                                        Kirim ke karyawan saat cuti ditolak
                                    </label>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class='bx bx-save'></i> Simpan Pengaturan
                                </button>
                                <button type="button" class="btn btn-info" onclick="showTestMessageModal()">
                                    <i class='bx bx-message-square-check'></i> Kirim Pesan Test
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Message Templates Card -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Template Pesan</h5>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetTemplates()">
                            <i class='bx bx-reset'></i> Reset ke Default
                        </button>
                    </div>
                    <div class="card-body">
                        <form id="templateForm">
                            @csrf
                            <input type="hidden" name="form_type" value="template">

                            <!-- Hidden fields untuk preserve settings lain saat update template -->
                            <input type="hidden" name="provider" value="fonnte">

                            <!-- Checkbox values preserved via hidden inputs -->
                            @if ($setting->is_enabled)
                                <input type="hidden" name="is_enabled" value="1">
                            @endif

                            @if ($setting->notify_checkin)
                                <input type="hidden" name="notify_checkin" value="1">
                            @endif

                            @if ($setting->notify_checkout)
                                <input type="hidden" name="notify_checkout" value="1">
                            @endif

                            @if ($setting->send_checkin_photo)
                                <input type="hidden" name="send_checkin_photo" value="1">
                            @endif

                            @if ($setting->send_checkout_photo)
                                <input type="hidden" name="send_checkout_photo" value="1">
                            @endif

                            @if ($setting->notify_leave_request)
                                <input type="hidden" name="notify_leave_request" value="1">
                            @endif

                            @if ($setting->notify_leave_approved)
                                <input type="hidden" name="notify_leave_approved" value="1">
                            @endif

                            @if ($setting->notify_leave_rejected)
                                <input type="hidden" name="notify_leave_rejected" value="1">
                            @endif

                            <h5 class="mb-3">Template Absensi</h5>

                            <!-- Check-in Template -->
                            <div class="mb-4">
                                <label class="form-label" for="checkin_template">Template Check-in</label>
                                <textarea class="form-control @error('checkin_template') is-invalid @enderror" id="checkin_template"
                                    name="checkin_template" rows="6">{{ old('checkin_template', $setting->checkin_template) }}</textarea>
                                <div class="form-text">
                                    <strong>Variabel tersedia:</strong> {name}, {time}, {status}, {location}
                                </div>
                                @error('checkin_template')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Check-out Template -->
                            <div class="mb-4">
                                <label class="form-label" for="checkout_template">Template Check-out</label>
                                <textarea class="form-control @error('checkout_template') is-invalid @enderror" id="checkout_template"
                                    name="checkout_template" rows="6">{{ old('checkout_template', $setting->checkout_template) }}</textarea>
                                <div class="form-text">
                                    <strong>Variabel tersedia:</strong> {name}, {time}, {duration}, {location}
                                </div>
                                @error('checkout_template')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <h5 class="mb-3 mt-4">Template Cuti/Izin</h5>

                            <!-- Leave Request Template (to Admin) -->
                            <div class="mb-4">
                                <label class="form-label" for="leave_request_template">Template Pengajuan Cuti (ke
                                    Admin)</label>
                                <textarea class="form-control @error('leave_request_template') is-invalid @enderror" id="leave_request_template"
                                    name="leave_request_template" rows="7">{{ old('leave_request_template', $setting->leave_request_template ?? \App\Models\WhatsAppSetting::getDefaultLeaveRequestTemplate()) }}</textarea>
                                <div class="form-text">
                                    <strong>Variabel tersedia:</strong> {employee_name}, {employee_nip} (Kode Karyawan),
                                    {leave_type},
                                    {start_date}, {end_date}, {total_days}, {reason}
                                </div>
                                @error('leave_request_template')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Leave Approved Template (to Employee) -->
                            <div class="mb-4">
                                <label class="form-label" for="leave_approved_template">Template Cuti Disetujui (ke
                                    Karyawan)</label>
                                <textarea class="form-control @error('leave_approved_template') is-invalid @enderror" id="leave_approved_template"
                                    name="leave_approved_template" rows="7">{{ old('leave_approved_template', $setting->leave_approved_template ?? \App\Models\WhatsAppSetting::getDefaultLeaveApprovedTemplate()) }}</textarea>
                                <div class="form-text">
                                    <strong>Variabel tersedia:</strong> {employee_name}, {leave_type}, {start_date},
                                    {end_date}, {total_days}, {approved_by}, {approved_at}
                                </div>
                                @error('leave_approved_template')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Leave Rejected Template (to Employee) -->
                            <div class="mb-4">
                                <label class="form-label" for="leave_rejected_template">Template Cuti Ditolak (ke
                                    Karyawan)</label>
                                <textarea class="form-control @error('leave_rejected_template') is-invalid @enderror" id="leave_rejected_template"
                                    name="leave_rejected_template" rows="7">{{ old('leave_rejected_template', $setting->leave_rejected_template ?? \App\Models\WhatsAppSetting::getDefaultLeaveRejectedTemplate()) }}</textarea>
                                <div class="form-text">
                                    <strong>Variabel tersedia:</strong> {employee_name}, {leave_type}, {start_date},
                                    {end_date}, {total_days}, {rejection_reason}, {approved_by}
                                </div>
                                @error('leave_rejected_template')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class='bx bx-save'></i> Simpan Template
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Info & Help Card -->
            <div class="col-md-4">
                <!-- Status Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Status Sistem</h5>
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar me-2">
                                <span
                                    class="avatar-initial rounded bg-label-{{ $setting->is_enabled ? 'success' : 'secondary' }}">
                                    <i class='bx {{ $setting->is_enabled ? 'bxs-check-circle' : 'bx-x-circle' }}'></i>
                                </span>
                            </div>
                            <div>
                                <h6 class="mb-0">WhatsApp Notification</h6>
                                <small class="text-muted">{{ $setting->is_enabled ? 'Aktif' : 'Nonaktif' }}</small>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-2">
                            <small class="text-muted">Provider:</small>
                            <strong class="float-end">Fonnte</strong>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">API Key:</small>
                            <strong
                                class="float-end">{{ $setting->api_key ? '••••••••' . substr($setting->api_key, -4) : 'Belum diset' }}</strong>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Check-in Notification:</small>
                            <strong class="float-end">{{ $setting->notify_checkin ? 'Ya' : 'Tidak' }}</strong>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Check-out Notification:</small>
                            <strong class="float-end">{{ $setting->notify_checkout ? 'Ya' : 'Tidak' }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Setup Guide Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class='bx bx-info-circle'></i> Setup Guide
                        </h5>

                        <div class="setup-steps">
                            <ol class="ps-3">
                                <li class="mb-2">Daftar di <a href="https://fonnte.com" target="_blank"
                                        class="fw-bold">fonnte.com</a></li>
                                <li class="mb-2">Verifikasi akun Anda via email</li>
                                <li class="mb-2">Login ke dashboard Fonnte</li>
                                <li class="mb-2">Scan QR Code WhatsApp di halaman dashboard</li>
                                <li class="mb-2">Copy <strong>API Key</strong> dari dashboard</li>
                                <li class="mb-2">Paste API Key di field "Fonnte API Key" di atas</li>
                                <li class="mb-2">Klik tombol <strong>"Test"</strong> untuk verifikasi koneksi</li>
                                <li class="mb-2">Aktifkan toggle <strong>"Aktifkan Notifikasi WhatsApp"</strong></li>
                                <li class="mb-2">Klik <strong>"Simpan Pengaturan"</strong></li>
                            </ol>

                            <div class="alert alert-success p-3 mt-3">
                                <div class="d-flex align-items-center">
                                    <i class='bx bx-gift fs-4 me-2'></i>
                                    <div>
                                        <strong>Paket Gratis:</strong><br>
                                        <small>100 pesan per bulan • Unlimited devices</small>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info p-3 mt-2">
                                <div class="d-flex align-items-center">
                                    <i class='bx bx-info-circle fs-4 me-2'></i>
                                    <div>
                                        <small><strong>Tips:</strong> Pastikan nomor WhatsApp Anda sudah terverifikasi dan
                                            aktif di Fonnte sebelum mengirim pesan.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Variables Info Card -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Variabel Template</h5>
                        <p class="small text-muted">Gunakan variabel berikut dalam template pesan:</p>

                        <h6 class="small fw-bold">Absensi</h6>

                        <h6 class="small mt-2">Check-in:</h6>
                        <ul class="small ps-3">
                            <li><code>{name}</code> - Nama karyawan</li>
                            <li><code>{time}</code> - Waktu check-in</li>
                            <li><code>{status}</code> - Status (Hadir/Terlambat)</li>
                            <li><code>{location}</code> - Lokasi check-in</li>
                        </ul>

                        <h6 class="small mt-2">Check-out:</h6>
                        <ul class="small ps-3">
                            <li><code>{name}</code> - Nama karyawan</li>
                            <li><code>{time}</code> - Waktu check-out</li>
                            <li><code>{duration}</code> - Durasi kerja</li>
                            <li><code>{location}</code> - Lokasi check-out</li>
                        </ul>

                        <h6 class="small fw-bold mt-3">Cuti/Izin</h6>

                        <h6 class="small mt-2">Pengajuan (ke Admin):</h6>
                        <ul class="small ps-3">
                            <li><code>{employee_name}</code> - Nama karyawan</li>
                            <li><code>{employee_nip}</code> - Kode Karyawan</li>
                            <li><code>{leave_type}</code> - Jenis (Cuti/Izin/Sakit)</li>
                            <li><code>{start_date}</code> - Tanggal mulai</li>
                            <li><code>{end_date}</code> - Tanggal selesai</li>
                            <li><code>{total_days}</code> - Total hari</li>
                            <li><code>{reason}</code> - Alasan</li>
                        </ul>

                        <h6 class="small mt-2">Disetujui (ke Karyawan):</h6>
                        <ul class="small ps-3">
                            <li><code>{employee_name}</code> - Nama karyawan</li>
                            <li><code>{leave_type}</code> - Jenis</li>
                            <li><code>{start_date}</code> - Tanggal mulai</li>
                            <li><code>{end_date}</code> - Tanggal selesai</li>
                            <li><code>{total_days}</code> - Total hari</li>
                            <li><code>{approved_by}</code> - Disetujui oleh</li>
                            <li><code>{approved_at}</code> - Tanggal approval</li>
                        </ul>

                        <h6 class="small mt-2">Ditolak (ke Karyawan):</h6>
                        <ul class="small ps-3">
                            <li><code>{employee_name}</code> - Nama karyawan</li>
                            <li><code>{leave_type}</code> - Jenis</li>
                            <li><code>{start_date}</code> - Tanggal mulai</li>
                            <li><code>{end_date}</code> - Tanggal selesai</li>
                            <li><code>{total_days}</code> - Total hari</li>
                            <li><code>{rejection_reason}</code> - Alasan ditolak</li>
                            <li><code>{approved_by}</code> - Ditolak oleh</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Message Modal -->
    <div class="modal fade" id="testMessageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kirim Pesan Test</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="test_phone">Nomor WhatsApp</label>
                        <input type="text" class="form-control" id="test_phone" placeholder="628123456789">
                        <div class="form-text">Format: 628xxx (tanpa + atau spasi)</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="test_message">Pesan</label>
                        <textarea class="form-control" id="test_message" rows="4" placeholder="Test message dari Sistem Absensi"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="sendTestMessage()">
                        <i class='bx bx-send'></i> Kirim
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        // Handle config form submission
        $('#configForm').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const originalHtml = submitBtn.html();

            submitBtn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm"></span> Menyimpan...');

            $.ajax({
                url: '/api/settings/whatsapp',
                method: 'POST',
                data: form.serialize(),
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                success: function(response) {
                    toastr.success(response.message || 'Pengaturan WhatsApp berhasil disimpan');
                    setTimeout(() => location.reload(), 1500);
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).html(originalHtml);

                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        let errorMsg = 'Validasi gagal:\n';
                        Object.keys(errors).forEach(key => {
                            errorMsg += errors[key][0] + '\n';
                            $(`#${key}`).addClass('is-invalid');
                        });
                        toastr.error(errorMsg);
                    } else {
                        toastr.error(xhr.responseJSON?.message || 'Gagal menyimpan pengaturan');
                    }
                }
            });
        });

        // Handle template form submission
        $('#templateForm').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const originalHtml = submitBtn.html();

            submitBtn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm"></span> Menyimpan...');

            $.ajax({
                url: '/api/settings/whatsapp',
                method: 'POST',
                data: form.serialize(),
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                success: function(response) {
                    toastr.success(response.message || 'Template berhasil disimpan');
                    submitBtn.prop('disabled', false).html(originalHtml);
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).html(originalHtml);

                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        let errorMsg = 'Validasi gagal:\n';
                        Object.keys(errors).forEach(key => {
                            errorMsg += errors[key][0] + '\n';
                            $(`#${key}`).addClass('is-invalid');
                        });
                        toastr.error(errorMsg);
                    } else {
                        toastr.error(xhr.responseJSON?.message || 'Gagal menyimpan template');
                    }
                }
            });
        });

        // Test connection
        function testConnection() {
            const btn = event.target.closest('button');
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Testing...';

            fetch('/api/settings/whatsapp/test-connection', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Koneksi Berhasil!',
                            text: data.message,
                            timer: 3000
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Koneksi Gagal',
                            text: data.message
                        });
                    }
                })
                .catch(error => {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Terjadi kesalahan saat test koneksi'
                    });
                });
        }

        // Show test message modal
        function showTestMessageModal() {
            const modal = new bootstrap.Modal(document.getElementById('testMessageModal'));
            modal.show();
        }

        // Send test message
        function sendTestMessage() {
            const phone = document.getElementById('test_phone').value;
            const message = document.getElementById('test_message').value;

            if (!phone || !message) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Perhatian',
                    text: 'Mohon isi nomor WhatsApp dan pesan'
                });
                return;
            }

            Swal.fire({
                title: 'Mengirim pesan...',
                text: 'Mohon tunggu',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('/api/settings/whatsapp/send-test', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        phone: phone,
                        message: message
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: data.message,
                            timer: 3000
                        });
                        bootstrap.Modal.getInstance(document.getElementById('testMessageModal')).hide();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: data.message
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Terjadi kesalahan saat mengirim pesan'
                    });
                });
        }

        // Reset templates
        function resetTemplates() {
            Swal.fire({
                title: 'Reset Template?',
                text: 'Template akan dikembalikan ke default',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Reset',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Mereset template...',
                        text: 'Mohon tunggu',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: '/api/settings/whatsapp/reset-templates',
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message || 'Template berhasil direset',
                                timer: 2000
                            }).then(() => {
                                location.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: xhr.responseJSON?.message || 'Gagal reset template'
                            });
                        }
                    });
                }
            });
        }
    </script>
@endpush
