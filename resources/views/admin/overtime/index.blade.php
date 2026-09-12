@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Overtime</h4>
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-4"><label class="form-label">Cari Karyawan</label><input type="search" name="search" value="{{ $search }}" placeholder="Nama atau kode karyawan" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">Dari</label><input type="date" name="date_from" value="{{ $from }}" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">Sampai</label><input type="date" name="date_to" value="{{ $to }}" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Kategori</label><select name="category" class="form-select"><option value="">Semua</option>@foreach(range(1, 8) as $i)<option value="OT{{ $i }}" @selected($category === 'OT'.$i)>OT{{ $i }}</option>@endforeach</select></div>
        <div class="col-md-2 d-flex align-items-end gap-2"><button class="btn btn-primary w-100">Filter</button></div>
        <div class="col-md-2 d-flex align-items-end"><a href="{{ route('admin.overtime.export', request()->query()) }}" class="btn btn-success w-100"><i class="bx bx-download me-1"></i>Export</a></div>
    </form>
    <div class="card"><div class="table-responsive"><table class="table table-hover"><thead><tr><th>Tanggal</th><th>Karyawan</th><th>Jam Pulang</th><th>Durasi</th><th>Kategori</th></tr></thead><tbody>
    @forelse($overtimes as $item)<tr><td>{{ $item->attendance_date->format('d-m-Y') }}</td><td>{{ $item->employee->name }}<br><small>{{ $item->employee->employee_code }}</small></td><td>{{ substr($item->check_out, 0, 5) }}</td><td>{{ intdiv($item->overtime_minutes, 60) }} jam {{ $item->overtime_minutes % 60 }} menit</td><td><span class="badge bg-label-primary">{{ $item->overtime_category }}</span></td></tr>@empty<tr><td colspan="5" class="text-center py-4">Tidak ada data overtime.</td></tr>@endforelse
    </tbody></table></div><div class="px-3">{{ $overtimes->links() }}</div></div>
</div>
@endsection