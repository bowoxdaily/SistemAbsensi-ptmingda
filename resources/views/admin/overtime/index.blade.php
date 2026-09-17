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
                    </tr>
                </thead>
                <tbody>
                    @forelse($overtimes as $item)
                        @php
                            $otNum = preg_match('/^OT(\d+)$/', (string) $item->overtime_category, $m) ? (int) $m[1] : 0;
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
                                <strong>{{ $otNum }} Jam</strong>
                                <span class="text-muted">({{ $otNum * 60 }} menit)</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">Tidak ada data overtime.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-3 pt-3">{{ $overtimes->links() }}</div>
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
