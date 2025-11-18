@extends('layouts.app')

@section('title', 'Pengaturan Cron Job')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Pengaturan /</span> Cron Job
        </h4>

        <!-- Alert Info -->
        <div class="alert alert-info alert-dismissible shadow-none border" role="alert">
            <h6 class="alert-heading mb-2">
                <i class='bx bx-info-circle me-2'></i>Apa itu Cron Job?
            </h6>
            <p class="mb-0">Cron Job adalah tugas otomatis yang berjalan secara terjadwal di server. Aplikasi ini
                membutuhkan Cron Job untuk menjalankan tugas-tugas seperti generate absensi alpha otomatis setiap hari.</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <div class="row">
            <!-- Command Cron Job -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class='bx bx-code-alt me-2'></i>Command Cron Job
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Auto Detect Info -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Informasi Server</h6>
                                <button class="btn btn-sm btn-outline-primary" onclick="detectEnvironment()">
                                    <i class='bx bx-refresh me-1'></i>Refresh
                                </button>
                            </div>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="30%">OS</th>
                                    <td id="serverOS">{{ PHP_OS }}</td>
                                </tr>
                                <tr>
                                    <th>PHP Version</th>
                                    <td>{{ PHP_VERSION }}</td>
                                </tr>
                                <tr>
                                    <th>Laravel Version</th>
                                    <td>{{ app()->version() }}</td>
                                </tr>
                                <tr>
                                    <th>Base Path</th>
                                    <td><code>{{ base_path() }}</code></td>
                                </tr>
                                <tr>
                                    <th>PHP Path</th>
                                    <td><code id="phpPath">{{ PHP_BINARY }}</code></td>
                                </tr>
                            </table>
                        </div>

                        <hr>

                        <!-- Cron Command -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Command untuk cPanel/Shared Hosting</label>
                            <div class="input-group">
                                <input type="text" class="form-control font-monospace" id="cronCommandCPanel"
                                    value="* * * * * /usr/local/bin/php {{ base_path() }}/artisan schedule:run >> /dev/null 2>&1"
                                    readonly>
                                <button class="btn btn-outline-primary" type="button"
                                    onclick="copyToClipboard('cronCommandCPanel')">
                                    <i class='bx bx-copy'></i> Copy
                                </button>
                            </div>
                            <small class="text-muted">
                                <i class='bx bx-info-circle'></i>
                                Gunakan <code>/usr/local/bin/php</code> atau jalankan <code>which php</code> via SSH untuk
                                cek path PHP yang benar
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Command untuk VPS/Dedicated Server</label>
                            <div class="input-group">
                                <input type="text" class="form-control font-monospace" id="cronCommandLinux"
                                    value="* * * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1"
                                    readonly>
                                <button class="btn btn-outline-primary" type="button"
                                    onclick="copyToClipboard('cronCommandLinux')">
                                    <i class='bx bx-copy'></i> Copy
                                </button>
                            </div>
                            <small class="text-muted">Untuk VPS Linux dengan full akses (gunakan <code>php</code> langsung
                                jika sudah di PATH)</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Command untuk Windows</label>
                            <div class="input-group">
                                <input type="text" class="form-control font-monospace" id="cronCommandWindows"
                                    value='schtasks /create /tn "Laravel Scheduler" /tr "cd {{ base_path() }} && {{ PHP_BINARY }} artisan schedule:run" /sc minute /mo 1'
                                    readonly>
                                <button class="btn btn-outline-primary" type="button"
                                    onclick="copyToClipboard('cronCommandWindows')">
                                    <i class='bx bx-copy'></i> Copy
                                </button>
                            </div>
                            <small class="text-muted">Untuk Windows Server / Local Development</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Alternative - Direct Artisan Command</label>
                            <div class="input-group">
                                <input type="text" class="form-control font-monospace" id="cronCommandDirect"
                                    value="{{ PHP_BINARY }} {{ base_path('artisan') }} schedule:run" readonly>
                                <button class="btn btn-outline-primary" type="button"
                                    onclick="copyToClipboard('cronCommandDirect')">
                                    <i class='bx bx-copy'></i> Copy
                                </button>
                            </div>
                            <small class="text-muted">Command langsung tanpa cd ke directory</small>
                        </div>
                    </div>
                </div>

                <!-- Scheduled Tasks List -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class='bx bx-time me-2'></i>Daftar Scheduled Tasks
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Command</th>
                                        <th>Schedule</th>
                                        <th>Deskripsi</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>attendance:generate-absent</code></td>
                                        <td>
                                            <span class="badge bg-label-primary">Hourly</span>
                                            <span class="badge bg-label-info">08:00 - 23:59</span>
                                            <span class="badge bg-label-secondary">Weekdays</span>
                                        </td>
                                        <td>
                                            Generate absensi alpha untuk karyawan yang tidak hadir<br>
                                            <small class="text-muted">Dijalankan setiap jam, cek apakah karyawan sudah
                                                melewati jam checkout + 30 menit</small>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-success"
                                                onclick="testCommand('attendance:generate-absent')">
                                                <i class='bx bx-play'></i> Test
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Setup Instructions -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class='bx bx-book-open me-2'></i>Panduan Setup
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Tabs -->
                        <ul class="nav nav-pills mb-3" role="tablist">
                            <li class="nav-item">
                                <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#cpanel">cPanel</button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#plesk">Plesk</button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#vps">VPS/SSH</button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#windows">Windows</button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <!-- cPanel -->
                            <div class="tab-pane fade show active" id="cpanel" role="tabpanel">
                                <h6 class="mb-3">Setup di cPanel</h6>

                                <div class="alert alert-primary shadow-none border mb-3">
                                    <strong>🔍 Langkah 1: Cek Path PHP yang Benar</strong><br>
                                    <ol class="mb-0 mt-2">
                                        <li>Login ke cPanel → <strong>Terminal</strong> (jika tersedia)</li>
                                        <li>Atau login via SSH ke server</li>
                                        <li>Jalankan command: <code class="text-dark">which php</code></li>
                                        <li>Akan muncul path seperti: <code class="text-dark">/usr/local/bin/php</code>
                                            atau <code class="text-dark">/usr/bin/php</code></li>
                                        <li>Gunakan path tersebut untuk command cron</li>
                                    </ol>
                                </div>

                                <strong>📝 Langkah 2: Setup Cron Job</strong>
                                <ol class="mt-2">
                                    <li>Login ke cPanel hosting Anda</li>
                                    <li>Cari menu <strong>"Cron Jobs"</strong> di bagian Advanced</li>
                                    <li>Pilih interval <strong>"Common Settings: Once Per Minute (* * * * *)"</strong></li>
                                    <li>Di field "Command", masukkan:<br>
                                        <code class="d-block my-2 p-2 bg-light rounded">/usr/local/bin/php
                                            {{ base_path() }}/artisan schedule:run >> /dev/null 2>&1</code>
                                        <small class="text-muted">⚠️ Ganti <code>/usr/local/bin/php</code> dengan hasil
                                            dari <code>which php</code></small>
                                    </li>
                                    <li>Klik <strong>"Add New Cron Job"</strong></li>
                                    <li>Cron Job akan otomatis berjalan setiap menit</li>
                                    <li>Kembali ke halaman ini dan klik "Check Status" untuk verifikasi</li>
                                </ol>

                                <div class="alert alert-warning shadow-none border mt-3">
                                    <strong>⚠️ Catatan Penting:</strong><br>
                                    • Jangan gunakan <code>php-fpm</code>, harus <code>php</code> biasa<br>
                                    • Path PHP berbeda-beda tergantung hosting provider<br>
                                    • Jika error, tanyakan path PHP yang benar ke support hosting<br>
                                    • Status akan update otomatis dalam 1-2 menit setelah setup
                                </div>
                                <div class="alert alert-info shadow-none border">
                                    <strong>💡 Tips cPanel:</strong><br>
                                    • Cek email untuk notifikasi error dari cron<br>
                                    • Tambahkan <code>>/dev/null 2>&1</code> di akhir command untuk disable email<br>
                                    • Gunakan <code>> /home/username/cron.log 2>&1</code> untuk save log<br>
                                    • Contoh provider: cPanel biasa pakai <code>/usr/local/bin/php</code>
                                </div>
                            </div>

                            <!-- Plesk -->
                            <div class="tab-pane fade" id="plesk" role="tabpanel">
                                <h6 class="mb-3">Setup di Plesk</h6>
                                <ol>
                                    <li>Login ke Plesk Panel</li>
                                    <li>Pilih domain/website Anda</li>
                                    <li>Klik <strong>"Scheduled Tasks"</strong></li>
                                    <li>Klik <strong>"Add Task"</strong></li>
                                    <li>Task type: <strong>Run a command</strong></li>
                                    <li>Schedule: <strong>*/1 * * * *</strong> (every minute)</li>
                                    <li>Copy paste command Linux di atas</li>
                                    <li>Klik <strong>"OK"</strong></li>
                                </ol>
                            </div>

                            <!-- VPS/SSH -->
                            <div class="tab-pane fade" id="vps" role="tabpanel">
                                <h6 class="mb-3">Setup di VPS via SSH</h6>
                                <ol>
                                    <li>SSH ke server VPS Anda</li>
                                    <li>Jalankan command: <code>crontab -e</code></li>
                                    <li>Tekan <kbd>i</kbd> untuk masuk mode insert</li>
                                    <li>Copy paste command Linux di atas</li>
                                    <li>Tekan <kbd>Esc</kbd> kemudian ketik <code>:wq</code> dan Enter</li>
                                    <li>Verifikasi: <code>crontab -l</code></li>
                                </ol>
                                <div class="alert alert-info shadow-none border">
                                    <strong>Tips:</strong> Untuk log cron, ubah command menjadi:<br>
                                    <code>* * * * * cd {{ base_path() }} && {{ PHP_BINARY }} artisan schedule:run >>
                                        {{ base_path('storage/logs/cron.log') }} 2>&1</code>
                                </div>
                            </div>

                            <!-- Windows -->
                            <div class="tab-pane fade" id="windows" role="tabpanel">
                                <h6 class="mb-3">Setup di Windows (Development)</h6>
                                <ol>
                                    <li>Buka <strong>Task Scheduler</strong></li>
                                    <li>Klik <strong>"Create Basic Task"</strong></li>
                                    <li>Name: <strong>Laravel Scheduler</strong></li>
                                    <li>Trigger: <strong>Daily</strong>, repeat every 1 minute</li>
                                    <li>Action: <strong>Start a program</strong></li>
                                    <li>Program/script: <code>{{ PHP_BINARY }}</code></li>
                                    <li>Add arguments: <code>artisan schedule:run</code></li>
                                    <li>Start in: <code>{{ base_path() }}</code></li>
                                    <li>Finish & Test</li>
                                </ol>
                                <div class="alert alert-warning shadow-none border">
                                    <strong>Alternatif untuk Development (Lebih Mudah):</strong><br>
                                    <ol class="mb-0 mt-2">
                                        <li>Buka PowerShell atau CMD</li>
                                        <li>Masuk ke folder project: <code>cd {{ base_path() }}</code></li>
                                        <li>Jalankan: <code class="text-primary">php artisan schedule:work</code></li>
                                        <li>Biarkan terminal tetap terbuka (Tekan Ctrl+C untuk stop)</li>
                                    </ol>
                                </div>
                                <div class="alert alert-info shadow-none border mb-0">
                                    <strong><i class='bx bx-info-circle'></i> Info Penting:</strong><br>
                                    • Command <code>schedule:work</code> akan otomatis menjalankan scheduler setiap
                                    menit<br>
                                    • Cocok untuk development/testing lokal<br>
                                    • Untuk production, gunakan Task Scheduler Windows<br>
                                    • Command <code>attendance:generate-absent</code> hanya jalan:<br>
                                    &nbsp;&nbsp;- Senin-Jumat (weekdays)<br>
                                    &nbsp;&nbsp;- Jam 08:00 - 23:59 WIB<br>
                                    &nbsp;&nbsp;- Setiap jam (hourly)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Status Cron -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class='bx bx-check-shield me-2'></i>Status Cron
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Last Run</label>
                            <div class="d-flex align-items-center">
                                <i class='bx bx-time-five me-2 text-primary'></i>
                                <span id="lastRun">-</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Next Run</label>
                            <div class="d-flex align-items-center">
                                <i class='bx bx-calendar-event me-2 text-success'></i>
                                <span id="nextRun">-</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cron Status</label>
                            <div id="cronStatus">
                                <span class="badge bg-label-warning">Belum Diketahui</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">
                                <i class='bx bx-info-circle'></i> Status:
                                <span id="trackingInfo">Checking...</span>
                            </small>
                        </div>
                        <button class="btn btn-primary w-100" onclick="checkCronStatus()">
                            <i class='bx bx-refresh me-1'></i>Check Status
                        </button>
                    </div>
                </div>

                <!-- Quick Test -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class='bx bx-test-tube me-2'></i>Quick Test
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Test menjalankan scheduler secara manual</p>
                        <button class="btn btn-outline-success w-100 mb-2" onclick="runScheduler()">
                            <i class='bx bx-play-circle me-1'></i>Run Scheduler Now
                        </button>
                        <button class="btn btn-outline-info w-100 mb-2" onclick="viewScheduleList()">
                            <i class='bx bx-list-ul me-1'></i>View Schedule List
                        </button>
                        <button class="btn btn-outline-warning w-100" onclick="runAbsentCommand()">
                            <i class='bx bx-user-x me-1'></i>Generate Absent Now
                        </button>
                        <hr>
                        <div class="alert alert-primary shadow-none border p-2 mb-0 small">
                            <strong>Development Mode:</strong><br>
                            Gunakan terminal command:<br>
                            <code class="d-block my-1 text-dark">php artisan schedule:work</code>
                        </div>
                    </div>
                </div>

                <!-- Help -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class='bx bx-help-circle me-2'></i>Bantuan
                        </h5>
                    </div>
                    <div class="card-body">
                        <h6 class="mb-2">Troubleshooting</h6>
                        <ul class="small mb-3">
                            <li>Pastikan path PHP sudah benar</li>
                            <li>Periksa permission folder storage/logs</li>
                            <li>Cek apakah cron sudah aktif di server</li>
                            <li>Lihat log di storage/logs/laravel.log</li>
                        </ul>

                        <h6 class="mb-2">Kontak Support</h6>
                        <p class="small mb-0">Jika mengalami kesulitan, hubungi administrator sistem atau hosting provider
                            Anda.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Schedule List -->
    <div class="modal fade" id="scheduleListModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Schedule List</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <pre id="scheduleListContent" class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;"></pre>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function copyToClipboard(elementId) {
                const input = document.getElementById(elementId);
                input.select();
                input.setSelectionRange(0, 99999);

                navigator.clipboard.writeText(input.value).then(() => {
                    toastr.success('Command berhasil di-copy ke clipboard', 'Copied!');
                });
            }

            function detectEnvironment() {
                // Auto detect sudah dilakukan di server side
                toastr.info('Server information telah di-refresh', 'Environment Detected');
            }

            function testCommand(command) {
                Swal.fire({
                    title: 'Test Command',
                    text: `Menjalankan command: ${command}?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Jalankan',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#696cff',
                    cancelButtonColor: '#8592a3'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Menjalankan command...',
                            text: 'Mohon tunggu',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.ajax({
                            url: '/api/settings/cronjob/test',
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                command: command
                            },
                            headers: {
                                'Accept': 'application/json'
                            },
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil!',
                                    text: response.message,
                                    html: '<pre class="text-start small">' + response.output +
                                        '</pre>'
                                });
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal',
                                    text: xhr.responseJSON?.message || 'Gagal menjalankan command'
                                });
                            }
                        });
                    }
                });
            }

            function runScheduler() {
                Swal.fire({
                    title: 'Run Scheduler',
                    text: 'Menjalankan scheduler sekarang?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Jalankan',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#696cff',
                    cancelButtonColor: '#8592a3'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Menjalankan scheduler...',
                            text: 'Mohon tunggu',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.ajax({
                            url: '/api/settings/cronjob/run',
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            headers: {
                                'Accept': 'application/json'
                            },
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil!',
                                    text: response.message,
                                    timer: 2000
                                });
                                checkCronStatus();
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal',
                                    text: xhr.responseJSON?.message || 'Gagal menjalankan scheduler'
                                });
                            }
                        });
                    }
                });
            }

            function runAbsentCommand() {
                Swal.fire({
                    title: 'Generate Absent Attendance',
                    text: 'Menjalankan command generate absent untuk hari ini?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Jalankan',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#696cff',
                    cancelButtonColor: '#8592a3'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Menjalankan command...',
                            html: 'Mengecek karyawan yang belum absen...<br><small class="text-muted">Mohon tunggu</small>',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.ajax({
                            url: '/api/settings/cronjob/test',
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                command: 'attendance:generate-absent'
                            },
                            headers: {
                                'Accept': 'application/json'
                            },
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil!',
                                    html: '<div class="text-start">' +
                                        '<p class="mb-2">' + response.message + '</p>' +
                                        '<hr>' +
                                        '<small><strong>Output:</strong></small>' +
                                        '<pre class="text-start small bg-light p-2 rounded" style="max-height: 300px; overflow-y: auto;">' +
                                        response.output + '</pre>' +
                                        '</div>',
                                    width: 600
                                });
                                checkCronStatus();
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal',
                                    text: xhr.responseJSON?.message || 'Gagal menjalankan command'
                                });
                            }
                        });
                    }
                });
            }

            function viewScheduleList() {
                $.ajax({
                    url: '/api/settings/cronjob/list',
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        $('#scheduleListContent').text(response.output);
                        new bootstrap.Modal(document.getElementById('scheduleListModal')).show();
                    },
                    error: function(xhr) {
                        toastr.error('Gagal mengambil schedule list', 'Error!');
                    }
                });
            }

            function checkCronStatus() {
                $.ajax({
                    url: '/api/settings/cronjob/status',
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        console.log('Cron Status:', response);

                        $('#lastRun').text(response.last_run_human || 'Belum pernah');
                        $('#nextRun').text(response.next_run || '-');

                        // Show tracking info
                        let trackingInfo = [];
                        if (response.sentinel_exists) {
                            trackingInfo.push('File tracking: ✓');
                        }
                        if (response.cache_exists) {
                            trackingInfo.push('Cache tracking: ✓');
                        }
                        $('#trackingInfo').text(trackingInfo.join(' | ') || 'No tracking data');

                        let statusBadge = '';
                        if (response.is_running) {
                            statusBadge =
                                '<span class="badge bg-success"><i class="bx bx-check-circle"></i> Active</span>';
                            toastr.success(response.message, 'Cron Active', {
                                timeOut: 2000
                            });
                        } else {
                            if (response.minutes_ago && response.minutes_ago <= 5) {
                                statusBadge =
                                    '<span class="badge bg-warning"><i class="bx bx-error"></i> Warning</span>';
                                toastr.warning(response.message, 'Cron Warning');
                            } else {
                                statusBadge =
                                    '<span class="badge bg-danger"><i class="bx bx-x-circle"></i> Inactive</span>';
                                toastr.error(response.message, 'Cron Inactive');
                            }
                        }
                        $('#cronStatus').html(statusBadge);
                    },
                    error: function(xhr) {
                        $('#cronStatus').html('<span class="badge bg-secondary">Unknown</span>');
                        $('#trackingInfo').text('Error checking status');
                        toastr.error('Gagal mengecek status cron', 'Error!');
                    }
                });
            }

            // Auto check status on load
            $(document).ready(function() {
                checkCronStatus();

                // Auto refresh every 30 seconds
                setInterval(checkCronStatus, 30000);
            });
        </script>
    @endpush
@endsection
