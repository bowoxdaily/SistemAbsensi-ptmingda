@extends('layouts.app')

@section('title', 'Absensi Saya')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold mb-2">
                        <span class="text-muted fw-light">Karyawan /</span> Absensi
                    </h4>
                    <span class="badge bg-primary" id="currentTime"></span>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                        <i class='bx bx-home'></i>
                        <span class="d-none d-sm-inline">Kembali ke Home</span>
                        <span class="d-sm-none">Home</span>
                    </a>
                    <a href="{{ route('employee.attendance.history') }}" class="btn btn-outline-primary">
                        <i class='bx bx-history'></i>
                        <span class="d-none d-sm-inline">Riwayat Absensi</span>
                        <span class="d-sm-none">Riwayat</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column - Attendance Form -->
            <div class="col-lg-8">
                <!-- Employee Info Card -->
                <div class="card mb-4" id="employeeInfoCard" style="display: none;">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg me-3">
                                <span class="avatar-initial rounded-circle bg-label-primary">
                                    <i class='bx bx-user bx-lg'></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-1" id="empName"></h5>
                                <p class="mb-0 text-muted" id="empDetails"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Camera Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Absensi dengan Face Detection</h5>
                    </div>
                    <div class="card-body">
                        <!-- Status Today -->
                        <div id="statusToday" style="display: none;" class="mb-4">
                            <div class="alert alert-info">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">Status Absensi Hari Ini</h6>
                                    <span class="badge" id="statusBadge"></span>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <i class='bx bx-log-in fs-4 me-2 text-success'></i>
                                            <div>
                                                <small class="text-muted d-block">Check In</small>
                                                <strong id="checkInTime">-</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <i class='bx bx-log-out fs-4 me-2 text-warning'></i>
                                            <div>
                                                <small class="text-muted d-block">Check Out</small>
                                                <strong id="checkOutTime">-</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2" id="lateBadgeContainer" style="display: none;">
                                    <span class="badge bg-warning" id="lateBadge"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Camera Section -->
                        <div id="cameraSection" style="display: none;">
                            <!-- Camera Preview -->
                            <div class="position-relative mb-3">
                                <video id="videoElement" width="100%" autoplay playsinline class="rounded border"
                                    style="background: #000; max-height: 400px; height: auto;"></video>
                                <canvas id="canvasElement" style="display: none;"></canvas>

                                <!-- Face Detection Indicator -->
                                <div class="position-absolute top-0 start-0 m-2 m-md-3">
                                    <span class="badge bg-warning" id="faceDetected"
                                        style="display: none; font-size: 0.75rem;">
                                        <i class='bx bx-loader-circle bx-spin'></i> <span
                                            class="d-none d-sm-inline">Mendeteksi wajah...</span><span
                                            class="d-sm-none">Deteksi...</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Capture Button -->
                            <div class="d-grid gap-2 mb-3" id="captureSection">
                                <button type="button" class="btn btn-lg btn-secondary" id="captureBtn" disabled>
                                    <i class='bx bx-camera me-1 me-md-2'></i>
                                    <span class="d-none d-sm-inline">Ambil Foto (Tunggu Wajah Terdeteksi)</span>
                                    <span class="d-sm-none">Ambil Foto</span>
                                </button>
                                <small class="text-muted text-center">
                                    <i class='bx bx-info-circle'></i> Tombol aktif saat wajah terdeteksi
                                </small>
                            </div>

                            <!-- Preview Captured Image -->
                            <div id="capturedPreview" style="display: none;" class="mb-3">
                                <img id="capturedImage" class="img-fluid rounded border mb-3" alt="Captured">

                                <div class="alert alert-info mb-3">
                                    <i class='bx bx-info-circle'></i>
                                    <strong>Penting:</strong> Tetap hadapkan wajah ke kamera saat menekan tombol
                                    check-in/out
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-success btn-lg" id="checkInBtn">
                                        <i class='bx bx-log-in-circle me-1 me-md-2'></i>
                                        <span class="d-none d-sm-inline">Check In Sekarang</span>
                                        <span class="d-sm-none">Check In</span>
                                    </button>
                                    <button type="button" class="btn btn-warning btn-lg" id="checkOutBtn">
                                        <i class='bx bx-log-out-circle me-1 me-md-2'></i>
                                        <span class="d-none d-sm-inline">Check Out Sekarang</span>
                                        <span class="d-sm-none">Check Out</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="retakeBtn">
                                        <i class='bx bx-refresh me-1 me-md-2'></i>
                                        Ambil Ulang
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Start Camera Button -->
                        <div class="text-center" id="startSection">
                            <button type="button" class="btn btn-lg btn-primary" id="startCameraBtn">
                                <i class='bx bx-camera-movie me-2'></i>
                                Mulai Absensi
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Instructions Card -->
                <div class="card d-none d-lg-block">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            <i class='bx bx-info-circle me-2'></i>
                            Petunjuk Absensi
                        </h6>
                        <ol class="mb-0 ps-3">
                            <li class="mb-2">Klik tombol <strong>"Mulai Absensi"</strong></li>
                            <li class="mb-2">Izinkan akses kamera saat diminta browser</li>
                            <li class="mb-2">Posisikan wajah Anda di tengah kamera</li>
                            <li class="mb-2">Pastikan pencahayaan cukup terang</li>
                            <li class="mb-2">Klik <strong>"Ambil Foto"</strong> untuk capture wajah</li>
                            <li class="mb-2">Pilih <strong>"Check In"</strong> (masuk) atau <strong>"Check Out"</strong>
                                (pulang)</li>
                            <li>Sistem akan menyimpan foto dan lokasi GPS Anda</li>
                        </ol>
                        <div class="alert alert-warning mt-3 mb-0">
                            <small>
                                <i class='bx bx-error-circle me-1'></i>
                                <strong>Penting:</strong> Pastikan wajah terlihat jelas dan tidak terhalang.
                                Sistem akan mendeteksi wajah secara otomatis.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Summary -->
            <div class="col-lg-4">
                <!-- Monthly Summary -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Ringkasan Bulan Ini</h6>
                    </div>
                    <div class="card-body">
                        <!-- Desktop View -->
                        <div class="d-none d-lg-block">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2">
                                        <span class="avatar-initial rounded bg-label-success">
                                            <i class='bx bx-check'></i>
                                        </span>
                                    </div>
                                    <span>Hadir</span>
                                </div>
                                <strong id="summaryHadir">0</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2">
                                        <span class="avatar-initial rounded bg-label-warning">
                                            <i class='bx bx-time'></i>
                                        </span>
                                    </div>
                                    <span>Terlambat</span>
                                </div>
                                <strong id="summaryTerlambat2">0</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2">
                                        <span class="avatar-initial rounded bg-label-info">
                                            <i class='bx bx-file'></i>
                                        </span>
                                    </div>
                                    <span>Izin</span>
                                </div>
                                <strong id="summaryIzin2">0</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2">
                                        <span class="avatar-initial rounded bg-label-danger">
                                            <i class='bx bx-x'></i>
                                        </span>
                                    </div>
                                    <span>Alpha</span>
                                </div>
                                <strong id="summaryAlpha2">0</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>Total</strong>
                                <strong class="text-primary" id="summaryTotal2">0</strong>
                            </div>
                        </div>

                        <!-- Mobile View (Grid) -->
                        <div class="d-lg-none">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="text-center p-2 border rounded">
                                        <i class='bx bx-check text-success' style="font-size: 24px;"></i>
                                        <div class="mt-1">
                                            <strong class="d-block text-success" id="summaryHadirMobile">0</strong>
                                            <small class="text-muted">Hadir</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-2 border rounded">
                                        <i class='bx bx-time text-warning' style="font-size: 24px;"></i>
                                        <div class="mt-1">
                                            <strong class="d-block text-warning" id="summaryTerlambatMobile">0</strong>
                                            <small class="text-muted">Terlambat</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-2 border rounded">
                                        <i class='bx bx-file text-info' style="font-size: 24px;"></i>
                                        <div class="mt-1">
                                            <strong class="d-block text-info" id="summaryIzinMobile">0</strong>
                                            <small class="text-muted">Izin</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-2 border rounded">
                                        <i class='bx bx-x text-danger' style="font-size: 24px;"></i>
                                        <div class="mt-1">
                                            <strong class="d-block text-danger" id="summaryAlphaMobile">0</strong>
                                            <small class="text-muted">Alpha</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="text-center">
                                <small class="text-muted">Total Absensi</small>
                                <h4 class="mb-0 text-primary" id="summaryTotalMobile">0</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Info -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Informasi</h6>
                        <div class="mb-3">
                            <small class="text-muted d-block">
                                <i class='bx bx-calendar'></i> Tanggal
                            </small>
                            <strong id="todayDate"></strong>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block">
                                <i class='bx bx-time'></i> Jadwal Shift
                            </small>
                            <strong id="shiftInfo">-</strong>
                        </div>
                        <div>
                            <small class="text-muted d-block">
                                <i class='bx bx-map'></i> Lokasi GPS
                            </small>
                            <small id="gpsLocation" class="text-success">
                                <i class='bx bx-loader-circle bx-spin'></i> Mendeteksi lokasi...
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs"></script>
        <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface"></script>

        <script>
            let video = document.getElementById('videoElement');
            let canvas = document.getElementById('canvasElement');
            let capturedImage = document.getElementById('capturedImage');
            let stream = null;
            let model = null;
            let capturedPhoto = null;
            let currentLocation = null;
            let lastLocationCheck = null;
            let isFaceDetected = false; // Track face detection status

            // Update current time
            function updateTime() {
                const now = new Date();
                document.getElementById('currentTime').textContent = now.toLocaleString('id-ID', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });

                document.getElementById('todayDate').textContent = now.toLocaleDateString('id-ID', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            }
            updateTime();
            setInterval(updateTime, 1000);

            // Detect fake GPS
            function detectFakeGPS(position) {
                const warnings = [];

                // 1. Check if mocked (Android & some browsers)
                if (position.coords.mocked === true) {
                    warnings.push('Mock location detected');
                }

                // 2. Check accuracy - Fake GPS often has suspiciously perfect accuracy
                const accuracy = position.coords.accuracy;
                if (accuracy < 5) {
                    warnings.push(`Accuracy terlalu sempurna (${accuracy.toFixed(2)}m) - kemungkinan fake GPS`);
                } else if (accuracy > 100) {
                    warnings.push(`Accuracy rendah (${accuracy.toFixed(2)}m) - sinyal GPS lemah`);
                }

                // 3. Check altitude accuracy (fake GPS often provides 0 or null)
                if (position.coords.altitude === 0 || position.coords.altitude === null) {
                    warnings.push('Altitude data tidak valid - kemungkinan fake GPS');
                }

                // 4. Check speed (fake GPS often has speed = 0 or null even when moving)
                if (position.coords.speed === 0 && lastLocationCheck) {
                    const timeDiff = (Date.now() - lastLocationCheck.timestamp) / 1000; // seconds
                    if (timeDiff < 60) { // Check if less than 1 minute
                        const distance = calculateDistance(
                            lastLocationCheck.coords.latitude,
                            lastLocationCheck.coords.longitude,
                            position.coords.latitude,
                            position.coords.longitude
                        );
                        // If moved more than 10 meters but speed is 0, suspicious
                        if (distance > 10) {
                            warnings.push('Movement detected but speed is 0 - kemungkinan fake GPS');
                        }
                    }
                }

                // 5. Check if all coordinates are whole numbers (very suspicious)
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                if (lat === Math.floor(lat) && lng === Math.floor(lng)) {
                    warnings.push('Koordinat terlalu bulat - kemungkinan fake GPS');
                }

                // Store current check for next comparison
                lastLocationCheck = {
                    coords: position.coords,
                    timestamp: Date.now()
                };

                return {
                    isSuspicious: warnings.length > 0,
                    warnings: warnings,
                    accuracy: accuracy
                };
            }

            // Calculate distance between two coordinates (in meters)
            function calculateDistance(lat1, lon1, lat2, lon2) {
                const R = 6371000; // Earth radius in meters
                const dLat = (lat2 - lat1) * Math.PI / 180;
                const dLon = (lon2 - lon1) * Math.PI / 180;
                const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                    Math.sin(dLon / 2) * Math.sin(dLon / 2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                return R * c;
            }

            // Get current location with fake GPS detection
            function getCurrentLocation() {
                return new Promise((resolve, reject) => {
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            position => {
                                // Detect fake GPS
                                const fakeCheck = detectFakeGPS(position);

                                const loc = {
                                    latitude: position.coords.latitude,
                                    longitude: position.coords.longitude,
                                    accuracy: position.coords.accuracy,
                                    altitude: position.coords.altitude,
                                    altitudeAccuracy: position.coords.altitudeAccuracy,
                                    heading: position.coords.heading,
                                    speed: position.coords.speed,
                                    timestamp: position.timestamp,
                                    isMocked: position.coords.mocked || false,
                                    fakeGpsWarnings: fakeCheck.warnings
                                };

                                currentLocation = loc;

                                // Show warning if suspicious
                                if (fakeCheck.isSuspicious) {
                                    document.getElementById('gpsLocation').innerHTML =
                                        `<i class='bx bx-error-circle text-warning'></i> ⚠️ GPS Mencurigakan (Accuracy: ${fakeCheck.accuracy.toFixed(0)}m)`;

                                    console.warn('Fake GPS Detection:', fakeCheck.warnings);

                                    // Show warning to user
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Peringatan GPS',
                                        html: '<strong>Terdeteksi kemungkinan Fake GPS:</strong><br>' +
                                            fakeCheck.warnings.join('<br>'),
                                        footer: 'Penggunaan Fake GPS dapat mengakibatkan absensi ditolak',
                                        confirmButtonText: 'Saya Mengerti'
                                    });
                                } else {
                                    document.getElementById('gpsLocation').innerHTML =
                                        `<i class='bx bx-map text-success'></i> Lokasi terdeteksi (${fakeCheck.accuracy.toFixed(0)}m)`;
                                }

                                resolve(loc);
                            },
                            error => {
                                document.getElementById('gpsLocation').innerHTML =
                                    `<i class='bx bx-error text-danger'></i> Gagal mendeteksi lokasi`;
                                reject(error);
                            }, {
                                enableHighAccuracy: true,
                                timeout: 10000,
                                maximumAge: 0
                            }
                        );
                    } else {
                        reject(new Error('Geolocation not supported'));
                    }
                });
            }

            // Initialize - get location immediately
            getCurrentLocation().catch(err => console.error('GPS Error:', err));

            // Load employee data
            async function loadEmployeeData() {
                try {
                    const response = await fetch('/api/employee/current');

                    if (!response.ok) {
                        const errorText = await response.text();
                        console.error('Response error:', errorText);

                        // Try to parse as JSON
                        try {
                            const result = JSON.parse(errorText);
                            throw new Error(result.message || 'Gagal memuat data karyawan');
                        } catch (e) {
                            throw new Error('Server error: Pastikan Anda sudah login dan memiliki data karyawan.');
                        }
                    }

                    const result = await response.json();

                    if (result.success) {
                        const emp = result.data;
                        document.getElementById('empName').textContent = emp.name;
                        document.getElementById('empDetails').textContent =
                            `${emp.employee_code} - ${emp.department.name} - ${emp.position.name}`;
                        document.getElementById('shiftInfo').textContent = emp.shift_type;
                        document.getElementById('employeeInfoCard').style.display = 'block';

                        // Check today's attendance
                        checkTodayAttendance();
                        loadMonthlySummary();
                    } else {
                        throw new Error(result.message || 'Gagal memuat data karyawan');
                    }
                } catch (error) {
                    console.error('Error loading employee:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memuat Data',
                        text: error.message || 'Pastikan Anda sudah login dan memiliki data karyawan di sistem.',
                        footer: 'Hubungi administrator jika masalah berlanjut'
                    });
                }
            }

            // Check today's attendance
            async function checkTodayAttendance() {
                try {
                    const response = await fetch('/api/employee/attendance/today');
                    const result = await response.json();

                    if (result.data) {
                        const att = result.data;
                        document.getElementById('statusToday').style.display = 'block';
                        document.getElementById('checkInTime').textContent = att.check_in || '-';
                        document.getElementById('checkOutTime').textContent = att.check_out || '-';

                        // Status badge mapping
                        const statusConfig = {
                            'hadir': {
                                label: 'HADIR',
                                class: 'bg-success'
                            },
                            'present': {
                                label: 'HADIR',
                                class: 'bg-success'
                            },
                            'terlambat': {
                                label: 'TERLAMBAT',
                                class: 'bg-warning'
                            },
                            'late': {
                                label: 'TERLAMBAT',
                                class: 'bg-warning'
                            },
                            'cuti': {
                                label: 'CUTI',
                                class: 'bg-info'
                            },
                            'leave': {
                                label: 'CUTI',
                                class: 'bg-info'
                            },
                            'izin': {
                                label: 'IZIN',
                                class: 'bg-primary'
                            },
                            'sick': {
                                label: 'SAKIT',
                                class: 'bg-secondary'
                            },
                            'sakit': {
                                label: 'SAKIT',
                                class: 'bg-secondary'
                            },
                            'alpha': {
                                label: 'ALPHA',
                                class: 'bg-danger'
                            }
                        };

                        // Set status badge
                        const statusBadge = document.getElementById('statusBadge');
                        const config = statusConfig[att.status.toLowerCase()] || {
                            label: att.status.toUpperCase(),
                            class: 'bg-secondary'
                        };
                        statusBadge.textContent = config.label;
                        statusBadge.className = 'badge ' + config.class;

                        // Late badge
                        if (att.late_minutes > 0) {
                            document.getElementById('lateBadge').textContent =
                                `Terlambat ${att.late_minutes} menit`;
                            document.getElementById('lateBadgeContainer').style.display = 'block';
                        }

                        // Status yang tidak boleh absen lagi - termasuk alpha
                        const blockedStatuses = ['cuti', 'leave', 'izin', 'sick', 'sakit', 'alpha', 'absent'];
                        const isBlocked = blockedStatuses.includes(att.status.toLowerCase());

                        // Jika sudah cuti/izin/sakit/alpha, disable semua tombol absensi dan tampilkan pesan
                        if (isBlocked) {
                            // Hide semua tombol check in/out
                            document.getElementById('checkInBtn').style.display = 'none';
                            document.getElementById('checkOutBtn').style.display = 'none';

                            // Disable tombol mulai absensi
                            const startBtn = document.getElementById('startCameraBtn');
                            startBtn.disabled = true;
                            startBtn.innerHTML = '<i class="bx bx-block me-2"></i>Absensi Tidak Tersedia';

                            // Tampilkan alert di status today
                            const statusDiv = document.getElementById('statusToday');
                            const alertHtml = `
                                <div class="alert alert-warning mt-3 mb-0">
                                    <i class="bx bx-info-circle me-2"></i>
                                    <strong>Absensi Tidak Tersedia</strong><br>
                                    Anda sedang dalam status <strong>${config.label}</strong>.
                                    Tidak dapat melakukan absensi hari ini.
                                </div>
                            `;
                            statusDiv.insertAdjacentHTML('beforeend', alertHtml);
                        } else {
                            // Logic normal untuk hadir/terlambat
                            if (att.check_in) {
                                document.getElementById('checkInBtn').style.display = 'none';
                            }
                            if (att.check_out) {
                                document.getElementById('checkOutBtn').style.display = 'none';

                                // Jika sudah check out, disable tombol mulai absensi
                                const startBtn = document.getElementById('startCameraBtn');
                                startBtn.disabled = true;
                                startBtn.innerHTML = '<i class="bx bx-check-circle me-2"></i>Absensi Hari Ini Selesai';
                            }
                        }
                    }
                } catch (error) {
                    console.error('Error checking attendance:', error);
                }
            }

            // Load monthly summary
            async function loadMonthlySummary() {
                try {
                    const response = await fetch('/api/employee/attendance/summary');
                    const result = await response.json();

                    if (result.success) {
                        const summary = result.data;
                        // Desktop
                        document.getElementById('summaryHadir').textContent = summary.hadir;
                        document.getElementById('summaryTerlambat2').textContent = summary.terlambat;
                        document.getElementById('summaryIzin2').textContent = summary.izin;
                        document.getElementById('summaryAlpha2').textContent = summary.alpha;
                        document.getElementById('summaryTotal2').textContent = summary.total;

                        // Mobile
                        document.getElementById('summaryHadirMobile').textContent = summary.hadir;
                        document.getElementById('summaryTerlambatMobile').textContent = summary.terlambat;
                        document.getElementById('summaryIzinMobile').textContent = summary.izin;
                        document.getElementById('summaryAlphaMobile').textContent = summary.alpha;
                        document.getElementById('summaryTotalMobile').textContent = summary.total;
                    }
                } catch (error) {
                    console.error('Error loading summary:', error);
                }
            }

            // Start camera
            document.getElementById('startCameraBtn').addEventListener('click', async function() {
                // Check if button is disabled (already blocked)
                if (this.disabled) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Absensi Tidak Tersedia',
                        text: 'Anda sudah melakukan absensi hari ini atau sedang dalam status cuti/izin/sakit/alpha.',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                try {
                    // Request location first
                    if (!currentLocation) {
                        await getCurrentLocation();
                    }

                    stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            width: 640,
                            height: 480,
                            facingMode: 'user'
                        }
                    });
                    video.srcObject = stream;

                    document.getElementById('cameraSection').style.display = 'block';
                    document.getElementById('startSection').style.display = 'none';

                    // Load face detection model
                    if (!model) {
                        model = await blazeface.load();
                        console.log('Face detection model loaded');
                        detectFaces();
                    }
                } catch (error) {
                    console.error('Error accessing camera:', error);
                    Swal.fire('Error', 'Gagal mengakses kamera. Pastikan izin kamera diberikan.', 'error');
                }
            });

            // Face detection loop
            async function detectFaces() {
                if (!video.srcObject) return;

                try {
                    const predictions = await model.estimateFaces(video, false);

                    const captureBtn = document.getElementById('captureBtn');
                    const faceIndicator = document.getElementById('faceDetected');

                    if (predictions.length > 0) {
                        // Face detected
                        isFaceDetected = true;
                        faceIndicator.style.display = 'inline-block';
                        faceIndicator.className = 'badge bg-success';
                        faceIndicator.innerHTML = '<i class="bx bx-check-circle"></i> Wajah Terdeteksi';

                        // Enable capture button
                        captureBtn.disabled = false;
                        captureBtn.classList.remove('btn-secondary');
                        captureBtn.classList.add('btn-primary');
                    } else {
                        // No face detected
                        isFaceDetected = false;
                        faceIndicator.style.display = 'inline-block';
                        faceIndicator.className = 'badge bg-warning';
                        faceIndicator.innerHTML = '<i class="bx bx-error"></i> Wajah Tidak Terdeteksi';

                        // Disable capture button
                        captureBtn.disabled = true;
                        captureBtn.classList.remove('btn-primary');
                        captureBtn.classList.add('btn-secondary');
                    }
                } catch (error) {
                    console.error('Face detection error:', error);
                }

                requestAnimationFrame(detectFaces);
            }

            // Capture photo
            document.getElementById('captureBtn').addEventListener('click', function() {
                if (!isFaceDetected) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Wajah Tidak Terdeteksi',
                        text: 'Pastikan wajah Anda terlihat jelas di kamera'
                    });
                    return;
                }

                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0);

                capturedPhoto = canvas.toDataURL('image/png');
                capturedImage.src = capturedPhoto;

                document.getElementById('capturedPreview').style.display = 'block';
                document.getElementById('captureSection').style.display = 'none';
                video.style.display = 'none';
            });

            // Retake photo
            document.getElementById('retakeBtn').addEventListener('click', function() {
                document.getElementById('capturedPreview').style.display = 'none';
                document.getElementById('captureSection').style.display = 'block';
                video.style.display = 'block';
                capturedPhoto = null;

                // Restart face detection
                if (model && video.srcObject) {
                    detectFaces();
                }
            });

            // Verify face before action
            async function verifyFaceBeforeAction() {
                if (!model) {
                    throw new Error('Model deteksi wajah belum dimuat');
                }

                // Check if video is still active
                if (!video.srcObject) {
                    throw new Error('Kamera tidak aktif. Silakan mulai ulang kamera.');
                }

                // Do a final face detection check
                const predictions = await model.estimateFaces(video, false);

                if (predictions.length === 0) {
                    throw new Error('Wajah tidak terdeteksi. Pastikan wajah Anda terlihat di kamera saat menekan tombol.');
                }

                return true;
            }

            // Check In
            document.getElementById('checkInBtn').addEventListener('click', async function() {
                if (!capturedPhoto) {
                    Swal.fire('Error', 'Ambil foto terlebih dahulu', 'error');
                    return;
                }

                if (!currentLocation) {
                    Swal.fire('Error', 'Lokasi GPS belum terdeteksi', 'error');
                    return;
                }

                // Verify face is still detected
                try {
                    await verifyFaceBeforeAction();
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Verifikasi Wajah Gagal',
                        text: error.message,
                        footer: 'Pastikan wajah Anda terlihat jelas di kamera'
                    });
                    return;
                }

                // Konfirmasi check in
                const confirmResult = await Swal.fire({
                    title: 'Konfirmasi Check In',
                    text: 'Apakah Anda yakin ingin melakukan check in sekarang?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Check In',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#28a745'
                });

                if (!confirmResult.isConfirmed) {
                    return;
                }

                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';

                try {
                    const response = await fetch('/api/employee/attendance/check-in', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            photo: capturedPhoto,
                            latitude: currentLocation.latitude,
                            longitude: currentLocation.longitude,
                            accuracy: currentLocation.accuracy,
                            is_mocked: currentLocation.isMocked,
                            fake_gps_warnings: currentLocation.fakeGpsWarnings || []
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        await Swal.fire('Berhasil!', result.message, 'success');
                        resetCamera();
                        checkTodayAttendance();
                        loadMonthlySummary();
                    } else {
                        // Check for specific error messages
                        if (result.message.includes('cuti') || result.message.includes('izin') || result.message
                            .includes('sakit') || result.message.includes('alpha')) {
                            await Swal.fire({
                                icon: 'warning',
                                title: 'Absensi Ditolak',
                                html: `<p class="mb-0">${result.message}</p>`,
                                confirmButtonText: 'OK, Saya Mengerti'
                            });
                            resetCamera();
                            checkTodayAttendance(); // Refresh status
                        } else {
                            Swal.fire('Error', result.message, 'error');
                        }
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bx bx-log-in-circle me-2"></i>Check In Sekarang';
                    }
                } catch (error) {
                    console.error('Check-in error:', error);
                    Swal.fire('Error', 'Gagal melakukan check-in: ' + error.message, 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bx bx-log-in-circle me-2"></i>Check In Sekarang';
                }
            });

            // Check Out
            document.getElementById('checkOutBtn').addEventListener('click', async function() {
                if (!capturedPhoto) {
                    Swal.fire('Error', 'Ambil foto terlebih dahulu', 'error');
                    return;
                }

                if (!currentLocation) {
                    Swal.fire('Error', 'Lokasi GPS belum terdeteksi', 'error');
                    return;
                }

                // Verify face is still detected
                try {
                    await verifyFaceBeforeAction();
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Verifikasi Wajah Gagal',
                        text: error.message,
                        footer: 'Pastikan wajah Anda terlihat jelas di kamera'
                    });
                    return;
                }

                // Konfirmasi check out
                const confirmResult = await Swal.fire({
                    title: 'Konfirmasi Check Out',
                    text: 'Apakah Anda yakin ingin melakukan check out sekarang?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Check Out',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#ffc107'
                });

                if (!confirmResult.isConfirmed) {
                    return;
                }

                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';

                try {
                    const response = await fetch('/api/employee/attendance/check-out', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            photo: capturedPhoto,
                            latitude: currentLocation.latitude,
                            longitude: currentLocation.longitude,
                            accuracy: currentLocation.accuracy,
                            is_mocked: currentLocation.isMocked,
                            fake_gps_warnings: currentLocation.fakeGpsWarnings || []
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        await Swal.fire('Berhasil!', result.message, 'success');
                        resetCamera();
                        checkTodayAttendance();
                        loadMonthlySummary();
                    } else {
                        // Handle specific error scenarios
                        if (response.status === 403 && result.shift_end) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Belum Saatnya Check-Out',
                                html: `<div class="text-center">
                                           <p class="mb-3"><strong>${result.message}</strong></p>
                                           <div class="alert alert-warning mb-0">
                                               <i class="bx bx-time"></i> Shift berakhir jam <strong>${result.shift_end}</strong><br>
                                               <i class="bx bx-clock"></i> Sekarang jam <strong>${result.current_time}</strong>
                                           </div>
                                       </div>`,
                                confirmButtonText: 'OK, Saya Mengerti'
                            });
                        } else if (result.message.includes('cuti') || result.message.includes('izin') || result
                            .message.includes('sakit') || result.message.includes('alpha')) {
                            await Swal.fire({
                                icon: 'warning',
                                title: 'Check Out Ditolak',
                                html: `<p class="mb-0">${result.message}</p>`,
                                confirmButtonText: 'OK, Saya Mengerti'
                            });
                            resetCamera();
                            checkTodayAttendance(); // Refresh status
                        } else {
                            Swal.fire('Error', result.message, 'error');
                        }
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bx bx-log-out-circle me-2"></i>Check Out Sekarang';
                    }
                } catch (error) {
                    console.error('Check-out error:', error);
                    Swal.fire('Error', 'Gagal melakukan check-out: ' + error.message, 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bx bx-log-out-circle me-2"></i>Check Out Sekarang';
                }
            });

            // Reset camera
            function resetCamera() {
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                    stream = null;
                }
                video.srcObject = null;
                document.getElementById('cameraSection').style.display = 'none';
                document.getElementById('capturedPreview').style.display = 'none';
                document.getElementById('startSection').style.display = 'block';
                document.getElementById('captureSection').style.display = 'block';
                video.style.display = 'block';
                capturedPhoto = null;
            }

            // Initialize
            loadEmployeeData();
        </script>
    @endpush
@endsection
