<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class SettingsTestMail extends Mailable
{
    public function __construct(private string $company) {}

    public function build(): self
    {
        return $this->subject("Test email from the {$this->company} admin")
            ->html("<p>Your email settings work. Website enquiries will arrive at this address.</p><p>{$this->escapedCompany()}</p>");
    }

    private function escapedCompany(): string
    {
        return e($this->company);
    }
}
