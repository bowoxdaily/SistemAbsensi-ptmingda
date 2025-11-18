<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        // Identitas Pribadi
        'employee_code',
        'nik',
        'name',
        'gender',
        'birth_place',
        'birth_date',
        'marital_status',
        'agama',
        'bangsa',
        'status_kependudukan',
        'tanggungan_anak',
        'nama_ibu_kandung',
        'ktp',
        'kartu_keluarga',

        // Data Pekerjaan
        'department_id',
        'sub_department',
        'sub_department_id',
        'position_id',
        'join_date',
        'employment_status',
        'lulusan_sekolah',
        'work_schedule_id',
        'supervisor_id',
        'salary_base',
        'tanggal_resign',

        // Data Keuangan
        'bank',
        'nomor_rekening',

        // Data Administrasi
        'tax_npwp',
        'bpjs_kesehatan',
        'bpjs_ketenagakerjaan',

        // Data Kontak & Alamat
        'address',
        'city',
        'province',
        'postal_code',
        'phone',
        'email',
        'emergency_contact_name',
        'emergency_contact_phone',

        // Data Akun & Sistem
        'user_id',
        'status',
        'profile_photo',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'join_date' => 'date',
        'tanggal_resign' => 'date',
        'salary_base' => 'decimal:2',
        'tanggungan_anak' => 'integer',
    ];

    /**
     * Relasi ke Department
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Relasi ke Position
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Relasi ke Sub Department
     */
    public function subDepartment(): BelongsTo
    {
        return $this->belongsTo(SubDepartment::class, 'sub_department_id');
    }

    /**
     * Relasi ke User (akun login)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke WorkSchedule
     */
    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class, 'work_schedule_id');
    }

    /**
     * Relasi ke Supervisor (self-referencing)
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    /**
     * Relasi ke Subordinates (karyawan yang di-supervisi)
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'supervisor_id');
    }

    /**
     * Relasi ke Attendances
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Relasi ke Leaves
     */
    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    /**
     * Accessor untuk full name dengan employee code
     */
    public function getFullNameWithCodeAttribute(): string
    {
        return "{$this->employee_code} - {$this->name}";
    }

    /**
     * Accessor untuk birth_date - pastikan selalu dalam format Y-m-d
     */
    public function getBirthDateAttribute($value)
    {
        return $value ? $this->asDate($value)->format('Y-m-d') : null;
    }

    /**
     * Accessor untuk join_date - pastikan selalu dalam format Y-m-d
     */
    public function getJoinDateAttribute($value)
    {
        return $value ? $this->asDate($value)->format('Y-m-d') : null;
    }

    /**
     * Accessor untuk tanggal_resign - pastikan selalu dalam format Y-m-d
     */
    public function getTanggalResignAttribute($value)
    {
        return $value ? $this->asDate($value)->format('Y-m-d') : null;
    }

    /**
     * Accessor untuk umur
     */
    public function getAgeAttribute(): ?int
    {
        return $this->birth_date ? \Carbon\Carbon::parse($this->birth_date)->age : null;
    }
}
