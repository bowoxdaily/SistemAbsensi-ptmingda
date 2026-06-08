<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Opl extends Model
{
    use HasFactory;

    protected $table = 'opls';

    protected $fillable = [
        'title',
        'attachment',
        'is_active',
        'show_popup',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_popup' => 'boolean',
    ];

    protected $appends = ['attachment_url'];

    public function getAttachmentUrlAttribute()
    {
        if (!$this->attachment) return null;
        // If already a full URL, return as-is
        if (str_starts_with($this->attachment, 'http')) return $this->attachment;
        return asset('storage/' . $this->attachment);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
