@extends('layouts.app')

@section('title', 'Jam Kerja Mingguan')

@section('content')
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.weekly-hours') }}" class="row g-3 align-items-end">
                        <div class="col-md-8">
                            <label class="form-label">Tanggal Acuan Minggu</label>
                            <input type="date" name="week_date" class="form-control" value="{{ $filters['week_date'] ?? now()->format('Y-m-d') }}">
                            <small class="text-muted">Tanggal apa pun di minggu itu akan dihitung sebagai periode Senin sampai Minggu.</small>
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">Tampilkan Minggu Ini</button>
                            <a href="{{ route('admin.weekly-hours', ['week_date' => $period['start']->copy()->subWeek()->format('Y-m-d')]) }}" class="btn btn-outline-secondary">Minggu Sebelumnya</a>
                            <a href="{{ route('admin.weekly-hours', ['week_date' => $period['start']->copy()->addWeek()->format('Y-m-d')]) }}" class="btn btn-outline-secondary">Minggu Berikutnya</a>
                        </div>
                    </form>
                    <small class="text-muted d-block mt-3">
                        Halaman ini otomatis memotong periode ke Senin sampai Minggu dari tanggal acuan yang dipilih.
                    </small>
                </div>
            </div>
        </div>

        <div class="col-12 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <h4 class="mb-1">Karyawan Lewat 60 Jam</h4>
                        <p class="mb-0 text-muted">
                            Periode {{ $period['start']->translatedFormat('d M Y') }} sampai {{ $period['end']->translatedFormat('d M Y') }}
                        </p>
                    </div>
                    <div class="text-md-end">
                        <span class="badge rounded-pill bg-primary fs-6 px-3 py-2">
                            {{ $overLimitCount }} Karyawan
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title m-0">Daftar Karyawan yang Melebihi 60 Jam</h5>
                </div>
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Karyawan</th>
                                <th>Total Jam</th>
                                <th>Lebih dari 60 Jam</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($employeesOverLimit->where('is_over_limit', true) as $row)
                                <tr>
                                    <td>
                                        <strong>{{ $row['employee']->name ?? '-' }}</strong><br>
                                        <small class="text-muted">{{ $row['employee']->employee_code ?? '-' }}</small>
                                    </td>
                                    <td>{{ $row['formatted_hours'] }}</td>
                                    <td class="text-danger">{{ number_format($row['total_hours'] - 60, 2) }} jam</td>
                                    <td><span class="badge bg-danger">Lewat 60 Jam</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Tidak ada karyawan yang melewati 60 jam pada periode ini</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection