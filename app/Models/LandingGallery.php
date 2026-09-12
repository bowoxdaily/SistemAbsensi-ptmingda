<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LandingGallery extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'category',
        'image_path',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'image_url',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'desc');
    }

    public function getImageUrlAttribute(): string
    {
        if (empty($this->image_path)) {
            return asset('sneat-1.0.0/assets/img/elements/18.jpg');
        }

        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        return Storage::disk('public')->url($this->image_path);
    }

    public static function categories(): array
    {
        return [
            'production'      => 'Lini Produksi',
            'facility'        => 'Fasilitas Pabrik',
            'quality_control' => 'Quality Control',
            'warehouse'       => 'Gudang & Logistik',
            'office'          => 'Area Manajemen',
        ];
    }
}
