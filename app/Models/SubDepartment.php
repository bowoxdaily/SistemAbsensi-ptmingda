<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubDepartment extends Model
{
    protected $fillable = [
        'department_id',
        'name',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke Department (parent)
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Relasi ke Employees
     */
    public function employees()
    {
        return $this->hasMany(Employee::class, 'sub_department_id');
    }

    /**
     * Scope untuk sub department aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
