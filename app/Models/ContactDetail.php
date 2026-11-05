<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactDetail extends Model
{
    protected $fillable = [
        'hotline_display', 'hotline_e164', 'whatsapp', 'email', 'street', 'floor', 'show_floor',
        'locality', 'city', 'postal_code', 'country', 'opening_days', 'opens', 'closes', 'show_hours',
    ];

    protected function casts(): array
    {
        return [
            'show_floor' => 'boolean',
            'opening_days' => 'array',
            'show_hours' => 'boolean',
        ];
    }
}
