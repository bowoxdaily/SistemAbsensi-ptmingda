@extends('layouts.app')

@section('title', 'Pengaturan Jadwal Kerja')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Pengaturan /</span> Jadwal Kerja
        </h4>

        <div class="row">
            <!-- Work Schedule List -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Daftar Jadwal Kerja</h5>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                            data-bs-target="#addScheduleModal">
                            <i class="bx bx-plus"></i> Tambah Jadwal
                        </button>
                    </div>
                    <div class="card-body">
                        @if ($schedules->isEmpty())
                            <div class="text-center py-5">
                                <i class="bx bx-time-five" style="font-size: 4rem; color: #ddd;"></i>
                                <p class="text-muted mt-3">Belum ada jadwal kerja yang dibuat</p>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#addScheduleModal">
                                    <i class="bx bx-plus"></i> Tambah Jadwal Pertama
                                </button>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Nama Jadwal</th>
                                            <th>Jam Kerja</th>
                                            <th>Toleransi</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($schedules as $index => $schedule)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <strong>{{ $schedule->name }}</strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-label-primary">
                                                        <i class="bx bx-time-five"></i>
                                                        {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} -
                                                        {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-label-info">
                                                        {{ $schedule->late_tolerance }} menit
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input toggle-status" type="checkbox"
                                                            data-id="{{ $schedule->id }}"
                                                            {{ $schedule->is_active ? 'checked' : '' }}>
                                                        <label class="form-check-label">
                                                            {{ $schedule->is_active ? 'Aktif' : 'Nonaktif' }}
                                                        </label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="dropdown">
                                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                                            data-bs-toggle="dropdown">
                                                            <i class="bx bx-dots-vertical-rounded"></i>
                                                        </button>
                                                        <div class="dropdown-menu">
                                                            <a class="dropdown-item edit-schedule"
                                                                href="javascript:void(0);" data-id="{{ $schedule->id }}">
                                                                <i class="bx bx-edit-alt me-1"></i> Edit
                                                            </a>
                                                            <a class="dropdown-item delete-schedule"
                                                                href="javascript:void(0);" data-id="{{ $schedule->id }}">
                                                                <i class="bx bx-trash me-1"></i> Hapus
                                                            </a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Information Card -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Informasi</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="mb-2">
                                <i class="bx bx-info-circle text-info"></i> Tentang Jadwal Kerja
                            </h6>
                            <p class="text-muted small">
                                Jadwal kerja digunakan untuk mengatur jam kerja karyawan. Anda dapat membuat beberapa
                                jadwal untuk shift yang berbeda.
                            </p>
                        </div>

                        <div class="mb-3">
                            <h6 class="mb-2">
                                <i class="bx bx-time-five text-primary"></i> Jam Kerja
                            </h6>
                            <p class="text-muted small">
                                Tentukan jam mulai dan jam selesai untuk setiap shift kerja.
                            </p>
                        </div>

                        <div class="mb-3">
                            <h6 class="mb-2">
                                <i class="bx bx-timer text-warning"></i> Toleransi Keterlambatan
                            </h6>
                            <p class="text-muted small">
                                Waktu toleransi (dalam menit) sebelum karyawan dianggap terlambat. Contoh: toleransi 15
                                menit berarti karyawan masih dianggap tepat waktu jika absen maksimal 15 menit setelah jam
                                mulai.
                            </p>
                        </div>

                        <div class="mb-0">
                            <h6 class="mb-2">
                                <i class="bx bx-toggle-left text-success"></i> Status
                            </h6>
                            <p class="text-muted small">
                                Jadwal yang aktif dapat digunakan untuk penempatan karyawan. Nonaktifkan jadwal jika
                                tidak digunakan sementara.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Statistics Card -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3">Statistik</h6>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Total Jadwal</span>
                            <span class="badge bg-primary">{{ $schedules->count() }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Jadwal Aktif</span>
                            <span class="badge bg-success">{{ $schedules->where('is_active', true)->count() }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Jadwal Nonaktif</span>
                            <span class="badge bg-secondary">{{ $schedules->where('is_active', false)->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Schedule Modal -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Jadwal Kerja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addScheduleForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="add_name">Nama Jadwal <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_name" name="name"
                                placeholder="Contoh: Shift Pagi, Shift Sore" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="add_start_time">Jam Mulai <span
                                        class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="add_start_time" name="start_time"
                                    required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="add_end_time">Jam Selesai <span
                                        class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="add_end_time" name="end_time" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="add_late_tolerance">Toleransi Keterlambatan (menit) <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="add_late_tolerance" name="late_tolerance"
                                min="0" max="120" value="15" required>
                            <small class="text-muted">Maksimal 120 menit (2 jam)</small>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" id="add_is_active" name="is_active"
                                    value="1" checked>
                                <label class="form-check-label" for="add_is_active">
                                    Aktifkan Jadwal
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Schedule Modal -->
    <div class="modal fade" id="editScheduleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Jadwal Kerja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editScheduleForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_schedule_id" name="schedule_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="edit_name">Nama Jadwal <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="edit_start_time">Jam Mulai <span
                                        class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="edit_start_time" name="start_time"
                                    required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="edit_end_time">Jam Selesai <span
                                        class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="edit_end_time" name="end_time" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="edit_late_tolerance">Toleransi Keterlambatan (menit) <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_late_tolerance" name="late_tolerance"
                                min="0" max="120" required>
                            <small class="text-muted">Maksimal 120 menit (2 jam)</small>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active"
                                    value="1">
                                <label class="form-check-label" for="edit_is_active">
                                    Aktifkan Jadwal
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save"></i> Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .table-responsive {
            border-radius: 8px;
        }

        .form-check-input {
            cursor: pointer;
        }

        .badge {
            font-size: 0.8rem;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Add Schedule Form Submit
            $('#addScheduleForm').on('submit', function(e) {
                e.preventDefault();

                // Clear previous errors
                $('.form-control').removeClass('is-invalid');
                $('.invalid-feedback').text('');

                $.ajax({
                    url: '/api/settings/work-schedule',
                    type: 'POST',
                    data: $(this).serialize(),
                    headers: {
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#addScheduleModal').modal('hide');
                            $('#addScheduleForm')[0].reset();

                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            Object.keys(errors).forEach(key => {
                                const input = $(`#add_${key}`);
                                input.addClass('is-invalid');
                                input.siblings('.invalid-feedback').text(errors[key][
                                    0
                                ]);
                            });

                            Swal.fire({
                                icon: 'error',
                                title: 'Validasi Gagal',
                                text: xhr.responseJSON.message
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Terjadi kesalahan saat menyimpan data'
                            });
                        }
                    }
                });
            });

            // Edit Schedule Button Click
            $('.edit-schedule').on('click', function() {
                const scheduleId = $(this).data('id');

                $.ajax({
                    url: `/api/settings/work-schedule/${scheduleId}`,
                    type: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        if (response.success) {
                            const schedule = response.data;

                            $('#edit_schedule_id').val(schedule.id);
                            $('#edit_name').val(schedule.name);
                            $('#edit_start_time').val(schedule.start_time.substring(0, 5));
                            $('#edit_end_time').val(schedule.end_time.substring(0, 5));
                            $('#edit_late_tolerance').val(schedule.late_tolerance);
                            $('#edit_is_active').prop('checked', schedule.is_active);

                            $('#editScheduleModal').modal('show');
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Gagal mengambil data jadwal'
                        });
                    }
                });
            });

            // Edit Schedule Form Submit
            $('#editScheduleForm').on('submit', function(e) {
                e.preventDefault();

                const scheduleId = $('#edit_schedule_id').val();

                // Clear previous errors
                $('.form-control').removeClass('is-invalid');
                $('.invalid-feedback').text('');

                $.ajax({
                    url: `/api/settings/work-schedule/${scheduleId}`,
                    type: 'PUT',
                    data: $(this).serialize(),
                    headers: {
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#editScheduleModal').modal('hide');

                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            Object.keys(errors).forEach(key => {
                                const input = $(`#edit_${key}`);
                                input.addClass('is-invalid');
                                input.siblings('.invalid-feedback').text(errors[key][
                                    0
                                ]);
                            });

                            Swal.fire({
                                icon: 'error',
                                title: 'Validasi Gagal',
                                text: xhr.responseJSON.message
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Terjadi kesalahan saat memperbarui data'
                            });
                        }
                    }
                });
            });

            // Delete Schedule
            $('.delete-schedule').on('click', function() {
                const scheduleId = $(this).data('id');

                Swal.fire({
                    title: 'Hapus Jadwal?',
                    text: "Data jadwal akan dihapus secara permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/api/settings/work-schedule/${scheduleId}`,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            headers: {
                                'Accept': 'application/json'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Terhapus!',
                                        text: response.message,
                                        timer: 2000,
                                        showConfirmButton: false
                                    }).then(() => {
                                        location.reload();
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Gagal menghapus jadwal'
                                });
                            }
                        });
                    }
                });
            });

            // Toggle Status
            $('.toggle-status').on('change', function() {
                const scheduleId = $(this).data('id');
                const checkbox = $(this);

                $.ajax({
                    url: `/api/settings/work-schedule/${scheduleId}/toggle`,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    headers: {
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        if (response.success) {
                            const label = checkbox.siblings('label');
                            label.text(response.data.is_active ? 'Aktif' : 'Nonaktif');

                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function() {
                        // Revert checkbox state
                        checkbox.prop('checked', !checkbox.prop('checked'));

                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Gagal mengubah status jadwal'
                        });
                    }
                });
            });
        });
    </script>
@endpush
