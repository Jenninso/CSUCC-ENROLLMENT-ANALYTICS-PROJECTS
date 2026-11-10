<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AccountCredentialsMail extends Mailable
{
    public function __construct(
        public string $roleName,
        public string $email,
        public string $temporaryPassword,
        public string $employeeId,
        public string $username = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your account is now '.$this->roleName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.account-credentials',
        );
    }
}
