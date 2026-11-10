<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OtpCodeMail extends Mailable
{
    public function __construct(
        public string $code,
        public string $purposeLabel,
        public int $minutes = 5,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Verification Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.otp',
        );
    }
}
