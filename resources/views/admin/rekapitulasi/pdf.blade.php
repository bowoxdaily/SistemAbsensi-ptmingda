<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekapitulasi Absensi - {{ $period }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 16px;
        }
        .header p {
            margin: 3px 0;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table th {
            background-color: #4472C4;
            color: white;
            padding: 8px 5px;
            text-align: center;
            font-size: 10px;
            border: 1px solid #ddd;
        }
        table td {
            padding: 6px 5px;
            border: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
        }
        table td.text-left {
            text-align: left;
        }
        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            color: white;
        }
        .badge-success { background-color: #28a745; }
        .badge-warning { background-color: #ffc107; color: #000; }
        .badge-info { background-color: #17a2b8; }
        .badge-primary { background-color: #007bff; }
        .badge-secondary { background-color: #6c757d; }
        .badge-danger { background-color: #dc3545; }
        .footer {
            margin-top: 20px;
            text-align: right;
            font-size: 10px;
            color: #666;
        }
        .percentage-high { color: #28a745; font-weight: bold; }
        .percentage-medium { color: #ffc107; font-weight: bold; }
        .percentage-low { color: #dc3545; font-weight: bold; }
        .no-print {
            margin: 20px 0;
            text-align: center;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                margin: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
            🖨️ Print / Save as PDF
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; margin-left: 10px;">
            ✖️ Close
        </button>
    </div>

    <div class="header">
        <h2>REKAPITULASI ABSENSI KARYAWAN</h2>
        <p>Periode: {{ $period }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Nama Karyawan</th>
                <th>Department</th>
                <th>Jabatan</th>
                <th>Hadir</th>
                <th>Terlambat</th>
                <th>Izin</th>
                <th>Sakit</th>
                <th>Cuti</th>
                <th>Alpha</th>
                <th>Total<br>Masuk</th>
                <th>Hari<br>Kerja</th>
                <th>%</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rekapitulasi as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row['employee_code'] }}</td>
                    <td class="text-left">{{ $row['name'] }}</td>
                    <td class="text-left">{{ $row['department'] }}</td>
                    <td class="text-left">{{ $row['position'] }}</td>
                    <td>{{ $row['hadir'] }}</td>
                    <td>{{ $row['terlambat'] }}</td>
                    <td>{{ $row['izin'] }}</td>
                    <td>{{ $row['sakit'] }}</td>
                    <td>{{ $row['cuti'] }}</td>
                    <td>{{ $row['alpha'] }}</td>
                    <td><strong>{{ $row['total_present'] }}</strong></td>
                    <td>{{ $row['working_days'] }}</td>
                    <td class="{{ $row['percentage'] >= 90 ? 'percentage-high' : ($row['percentage'] >= 75 ? 'percentage-medium' : 'percentage-low') }}">
                        {{ $row['percentage'] }}%
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak pada: {{ $generated_at }}</p>
    </div>

    <script>
        // Auto print when page loads (optional - comment out if you don't want auto-print)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
