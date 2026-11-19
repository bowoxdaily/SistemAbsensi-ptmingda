<!doctype html>
<html lang="id"><head><meta charset="utf-8"><title>Surat Panggilan {{ $letter->letter_number }}</title><style>
*{box-sizing:border-box}body{font:12pt Arial,sans-serif;color:#111;margin:24mm auto;max-width:180mm;line-height:1.55}.header{text-align:center;font-size:15pt;font-weight:bold}.subtitle{text-align:center;font-size:10pt}.rule{border:0;border-top:2px solid;margin:8px 0 24px}.right{text-align:right}.meta{width:100%;border-collapse:collapse;margin:12px 0}.meta td{padding:3px 0;vertical-align:top}.meta td:first-child{width:145px}.signature{margin:48px 0 0 58%}.signature-name{margin-top:72px;font-weight:bold;text-decoration:underline}.section-title{font-weight:bold;text-decoration:underline;margin:22px 0 8px}.print-action{text-align:center;margin-top:35px}@page{size:A4;margin:20mm}@media print{body{margin:0;max-width:none}.print-action{display:none}}
</style></head><body>
<div class="header">{{ config('app.name', 'PT MINGDA') }}</div><div class="subtitle">HUMAN RESOURCES DEPARTMENT</div><hr class="rule">
<p class="right">{{ $letter->issue_date->translatedFormat('d F Y') }}</p>
<p>Kepada Yth.<br><strong>{{ $letter->employee->name }}</strong><br>{{ $letter->employee->position->name ?? '' }}{{ $letter->employee->department ? ' — '.$letter->employee->department->name : '' }}<br>di tempat</p>
<table class="meta"><tr><td>Nomor surat</td><td>: {{ $letter->letter_number }}</td></tr><tr><td>Perihal</td><td>: Panggilan Klarifikasi</td></tr></table>
<p>Dengan hormat,</p><p>Dengan ini perusahaan mengundang Saudara/i untuk hadir dalam klarifikasi terkait hal berikut:</p>
<p class="section-title">KEPERLUAN KLARIFIKASI</p><p>{{ $letter->purpose }}</p>
<table class="meta"><tr><td>Hari / tanggal</td><td>: {{ $letter->meeting_at->translatedFormat('l, d F Y') }}</td></tr><tr><td>Waktu</td><td>: {{ $letter->meeting_at->format('H:i') }} WIB</td></tr><tr><td>Tempat</td><td>: {{ $letter->location }}</td></tr></table>
@if($letter->description)<p class="section-title">KETERANGAN TAMBAHAN</p><p>{{ $letter->description }}</p>@endif
<p>Mohon hadir tepat waktu untuk memberikan klarifikasi. Demikian surat panggilan ini disampaikan. Atas perhatian dan kerja sama Saudara/i, kami ucapkan terima kasih.</p>
<div class="signature">Hormat kami,<br>Human Resources Department<br>{{ config('app.name', 'PT MINGDA') }}<div class="signature-name">{{ $letter->issuer->name }}</div></div>
<div class="print-action"><button onclick="window.print()">Cetak / Simpan PDF</button></div>
</body></html>

