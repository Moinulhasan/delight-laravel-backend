<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class LeadReceived extends Mailable
{
    /**
     * @param  array<string, string>  $rows  label => value
     * @param  array<string, string>  $source  label => value (page and UTM)
     */
    public function __construct(
        public string $leadName,
        public string $phone,
        public array $rows,
        public array $source,
        public string $received,
        public string $company,
        public string $mailSubject,
    ) {}

    public function build(): self
    {
        return $this->subject($this->mailSubject)
            ->view('emails.lead')
            ->text('emails.lead-text');
    }
}
