<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'slug', 'name_en', 'name_bn', 'tagline_en', 'tagline_bn', 'status', 'type', 'handover',
        'area_slug', 'area_name_en', 'area_name_bn', 'address_en', 'address_bn',
        'cover_image', 'cover_alt_en', 'cover_alt_bn', 'card_stats', 'is_featured', 'sort_order', 'is_active',
        'seo_title_en', 'seo_title_bn', 'seo_description_en', 'seo_description_bn',
    ];

    protected function casts(): array
    {
        return [
            'cover_image' => 'array',
            'card_stats' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
