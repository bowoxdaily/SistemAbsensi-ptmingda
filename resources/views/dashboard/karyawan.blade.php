@extends('layouts.app')

@section('title', 'Dashboard Karyawan')

@section('content')
    @if (isset($message))
        <div class="alert alert-warning">
            {{ $message }}
        </div>
    @else
        <div class="row">
            <!-- Welcome Card & Absen Hari Ini -->
            <div class="col-lg-8 mb-4 order-0">
                <div class="card">
                    <div class="d-flex align-items-end row">
                        <div class="col-sm-7">
                            <div class="card-body">
                                <h5 class="card-title text-primary">Selamat Datang, {{ Auth::user()->name }}! 👋</h5>
                                <p class="mb-2">
                                    <strong>NIK:</strong> {{ $employee->nik ?? '-' }}<br>
                                    <strong>Kode:</strong> {{ $employee->employee_code }}<br>
                                    <strong>Departemen:</strong> {{ $employee->department->name ?? '-' }}<br>
                                    <strong>Jabatan:</strong> {{ $employee->position->name ?? '-' }}
                                </p>

                                @if ($absensiHariIni)
                                    <div class="alert alert-success p-2 mb-2">
                                        <small><i class="bx bx-check-circle"></i> Anda sudah check-in hari ini</small><br>
                                        <small><strong>Masuk:</strong>
                                            {{ \Carbon\Carbon::parse($absensiHariIni->check_in)->format('H:i') }}</small>
                                        @if ($absensiHariIni->check_out)
                                            <small class="ms-2"><strong>Keluar:</strong>
                                                {{ \Carbon\Carbon::parse($absensiHariIni->check_out)->format('H:i') }}</small>
                                        @endif
                                    </div>

                                    @if (!$absensiHariIni->check_out)
                                        <div class="alert alert-warning p-2 mb-2">
                                            <small><i class="bx bx-log-out"></i> Jangan lupa check-out sebelum
                                                pulang</small>
                                        </div>
                                        <a href="{{ route('employee.attendance.index') }}" class="btn btn-sm btn-warning">
                                            <i class="bx bx-log-out"></i> Check-Out Sekarang
                                        </a>
                                    @else
                                        <a href="{{ route('employee.attendance.index') }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="bx bx-history"></i> Lihat Absensi
                                        </a>
                                    @endif
                                @else
                                    <div class="alert alert-warning p-2 mb-2">
                                        <small><i class="bx bx-time"></i> Anda belum check-in hari ini</small>
                                    </div>
                                    <a href="{{ route('employee.attendance.index') }}" class="btn btn-sm btn-primary">
                                        <i class="bx bx-fingerprint"></i> Check-In Sekarang
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-5 text-center text-sm-left">
                            <div class="card-body pb-0 px-0 px-md-4">
                                <img src="{{ asset('sneat-1.0.0/assets/img/illustrations/man-with-laptop-light.png') }}"
                                    height="120" alt="Karyawan Dashboard" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistik Bulan Ini -->
            <div class="col-lg-4 col-md-4 order-1">
                <div class="row">
                    <div class="col-lg-6 col-md-12 col-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="card-title d-flex align-items-start justify-content-between">
                                    <div class="avatar flex-shrink-0">
                                        <i class="bx bx-calendar-check bx-md text-success"></i>
                                    </div>
                                </div>
                                <span class="fw-semibold d-block mb-1">Hadir</span>
                                <h3 class="card-title mb-2">{{ $totalHadirBulanIni }}</h3>
                                <small class="text-success fw-semibold">Bulan Ini</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="card-title d-flex align-items-start justify-content-between">
                                    <div class="avatar flex-shrink-0">
                                        <i class="bx bx-time-five bx-md text-warning"></i>
                                    </div>
                                </div>
                                <span class="fw-semibold d-block mb-1">Terlambat</span>
                                <h3 class="card-title mb-2">{{ $totalTerlambatBulanIni }}</h3>
                                <small class="text-warning fw-semibold">Bulan Ini</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Info Cuti -->
            <div class="col-12 col-md-8 col-lg-4 order-3 order-md-2">
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="card-title d-flex align-items-start justify-content-between">
                                    <div class="avatar flex-shrink-0">
                                        <i class="bx bx-calendar-event bx-md text-info"></i>
                                    </div>
                                </div>
                                <span>Sisa Cuti Tahunan</span>
                                <h3 class="card-title text-nowrap mb-1">{{ $cutiTersedia - $cutiTerpakai }} Hari</h3>
                                <small class="text-muted fw-semibold">Dari {{ $cutiTersedia }} hari</small>
                                <div class="progress mt-2" style="height: 6px;">
                                    <div class="progress-bar bg-info"
                                        style="width: {{ (($cutiTersedia - $cutiTerpakai) / $cutiTersedia) * 100 }}%">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="card-title d-flex align-items-start justify-content-between">
                                    <div class="avatar flex-shrink-0">
                                        <i class="bx bx-user-x bx-md text-danger"></i>
                                    </div>
                                </div>
                                <span>Izin/Sakit</span>
                                <h3 class="card-title text-nowrap mb-1">{{ $totalIzinBulanIni }} Hari</h3>
                                <small class="text-danger fw-semibold">Bulan Ini</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Riwayat Absensi -->
            <div class="col-12 col-md-8 col-lg-8 order-2 order-md-3 order-lg-2 mb-4">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h5 class="card-title m-0 me-2">
                            <span class="d-none d-sm-inline">Riwayat Absensi Terbaru</span>
                            <span class="d-sm-none">Riwayat Absensi</span>
                        </h5>
                        <a href="{{ route('employee.attendance.history') }}" class="btn btn-sm btn-outline-primary">
                            <span class="d-none d-sm-inline">Lihat Semua</span>
                            <span class="d-sm-none">Semua</span>
                        </a>
                    </div>
                    <div class="card-body">
                        <!-- Desktop Table View -->
                        <div class="table-responsive text-nowrap d-none d-md-block">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Jam Masuk</th>
                                        <th>Jam Keluar</th>
                                        <th>Status</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0">
                                    @forelse($riwayatAbsensi as $absensi)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($absensi->attendance_date)->translatedFormat('d M Y') }}
                                            </td>
                                            <td>{{ $absensi->check_in ? \Carbon\Carbon::parse($absensi->check_in)->format('H:i') : '-' }}
                                            </td>
                                            <td>{{ $absensi->check_out ? \Carbon\Carbon::parse($absensi->check_out)->format('H:i') : '-' }}
                                            </td>
                                            <td>
                                                @if ($absensi->status == 'hadir')
                                                    <span class="badge bg-label-success">Hadir</span>
                                                @elseif($absensi->status == 'terlambat')
                                                    <span class="badge bg-label-warning">Terlambat</span>
                                                @elseif($absensi->status == 'izin')
                                                    <span class="badge bg-label-info">Izin</span>
                                                @elseif($absensi->status == 'sakit')
                                                    <span class="badge bg-label-secondary">Sakit</span>
                                                @elseif($absensi->status == 'cuti')
                                                    <span class="badge bg-label-primary">Cuti</span>
                                                @else
                                                    <span
                                                        class="badge bg-label-danger">{{ ucfirst($absensi->status) }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($absensi->status == 'terlambat')
                                                    <small class="text-warning">{{ $absensi->late_minutes }} menit</small>
                                                @elseif($absensi->notes)
                                                    <small>{{ Str::limit($absensi->notes, 30) }}</small>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">Belum ada riwayat absensi</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card View -->
                        <div class="d-md-none">
                            @forelse($riwayatAbsensi as $absensi)
                                <div class="card mb-3 shadow-sm">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="mb-1">
                                                    {{ \Carbon\Carbon::parse($absensi->attendance_date)->translatedFormat('d M Y') }}
                                                </h6>
                                                <small class="text-muted">
                                                    <i class='bx bx-calendar'></i>
                                                    {{ \Carbon\Carbon::parse($absensi->attendance_date)->translatedFormat('l') }}
                                                </small>
                                            </div>
                                            <div>
                                                @if ($absensi->status == 'hadir')
                                                    <span class="badge bg-success">Hadir</span>
                                                @elseif($absensi->status == 'terlambat')
                                                    <span class="badge bg-warning">Terlambat</span>
                                                @elseif($absensi->status == 'izin')
                                                    <span class="badge bg-info">Izin</span>
                                                @elseif($absensi->status == 'sakit')
                                                    <span class="badge bg-secondary">Sakit</span>
                                                @elseif($absensi->status == 'cuti')
                                                    <span class="badge bg-primary">Cuti</span>
                                                @else
                                                    <span class="badge bg-danger">{{ ucfirst($absensi->status) }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="row g-2">
                                            <div class="col-6">
                                                <div class="border rounded p-2 text-center">
                                                    <small class="text-muted d-block">Masuk</small>
                                                    <strong class="text-success">
                                                        {{ $absensi->check_in ? \Carbon\Carbon::parse($absensi->check_in)->format('H:i') : '-' }}
                                                    </strong>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="border rounded p-2 text-center">
                                                    <small class="text-muted d-block">Keluar</small>
                                                    <strong class="text-warning">
                                                        {{ $absensi->check_out ? \Carbon\Carbon::parse($absensi->check_out)->format('H:i') : '-' }}
                                                    </strong>
                                                </div>
                                            </div>
                                        </div>

                                        @if ($absensi->status == 'terlambat' || $absensi->notes)
                                            <div class="mt-2">
                                                @if ($absensi->status == 'terlambat')
                                                    <span class="badge bg-label-warning">
                                                        <i class='bx bx-time'></i> Terlambat {{ $absensi->late_minutes }}
                                                        menit
                                                    </span>
                                                @endif
                                                @if ($absensi->notes)
                                                    <small class="text-muted d-block mt-1">
                                                        <i class='bx bx-note'></i> {{ Str::limit($absensi->notes, 50) }}
                                                    </small>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4">
                                    <i class='bx bx-calendar-x' style="font-size: 48px; color: #ccc;"></i>
                                    <p class="text-muted mt-2 mb-0">Belum ada riwayat absensi</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pengajuan Cuti & Quick Actions -->
        <div class="row">
            <div class="col-md-6 col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h5 class="card-title m-0">
                            <span class="d-none d-sm-inline">Pengajuan Cuti Anda</span>
                            <span class="d-sm-none">Pengajuan Cuti</span>
                        </h5>
                        <div>
                            <a href="{{ route('employee.leave.index') }}" class="btn btn-sm btn-outline-primary me-2">
                                <span class="d-none d-sm-inline">Lihat Semua</span>
                                <span class="d-sm-none">Semua</span>
                            </a>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalCuti">
                                <i class="bx bx-plus"></i>
                                <span class="d-none d-sm-inline">Ajukan Cuti</span>
                                <span class="d-sm-none">Ajukan</span>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        @forelse($cutiPending as $cuti)
                            <div class="mb-3 pb-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1 me-2">
                                        <h6 class="mb-1">
                                            @if ($cuti->leave_type == 'cuti')
                                                <i class='bx bx-calendar text-primary'></i>
                                            @elseif($cuti->leave_type == 'izin')
                                                <i class='bx bx-time-five text-info'></i>
                                            @else
                                                <i class='bx bx-first-aid text-secondary'></i>
                                            @endif
                                            {{ ucfirst($cuti->leave_type) }} - {{ $cuti->total_days }} hari
                                        </h6>
                                        <small class="text-muted">
                                            <i class='bx bx-calendar-check'></i>
                                            {{ \Carbon\Carbon::parse($cuti->start_date)->format('d M Y') }}
                                            - {{ \Carbon\Carbon::parse($cuti->end_date)->format('d M Y') }}
                                        </small><br>
                                        <small><strong>Alasan:</strong> {{ Str::limit($cuti->reason, 50) }}</small>

                                        @if ($cuti->attachment)
                                            <br><small class="text-muted">
                                                <i class='bx bx-paperclip'></i> Ada lampiran
                                            </small>
                                        @endif
                                    </div>
                                    <div class="flex-shrink-0">
                                        @if ($cuti->status == 'pending')
                                            <span class="badge bg-warning">
                                                <i class='bx bx-time'></i> Pending
                                            </span>
                                        @elseif($cuti->status == 'approved')
                                            <span class="badge bg-success">
                                                <i class='bx bx-check-circle'></i> Disetujui
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                <i class='bx bx-x-circle'></i> Ditolak
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                @if ($cuti->status == 'approved')
                                    <div class="mt-2">
                                        <div class="alert alert-success p-2 mb-0">
                                            <small>
                                                <i class='bx bx-info-circle'></i>
                                                <strong>Info:</strong> Absensi Anda akan otomatis tercatat sebagai
                                                <strong>"{{ ucfirst($cuti->leave_type) }}"</strong> oleh sistem pada
                                                tanggal cuti.
                                            </small>
                                        </div>
                                    </div>
                                @elseif($cuti->status == 'pending')
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-outline-danger cancel-leave"
                                            data-id="{{ $cuti->id }}">
                                            <i class='bx bx-x'></i> Batalkan
                                        </button>
                                    </div>
                                @elseif($cuti->status == 'rejected' && $cuti->rejection_reason)
                                    <div class="mt-2">
                                        <div class="alert alert-danger p-2 mb-0">
                                            <small>
                                                <i class='bx bx-error-circle'></i>
                                                <strong>Alasan Ditolak:</strong> {{ $cuti->rejection_reason }}
                                            </small>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-3">
                                <i class='bx bx-calendar-event' style="font-size: 48px; color: #ccc;"></i>
                                <p class="text-muted mt-2 mb-0">Tidak ada pengajuan cuti</p>
                                <small class="text-muted">Klik "Ajukan Cuti" untuk mengajukan cuti/izin/sakit</small>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Grafik Kehadiran Bulan Ini -->
            <div class="col-md-6 col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Statistik Kehadiran Bulan Ini</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="chartAbsensiKaryawan" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Absen -->
        <div class="modal fade" id="modalAbsen" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Absen Sekarang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="#" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control" required>
                                    <option value="hadir">Hadir</option>
                                    <option value="izin">Izin</option>
                                    <option value="sakit">Sakit</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Catatan (Opsional)</label>
                                <textarea name="notes" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Foto (Opsional)</label>
                                <input type="file" name="photo_in" class="form-control" accept="image/*">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Absensi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Ajukan Cuti -->
        <div class="modal fade" id="modalCuti" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ajukan Cuti/Izin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="dashboardLeaveForm" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Jenis <span class="text-danger">*</span></label>
                                <select name="leave_type" class="form-select @error('leave_type') is-invalid @enderror"
                                    required>
                                    <option value="">Pilih Jenis</option>
                                    <option value="cuti" {{ old('leave_type') == 'cuti' ? 'selected' : '' }}>Cuti
                                    </option>
                                    <option value="izin" {{ old('leave_type') == 'izin' ? 'selected' : '' }}>Izin
                                    </option>
                                    <option value="sakit" {{ old('leave_type') == 'sakit' ? 'selected' : '' }}>Sakit
                                    </option>
                                </select>
                                @error('leave_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                    <input type="date" name="start_date"
                                        class="form-control @error('start_date') is-invalid @enderror"
                                        value="{{ old('start_date', date('Y-m-d')) }}" min="{{ date('Y-m-d') }}"
                                        required>
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                                    <input type="date" name="end_date"
                                        class="form-control @error('end_date') is-invalid @enderror"
                                        value="{{ old('end_date', date('Y-m-d')) }}" min="{{ date('Y-m-d') }}" required>
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alasan <span class="text-danger">*</span></label>
                                <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="3"
                                    placeholder="Jelaskan alasan cuti/izin Anda..." required>{{ old('reason') }}</textarea>
                                @error('reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Lampiran (Opsional)</label>
                                <input type="file" name="attachment"
                                    class="form-control @error('attachment') is-invalid @enderror"
                                    accept=".jpg,.jpeg,.png,.pdf">
                                <small class="text-muted">Format: JPG, PNG, PDF. Max 2MB. Upload surat keterangan jika
                                    sakit.</small>
                                @error('attachment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="alert alert-info mb-2">
                                <small><i class='bx bx-info-circle'></i> <strong>Catatan Penting:</strong></small><br>
                                <small>✓ Pengajuan akan diproses oleh admin</small><br>
                                <small>✓ Anda akan menerima notifikasi setelah disetujui/ditolak</small><br>
                                <small>✓ Sisa cuti tahunan Anda: <strong>{{ $cutiTersedia - $cutiTerpakai }}
                                        hari</strong></small>
                            </div>

                            <div class="alert alert-success mb-0">
                                <small><i class='bx bx-check-shield'></i> <strong>Sistem Otomatis:</strong></small><br>
                                <small>Jika cuti/izin/sakit Anda <strong>disetujui</strong>, sistem akan otomatis mencatat
                                    absensi Anda dengan status sesuai jenis cuti pada tanggal yang diajukan.</small><br>
                                <small class="text-success"><strong>✓ Tidak perlu absen manual</strong> saat cuti
                                    approved!</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <i class='bx bx-send'></i> Ajukan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Chart Statistik Bulan Ini
        const ctx = document.getElementById('chartAbsensiKaryawan');
        if (ctx) {
            const labels = {!! json_encode($statistikBulanIni['labels'] ?? []) !!};
            const statuses = {!! json_encode($statistikBulanIni['status'] ?? []) !!};

            // Convert status to numeric data
            const hadirData = statuses.map(s => ['hadir', 'terlambat'].includes(s) ? 1 : 0);
            const izinData = statuses.map(s => ['izin', 'sakit', 'cuti'].includes(s) ? 1 : 0);
            const alphaData = statuses.map(s => s === 'alpha' ? 1 : 0);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Hadir',
                        data: hadirData,
                        backgroundColor: 'rgba(75, 192, 192, 0.8)',
                    }, {
                        label: 'Izin/Sakit',
                        data: izinData,
                        backgroundColor: 'rgba(255, 206, 86, 0.8)',
                    }, {
                        label: 'Alpha',
                        data: alphaData,
                        backgroundColor: 'rgba(255, 99, 132, 0.8)',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 1,
                            ticks: {
                                stepSize: 1,
                                callback: function(value) {
                                    return value === 1 ? 'Ada' : 'Tidak';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Dashboard Leave Form
        $('#dashboardLeaveForm').on('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();

            submitBtn.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i> Mengirim...');

            $.ajax({
                url: '/api/employee/leave',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message || 'Pengajuan cuti berhasil dikirim');
                        $('#modalCuti').modal('hide');
                        $('#dashboardLeaveForm')[0].reset();
                        setTimeout(() => location.reload(), 1500);
                    }
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).html(originalText);

                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(key => {
                            toastr.error(errors[key][0]);
                        });
                    } else if (xhr.status === 403) {
                        toastr.error('Anda tidak memiliki akses untuk melakukan aksi ini');
                    } else {
                        toastr.error(xhr.responseJSON?.message ||
                            'Terjadi kesalahan saat mengajukan cuti');
                    }
                }
            });
        });

        // Cancel Leave from Dashboard
        $(document).on('click', '.cancel-leave', function() {
            const leaveId = $(this).data('id');
            const button = $(this);

            Swal.fire({
                title: 'Yakin ingin membatalkan?',
                text: "Pengajuan cuti akan dibatalkan",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Batalkan!',
                cancelButtonText: 'Tidak'
            }).then((result) => {
                if (result.isConfirmed) {
                    button.prop('disabled', true);

                    $.ajax({
                        url: `/api/employee/leave/${leaveId}`,
                        type: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message ||
                                    'Pengajuan cuti berhasil dibatalkan');
                                setTimeout(() => location.reload(), 1500);
                            }
                        },
                        error: function(xhr) {
                            button.prop('disabled', false);
                            toastr.error(xhr.responseJSON?.message ||
                                'Terjadi kesalahan saat membatalkan cuti');
                        }
                    });
                }
            });
        });

        // Reset form when modal closed
        $('#modalCuti').on('hidden.bs.modal', function() {
            $('#dashboardLeaveForm')[0].reset();
            $('#dashboardLeaveForm').find('.is-invalid').removeClass('is-invalid');
        });
    </script>
@endpush
