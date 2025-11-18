@extends('layouts.app')

@section('title', 'Riwayat Pengajuan Cuti')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">
                <span class="text-muted fw-light">Karyawan /</span> Riwayat Pengajuan Cuti
            </h4>
            <div>
                <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary me-2">
                    <i class='bx bx-arrow-back'></i> Kembali
                </a>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalCuti">
                    <i class='bx bx-plus'></i> Ajukan Cuti Baru
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="fw-semibold d-block mb-1">Menunggu</span>
                                <h3 class="card-title mb-0">{{ $stats['pending'] }}</h3>
                            </div>
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-warning">
                                    <i class='bx bx-time-five bx-sm'></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="fw-semibold d-block mb-1">Disetujui</span>
                                <h3 class="card-title mb-0">{{ $stats['approved'] }}</h3>
                            </div>
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class='bx bx-check-circle bx-sm'></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="fw-semibold d-block mb-1">Ditolak</span>
                                <h3 class="card-title mb-0">{{ $stats['rejected'] }}</h3>
                            </div>
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-danger">
                                    <i class='bx bx-x-circle bx-sm'></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-12 col-sm-12 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="fw-semibold d-block mb-1">Cuti Terpakai</span>
                                <h3 class="card-title mb-0">{{ $stats['total_days_approved'] }}</h3>
                                <small class="text-muted">Hari (Tahun Ini)</small>
                            </div>
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class='bx bx-calendar bx-sm'></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leave History Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Riwayat Pengajuan Cuti & Izin</h5>
            </div>

            <!-- Desktop Table View -->
            <div class="table-responsive text-nowrap d-none d-md-block">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tanggal Pengajuan</th>
                            <th>Jenis</th>
                            <th>Periode</th>
                            <th>Durasi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse($leaves as $leave)
                            <tr>
                                <td>
                                    <strong>{{ $leave->created_at->format('d M Y') }}</strong><br>
                                    <small class="text-muted">{{ $leave->created_at->format('H:i') }}</small>
                                </td>
                                <td>
                                    @if ($leave->leave_type == 'cuti')
                                        <span class="badge bg-label-primary">
                                            <i class='bx bx-calendar'></i> Cuti
                                        </span>
                                    @elseif($leave->leave_type == 'izin')
                                        <span class="badge bg-label-info">
                                            <i class='bx bx-time-five'></i> Izin
                                        </span>
                                    @else
                                        <span class="badge bg-label-warning">
                                            <i class='bx bx-first-aid'></i> Sakit
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ \Carbon\Carbon::parse($leave->start_date)->format('d M Y') }}</strong><br>
                                    <small class="text-muted">s/d
                                        {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}</small>
                                </td>
                                <td>
                                    <strong>{{ $leave->total_days }}</strong> hari
                                </td>
                                <td>
                                    @if ($leave->status == 'pending')
                                        <span class="badge bg-label-warning">
                                            <i class='bx bx-time-five'></i> Pending
                                        </span>
                                    @elseif($leave->status == 'approved')
                                        <span class="badge bg-label-success">
                                            <i class='bx bx-check-circle'></i> Disetujui
                                        </span>
                                        @if ($leave->approved_at)
                                            <br><small
                                                class="text-muted">{{ $leave->approved_at->format('d M Y') }}</small>
                                        @endif
                                    @else
                                        <span class="badge bg-label-danger">
                                            <i class='bx bx-x-circle'></i> Ditolak
                                        </span>
                                        @if ($leave->approved_at)
                                            <br><small
                                                class="text-muted">{{ $leave->approved_at->format('d M Y') }}</small>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                        data-bs-target="#detailModal{{ $leave->id }}">
                                        <i class='bx bx-show'></i> Detail
                                    </button>
                                    @if ($leave->status == 'pending')
                                        <button class="btn btn-sm btn-outline-danger cancel-leave"
                                            data-id="{{ $leave->id }}">
                                            <i class='bx bx-x'></i> Batal
                                        </button>
                                    @endif
                                </td>
                            </tr>

                            <!-- Detail Modal -->
                            <div class="modal fade" id="detailModal{{ $leave->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Detail Pengajuan Cuti</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="text-muted small">Jenis</label>
                                                    <div>
                                                        @if ($leave->leave_type == 'cuti')
                                                            <span class="badge bg-label-primary">Cuti</span>
                                                        @elseif($leave->leave_type == 'izin')
                                                            <span class="badge bg-label-info">Izin</span>
                                                        @else
                                                            <span class="badge bg-label-warning">Sakit</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="text-muted small">Durasi</label>
                                                    <div><strong>{{ $leave->total_days }} hari</strong></div>
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="text-muted small">Tanggal Mulai</label>
                                                    <div>
                                                        <strong>{{ \Carbon\Carbon::parse($leave->start_date)->format('d F Y') }}</strong>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="text-muted small">Tanggal Selesai</label>
                                                    <div>
                                                        <strong>{{ \Carbon\Carbon::parse($leave->end_date)->format('d F Y') }}</strong>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="text-muted small">Alasan</label>
                                                <div class="border rounded p-3 bg-light">
                                                    {{ $leave->reason }}
                                                </div>
                                            </div>

                                            @if ($leave->attachment)
                                                <div class="mb-3">
                                                    <label class="text-muted small">Lampiran</label>
                                                    <div>
                                                        @php
                                                            $extension = pathinfo(
                                                                $leave->attachment,
                                                                PATHINFO_EXTENSION,
                                                            );
                                                        @endphp
                                                        @if (in_array($extension, ['jpg', 'jpeg', 'png']))
                                                            <a href="{{ asset('storage/' . $leave->attachment) }}"
                                                                target="_blank">
                                                                <img src="{{ asset('storage/' . $leave->attachment) }}"
                                                                    alt="Lampiran" class="img-fluid rounded border"
                                                                    style="max-width: 300px;">
                                                            </a>
                                                        @else
                                                            <a href="{{ asset('storage/' . $leave->attachment) }}"
                                                                target="_blank" class="btn btn-outline-primary btn-sm">
                                                                <i class='bx bx-file'></i> Download Lampiran
                                                                ({{ strtoupper($extension) }})
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif

                                            <hr>

                                            <div class="row">
                                                <div class="col-md-6 mb-2">
                                                    <label class="text-muted small">Status</label>
                                                    <div>
                                                        @if ($leave->status == 'pending')
                                                            <span class="badge bg-warning">Pending</span>
                                                        @elseif($leave->status == 'approved')
                                                            <span class="badge bg-success">Disetujui</span>
                                                        @else
                                                            <span class="badge bg-danger">Ditolak</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <label class="text-muted small">Tanggal Pengajuan</label>
                                                    <div><strong>{{ $leave->created_at->format('d F Y, H:i') }}
                                                            WIB</strong></div>
                                                </div>
                                            </div>

                                            @if ($leave->status == 'rejected' && $leave->rejection_reason)
                                                <div class="alert alert-danger mt-3">
                                                    <h6 class="alert-heading"><i class='bx bx-error-circle'></i> Alasan
                                                        Penolakan</h6>
                                                    <p class="mb-0">{{ $leave->rejection_reason }}</p>
                                                </div>
                                            @endif

                                            @if ($leave->status == 'approved' && $leave->approver)
                                                <div class="alert alert-success mt-3">
                                                    <small><strong>Disetujui oleh:</strong>
                                                        {{ $leave->approver->name }}</small><br>
                                                    <small><strong>Tanggal:</strong>
                                                        {{ $leave->approved_at->format('d F Y, H:i') }} WIB</small>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary"
                                                data-bs-dismiss="modal">Tutup</button>
                                            @if ($leave->status == 'pending')
                                                <button class="btn btn-danger cancel-leave"
                                                    data-id="{{ $leave->id }}">
                                                    <i class='bx bx-x'></i> Batalkan Pengajuan
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class='bx bx-calendar-x' style="font-size: 48px; color: #ccc;"></i>
                                    <p class="text-muted mt-2 mb-0">Belum ada riwayat pengajuan cuti</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="d-md-none">
                @forelse($leaves as $leave)
                    <div class="card-body border-bottom">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                @if ($leave->leave_type == 'cuti')
                                    <span class="badge bg-label-primary"><i class='bx bx-calendar'></i> Cuti</span>
                                @elseif($leave->leave_type == 'izin')
                                    <span class="badge bg-label-info"><i class='bx bx-time-five'></i> Izin</span>
                                @else
                                    <span class="badge bg-label-warning"><i class='bx bx-first-aid'></i> Sakit</span>
                                @endif
                                <strong class="ms-2">{{ $leave->total_days }} hari</strong>
                            </div>
                            @if ($leave->status == 'pending')
                                <span class="badge bg-warning">Pending</span>
                            @elseif($leave->status == 'approved')
                                <span class="badge bg-success">Disetujui</span>
                            @else
                                <span class="badge bg-danger">Ditolak</span>
                            @endif
                        </div>

                        <small class="text-muted d-block mb-2">
                            {{ \Carbon\Carbon::parse($leave->start_date)->format('d M Y') }} -
                            {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}
                        </small>

                        <small class="text-muted d-block mb-3">
                            Diajukan: {{ $leave->created_at->format('d M Y, H:i') }}
                        </small>

                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                data-bs-target="#detailModal{{ $leave->id }}">
                                <i class='bx bx-show'></i> Detail
                            </button>
                            @if ($leave->status == 'pending')
                                <button class="btn btn-sm btn-outline-danger cancel-leave" data-id="{{ $leave->id }}">
                                    <i class='bx bx-x'></i> Batal
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="card-body text-center py-4">
                        <i class='bx bx-calendar-x' style="font-size: 48px; color: #ccc;"></i>
                        <p class="text-muted mt-2 mb-0">Belum ada riwayat pengajuan cuti</p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if ($leaves->hasPages())
                <div class="card-footer">
                    <div class="d-flex justify-content-center">
                        {{ $leaves->links() }}
                    </div>
                </div>
            @endif
        </div>

        <!-- Modal Ajukan Cuti Baru -->
        <div class="modal fade" id="modalCuti" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ajukan Cuti/Izin Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="createLeaveForm" enctype="multipart/form-data">
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

                            <div class="alert alert-info">
                                <small><i class='bx bx-info-circle'></i> <strong>Catatan:</strong></small><br>
                                <small>- Pengajuan akan diproses oleh admin</small><br>
                                <small>- Anda akan menerima notifikasi setelah disetujui/ditolak</small>
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
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Create Leave Form
            $('#createLeaveForm').on('submit', function(e) {
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
                            toastr.success(response.message ||
                                'Pengajuan cuti berhasil dikirim');
                            $('#modalCuti').modal('hide');
                            $('#createLeaveForm')[0].reset();
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

            // Cancel Leave with confirmation
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
                $('#createLeaveForm')[0].reset();
                $('#createLeaveForm').find('.is-invalid').removeClass('is-invalid');
            });
        });
    </script>
@endpush
