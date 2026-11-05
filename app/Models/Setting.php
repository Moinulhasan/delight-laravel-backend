<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'company_name', 'legal_name', 'logo_header', 'logo_header_light', 'logo_footer', 'logo_footer_light', 'favicon',
        'lead_recipient', 'mail_from_address', 'mail_from_name', 'smtp_host', 'smtp_port', 'smtp_encryption',
        'smtp_username', 'smtp_password', 'seo_title_en', 'seo_title_bn', 'seo_description_en', 'seo_description_bn', 'seo_image',
    ];

    protected $hidden = ['smtp_username', 'smtp_password'];

    protected function casts(): array
    {
        return [
            'smtp_username' => 'encrypted',
            'smtp_password' => 'encrypted',
            'seo_image' => 'array',
        ];
    }

    /** The one settings row, created with safe defaults the first time it is needed. */
    public static function current(): self
    {
        return once(fn () => self::firstOrCreate([], [
            'company_name' => 'Delight Real Estate',
            'legal_name' => 'Delight Real Estate Ltd.',
        ]));
    }

    /** Null instead of a crash if APP_KEY changed, so the admin can simply enter it again. */
    public function credential(string $name): ?string
    {
        try {
            return $this->{$name};
        } catch (DecryptException) {
            return null;
        }
    }

    public function hasSmtpPassword(): bool
    {
        return filled($this->getRawOriginal('smtp_password'));
    }

    public function logo(string $name, string $default): string
    {
        return $this->{$name} ?: $default;
    }
}
