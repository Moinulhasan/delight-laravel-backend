<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/** Sends mail with the SMTP details saved in Settings, not the ones in .env. */
class SiteMailer
{
    public function send(Mailable $mail): void
    {
        $settings = Setting::current();
        $password = $settings->credential('smtp_password');

        if (blank($settings->lead_recipient) || blank($settings->smtp_host) || blank($settings->mail_from_address)) {
            throw new RuntimeException('Email is not set up yet: fill in the recipient, sender and SMTP server in Settings.');
        }

        if ($settings->hasSmtpPassword() && $password === null) {
            throw new RuntimeException('The saved SMTP password can no longer be read because APP_KEY changed. Please enter it again.');
        }

        config(['mail.mailers.settings' => [
            'transport' => 'smtp',
            'scheme' => $settings->smtp_encryption === 'ssl' ? 'smtps' : 'smtp',
            'require_tls' => $settings->smtp_encryption === 'tls',
            'host' => $settings->smtp_host,
            'port' => $settings->smtp_port,
            'username' => $settings->credential('smtp_username'),
            'password' => $password,
            'timeout' => 15,
        ]]);

        // Forget any mailer built from older settings earlier in this request.
        Mail::purge('settings');

        Mail::mailer('settings')
            ->to($settings->lead_recipient)
            ->send($mail->from($settings->mail_from_address, $settings->mail_from_name ?: $settings->company_name));
    }
}
