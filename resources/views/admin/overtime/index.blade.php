@extends('layouts.app')

@push('styles')
<style>
    .select2-container {
        width: 100% !important;
    }
    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #d9dee3 !important;
        border-radius: 0.375rem !important;
        padding: 0.4375rem 0.875rem !important;
        display: flex !important;
        align-items: center !important;
        width: 100% !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 8px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: normal !important;
        padding-left: 0 !important;
        color: #697a8d !important;
        width: 100% !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__clear {
        margin-right: 18px !important;
    }
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Overtime</h4>
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label">Cari atau Pilih Karyawan</label>
            <select name="employee_id" id="employeeSelect" class="form-select">
                <option value=""></option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" @selected($employeeId == $emp->id)>
                        {{ $emp->employee_code }} - {{ $emp->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Dari</label>
            <input type="date" name="date_from" value="{{ $from }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Sampai</label>
            <input type="date" name="date_to" value="{{ $to }}" class="form-control">
        </div>
        <div class="col-md-2">
            <label class="form-label">Kategori</label>
            <select name="category" class="form-select">
                <option value="">Semua</option>
                @foreach(range(1, 8) as $i)
                    <option value="OT{{ $i }}" @selected($category === 'OT'.$i)>OT{{ $i }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">Semua</option>
                <option value="pending" @selected(($status ?? '') === 'pending')>Pending</option>
                <option value="approved" @selected(($status ?? '') === 'approved')>Approved</option>
                <option value="rejected" @selected(($status ?? '') === 'rejected')>Rejected</option>
            </select>
        </div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bx bx-filter-alt me-1"></i>Filter
            </button>
            <a href="{{ route('admin.overtime.index') }}" class="btn btn-secondary">
                <i class="bx bx-reset me-1"></i>Reset
            </a>
        </div>
    </form>
    <form method="POST" action="{{ route('admin.overtime.export') }}" class="d-inline" id="exportForm">
        @csrf
        <input type="hidden" name="date_from" value="{{ $from }}">
        <input type="hidden" name="date_to" value="{{ $to }}">
        <input type="hidden" name="category" value="{{ $category }}">
        <input type="hidden" name="search" value="{{ $search }}">
        <input type="hidden" name="employee_id" value="{{ $employeeId }}">
        <button type="submit" class="btn btn-success" id="btnExport">
            <i class="bx bx-download me-1"></i>Export Excel
        </button>
    </form>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Export status alerts --}}
    @if(session('export_queued'))
        <div class="alert alert-info alert-dismissible fade show" role="alert" id="exportQueuedAlert">
            <i class="bx bx-loader-alt bx-spin me-1"></i>
            <span id="exportStatusText">{{ session('export_queued') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($pendingExport)
        <div class="alert alert-info" id="exportPendingAlert">
            <i class="bx bx-loader-alt bx-spin me-1"></i>
            <span id="exportStatusText2">Export <strong>{{ $pendingExport->filename }}</strong> sedang diproses...</span>
        </div>
    @endif

    @if($doneExport)
        <div class="alert alert-success alert-dismissible fade show" id="exportDoneAlert">
            <i class="bx bx-check-circle me-1"></i>
            Export <strong>{{ $doneExport->filename }}</strong> siap!
            <a href="{{ route('admin.overtime.export.download', $doneExport) }}" class="btn btn-sm btn-success ms-2">
                <i class="bx bx-download me-1"></i>Download
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Karyawan</th>
                        <th>Jam Pulang</th>
                        <th>Durasi Riil</th>
                        <th>Kategori</th>
                        <th>Dihitung Lembur</th>
                        <th>Menit Disetujui</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($overtimes as $item)
                        @php
                            $otNum = preg_match('/^OT(\d+)$/', (string) $item->overtime_category, $m) ? (int) $m[1] : 0;
                            $statusClass = match($item->overtime_approval_status ?? 'pending') {
                                'approved' => 'bg-label-success',
                                'rejected' => 'bg-label-danger',
                                default => 'bg-label-warning',
                            };
                        @endphp
                        <tr>
                            <td>{{ $item->attendance_date->format('d-m-Y') }}</td>
                            <td>
                                <strong>{{ $item->employee->name }}</strong><br>
                                <small class="text-muted">{{ $item->employee->employee_code }}</small>
                            </td>
                            <td>{{ substr($item->check_out, 0, 5) }}</td>
                            <td>{{ intdiv($item->overtime_minutes, 60) }} jam {{ $item->overtime_minutes % 60 }} menit</td>
                            <td><span class="badge bg-label-primary">{{ $item->overtime_category }}</span></td>
                            <td>
                                <strong>{{ intdiv($item->overtime_minutes, 60) }} jam {{ $item->overtime_minutes % 60 }} menit</strong>
                                <span class="text-muted">(deteksi mesin)</span>
                            </td>
                            <td>
                                @if($item->overtime_approval_status === 'approved')
                                    {{ intdiv($item->approved_overtime_minutes, 60) }} jam {{ $item->approved_overtime_minutes % 60 }} menit
                                @elseif($item->overtime_approval_status === 'rejected')
                                    <span class="text-danger">Ditolak</span>
                                @else
                                    <span class="text-muted">Belum disetujui</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $statusClass }}">
                                    {{ ucfirst($item->overtime_approval_status ?? 'pending') }}
                                </span>
                                @if($item->overtime_approved_at)
                                    <br><small class="text-muted">oleh {{ $item->overtimeApprover?->name ?? '-' }}</small>
                                @endif
                            </td>
                            <td>
                                @if(($item->overtime_approval_status ?? 'pending') !== 'approved' && ($item->overtime_approval_status ?? 'pending') !== 'rejected')
                                    <button type="button" class="btn btn-sm btn-success btn-approve"
                                        data-id="{{ $item->id }}"
                                        data-name="{{ $item->employee->name }}"
                                        data-date="{{ $item->attendance_date->format('d-m-Y') }}"
                                        data-overtime="{{ $item->overtime_minutes }}"
                                        data-category="{{ $item->overtime_category }}">
                                        <i class='bx bx-check me-1'></i>Approve
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger btn-reject"
                                        data-id="{{ $item->id }}"
                                        data-name="{{ $item->employee->name }}"
                                        data-date="{{ $item->attendance_date->format('d-m-Y') }}">
                                        <i class='bx bx-x me-1'></i>Reject
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">Tidak ada data overtime.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-3 pt-3">{{ $overtimes->links() }}</div>
    </div>

    {{-- Approve Modal --}}
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="approveForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bx bx-check-circle text-success me-1"></i>Approve Lembur</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Karyawan: <strong id="approveEmployeeName"></strong></p>
                        <p>Tanggal: <strong id="approveDate"></strong></p>
                        <p>Kategori: <span class="badge bg-label-primary" id="approveCategory"></span></p>
                        <p>Durasi terdeteksi mesin: <strong id="approveDetected"></strong></p>
                        <hr>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Menit lembur disetujui (sesuai form fisik)</label>
                            <input type="number" class="form-control" name="approved_overtime_minutes"
                                id="approveMinutesInput" min="0" required>
                            <small class="text-muted">Isi sesuai durasi di form fisik. Jika sama dengan mesin, biarkan default.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success"><i class="bx bx-check me-1"></i>Approve</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="rejectForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bx bx-x-circle text-danger me-1"></i>Reject Lembur</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Karyawan: <strong id="rejectEmployeeName"></strong></p>
                        <p>Tanggal: <strong id="rejectDate"></strong></p>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Alasan penolakan <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="overtime_rejection_reason" rows="3"
                                required placeholder="Contoh: Form fisik tidak ada, jam tidak sesuai, dll"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger"><i class="bx bx-x me-1"></i>Reject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('#employeeSelect').select2({
                placeholder: '-- Semua Karyawan --',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "Tidak ada karyawan yang ditemukan";
                    },
                    searching: function() {
                        return "Mencari...";
                    }
                }
            });
        }

        // Approve handler
        $(document).on('click', '.btn-approve', function() {
            var btn = $(this);
            var minutes = parseInt(btn.data('overtime'), 10) || 0;
            $('#approveForm').attr('action', '/admin/overtime/' + btn.data('id') + '/approve');
            $('#approveEmployeeName').text(btn.data('name'));
            $('#approveDate').text(btn.data('date'));
            $('#approveCategory').text(btn.data('category'));
            $('#approveDetected').text(Math.floor(minutes / 60) + ' jam ' + (minutes % 60) + ' menit (' + minutes + ' menit)');
            $('#approveMinutesInput').val(minutes).removeAttr('max');
            $('#approveModal').modal('show');
        });

        // Reject handler
        $(document).on('click', '.btn-reject', function() {
            var btn = $(this);
            $('#rejectForm').attr('action', '/admin/overtime/' + btn.data('id') + '/reject');
            $('#rejectEmployeeName').text(btn.data('name'));
            $('#rejectDate').text(btn.data('date'));
            $('#rejectModal').modal('show');
        });

        // Poll export status when there's a pending/queued export
        var hasPending = {{ ($pendingExport || session('export_queued')) ? 'true' : 'false' }};
        if (hasPending) {
            var pollInterval = setInterval(function() {
                fetch('{{ route("admin.overtime.export.status") }}')
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.status === 'done') {
                            clearInterval(pollInterval);
                            // Replace alerts with download button
                            var html = '<div class="alert alert-success alert-dismissible fade show">' +
                                '<i class="bx bx-check-circle me-1"></i>' +
                                'Export <strong>' + data.filename + '</strong> siap! ' +
                                '<a href="/admin/overtime/export/' + data.id + '/download" class="btn btn-sm btn-success ms-2">' +
                                '<i class="bx bx-download me-1"></i>Download</a>' +
                                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                            var container = document.getElementById('exportQueuedAlert') ||
                                            document.getElementById('exportPendingAlert');
                            if (container) {
                                container.outerHTML = html;
                            }
                        } else if (data.status === 'failed') {
                            clearInterval(pollInterval);
                            var container = document.getElementById('exportQueuedAlert') ||
                                            document.getElementById('exportPendingAlert');
                            if (container) {
                                container.outerHTML = '<div class="alert alert-danger alert-dismissible fade show">' +
                                    '<i class="bx bx-error me-1"></i>Export gagal: ' + (data.error || 'Unknown error') +
                                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                            }
                        }
                    })
                    .catch(function() {}); // silent on network error, will retry
            }, 5000); // poll every 5 seconds
        }
    });
</script>
@endpush
