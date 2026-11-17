{{ $appName }}
Human Resources Department

PEMBERITAHUAN RESMI
SURAT PERINGATAN {{ $warningLetter->sp_type }}
Nomor: {{ $warningLetter->sp_number }}
Tanggal: {{ $warningLetter->issue_date->format('d F Y') }}

Kepada Yth.
{{ $recipientName }}

Dengan hormat,

Dengan ini perusahaan menyampaikan surat peringatan kepada Saudara/i dengan rincian sebagai berikut:

Jenis surat       : Surat Peringatan {{ $warningLetter->sp_type }}
Nomor surat       : {{ $warningLetter->sp_number }}
Tanggal terbit    : {{ $warningLetter->issue_date->format('d-m-Y') }}
Mulai berlaku     : {{ $warningLetter->effective_date->format('d-m-Y') }}
@if ($warningLetter->completion_date)
Berlaku sampai    : {{ $warningLetter->completion_date->format('d-m-Y') }}
@endif

URAIAN PELANGGARAN
{{ $warningLetter->violation }}
@if ($warningLetter->description)

KETERANGAN TAMBAHAN
{{ $warningLetter->description }}
@endif

Kami mengharapkan perhatian dan komitmen Anda untuk mematuhi ketentuan serta tata tertib perusahaan. Apabila memerlukan penjelasan lebih lanjut, silakan menghubungi Departemen HRD.

Hormat kami,
Human Resources Department
{{ $appName }}

Email ini berisi informasi kepegawaian yang bersifat rahasia dan ditujukan hanya kepada penerima yang tercantum. Mohon tidak meneruskan email ini kepada pihak lain.

