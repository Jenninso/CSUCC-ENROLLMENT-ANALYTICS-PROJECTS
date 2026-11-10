<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BrevoMailer
{
    public function enabled(): bool
    {
        return $this->key() !== '';
    }

    public function key(): string
    {
        $stored = preg_replace('/\s+/', '', (string) SystemSetting::getValue('brevo_api_key', '')) ?? '';
        if ($stored !== '') {
            return $stored;
        }

        return preg_replace('/\s+/', '', (string) env('BREVO_API_KEY')) ?? '';
    }

    public function send(Mailable $mailable, string $to): void
    {
        $from = trim((string) (SystemSetting::getValue('mail_from_address') ?: SystemSetting::getValue('mail_username') ?: env('MAIL_FROM_ADDRESS') ?: env('MAIL_USERNAME')));
        if ($from === '') {
            throw new RuntimeException('Set the Gmail sender address in Settings before sending email.');
        }

        $response = Http::withHeaders([
            'api-key' => $this->key(),
            'accept' => 'application/json',
        ])->timeout(20)->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => [
                'email' => $from,
                'name' => (string) (SystemSetting::getValue('mail_from_name') ?: config('app.name', 'Enrollment Analytics')),
            ],
            'to' => [['email' => $to]],
            'subject' => $mailable->envelope()->subject ?? 'Enrollment Analytics',
            'htmlContent' => $mailable->render(),
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Brevo rejected the email (HTTP '.$response->status().'). Confirm the sender address is verified in Brevo.');
        }
    }
}
