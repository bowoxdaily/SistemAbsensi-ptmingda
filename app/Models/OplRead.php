<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OplRead extends Model
{
    use HasFactory;

    protected $table = 'opl_reads';

    protected $fillable = [
        'opl_id', 'employee_id', 'read_at'
    ];

    protected $casts = [
        'read_at' => 'datetime'
    ];

    public function opl()
    {
        return $this->belongsTo(Opl::class, 'opl_id');
    }

    public function employee()
    {
        return $this->belongsTo(\App\Models\Karyawans::class, 'employee_id');
    }
}
