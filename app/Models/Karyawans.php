<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Karyawans extends Model
{
    protected $table = 'employees';

    protected $fillable = [
        'employee_code',
        'fingerspot_pin',
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
        'department_id',
        'sub_department',
        'sub_department_id',
        'position_id',
        'join_date',
        'employment_status',
        'serikat',
        'lulusan_sekolah',
        'work_schedule_id',
        'supervisor_id',
        'salary_base',
        'tanggal_resign',
        'tanggal_mangkir',
        'tanggal_gagal_probation',
        'tanggal_pending',
        'bank',
        'nomor_rekening',
        'tax_npwp',
        'bpjs_kesehatan',
        'bpjs_ketenagakerjaan',
        'address',
        'city',
        'province',
        'desa',
        'kecamatan',
        'kabupaten',
        'postal_code',
        'phone',
        'email',
        'emergency_contact_name',
        'emergency_contact_phone',
        'user_id',
        'status',
        'profile_photo'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'join_date' => 'date',
        'tanggal_resign'           => 'date',
        'tanggal_mangkir'         => 'date',
        'tanggal_gagal_probation' => 'date',
        'tanggal_pending'         => 'date',
        'salary_base' => 'decimal:2',
        'tanggungan_anak' => 'integer',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function subDepartment(): BelongsTo
    {
        return $this->belongsTo(SubDepartment::class, 'sub_department_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Karyawans::class, 'supervisor_id');
    }

    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class, 'work_schedule_id');
    }

    /**
     * Accessor untuk birth_date - pastikan selalu dalam format Y-m-d
     */
    public function getBirthDateAttribute($value)
    {
        if (!$value) {
            return null;
        }
        try {
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return $value; // Already formatted
            }
            return $this->asDate($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Accessor untuk join_date - pastikan selalu dalam format Y-m-d
     */
    public function getJoinDateAttribute($value)
    {
        if (!$value) {
            return null;
        }
        try {
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return $value; // Already formatted
            }
            return $this->asDate($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Accessor untuk tanggal_resign - pastikan selalu dalam format Y-m-d
     */
    public function getTanggalResignAttribute($value)
    {
        if (!$value) {
            return null;
        }
        try {
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return $value; // Already formatted
            }
            return $this->asDate($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Accessor untuk tanggal_mangkir - pastikan selalu dalam format Y-m-d
     */
    public function getTanggalMangkirAttribute($value)
    {
        if (!$value) {
            return null;
        }
        try {
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return $value; // Already formatted
            }
            return $this->asDate($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Accessor untuk tanggal_gagal_probation - pastikan selalu dalam format Y-m-d
     */
    public function getTanggalGagalProbationAttribute($value)
    {
        if (!$value) {
            return null;
        }
        try {
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return $value; // Already formatted
            }
            return $this->asDate($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Accessor untuk tanggal_pending - pastikan selalu dalam format Y-m-d
     */
    public function getTanggalPendingAttribute($value)
    {
        if (!$value) {
            return null;
        }
        try {
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return $value; // Already formatted
            }
            return $this->asDate($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return $value;
        }
    }
}
