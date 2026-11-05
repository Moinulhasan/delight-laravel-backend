<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroSlide extends Model
{
    protected $fillable = ['desktop_images', 'mobile_images', 'alt_en', 'alt_bn', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'desktop_images' => 'array',
            'mobile_images' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
