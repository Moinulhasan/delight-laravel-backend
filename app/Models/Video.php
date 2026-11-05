<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = [
        'youtube_id', 'title_en', 'title_bn', 'description_en', 'description_bn', 'upload_date',
        'duration_seconds', 'poster_image', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'poster_image' => 'array',
            'upload_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /** "PT2M30S", the form schema.org wants. */
    public function isoDuration(): ?string
    {
        if (! $this->duration_seconds) {
            return null;
        }

        $seconds = $this->duration_seconds;

        return 'PT'.(intdiv($seconds, 3600) ? intdiv($seconds, 3600).'H' : '')
            .(intdiv($seconds % 3600, 60) ? intdiv($seconds % 3600, 60).'M' : '')
            .($seconds % 60 ? ($seconds % 60).'S' : '');
    }

    /** "2:30", as the admin form shows it. */
    public function clockDuration(): ?string
    {
        if (! $this->duration_seconds) {
            return null;
        }

        $seconds = $this->duration_seconds;
        $clock = sprintf('%d:%02d', intdiv($seconds % 3600, 60), $seconds % 60);

        return $seconds >= 3600 ? intdiv($seconds, 3600).':'.str_pad($clock, 5, '0', STR_PAD_LEFT) : $clock;
    }
}
