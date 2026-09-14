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
            <button type="submit" formaction="{{ route('admin.overtime.export') }}" class="btn btn-success">
                <i class="bx bx-download me-1"></i>Export Excel
            </button>
        </div>
    </form>
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
    });
</script>
@endpush
