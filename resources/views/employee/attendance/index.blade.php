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
                            <div class="avatar avatar-lg me-3" id="empAvatarContainer">
                                <!-- Will be filled by JavaScript -->
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-1" id="empName"></h5>
                                <p class="mb-0 text-muted" id="empDetails"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Status Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Status Absensi</h5>
                    </div>
                    <div class="card-body">
                        <!-- Fingerspot Announcement -->
                        <div class="alert alert-warning mb-4" id="fingerspotAnnouncement">
                            <div class="d-flex align-items-start">
                                <i class='bx bx-info-circle fs-3 me-3 text-warning'></i>
                                <div>
                                    <h6 class="alert-heading mb-2">Pemberitahuan Penting</h6>
                                    <p class="mb-2">
                                        <strong>Absensi melalui kamera sudah tidak tersedia.</strong>
                                    </p>
                                    <p class="mb-0">
                                        Silakan gunakan mesin <strong>Fingerspot</strong> Didepan Ruang HRD untuk melakukan
                                        absensi check-in dan check-out.
                                        Hubungi HRD jika Anda mengalami kendala dengan mesin Fingerspot.
                                    </p>
                                </div>
                            </div>
                        </div>
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
                                <!-- Photo Preview from Fingerspot -->
                                <div class="row mt-3" id="photoPreviewContainer" style="display: none;">
                                    <div class="col-6" id="photoInContainer" style="display: none;">
                                        <div class="text-center">
                                            <small class="text-muted d-block mb-1">Foto Check In</small>
                                            <img id="photoInPreview" class="rounded border"
                                                style="max-width: 100%; max-height: 120px; cursor: pointer;"
                                                onclick="showFullPhoto(this.src, 'Foto Check In')">
                                        </div>
                                    </div>
                                    <div class="col-6" id="photoOutContainer" style="display: none;">
                                        <div class="text-center">
                                            <small class="text-muted d-block mb-1">Foto Check Out</small>
                                            <img id="photoOutPreview" class="rounded border"
                                                style="max-width: 100%; max-height: 120px; cursor: pointer;"
                                                onclick="showFullPhoto(this.src, 'Foto Check Out')">
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2" id="lateBadgeContainer" style="display: none;">
                                    <span class="badge bg-warning" id="lateBadge"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Camera functionality disabled - Hidden elements for JS compatibility -->
                        <div id="cameraSection" style="display: none;"></div>
                        <div id="startSection" style="display: none;"></div>
                        <video id="videoElement" style="display: none;"></video>
                        <canvas id="canvasElement" style="display: none;"></canvas>
                    </div>
                </div>

                <!-- Instructions Card -->
                <div class="card d-none d-lg-block">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            <i class='bx bx-fingerprint me-2'></i>
                            Petunjuk Absensi Fingerspot
                        </h6>
                        <ol class="mb-0 ps-3">
                            <li class="mb-2">Pastikan jari Anda <strong>bersih dan kering</strong></li>
                            <li class="mb-2">Tempelkan jari yang terdaftar pada mesin Fingerspot</li>
                            <li class="mb-2">Tunggu hingga mesin menampilkan <strong>pesan sukses</strong></li>
                            <li class="mb-2">Data absensi akan otomatis tercatat di sistem</li>
                            <li class="mb-2">Cek status absensi Anda di halaman ini atau <strong>Riwayat Absensi</strong>
                            </li>
                        </ol>
                        <div class="alert alert-info mt-3 mb-0">
                            <small>
                                <i class='bx bx-help-circle me-1'></i>
                                <strong>Masalah dengan Fingerspot?</strong> Hubungi HRD atau administrator
                                untuk bantuan pendaftaran sidik jari atau masalah teknis lainnya.
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
        <script>
            // Show full photo in SweetAlert
            function showFullPhoto(src, title) {
                Swal.fire({
                    title: title,
                    imageUrl: src,
                    imageAlt: title,
                    imageWidth: 400,
                    showCloseButton: true,
                    showConfirmButton: false
                });
            }

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

            // Hide GPS location info (not needed for Fingerspot)
            document.getElementById('gpsLocation').innerHTML =
                '<i class="bx bx-fingerprint text-primary"></i> Absensi via Fingerspot';

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

                        // Set avatar - photo or initial
                        const avatarContainer = document.getElementById('empAvatarContainer');
                        if (emp.profile_photo) {
                            avatarContainer.innerHTML = `
                                <img src="/storage/${emp.profile_photo}" alt="${emp.name}"
                                     class="rounded-circle"
                                     style="width: 48px; height: 48px; object-fit: cover;">
                            `;
                        } else {
                            const initial = emp.name.charAt(0).toUpperCase();
                            avatarContainer.innerHTML = `
                                <span class="avatar-initial rounded-circle bg-label-primary"
                                      style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                    ${initial}
                                </span>
                            `;
                        }

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

                        // Show photos if available (support Fingerspot S3 direct URL)
                        if (att.photo_in || att.photo_out) {
                            document.getElementById('photoPreviewContainer').style.display = 'flex';

                            if (att.photo_in) {
                                const photoIn = String(att.photo_in);
                                const isExternalIn = photoIn.startsWith('http://') || photoIn.startsWith('https://');
                                const photoInUrl = isExternalIn ? photoIn : '/storage/' + photoIn;

                                document.getElementById('photoInContainer').style.display = 'block';
                                document.getElementById('photoInPreview').src = photoInUrl;
                            }

                            if (att.photo_out) {
                                const photoOut = String(att.photo_out);
                                const isExternalOut = photoOut.startsWith('http://') || photoOut.startsWith('https://');
                                const photoOutUrl = isExternalOut ? photoOut : '/storage/' + photoOut;

                                document.getElementById('photoOutContainer').style.display = 'block';
                                document.getElementById('photoOutPreview').src = photoOutUrl;
                            }
                        }

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

            // Initialize
            loadEmployeeData();
        </script>
    @endpush
@endsection
