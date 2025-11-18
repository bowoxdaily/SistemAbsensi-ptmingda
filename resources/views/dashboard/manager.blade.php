@extends('layouts.app')

@section('title', 'Dashboard Manager')

@section('content')
    @if (isset($message))
        <div class="alert alert-warning">
            {{ $message }}
        </div>
    @else
        <div class="row">
            <!-- Welcome Card -->
            <div class="col-lg-8 mb-4 order-0">
                <div class="card">
                    <div class="d-flex align-items-end row">
                        <div class="col-sm-7">
                            <div class="card-body">
                                <h5 class="card-title text-primary">Selamat Datang, {{ Auth::user()->name }}! 👨‍💼</h5>
                                <p class="mb-4">
                                    <strong>Departemen:</strong> {{ $employee->department->name ?? '-' }}<br>
                                    <strong>Jabatan:</strong> {{ $employee->position->name ?? '-' }}<br>
                                    <span class="text-muted">Kelola tim Anda dengan efektif</span>
                                </p>
                                <a href="{{ route('absensi.index') }}" class="btn btn-sm btn-outline-primary">Lihat Absensi
                                    Tim</a>
                            </div>
                        </div>
                        <div class="col-sm-5 text-center text-sm-left">
                            <div class="card-body pb-0 px-0 px-md-4">
                                <img src="{{ asset('sneat-1.0.0/assets/img/illustrations/man-with-laptop-light.png') }}"
                                    height="140" alt="Manager Dashboard" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistik Tim Hari Ini -->
            <div class="col-lg-4 col-md-4 order-1">
                <div class="row">
                    <div class="col-lg-6 col-md-12 col-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="card-title d-flex align-items-start justify-content-between">
                                    <div class="avatar flex-shrink-0">
                                        <i class="bx bx-user-check bx-md text-success"></i>
                                    </div>
                                </div>
                                <span class="fw-semibold d-block mb-1">Hadir</span>
                                <h3 class="card-title mb-2">{{ $hadirHariIni }}</h3>
                                <small class="text-success fw-semibold">Tim Anda</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="card-title d-flex align-items-start justify-content-between">
                                    <div class="avatar flex-shrink-0">
                                        <i class="bx bx-user-x bx-md text-danger"></i>
                                    </div>
                                </div>
                                <span class="fw-semibold d-block mb-1">Tidak Hadir</span>
                                <h3 class="card-title mb-2">{{ $tidakHadirHariIni }}</h3>
                                <small class="text-danger fw-semibold">Tim Anda</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Statistik Tim -->
            <div class="col-12 col-md-8 col-lg-4 order-3 order-md-2">
                <div class="row">
                    <div class="col-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="card-title d-flex align-items-start justify-content-between">
                                    <div class="avatar flex-shrink-0">
                                        <i class="bx bx-group bx-md text-primary"></i>
                                    </div>
                                </div>
                                <span>Anggota Tim</span>
                                <h3 class="card-title text-nowrap mb-1">{{ $totalTeamMembers }}</h3>
                                <small class="text-success fw-semibold">Aktif</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="card-title d-flex align-items-start justify-content-between">
                                    <div class="avatar flex-shrink-0">
                                        <i class="bx bx-calendar-event bx-md text-warning"></i>
                                    </div>
                                </div>
                                <span>Cuti Pending</span>
                                <h3 class="card-title text-nowrap mb-1">{{ $cutiPending }}</h3>
                                <small class="text-warning fw-semibold">Perlu Approval</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="card-title d-flex align-items-start justify-content-between">
                                    <div class="avatar flex-shrink-0">
                                        <i class="bx bx-building bx-md text-info"></i>
                                    </div>
                                </div>
                                <span>Departemen</span>
                                <h5 class="card-title text-nowrap mb-1">{{ $employee->department->name ?? '-' }}</h5>
                                <small
                                    class="text-muted fw-semibold">{{ $employee->department->description ?? '' }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Absensi Tim Hari Ini -->
            <div class="col-12 col-md-8 col-lg-8 order-2 order-md-3 order-lg-2 mb-4">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title m-0 me-2">Absensi Tim Hari Ini</h5>
                        <a href="{{ route('absensi.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive text-nowrap">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Karyawan</th>
                                        <th>Jabatan</th>
                                        <th>Jam Masuk</th>
                                        <th>Jam Keluar</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0">
                                    @forelse($absensiTeam as $absensi)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-sm me-3">
                                                        <img src="{{ $absensi->employee->profile_photo ?? asset('sneat-1.0.0/assets/img/avatars/1.png') }}"
                                                            alt="Avatar" class="rounded-circle">
                                                    </div>
                                                    <div>
                                                        <strong>{{ $absensi->employee->name }}</strong><br>
                                                        <small
                                                            class="text-muted">{{ $absensi->employee->employee_code }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $absensi->employee->position->name ?? '-' }}</td>
                                            <td>{{ $absensi->check_in ? \Carbon\Carbon::parse($absensi->check_in)->format('H:i') : '-' }}
                                            </td>
                                            <td>{{ $absensi->check_out ? \Carbon\Carbon::parse($absensi->check_out)->format('H:i') : '-' }}
                                            </td>
                                            <td>
                                                @if ($absensi->status == 'hadir')
                                                    <span class="badge bg-label-success">Hadir</span>
                                                @elseif($absensi->status == 'terlambat')
                                                    <span class="badge bg-label-warning">Terlambat
                                                        ({{ $absensi->late_minutes }} menit)</span>
                                                @elseif($absensi->status == 'izin')
                                                    <span class="badge bg-label-info">Izin</span>
                                                @elseif($absensi->status == 'sakit')
                                                    <span class="badge bg-label-secondary">Sakit</span>
                                                @else
                                                    <span
                                                        class="badge bg-label-danger">{{ ucfirst($absensi->status) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">Belum ada data absensi hari ini</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pengajuan Cuti Pending & Daftar Tim -->
        <div class="row">
            <div class="col-md-6 col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title m-0">Pengajuan Cuti Pending</h5>
                    </div>
                    <div class="card-body">
                        @forelse($cutiPendingList as $cuti)
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div>
                                    <h6 class="mb-1">{{ $cuti->employee->name }}</h6>
                                    <small class="text-muted">{{ ucfirst($cuti->leave_type) }} - {{ $cuti->total_days }}
                                        hari</small><br>
                                    <small
                                        class="text-muted">{{ \Carbon\Carbon::parse($cuti->start_date)->format('d M Y') }}
                                        - {{ \Carbon\Carbon::parse($cuti->end_date)->format('d M Y') }}</small><br>
                                    <small><strong>Alasan:</strong> {{ Str::limit($cuti->reason, 50) }}</small>
                                </div>
                                <div>
                                    <span class="badge bg-label-warning">Pending</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-muted">Tidak ada pengajuan cuti pending</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Daftar Anggota Tim -->
            <div class="col-md-6 col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Anggota Tim</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Jabatan</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($teamMembers as $member)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-xs me-2">
                                                        <img src="{{ $member->profile_photo ?? asset('sneat-1.0.0/assets/img/avatars/1.png') }}"
                                                            alt="Avatar" class="rounded-circle">
                                                    </div>
                                                    <small>{{ $member->name }}</small>
                                                </div>
                                            </td>
                                            <td><small>{{ $member->position->name ?? '-' }}</small></td>
                                            <td>
                                                @if ($member->status == 'active')
                                                    <span class="badge badge-sm bg-label-success">Aktif</span>
                                                @else
                                                    <span
                                                        class="badge badge-sm bg-label-secondary">{{ ucfirst($member->status) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center">Tidak ada data tim</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
