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

                            <!-- Provider -->
                            <div class="mb-3">
                                <label class="form-label" for="provider">Provider</label>
                                <select class="form-select" id="provider" name="provider" onchange="toggleProviderFields()">
                                    <option value="fonnte" {{ ($setting->provider ?? 'fonnte') === 'fonnte' ? 'selected' : '' }}>Fonnte</option>
                                    <option value="kirimdev" {{ ($setting->provider ?? 'fonnte') === 'kirimdev' ? 'selected' : '' }}>Kirim.dev</option>
                                </select>
                            </div>

                            <!-- API Key -->
                            <div class="mb-3">
                                <label class="form-label" for="api_key">API Key (Default)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control @error('api_key') is-invalid @enderror"
                                        id="api_key" name="api_key" value="{{ old('api_key', $setting->api_key) }}"
                                        placeholder="Masukkan API Key provider">
                                    <button class="btn btn-outline-secondary" type="button" onclick="testConnection()">
                                        <i class='bx bx-test-tube'></i> Test
                                    </button>
                                </div>
                                <div class="form-text" id="apiKeyHint">
                                    Fonnte: Ambil API key dari <a href="https://fonnte.com/dashboard" target="_blank">Fonnte Dashboard</a>.
                                </div>
                                @error('api_key')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Kirimdev Phone Number ID -->
                            <div class="mb-3" id="kirimPhoneIdGroup" style="display: none;">
                                <label class="form-label" for="kirim_phone_number_id">Kirim.dev Phone Number ID</label>
                                <input type="text" class="form-control @error('kirim_phone_number_id') is-invalid @enderror"
                                    id="kirim_phone_number_id" name="kirim_phone_number_id"
                                    value="{{ old('kirim_phone_number_id', $setting->kirim_phone_number_id) }}"
                                    placeholder="contoh: 106540352242922">
                                <div class="form-text">
                                    Dapatkan dari Kirim.dev dashboard atau endpoint <code>/v1/accounts</code>.
                                </div>
                                @error('kirim_phone_number_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Kirimdev Fallback Template (24h Window Bypass) -->
                            <div class="mb-3" id="kirimFallbackGroup" style="display: none;">
                                <div class="card border-warning">
                                    <div class="card-body py-3">
                                        <h6 class="card-title mb-1">
                                            <i class='bx bx-time-five text-warning me-1'></i>
                                            Template Fallback (Bypass 24 Jam Window)
                                        </h6>
                                        <p class="text-muted small mb-3">
                                            WhatsApp Business API (Meta) membatasi pengiriman pesan bebas hanya kepada penerima yang
                                            membalas dalam <strong>24 jam terakhir</strong>. Jika pesan gagal karena batas ini,
                                            sistem akan otomatis mengirim ulang menggunakan template Meta yang sudah disetujui.
                                        </p>
                                        <div class="row g-2">
                                            <div class="col-md-8">
                                                <label class="form-label" for="kirim_fallback_template_name">
                                                    Nama Template Fallback
                                                </label>
                                                <input type="text"
                                                    class="form-control @error('kirim_fallback_template_name') is-invalid @enderror"
                                                    id="kirim_fallback_template_name"
                                                    name="kirim_fallback_template_name"
                                                    value="{{ old('kirim_fallback_template_name', $setting->kirim_fallback_template_name) }}"
                                                    placeholder="contoh: notifikasi_sistem">
                                                <div class="form-text">
                                                    Nama template yang sudah disetujui Meta di akun KirimDev Anda.
                                                    Template harus memiliki variabel <code>{{1}}</code> di body agar isi pesan ikut terkirim.
                                                    Kosongkan jika tidak ingin menggunakan fallback otomatis.
                                                </div>
                                                @error('kirim_fallback_template_name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="kirim_fallback_template_language">
                                                    Kode Bahasa
                                                </label>
                                                <input type="text"
                                                    class="form-control @error('kirim_fallback_template_language') is-invalid @enderror"
                                                    id="kirim_fallback_template_language"
                                                    name="kirim_fallback_template_language"
                                                    value="{{ old('kirim_fallback_template_language', $setting->kirim_fallback_template_language ?? 'id') }}"
                                                    placeholder="id"
                                                    maxlength="20">
                                                <div class="form-text">
                                                    <code>id</code> = Indonesia, <code>en</code> = English
                                                </div>
                                                @error('kirim_fallback_template_language')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        @if (!empty($setting->kirim_fallback_template_name))
                                            <div class="alert alert-success alert-sm p-2 mt-2 mb-0">
                                                <small>
                                                    <i class='bx bx-check-circle me-1'></i>
                                                    <strong>Fallback aktif:</strong>
                                                    Template <code>{{ $setting->kirim_fallback_template_name }}</code>
                                                    ({{ $setting->kirim_fallback_template_language ?? 'id' }})
                                                    akan otomatis digunakan saat pesan gagal karena 24h window.
                                                </small>
                                            </div>
                                        @else
                                            <div class="alert alert-secondary alert-sm p-2 mt-2 mb-0">
                                                <small>
                                                    <i class='bx bx-info-circle me-1'></i>
                                                    Fallback belum dikonfigurasi. Pesan yang gagal karena 24h window tidak akan dikirim ulang otomatis.
                                                </small>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Kirimdev Alpha Notification Template -->
                            <div class="mb-3" id="kirimAlphaTemplateGroup" style="display: none;">
                                <div class="card border-danger">
                                    <div class="card-body py-3">
                                        <h6 class="card-title mb-1">
                                            <i class='bx bx-user-x text-danger me-1'></i>
                                            Template Notifikasi Alpha (Karyawan)
                                        </h6>
                                        <p class="text-muted small mb-3">
                                            Jika diisi, notifikasi alpha akan dikirim <strong>langsung via template Meta</strong>
                                            — bypass 24 jam window sepenuhnya. Template harus sudah disetujui Meta dan memiliki
                                            <strong>5 variabel</strong>: <code>{{1}}</code> Nama, <code>{{2}}</code> NIP,
                                            <code>{{3}}</code> Departemen, <code>{{4}}</code> Tanggal, <code>{{5}}</code> Total Alpha.
                                        </p>

                                        @php
                                            $alphaTemplateId = 'tmpl_2BGZQ77GKB9YKC2ZVWK0346AP1';
                                        @endphp

                                        <div class="alert alert-info p-2 mb-3">
                                            <small>
                                                <i class='bx bx-info-circle me-1'></i>
                                                <strong>Template sudah dibuat otomatis:</strong>
                                                <code>alpha_notification</code> (ID: <code>{{ $alphaTemplateId }}</code>) —
                                                status <span class="badge bg-warning text-dark">pending review Meta</span>.
                                                Setelah disetujui, isi kolom di bawah dan simpan.
                                            </small>
                                        </div>

                                        <div class="row g-2">
                                            <div class="col-md-8">
                                                <label class="form-label" for="kirim_alpha_template_name">
                                                    Nama Template Alpha
                                                </label>
                                                <input type="text"
                                                    class="form-control @error('kirim_alpha_template_name') is-invalid @enderror"
                                                    id="kirim_alpha_template_name"
                                                    name="kirim_alpha_template_name"
                                                    value="{{ old('kirim_alpha_template_name', $setting->kirim_alpha_template_name) }}"
                                                    placeholder="alpha_notification">
                                                <div class="form-text">
                                                    Isi setelah template disetujui Meta.
                                                </div>
                                                @error('kirim_alpha_template_name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="kirim_alpha_template_language">
                                                    Kode Bahasa
                                                </label>
                                                <input type="text"
                                                    class="form-control @error('kirim_alpha_template_language') is-invalid @enderror"
                                                    id="kirim_alpha_template_language"
                                                    name="kirim_alpha_template_language"
                                                    value="{{ old('kirim_alpha_template_language', $setting->kirim_alpha_template_language ?? 'id') }}"
                                                    placeholder="id"
                                                    maxlength="20">
                                                <div class="form-text">
                                                    <code>id</code> = Indonesia
                                                </div>
                                                @error('kirim_alpha_template_language')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        @if (!empty($setting->kirim_alpha_template_name))
                                            <div class="alert alert-success alert-sm p-2 mt-2 mb-0">
                                                <small>
                                                    <i class='bx bx-check-circle me-1'></i>
                                                    <strong>Aktif:</strong>
                                                    Notifikasi alpha akan langsung dikirim via template Meta
                                                    <code>{{ $setting->kirim_alpha_template_name }}</code>
                                                    dengan data karyawan lengkap — tidak terpengaruh 24h window.
                                                </small>
                                            </div>
                                        @else
                                            <div class="alert alert-secondary alert-sm p-2 mt-2 mb-0">
                                                <small>
                                                    <i class='bx bx-info-circle me-1'></i>
                                                    Belum dikonfigurasi. Notifikasi alpha menggunakan pesan teks biasa (terkena 24h window).
                                                </small>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Sender Number -->
                            <div class="mb-3">
                                <label class="form-label" for="sender">Nomor Pengirim Default (Optional)</label>
                                <input type="text" class="form-control @error('sender') is-invalid @enderror"
                                    id="sender" name="sender" value="{{ old('sender', $setting->sender) }}"
                                    placeholder="628123456789">
                                <div class="form-text">Format: 628xxx (optional, untuk tracking purposes)</div>
                                @error('sender')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <hr class="my-4">

                            <!-- Custom API Keys & Senders -->
                            <div class="mb-3">
                                <h6 class="mb-3">
                                    <i class='bx bx-key'></i> Custom API Keys & Nomor Pengirim (Optional)
                                    <button type="button" class="btn btn-sm btn-link" onclick="toggleCustomKeys()">
                                        <span id="toggleCustomKeysText">Tampilkan</span>
                                    </button>
                                </h6>
                                <div class="form-text mb-3">
                                    Anda dapat menggunakan API key dan nomor pengirim yang berbeda untuk setiap jenis notifikasi.
                                    Jika tidak diisi, akan menggunakan API key dan nomor pengirim default di atas.
                                </div>

                                <div id="customKeysSection" style="display: none;">
                                    <!-- Check-in -->
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6 class="card-title">Notifikasi Check-in</h6>
                                            <div class="row">
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label" for="checkin_api_key">API Key</label>
                                                    <input type="text" class="form-control" id="checkin_api_key" name="checkin_api_key"
                                                        value="{{ old('checkin_api_key', $setting->checkin_api_key) }}"
                                                        placeholder="Kosongkan untuk gunakan default">
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label" for="checkin_sender">Nomor Pengirim</label>
                                                    <input type="text" class="form-control" id="checkin_sender" name="checkin_sender"
                                                        value="{{ old('checkin_sender', $setting->checkin_sender) }}"
                                                        placeholder="628xxx (kosongkan untuk default)">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Check-out -->
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6 class="card-title">Notifikasi Check-out</h6>
                                            <div class="row">
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label" for="checkout_api_key">API Key</label>
                                                    <input type="text" class="form-control" id="checkout_api_key" name="checkout_api_key"
                                                        value="{{ old('checkout_api_key', $setting->checkout_api_key) }}"
                                                        placeholder="Kosongkan untuk gunakan default">
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label" for="checkout_sender">Nomor Pengirim</label>
                                                    <input type="text" class="form-control" id="checkout_sender" name="checkout_sender"
                                                        value="{{ old('checkout_sender', $setting->checkout_sender) }}"
                                                        placeholder="628xxx (kosongkan untuk default)">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Leave -->
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6 class="card-title">Notifikasi Cuti/Izin</h6>
                                            <div class="row">
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label" for="leave_api_key">API Key</label>
                                                    <input type="text" class="form-control" id="leave_api_key" name="leave_api_key"
                                                        value="{{ old('leave_api_key', $setting->leave_api_key) }}"
                                                        placeholder="Kosongkan untuk gunakan default">
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label" for="leave_sender">Nomor Pengirim</label>
                                                    <input type="text" class="form-control" id="leave_sender" name="leave_sender"
                                                        value="{{ old('leave_sender', $setting->leave_sender) }}"
                                                        placeholder="628xxx (kosongkan untuk default)">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Warning Letter -->
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6 class="card-title">Notifikasi Surat Peringatan (SP)</h6>
                                            <div class="row">
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label" for="warning_letter_api_key">API Key</label>
                                                    <input type="text" class="form-control" id="warning_letter_api_key" name="warning_letter_api_key"
                                                        value="{{ old('warning_letter_api_key', $setting->warning_letter_api_key) }}"
                                                        placeholder="Kosongkan untuk gunakan default">
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label" for="warning_letter_sender">Nomor Pengirim</label>
                                                    <input type="text" class="form-control" id="warning_letter_sender" name="warning_letter_sender"
                                                        value="{{ old('warning_letter_sender', $setting->warning_letter_sender) }}"
                                                        placeholder="628xxx (kosongkan untuk default)">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Payroll -->
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6 class="card-title">Notifikasi Payroll</h6>
                                            <div class="row">
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label" for="payroll_api_key">API Key</label>
                                                    <input type="text" class="form-control" id="payroll_api_key" name="payroll_api_key"
                                                        value="{{ old('payroll_api_key', $setting->payroll_api_key) }}"
                                                        placeholder="Kosongkan untuk gunakan default">
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label" for="payroll_sender">Nomor Pengirim</label>
                                                    <input type="text" class="form-control" id="payroll_sender" name="payroll_sender"
                                                        value="{{ old('payroll_sender', $setting->payroll_sender) }}"
                                                        placeholder="628xxx (kosongkan untuk default)">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Alpha -->
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6 class="card-title">Notifikasi Alpha</h6>
                                            <div class="row">
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label" for="alpha_api_key">API Key</label>
                                                    <input type="text" class="form-control" id="alpha_api_key" name="alpha_api_key"
                                                        value="{{ old('alpha_api_key', $setting->alpha_api_key) }}"
                                                        placeholder="Kosongkan untuk gunakan default">
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label" for="alpha_sender">Nomor Pengirim</label>
                                                    <input type="text" class="form-control" id="alpha_sender" name="alpha_sender"
                                                        value="{{ old('alpha_sender', $setting->alpha_sender) }}"
                                                        placeholder="628xxx (kosongkan untuk default)">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Interview -->
                                    <div class="card mb-3 border-primary">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <i class='bx bx-user-voice me-1 text-primary'></i>
                                                Notifikasi Interview Kandidat
                                            </h6>
                                            <div class="row">
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label" for="interview_api_key">API Key</label>
                                                    <input type="text" class="form-control" id="interview_api_key" name="interview_api_key"
                                                        value="{{ old('interview_api_key', $setting->interview_api_key) }}"
                                                        placeholder="Kosongkan untuk gunakan default">
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label" for="interview_sender">Nomor Pengirim</label>
                                                    <input type="text" class="form-control" id="interview_sender" name="interview_sender"
                                                        value="{{ old('interview_sender', $setting->interview_sender) }}"
                                                        placeholder="628xxx (kosongkan untuk default)">
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <i class='bx bx-info-circle'></i>
                                                Digunakan saat mengirim undangan interview & QR Code ke kandidat
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Join Call -->
                                    <div class="card mb-3 border-success">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <i class='bx bx-user-check me-1 text-success'></i>
                                                Notifikasi Panggilan Join
                                            </h6>
                                            <div class="row">
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label" for="join_call_api_key">API Key</label>
                                                    <input type="text" class="form-control" id="join_call_api_key" name="join_call_api_key"
                                                        value="{{ old('join_call_api_key', $setting->join_call_api_key) }}"
                                                        placeholder="Kosongkan untuk gunakan default">
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label" for="join_call_sender">Nomor Pengirim</label>
                                                    <input type="text" class="form-control" id="join_call_sender" name="join_call_sender"
                                                        value="{{ old('join_call_sender', $setting->join_call_sender) }}"
                                                        placeholder="628xxx (kosongkan untuk default)">
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <i class='bx bx-info-circle'></i>
                                                Digunakan saat mengirim undangan bergabung & QR Code check-in ke kandidat yang diterima
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Welcome -->
                                    <div class="card mb-3 border-info">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <i class='bx bx-user-plus me-1 text-info'></i>
                                                Notifikasi Karyawan Baru
                                            </h6>
                                            <div class="row">
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label" for="welcome_api_key">API Key</label>
                                                    <input type="text" class="form-control" id="welcome_api_key" name="welcome_api_key"
                                                        value="{{ old('welcome_api_key', $setting->welcome_api_key) }}"
                                                        placeholder="Kosongkan untuk gunakan default">
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <label class="form-label" for="welcome_sender">Nomor Pengirim</label>
                                                    <input type="text" class="form-control" id="welcome_sender" name="welcome_sender"
                                                        value="{{ old('welcome_sender', $setting->welcome_sender) }}"
                                                        placeholder="628xxx (kosongkan untuk default)">
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <i class='bx bx-info-circle'></i>
                                                Digunakan saat mengirim notifikasi selamat datang ke karyawan baru yang terdaftar
                                            </small>
                                        </div>
                                    </div>

                                </div>
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

                            <!-- SP Number Format Settings -->
                            <div class="mb-3">
                                <h6 class="mb-3">
                                    <i class='bx bx-file-blank'></i> Format Nomor Surat Peringatan (SP)
                                </h6>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="sp_number_format">Format Nomor Surat</label>
                                        <input type="text" class="form-control @error('sp_number_format') is-invalid @enderror"
                                            id="sp_number_format" name="sp_number_format"
                                            value="{{ old('sp_number_format', $setting->sp_number_format ?? '{sp_type}/{dept}/{counter}/{year}') }}"
                                            placeholder="{sp_type}/{dept}/{counter}/{year}">
                                        <div class="form-text">
                                            <strong>Variabel:</strong> {sp_type}, {dept}, {counter}, {year}, {month}
                                        </div>
                                        @error('sp_number_format')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label class="form-label" for="sp_department_code">Kode Departemen</label>
                                        <input type="text" class="form-control @error('sp_department_code') is-invalid @enderror"
                                            id="sp_department_code" name="sp_department_code"
                                            value="{{ old('sp_department_code', $setting->sp_department_code ?? 'HR') }}"
                                            placeholder="HR" maxlength="10">
                                        <div class="form-text">Digunakan di variabel {dept}</div>
                                        @error('sp_department_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label class="form-label" for="sp_counter_width">Digit Counter</label>
                                        <select class="form-select @error('sp_counter_width') is-invalid @enderror"
                                            id="sp_counter_width" name="sp_counter_width">
                                            <option value="3" {{ (old('sp_counter_width', $setting->sp_counter_width ?? 3) == 3) ? 'selected' : '' }}>3 digit (001)</option>
                                            <option value="4" {{ (old('sp_counter_width', $setting->sp_counter_width ?? 3) == 4) ? 'selected' : '' }}>4 digit (0001)</option>
                                            <option value="5" {{ (old('sp_counter_width', $setting->sp_counter_width ?? 3) == 5) ? 'selected' : '' }}>5 digit (00001)</option>
                                        </select>
                                        <div class="form-text">Jumlah digit counter</div>
                                        @error('sp_counter_width')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="alert alert-info p-2 mb-0">
                                    <small>
                                        <strong>Contoh hasil:</strong>
                                        <ul class="mb-0 ps-3">
                                            <li><code>{sp_type}/{dept}/{counter}/{year}</code> → SP1/HR/001/2026</li>
                                            <li><code>{counter}/{sp_type}/{dept}/{month}/{year}</code> → 001/SP1/HR/03/2026</li>
                                            <li><code>{sp_type}-{counter}-{dept}-{year}</code> → SP1-001-HR-2026</li>
                                        </ul>
                                    </small>
                                </div>
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

                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="notify_leave_rejected"
                                        name="notify_leave_rejected"
                                        {{ $setting->notify_leave_rejected ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notify_leave_rejected">
                                        Kirim ke karyawan saat cuti ditolak
                                    </label>
                                </div>

                                <h6 class="mt-3 mb-2">Notifikasi Surat Peringatan (SP)</h6>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="notify_warning_letter"
                                        name="notify_warning_letter"
                                        {{ $setting->notify_warning_letter ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notify_warning_letter">
                                        Kirim notifikasi saat SP diterbitkan
                                    </label>
                                </div>

                                <h6 class="mt-3 mb-2">Notifikasi Payroll</h6>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="notify_payroll"
                                        name="notify_payroll"
                                        {{ $setting->notify_payroll ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notify_payroll">
                                        Kirim notifikasi slip gaji
                                    </label>
                                </div>

                                <h6 class="mt-3 mb-2">Notifikasi Alpha</h6>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="notify_alpha"
                                        name="notify_alpha"
                                        {{ $setting->notify_alpha ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notify_alpha">
                                        Kirim notifikasi saat karyawan Alpha
                                    </label>
                                </div>
                                <div class="ms-4">
                                    <small class="text-muted">
                                        <i class='bx bx-info-circle'></i> Karyawan akan menerima pesan WhatsApp otomatis saat tercatat Alpha,
                                        sehingga dapat segera klarifikasi ke HRD sebelum tutup buku penggajian.
                                    </small>
                                </div>

                                <h6 class="mt-3 mb-2">Notifikasi Karyawan Baru</h6>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="notify_welcome"
                                        name="notify_welcome"
                                        {{ $setting->notify_welcome ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notify_welcome">
                                        Kirim notifikasi selamat datang ke karyawan baru
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
                            <input type="hidden" name="provider" value="{{ $setting->provider ?? 'fonnte' }}">
                            <input type="hidden" name="kirim_phone_number_id" value="{{ $setting->kirim_phone_number_id }}">

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

                            @if ($setting->notify_warning_letter)
                                <input type="hidden" name="notify_warning_letter" value="1">
                            @endif

                            @if ($setting->notify_payroll)
                                <input type="hidden" name="notify_payroll" value="1">
                            @endif

                            @if ($setting->notify_alpha)
                                <input type="hidden" name="notify_alpha" value="1">
                            @endif

                            @if ($setting->notify_welcome)
                                <input type="hidden" name="notify_welcome" value="1">
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

                            <h5 class="mb-3 mt-4">Template Surat Peringatan (SP)</h5>

                            <!-- Warning Letter Template -->
                            <div class="mb-4">
                                <label class="form-label" for="warning_letter_template">Template Surat Peringatan (ke Karyawan)</label>
                                <textarea class="form-control @error('warning_letter_template') is-invalid @enderror" id="warning_letter_template"
                                    name="warning_letter_template" rows="8">{{ old('warning_letter_template', $setting->warning_letter_template ?? \App\Models\WhatsAppSetting::getDefaultWarningLetterTemplate()) }}</textarea>
                                <div class="form-text">
                                    <strong>Variabel tersedia:</strong> {employee_name}, {sp_type}, {sp_number}, {violation}, {issue_date}, {effective_date}, {issued_by}
                                </div>
                                @error('warning_letter_template')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <h5 class="mb-3 mt-4">Template Payroll</h5>

                            <!-- Payroll Template -->
                            <div class="mb-4">
                                <label class="form-label" for="payroll_template">Template Slip Gaji (ke Karyawan)</label>
                                <textarea class="form-control @error('payroll_template') is-invalid @enderror" id="payroll_template"
                                    name="payroll_template" rows="7">{{ old('payroll_template', $setting->payroll_template ?? \App\Models\WhatsAppSetting::getDefaultPayrollTemplate()) }}</textarea>
                                <div class="form-text">
                                    <strong>Variabel tersedia:</strong> {employee_name}, {month}, {year}, {total_salary}, {payment_date}
                                </div>
                                @error('payroll_template')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <h5 class="mb-3 mt-4">Template Notifikasi Alpha</h5>

                            <!-- Alpha Template -->
                            <div class="mb-4">
                                <label class="form-label" for="alpha_template">Template Notifikasi Alpha (ke Karyawan)</label>
                                <textarea class="form-control @error('alpha_template') is-invalid @enderror" id="alpha_template"
                                    name="alpha_template" rows="8">{{ old('alpha_template', $setting->alpha_template ?? \App\Models\WhatsAppSetting::getDefaultAlphaTemplate()) }}</textarea>
                                <div class="form-text">
                                    <strong>Variabel tersedia:</strong> {employee_name}, {employee_code}, {department}, {date}, {total_alpha}
                                </div>
                                @error('alpha_template')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <h5 class="mb-3 mt-4">Template Karyawan Baru</h5>

                            <!-- Welcome Template -->
                            <div class="mb-4">
                                <label class="form-label" for="welcome_template">Template Selamat Datang (ke Karyawan Baru)</label>
                                <textarea class="form-control @error('welcome_template') is-invalid @enderror" id="welcome_template"
                                    name="welcome_template" rows="8">{{ old('welcome_template', $setting->welcome_template ?? \App\Models\WhatsAppSetting::getDefaultWelcomeTemplate()) }}</textarea>
                                <div class="form-text">
                                    <strong>Variabel tersedia:</strong> {employee_name}, {employee_code}, {email}, {password}
                                </div>
                                @error('welcome_template')
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
                            <strong class="float-end">{{ strtoupper($setting->provider ?? 'fonnte') }}</strong>
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

                <!-- Kirim.dev Template Manager Card -->
                <div class="card mb-4" id="kirimTemplateManagerCard" style="display: none;">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class='bx bx-layout'></i> Template Kirim.dev
                        </h5>

                        <div class="mb-2">
                            <label class="form-label" for="kirim_template_name">Nama Template</label>
                            <input type="text" class="form-control" id="kirim_template_name" placeholder="contoh: interview_reminder_v2">
                            <small class="text-muted">Gunakan huruf kecil, angka, dan underscore.</small>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-2">
                                <label class="form-label" for="kirim_template_category">Kategori</label>
                                <select class="form-select" id="kirim_template_category">
                                    <option value="UTILITY">UTILITY</option>
                                    <option value="MARKETING">MARKETING</option>
                                    <option value="AUTHENTICATION">AUTHENTICATION</option>
                                </select>
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label" for="kirim_template_language">Bahasa</label>
                                <input type="text" class="form-control" id="kirim_template_language" value="id" placeholder="id">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label" for="kirim_template_body">Body Text</label>
                            <textarea class="form-control" id="kirim_template_body" rows="3" placeholder="Halo {{1}}, interview Anda pada {{2}}."></textarea>
                            <small class="text-muted">Untuk mode cepat body-only. Jika perlu header/button, gunakan endpoint advanced via API.</small>
                        </div>

                        <div id="kirimAuthOptions" class="border rounded p-2 mb-2" style="display: none;">
                            <h6 class="mb-2">Opsi AUTHENTICATION (OTP)</h6>
                            <div class="row">
                                <div class="col-6 mb-2">
                                    <label class="form-label" for="auth_message_ttl_seconds">TTL OTP (detik)</label>
                                    <input type="number" class="form-control" id="auth_message_ttl_seconds" min="30" max="900" value="600">
                                </div>
                                <div class="col-6 mb-2">
                                    <label class="form-label" for="auth_code_expiration_minutes">Expired Footer (menit)</label>
                                    <input type="number" class="form-control" id="auth_code_expiration_minutes" min="1" max="90" value="5">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-2">
                                    <label class="form-label" for="auth_otp_type">OTP Button Type</label>
                                    <select class="form-select" id="auth_otp_type">
                                        <option value="COPY_CODE" selected>COPY_CODE</option>
                                        <option value="ONE_TAP">ONE_TAP</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-2 d-flex align-items-end">
                                    <div class="form-check me-3">
                                        <input class="form-check-input" type="checkbox" id="auth_add_security_recommendation" checked>
                                        <label class="form-check-label" for="auth_add_security_recommendation">Security recommendation</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="auth_include_footer" checked>
                                    <label class="form-check-label" for="auth_include_footer">Sertakan footer expiry</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="auth_include_copy_button" checked>
                                    <label class="form-check-label" for="auth_include_copy_button">Sertakan tombol OTP</label>
                                </div>
                            </div>

                            <small class="text-muted d-block mt-2">Untuk AUTHENTICATION, body text Meta bersifat fixed. Field body di atas tidak dipakai saat create AUTH.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="kirim_template_examples">Contoh Variabel</label>
                            <input type="text" class="form-control" id="kirim_template_examples" placeholder="Budi, Senin 10:00">
                            <small class="text-muted">Pisahkan dengan koma sesuai urutan placeholder {{1}}, {{2}}, dst.</small>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="applyInterviewTemplatePreset()">
                                <i class='bx bx-user-voice'></i> Preset Interview
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="applyJoinCallTemplatePreset()">
                                <i class='bx bx-phone-call'></i> Preset Join Call
                            </button>
                            <button type="button" class="btn btn-outline-dark btn-sm" onclick="applyAuthTemplatePreset()">
                                <i class='bx bx-shield-quarter'></i> Preset AUTH OTP
                            </button>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button type="button" class="btn btn-primary btn-sm" id="btnCreateKirimTemplate" onclick="createKirimTemplate()">
                                <i class='bx bx-plus'></i> Buat Template
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btnCheckKirimTemplate" onclick="checkKirimTemplateStatus()">
                                <i class='bx bx-search'></i> Cek Status Nama
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-sm" id="btnSyncKirimTemplate" onclick="syncKirimTemplates()">
                                <i class='bx bx-refresh'></i> Sync Status
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnLoadKirimTemplates" onclick="loadKirimTemplates()">
                                <i class='bx bx-list-ul'></i> Muat Daftar
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Kategori</th>
                                        <th>Bahasa</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="kirimTemplateListBody">
                                    <tr>
                                        <td colspan="4" class="text-muted">Belum ada data template dimuat.</td>
                                    </tr>
                                </tbody>
                            </table>
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

                        <h6 class="small fw-bold mt-3">Surat Peringatan (SP)</h6>
                        <ul class="small ps-3">
                            <li><code>{employee_name}</code> - Nama karyawan</li>
                            <li><code>{sp_type}</code> - Jenis SP (ST/SP1/SP2/SP3)</li>
                            <li><code>{sp_number}</code> - Nomor surat</li>
                            <li><code>{violation}</code> - Pelanggaran</li>
                            <li><code>{issue_date}</code> - Tanggal terbit</li>
                            <li><code>{effective_date}</code> - Tanggal berlaku</li>
                            <li><code>{issued_by}</code> - Diterbitkan oleh</li>
                        </ul>

                        <h6 class="small fw-bold mt-3">Payroll</h6>
                        <ul class="small ps-3">
                            <li><code>{employee_name}</code> - Nama karyawan</li>
                            <li><code>{month}</code> - Bulan</li>
                            <li><code>{year}</code> - Tahun</li>
                            <li><code>{total_salary}</code> - Total gaji</li>
                            <li><code>{payment_date}</code> - Tanggal bayar</li>
                        </ul>

                        <h6 class="small fw-bold mt-3">Notifikasi Alpha</h6>
                        <ul class="small ps-3">
                            <li><code>{employee_name}</code> - Nama karyawan</li>
                            <li><code>{employee_code}</code> - NIP / Kode karyawan</li>
                            <li><code>{department}</code> - Departemen</li>
                            <li><code>{date}</code> - Tanggal alpha</li>
                            <li><code>{total_alpha}</code> - Total alpha bulan ini</li>
                        </ul>

                        <h6 class="small fw-bold mt-3">Karyawan Baru (Welcome)</h6>
                        <ul class="small ps-3">
                            <li><code>{employee_name}</code> - Nama karyawan</li>
                            <li><code>{employee_code}</code> - NIP / Kode karyawan</li>
                            <li><code>{email}</code> - Email karyawan</li>
                            <li><code>{password}</code> - Password default sementara</li>
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

        function getKirimTemplateStatusBadge(status) {
            const normalized = String(status || '').toLowerCase();
            if (normalized.includes('approved')) {
                return '<span class="badge bg-success">' + (status || 'approved') + '</span>';
            }
            if (normalized.includes('reject')) {
                return '<span class="badge bg-danger">' + (status || 'rejected') + '</span>';
            }
            if (normalized.includes('pending')) {
                return '<span class="badge bg-warning">' + (status || 'pending') + '</span>';
            }
            return '<span class="badge bg-secondary">' + (status || '-') + '</span>';
        }

        function normalizeKirimTemplates(rawData) {
            if (Array.isArray(rawData)) {
                return rawData;
            }
            if (rawData && Array.isArray(rawData.items)) {
                return rawData.items;
            }
            if (rawData && Array.isArray(rawData.templates)) {
                return rawData.templates;
            }
            return [];
        }

        function toggleKirimTemplateCategoryFields() {
            const category = document.getElementById('kirim_template_category')?.value || 'UTILITY';
            const authOptions = document.getElementById('kirimAuthOptions');
            const bodyInput = document.getElementById('kirim_template_body');
            const exampleInput = document.getElementById('kirim_template_examples');

            if (category === 'AUTHENTICATION') {
                if (authOptions) authOptions.style.display = 'block';
                if (bodyInput) {
                    bodyInput.value = '';
                    bodyInput.disabled = true;
                    bodyInput.placeholder = 'Tidak digunakan untuk AUTHENTICATION';
                }
                if (exampleInput) {
                    exampleInput.value = '';
                    exampleInput.disabled = true;
                    exampleInput.placeholder = 'Tidak digunakan untuk AUTHENTICATION';
                }
            } else {
                if (authOptions) authOptions.style.display = 'none';
                if (bodyInput) {
                    bodyInput.disabled = false;
                    bodyInput.placeholder = 'Halo {{1}}, interview Anda pada {{2}}.';
                }
                if (exampleInput) {
                    exampleInput.disabled = false;
                    exampleInput.placeholder = 'Budi, Senin 10:00';
                }
            }
        }

        function applyInterviewTemplatePreset() {
            const now = new Date();
            const dateCode = now.getFullYear().toString()
                + String(now.getMonth() + 1).padStart(2, '0')
                + String(now.getDate()).padStart(2, '0');

            const nameEl = document.getElementById('kirim_template_name');
            const categoryEl = document.getElementById('kirim_template_category');
            const languageEl = document.getElementById('kirim_template_language');
            const bodyEl = document.getElementById('kirim_template_body');
            const exampleEl = document.getElementById('kirim_template_examples');

            if (nameEl) nameEl.value = 'interview_invitation_' + dateCode;
            if (categoryEl) categoryEl.value = 'UTILITY';
            if (languageEl) languageEl.value = 'id';

            toggleKirimTemplateCategoryFields();

            if (bodyEl) {
                bodyEl.value = 'Halo {{1}}, kami mengundang Anda untuk interview posisi {{2}} pada {{3}} pukul {{4}} di {{5}}. {{6}}';
            }
            if (exampleEl) {
                exampleEl.value = 'Budi, Staff Admin, Senin 29 Juni 2026, 10:00 WIB, Kantor PT Mingda, Mohon hadir 10 menit lebih awal';
            }
        }

        function applyJoinCallTemplatePreset() {
            const now = new Date();
            const dateCode = now.getFullYear().toString()
                + String(now.getMonth() + 1).padStart(2, '0')
                + String(now.getDate()).padStart(2, '0');

            const nameEl = document.getElementById('kirim_template_name');
            const categoryEl = document.getElementById('kirim_template_category');
            const languageEl = document.getElementById('kirim_template_language');
            const bodyEl = document.getElementById('kirim_template_body');
            const exampleEl = document.getElementById('kirim_template_examples');

            if (nameEl) nameEl.value = 'join_call_invitation_' + dateCode;
            if (categoryEl) categoryEl.value = 'UTILITY';
            if (languageEl) languageEl.value = 'id';

            toggleKirimTemplateCategoryFields();

            if (bodyEl) {
                bodyEl.value = 'Halo {{1}}, Anda dijadwalkan bergabung di departemen {{2}} pada {{3}} pukul {{4}} di {{5}}. {{6}}';
            }
            if (exampleEl) {
                exampleEl.value = 'Siti, Finance, Selasa 30 Juni 2026, 13:30 WIB, Kantor PT Mingda, Harap membawa dokumen asli';
            }
        }

        function applyAuthTemplatePreset() {
            const now = new Date();
            const dateCode = now.getFullYear().toString()
                + String(now.getMonth() + 1).padStart(2, '0')
                + String(now.getDate()).padStart(2, '0');

            const nameEl = document.getElementById('kirim_template_name');
            const categoryEl = document.getElementById('kirim_template_category');
            const languageEl = document.getElementById('kirim_template_language');
            const ttlEl = document.getElementById('auth_message_ttl_seconds');
            const expEl = document.getElementById('auth_code_expiration_minutes');
            const secEl = document.getElementById('auth_add_security_recommendation');
            const footerEl = document.getElementById('auth_include_footer');
            const btnEl = document.getElementById('auth_include_copy_button');
            const otpTypeEl = document.getElementById('auth_otp_type');

            if (nameEl) nameEl.value = 'candidate_verification_otp_' + dateCode;
            if (categoryEl) categoryEl.value = 'AUTHENTICATION';
            if (languageEl) languageEl.value = 'id';
            if (ttlEl) ttlEl.value = 600;
            if (expEl) expEl.value = 5;
            if (secEl) secEl.checked = true;
            if (footerEl) footerEl.checked = true;
            if (btnEl) btnEl.checked = true;
            if (otpTypeEl) otpTypeEl.value = 'COPY_CODE';

            toggleKirimTemplateCategoryFields();
        }

        function renderKirimTemplateList(rawData) {
            const rows = normalizeKirimTemplates(rawData);
            const tbody = document.getElementById('kirimTemplateListBody');
            if (!tbody) return;

            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-muted">Tidak ada template.</td></tr>';
                return;
            }

            tbody.innerHTML = rows.map(item => {
                const name = item.name || item.template_name || '-';
                const category = item.category || '-';
                const language = item.language || item.language_code || '-';
                const status = item.status || item.review_status || '-';
                return `
                    <tr>
                        <td>${name}</td>
                        <td>${category}</td>
                        <td>${language}</td>
                        <td>${getKirimTemplateStatusBadge(status)}</td>
                    </tr>
                `;
            }).join('');
        }

        async function loadKirimTemplates() {
            try {
                const response = await fetch('/api/settings/whatsapp/kirim/templates', {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Gagal mengambil daftar template');
                }

                renderKirimTemplateList(data.data);
                toastr.success(data.message || 'Daftar template berhasil dimuat');
            } catch (error) {
                toastr.error(error.message || 'Gagal mengambil daftar template');
            }
        }

        async function createKirimTemplate() {
            const btn = document.getElementById('btnCreateKirimTemplate');
            const original = btn ? btn.innerHTML : '';
            const name = (document.getElementById('kirim_template_name')?.value || '').trim();
            const category = document.getElementById('kirim_template_category')?.value || 'UTILITY';
            const language = (document.getElementById('kirim_template_language')?.value || 'id').trim();
            const bodyText = (document.getElementById('kirim_template_body')?.value || '').trim();
            const exampleRaw = (document.getElementById('kirim_template_examples')?.value || '').trim();
            const authMessageTtlSeconds = Number(document.getElementById('auth_message_ttl_seconds')?.value || 600);
            const authCodeExpirationMinutes = Number(document.getElementById('auth_code_expiration_minutes')?.value || 5);
            const authAddSecurityRecommendation = !!document.getElementById('auth_add_security_recommendation')?.checked;
            const authIncludeFooter = !!document.getElementById('auth_include_footer')?.checked;
            const authIncludeCopyButton = !!document.getElementById('auth_include_copy_button')?.checked;
            const authOtpType = document.getElementById('auth_otp_type')?.value || 'COPY_CODE';

            if (!name) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Perhatian',
                    text: 'Nama template wajib diisi.'
                });
                return;
            }

            if (category !== 'AUTHENTICATION' && !bodyText) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Perhatian',
                    text: 'Body text wajib diisi untuk kategori selain AUTHENTICATION.'
                });
                return;
            }

            const exampleValues = exampleRaw ? exampleRaw.split(',').map(v => v.trim()).filter(Boolean) : [];

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Membuat...';
            }

            try {
                const response = await fetch('/api/settings/whatsapp/kirim/templates', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        name,
                        category,
                        language,
                        body_text: bodyText,
                        example_values: exampleValues,
                        message_send_ttl_seconds: authMessageTtlSeconds,
                        auth_code_expiration_minutes: authCodeExpirationMinutes,
                        auth_add_security_recommendation: authAddSecurityRecommendation,
                        auth_include_footer: authIncludeFooter,
                        auth_include_copy_button: authIncludeCopyButton,
                        auth_otp_type: authOtpType
                    })
                });

                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Gagal membuat template');
                }

                toastr.success(data.message || 'Template berhasil dibuat');
                await loadKirimTemplates();
            } catch (error) {
                toastr.error(error.message || 'Gagal membuat template');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = original;
                }
            }
        }

        async function checkKirimTemplateStatus() {
            const btn = document.getElementById('btnCheckKirimTemplate');
            const original = btn ? btn.innerHTML : '';
            const name = (document.getElementById('kirim_template_name')?.value || '').trim();

            if (!name) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Perhatian',
                    text: 'Isi nama template terlebih dahulu.'
                });
                return;
            }

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Mengecek...';
            }

            try {
                const response = await fetch('/api/settings/whatsapp/kirim/templates/' + encodeURIComponent(name), {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Gagal mengambil status template');
                }

                renderKirimTemplateList([data.data || {}]);
                toastr.success(data.message || 'Status template berhasil diambil');
            } catch (error) {
                toastr.error(error.message || 'Gagal mengambil status template');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = original;
                }
            }
        }

        async function syncKirimTemplates() {
            const btn = document.getElementById('btnSyncKirimTemplate');
            const original = btn ? btn.innerHTML : '';

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sync...';
            }

            try {
                const response = await fetch('/api/settings/whatsapp/kirim/templates/sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({})
                });

                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Gagal sync template');
                }

                toastr.success(data.message || 'Sync template berhasil');
                await loadKirimTemplates();
            } catch (error) {
                toastr.error(error.message || 'Gagal sync template');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = original;
                }
            }
        }

        // Toggle custom keys section
        function toggleCustomKeys() {
            const section = document.getElementById('customKeysSection');
            const text = document.getElementById('toggleCustomKeysText');

            if (section.style.display === 'none') {
                section.style.display = 'block';
                text.textContent = 'Sembunyikan';
            } else {
                section.style.display = 'none';
                text.textContent = 'Tampilkan';
            }
        }

        function toggleProviderFields() {
            const provider = document.getElementById('provider')?.value || 'fonnte';
            const kirimGroup = document.getElementById('kirimPhoneIdGroup');
            const kirimFallbackGroup = document.getElementById('kirimFallbackGroup');
            const kirimAlphaGroup = document.getElementById('kirimAlphaTemplateGroup');
            const apiKeyHint = document.getElementById('apiKeyHint');
            const kirimTemplateManagerCard = document.getElementById('kirimTemplateManagerCard');

            if (provider === 'kirimdev') {
                if (kirimGroup) kirimGroup.style.display = 'block';
                if (kirimFallbackGroup) kirimFallbackGroup.style.display = 'block';
                if (kirimAlphaGroup) kirimAlphaGroup.style.display = 'block';
                if (kirimTemplateManagerCard) kirimTemplateManagerCard.style.display = 'block';
                if (apiKeyHint) {
                    apiKeyHint.innerHTML = 'Kirim.dev: Ambil API key dari <a href="https://app.kirimdev.com" target="_blank">Dashboard Kirim.dev</a>.';
                }
            } else {
                if (kirimGroup) kirimGroup.style.display = 'none';
                if (kirimFallbackGroup) kirimFallbackGroup.style.display = 'none';
                if (kirimAlphaGroup) kirimAlphaGroup.style.display = 'none';
                if (kirimTemplateManagerCard) kirimTemplateManagerCard.style.display = 'none';
                if (apiKeyHint) {
                    apiKeyHint.innerHTML = 'Fonnte: Ambil API key dari <a href="https://fonnte.com/dashboard" target="_blank">Fonnte Dashboard</a>.';
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            toggleProviderFields();
            toggleKirimTemplateCategoryFields();
            const categorySelect = document.getElementById('kirim_template_category');
            if (categorySelect) {
                categorySelect.addEventListener('change', toggleKirimTemplateCategoryFields);
            }
            if ((document.getElementById('provider')?.value || 'fonnte') === 'kirimdev') {
                loadKirimTemplates();
            }
        });
    </script>
@endpush
