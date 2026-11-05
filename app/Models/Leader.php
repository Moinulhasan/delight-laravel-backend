<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Leader extends Model
{
    protected $fillable = [
        'name', 'initials', 'role_en', 'role_bn', 'quote_en', 'quote_bn',
        'photo', 'photo_alt_en', 'photo_alt_bn', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'photo' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
