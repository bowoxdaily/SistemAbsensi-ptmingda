@extends('layouts.app')

@section('title', 'Absensi Manual')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">
                <span class="text-muted fw-light">Absensi /</span> Manual
            </h4>
        </div>

        <!-- Attendance Card -->
        <div class="row">
            <div class="col-xl-8 col-lg-7 mx-auto">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Absensi Manual Karyawan</h5>
                        <span class="badge bg-primary" id="currentTime"></span>
                    </div>
                    <div class="card-body">
                        <!-- Employee Info -->
                        <div class="mb-4" id="employeeInfo" style="display: none;">
                            <div class="alert alert-info">
                                <h6 class="alert-heading mb-2">Informasi Karyawan</h6>
                                <div class="d-flex align-items-center">
                                    <i class='bx bx-user-circle bx-lg me-3'></i>
                                    <div>
                                        <div class="fw-bold" id="empName"></div>
                                        <small id="empDetails"></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Scan Section -->
                        <div class="text-center mb-4" id="scanSection">
                            <div class="mb-3">
                                <label class="form-label">Cari atau Pilih Karyawan</label>
                                <select class="form-select searchable-select" id="employeeSelect">
                                    <option value="">-- Pilih Karyawan --</option>
                                </select>
                            </div>

                            <!-- Date Selection -->
                            <div class="mb-3">
                                <label class="form-label">Tanggal Absensi</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" id="attendanceDate" />
                                    <button type="button" class="btn btn-outline-primary" id="setTodayDate">
                                        <i class='bx bx-calendar me-1'></i>
                                        Hari Ini
                                    </button>
                                </div>
                                <div class="form-text">Pilih tanggal untuk input atau koreksi absensi</div>
                            </div>

                            <div id="attendanceForm" style="display: none;">
                                <!-- Check In Form -->
                                <div id="checkInForm" class="mb-3" style="display: none;">
                                    <div class="alert alert-info" id="statusLockedAlert" style="display: none;">
                                        <i class='bx bx-lock-alt me-2'></i>
                                        <strong>Status Terkunci:</strong> Status absensi tidak dapat diubah setelah data
                                        tersimpan.
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Status Absensi</label>
                                        <select class="form-select" id="attendanceStatus">
                                            <option value="hadir">Hadir</option>
                                            <option value="terlambat">Terlambat</option>
                                            <option value="izin">Izin</option>
                                            <option value="sakit">Sakit</option>
                                            <option value="cuti">Cuti</option>
                                            <option value="alpha">Alpha (Tanpa Keterangan)</option>
                                        </select>
                                        <div class="form-text" id="statusHelpText">Pilih status absensi karyawan</div>
                                    </div>
                                    <div class="row mb-3" id="checkInTimeSection">
                                        <div class="col-md-6">
                                            <label class="form-label">Jam Check In</label>
                                            <input type="time" class="form-control" id="checkInTimeInput" />
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="button" class="btn btn-sm btn-info w-100"
                                                id="setCurrentCheckInTime">
                                                <i class='bx bx-time me-1'></i>
                                                Jam Sekarang
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Catatan</label>
                                        <textarea class="form-control" id="checkInNotes" rows="2" placeholder="Masukkan catatan (opsional)"></textarea>
                                    </div>
                                    <div class="d-grid gap-2 mb-3">
                                        <button type="button" class="btn btn-success btn-lg" id="checkInBtn">
                                            <i class='bx bx-log-in me-2'></i>
                                            Simpan Absensi
                                        </button>
                                    </div>
                                </div>

                                <!-- Check Out Form -->
                                <div id="checkOutForm" class="mb-3" style="display: none;">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Jam Check Out</label>
                                            <input type="time" class="form-control" id="checkOutTimeInput" />
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="button" class="btn btn-sm btn-info w-100"
                                                id="setCurrentCheckOutTime">
                                                <i class='bx bx-time me-1'></i>
                                                Jam Sekarang
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Catatan Check Out</label>
                                        <textarea class="form-control" id="checkOutNotes" rows="2" placeholder="Masukkan catatan (opsional)"></textarea>
                                    </div>
                                    <div class="d-grid gap-2 mb-3">
                                        <button type="button" class="btn btn-warning btn-lg" id="checkOutBtn">
                                            <i class='bx bx-log-out me-2'></i>
                                            Check Out
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Today -->
                        <div id="statusToday" style="display: none;">
                            <div class="alert alert-success">
                                <h6 class="alert-heading mb-2">Status Absensi Hari Ini</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class='bx bx-log-in me-2'></i>
                                            <div>
                                                <small class="text-muted">Check In</small>
                                                <div class="fw-bold" id="checkInTime">-</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class='bx bx-log-out me-2'></i>
                                            <div>
                                                <small class="text-muted">Check Out</small>
                                                <div class="fw-bold" id="checkOutTime">-</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <span class="badge bg-label-primary" id="statusBadge"></span>
                                    <span class="badge bg-label-warning" id="lateBadge" style="display: none;"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Petunjuk Penggunaan</h6>
                        <ol class="mb-0">
                            <li>Pilih nama karyawan dari dropdown</li>
                            <li>Pilih tanggal absensi (default: hari ini)</li>
                            <li>Data karyawan akan ditampilkan secara otomatis</li>
                            <li>Klik tombol "Check In" untuk masuk</li>
                            <li>Klik tombol "Check Out" untuk pulang</li>
                            <li>Tambahkan catatan jika diperlukan (opsional)</li>
                            <li>Sistem akan mencatat waktu otomatis</li>
                        </ol>

                        <div class="mt-3">
                            <h6 class="card-title">Keterangan Status Absensi</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2">
                                            <span class="badge bg-success me-2">HADIR</span>
                                            <small>Check-in tepat waktu</small>
                                        </li>
                                        <li class="mb-2">
                                            <span class="badge bg-warning me-2">TERLAMBAT</span>
                                            <small>Check-in melewati jam masuk</small>
                                        </li>
                                        <li class="mb-2">
                                            <span class="badge bg-info me-2">IZIN</span>
                                            <small>Izin dengan keterangan</small>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2">
                                            <span class="badge bg-primary me-2">SAKIT</span>
                                            <small>Sakit dengan surat dokter</small>
                                        </li>
                                        <li class="mb-2">
                                            <span class="badge bg-secondary me-2">CUTI</span>
                                            <small>Cuti yang disetujui</small>
                                        </li>
                                        <li class="mb-2">
                                            <span class="badge bg-danger me-2">ALPHA</span>
                                            <small>Tidak hadir tanpa keterangan</small>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning mt-3 mb-0">
                            <i class='bx bx-info-circle me-2'></i>
                            <strong>Catatan:</strong> Pastikan karyawan dan tanggal yang dipilih sudah benar sebelum
                            melakukan absensi.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            let selectedEmployeeId = null;
            let allEmployees = [];
            let selectedDate = null;

            // Update current time
            function updateTime() {
                const now = new Date();
                document.getElementById('currentTime').textContent = now.toLocaleString('id-ID');
            }
            updateTime();
            setInterval(updateTime, 1000);

            // Set default date to today
            function setDefaultDate() {
                const today = new Date();
                const dateString = today.toISOString().split('T')[0];
                document.getElementById('attendanceDate').value = dateString;
                selectedDate = dateString;
            }
            setDefaultDate();

            // Set today date button
            document.getElementById('setTodayDate').addEventListener('click', function() {
                setDefaultDate();
                if (selectedEmployeeId) {
                    checkAttendanceByDate();
                }
            });

            // Date change handler
            document.getElementById('attendanceDate').addEventListener('change', function() {
                selectedDate = this.value;
                if (selectedEmployeeId) {
                    checkAttendanceByDate();
                }
            });

            // Status change handler - show/hide time input
            document.getElementById('attendanceStatus').addEventListener('change', function() {
                const status = this.value;
                const timeSection = document.getElementById('checkInTimeSection');
                const timeInput = document.getElementById('checkInTimeInput');

                // Only show time input for 'hadir' and 'terlambat'
                if (status === 'hadir' || status === 'terlambat') {
                    timeSection.style.display = 'flex';
                    timeInput.required = true;
                } else {
                    timeSection.style.display = 'none';
                    timeInput.required = false;
                    timeInput.value = ''; // Clear the value
                }
            });

            // Load employees
            async function loadEmployees() {
                try {
                    // Load ALL employees without pagination limit
                    const response = await fetch('/api/karyawan?per_page=1000&status=active');
                    const result = await response.json();

                    const select = document.getElementById('employeeSelect');
                    select.innerHTML = '<option value="">-- Pilih Karyawan --</option>';

                    if (result.data && result.data.data) {
                        allEmployees = result.data.data;
                        result.data.data.forEach(emp => {
                            const option = document.createElement('option');
                            option.value = emp.id;
                            option.textContent = `${emp.employee_code} - ${emp.name}`;
                            option.setAttribute('data-name', emp.name);
                            option.setAttribute('data-code', emp.employee_code);
                            select.appendChild(option);
                        });

                        // Initialize Select2
                        $('#employeeSelect').select2({
                            placeholder: '-- Cari dan Pilih Karyawan --',
                            allowClear: true,
                            language: {
                                noResults: function() {
                                    return "Tidak ada karyawan yang ditemukan";
                                },
                                searching: function() {
                                    return "Mencari...";
                                }
                            }
                        });

                        // Handle Select2 change event
                        $('#employeeSelect').on('change', async function() {
                            selectedEmployeeId = this.value;

                            if (selectedEmployeeId) {
                                try {
                                    const response = await fetch(`/api/karyawan/${selectedEmployeeId}`);
                                    const result = await response.json();
                                    const emp = result.data;

                                    document.getElementById('empName').textContent = emp.name;
                                    document.getElementById('empDetails').textContent =
                                        `${emp.employee_code} - ${emp.department.name} - ${emp.position.name}`;
                                    document.getElementById('employeeInfo').style.display = 'block';

                                    // Check attendance by selected date
                                    checkAttendanceByDate();
                                } catch (error) {
                                    console.error('Error loading employee:', error);
                                    Swal.fire('Error', 'Gagal memuat data karyawan', 'error');
                                }
                            } else {
                                document.getElementById('employeeInfo').style.display = 'none';
                                document.getElementById('attendanceForm').style.display = 'none';
                                document.getElementById('statusToday').style.display = 'none';
                            }
                        });
                    }
                } catch (error) {
                    console.error('Error loading employees:', error);
                    Swal.fire('Error', 'Gagal memuat data karyawan', 'error');
                }
            }

            // Check attendance by date
            async function checkAttendanceByDate() {
                if (!selectedEmployeeId || !selectedDate) {
                    return;
                }

                try {
                    const response = await fetch(
                        `/api/attendance/by-date/${selectedEmployeeId}?date=${selectedDate}`);
                    const result = await response.json();

                    document.getElementById('attendanceForm').style.display = 'block';

                    if (result.data) {
                        const att = result.data;
                        document.getElementById('statusToday').style.display = 'block';
                        document.getElementById('checkInTime').textContent = att.check_in || '-';
                        document.getElementById('checkOutTime').textContent = att.check_out || '-';

                        // Set status badge with appropriate color
                        const statusBadge = document.getElementById('statusBadge');
                        const statusText = att.status.toUpperCase();
                        statusBadge.textContent = statusText;

                        // Remove all badge color classes
                        statusBadge.className = 'badge';

                        // Add appropriate color based on status
                        switch (att.status.toLowerCase()) {
                            case 'hadir':
                                statusBadge.classList.add('bg-success');
                                break;
                            case 'terlambat':
                                statusBadge.classList.add('bg-warning');
                                break;
                            case 'izin':
                                statusBadge.classList.add('bg-info');
                                break;
                            case 'sakit':
                                statusBadge.classList.add('bg-primary');
                                break;
                            case 'cuti':
                                statusBadge.classList.add('bg-secondary');
                                break;
                            case 'alpha':
                                statusBadge.classList.add('bg-danger');
                                break;
                            default:
                                statusBadge.classList.add('bg-label-primary');
                        }

                        if (att.late_minutes > 0) {
                            document.getElementById('lateBadge').textContent =
                                `Terlambat ${att.late_minutes} menit`;
                            document.getElementById('lateBadge').style.display = 'inline-block';
                        } else {
                            document.getElementById('lateBadge').style.display = 'none';
                        }

                        // Show/hide check-in and check-out forms
                        if (att.check_in && att.check_out) {
                            // Sudah check-in dan check-out - sembunyikan semua form
                            document.getElementById('checkInForm').style.display = 'none';
                            document.getElementById('checkOutForm').style.display = 'none';
                        } else if (att.check_in || att.status) {
                            // Sudah ada data absensi (check-in atau status sudah tercatat)
                            // Jika status bukan hadir/terlambat, tidak perlu form check-out
                            const statusesWithoutCheckOut = ['alpha', 'cuti', 'sakit', 'izin'];

                            if (statusesWithoutCheckOut.includes(att.status.toLowerCase())) {
                                // Status tanpa check-out, sembunyikan semua form
                                document.getElementById('checkInForm').style.display = 'none';
                                document.getElementById('checkOutForm').style.display = 'none';
                            } else {
                                // Status hadir/terlambat, tampilkan form check-out
                                document.getElementById('checkInForm').style.display = 'none';
                                document.getElementById('checkOutForm').style.display = 'block';
                            }
                        } else {
                            // Belum check-in, tampilkan form check-in
                            document.getElementById('checkInForm').style.display = 'block';
                            document.getElementById('checkOutForm').style.display = 'none';
                        }
                    } else {
                        // Tidak ada data attendance - tampilkan form check-in
                        document.getElementById('statusToday').style.display = 'none';
                        document.getElementById('checkInForm').style.display = 'block';
                        document.getElementById('checkOutForm').style.display = 'none';

                        // Reset dan enable status dropdown untuk input baru
                        const statusDropdown = document.getElementById('attendanceStatus');
                        statusDropdown.disabled = false;
                        statusDropdown.value = 'hadir';

                        // Hide locked alert
                        document.getElementById('statusLockedAlert').style.display = 'none';

                        // Show time section for default 'hadir'
                        const timeSection = document.getElementById('checkInTimeSection');
                        timeSection.style.display = 'flex';
                    }
                } catch (error) {
                    console.error('Error checking attendance:', error);
                    // Jika error, tampilkan form check-in
                    document.getElementById('attendanceForm').style.display = 'block';
                    document.getElementById('checkInForm').style.display = 'block';
                    document.getElementById('checkOutForm').style.display = 'none';
                    document.getElementById('statusToday').style.display = 'none';
                }
            }

            // Check In
            document.getElementById('checkInBtn').addEventListener('click', async function() {
                if (!selectedEmployeeId) {
                    Swal.fire('Error', 'Pilih karyawan terlebih dahulu', 'error');
                    return;
                }

                const status = document.getElementById('attendanceStatus').value;
                const checkInTime = document.getElementById('checkInTimeInput').value;

                // Validate time input only for 'hadir' and 'terlambat'
                if ((status === 'hadir' || status === 'terlambat') && !checkInTime) {
                    Swal.fire('Error', 'Masukkan jam check in terlebih dahulu', 'error');
                    return;
                }

                try {
                    const notes = document.getElementById('checkInNotes').value;
                    const status = document.getElementById('attendanceStatus').value;

                    const response = await fetch('/api/attendance/check-in', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            employee_id: selectedEmployeeId,
                            date: selectedDate,
                            check_in_time: checkInTime,
                            status: status,
                            notes: notes
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        toastr.success(result.message || 'Absensi berhasil dicatat');

                        // Clear form
                        document.getElementById('checkInNotes').value = '';
                        document.getElementById('checkInTimeInput').value = '';

                        // Disable status dropdown after successful save
                        const statusDropdown = document.getElementById('attendanceStatus');
                        statusDropdown.disabled = true;

                        // Show locked alert
                        document.getElementById('statusLockedAlert').style.display = 'block';

                        // Reload attendance data
                        checkAttendanceByDate();
                    } else {
                        Swal.fire('Error', result.message || 'Gagal melakukan check-in', 'error');
                    }
                } catch (error) {
                    console.error('Check-in error:', error);
                    Swal.fire('Error', 'Gagal melakukan check-in: ' + error.message, 'error');
                }
            });

            // Check Out
            document.getElementById('checkOutBtn').addEventListener('click', async function() {
                if (!selectedEmployeeId) {
                    Swal.fire('Error', 'Pilih karyawan terlebih dahulu', 'error');
                    return;
                }

                const checkOutTime = document.getElementById('checkOutTimeInput').value;
                if (!checkOutTime) {
                    Swal.fire('Error', 'Masukkan jam check out terlebih dahulu', 'error');
                    return;
                }

                try {
                    const notes = document.getElementById('checkOutNotes').value;

                    const response = await fetch('/api/attendance/check-out', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            employee_id: selectedEmployeeId,
                            date: selectedDate,
                            check_out_time: checkOutTime,
                            notes: notes
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        toastr.success(result.message || 'Check out berhasil dicatat');
                        document.getElementById('checkOutNotes').value = '';
                        document.getElementById('checkOutTimeInput').value = '';
                        checkAttendanceByDate();
                    } else {
                        Swal.fire('Error', result.message || 'Gagal melakukan check-out', 'error');
                    }
                } catch (error) {
                    console.error('Check-out error:', error);
                    Swal.fire('Error', 'Gagal melakukan check-out: ' + error.message, 'error');
                }
            });

            // Set current time for check in
            document.getElementById('setCurrentCheckInTime').addEventListener('click', function() {
                const now = new Date();
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                document.getElementById('checkInTimeInput').value = `${hours}:${minutes}`;
            });

            // Set current time for check out
            document.getElementById('setCurrentCheckOutTime').addEventListener('click', function() {
                const now = new Date();
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                document.getElementById('checkOutTimeInput').value = `${hours}:${minutes}`;
            });

            // Initialize
            loadEmployees();
        </script>
    @endpush
@endsection
