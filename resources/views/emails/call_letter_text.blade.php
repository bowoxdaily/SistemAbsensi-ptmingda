{{ $appName }}
Human Resources Department

PEMBERITAHUAN RESMI
SURAT PANGGILAN
Nomor: {{ $callLetter->letter_number }}
Tanggal: {{ $callLetter->issue_date->format('d F Y') }}

Kepada Yth.
{{ $recipientName }}

Dengan hormat,

Dengan ini perusahaan menyampaikan surat panggilan kepada Saudara/i dengan rincian sebagai berikut:

Nomor surat       : {{ $callLetter->letter_number }}
Tanggal terbit    : {{ $callLetter->issue_date->format('d-m-Y') }}
Jadwal klarifikasi: {{ $callLetter->meeting_at->format('d-m-Y H:i') }}
Tempat            : {{ $callLetter->location }}

KEPERLUAN / HAL YANG DIKLARIFIKASI
{{ $callLetter->purpose }}
@if ($callLetter->description)

KETERANGAN TAMBAHAN
{{ $callLetter->description }}
@endif

Surat resmi tersedia sebagai lampiran PDF email ini.

Hormat kami,
Human Resources Department
{{ $appName }}

Email ini berisi informasi kepegawaian yang bersifat rahasia dan ditujukan hanya kepada penerima yang tercantum. Mohon tidak meneruskan email ini kepada pihak lain.
