<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

class MailSettings
{
    public function apply(bool $verifyPeer = true): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $username = $this->username();
        $password = $this->password();
        if ($username === '' || $password === '') {
            return;
        }

        $port = (int) ($this->value('mail_port') ?: env('MAIL_PORT', 465));
        $host = $this->value('mail_host') ?: env('MAIL_HOST', 'smtp.gmail.com');
        $caFile = $this->caBundlePath();

        if ($caFile) {
            @ini_set('openssl.cafile', $caFile);
            @ini_set('curl.cainfo', $caFile);
        }

        // Gmail STARTTLS on 587 hits a Windows/XAMPP OpenSSL verify failure; implicit SSL on 465 works.
        if ($port === 587 && str_contains(strtolower($host), 'gmail.com')) {
            $port = 465;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.username' => $username,
            'mail.mailers.smtp.password' => $password,
            'mail.mailers.smtp.timeout' => 25,
            'mail.mailers.smtp.scheme' => $port === 465 ? 'smtps' : 'smtp',
            'mail.mailers.smtp.verify_peer' => $verifyPeer,
            'mail.mailers.smtp.local_domain' => '127.0.0.1',
            'mail.from.address' => $this->value('mail_from_address') ?: $username,
            'mail.from.name' => $this->value('mail_from_name') ?: config('app.name', 'Enrollment Analytics'),
        ]);

        Mail::purge();
        $this->configureStream($caFile, $verifyPeer);
    }

    public function isConfigured(): bool
    {
        return $this->username() !== '' && $this->password() !== '';
    }

    public function username(): string
    {
        return trim((string) ($this->value('mail_username') ?: env('MAIL_USERNAME')));
    }

    public function password(): string
    {
        return preg_replace('/\s+/', '', (string) ($this->value('mail_password') ?: env('MAIL_PASSWORD'))) ?? '';
    }

    public function caBundlePath(): ?string
    {
        $target = storage_path('app/certs/cacert.pem');
        if ($this->isUsableBundle($target)) {
            return $target;
        }

        foreach ($this->candidateBundles() as $candidate) {
            if ($this->isUsableBundle($candidate)) {
                $this->copyCaBundle($candidate, $target);

                return $this->isUsableBundle($target) ? $target : $candidate;
            }
        }

        $this->downloadCaBundle($target);

        return $this->isUsableBundle($target) ? $target : null;
    }

    private function configureStream(?string $caFile, bool $verifyPeer): void
    {
        $transport = Mail::mailer('smtp')->getSymfonyTransport();
        if (! $transport instanceof EsmtpTransport) {
            return;
        }

        $stream = $transport->getStream();
        if (! $stream instanceof SocketStream) {
            return;
        }

        $options = $stream->getStreamOptions();
        $options['ssl']['verify_peer'] = $verifyPeer;
        $options['ssl']['verify_peer_name'] = $verifyPeer;
        $options['ssl']['allow_self_signed'] = false;
        if ($caFile && is_readable($caFile)) {
            $options['ssl']['cafile'] = $caFile;
        }

        $stream->setStreamOptions($options);
    }

    private function candidateBundles(): array
    {
        return array_values(array_filter([
            ini_get('openssl.cafile') ?: null,
            ini_get('curl.cainfo') ?: null,
            'C:\\xampp new\\apache\\bin\\curl-ca-bundle.crt',
            'C:\\xampp\\apache\\bin\\curl-ca-bundle.crt',
        ]));
    }

    private function isUsableBundle(?string $path): bool
    {
        return is_string($path) && $path !== '' && is_readable($path) && filesize($path) > 1000;
    }

    private function copyCaBundle(string $source, string $target): void
    {
        try {
            File::ensureDirectoryExists(dirname($target));
            File::copy($source, $target);
        } catch (\Throwable) {
            // Keep using the source path if storage is not writable.
        }
    }

    private function downloadCaBundle(string $target): void
    {
        try {
            File::ensureDirectoryExists(dirname($target));
            $response = Http::timeout(15)->get('https://curl.se/ca/cacert.pem');
            if ($response->successful() && strlen((string) $response->body()) > 1000) {
                File::put($target, $response->body());
            }
        } catch (\Throwable) {
            // Offline or blocked; SMTP can still retry without peer verification.
        }
    }

    private function value(string $key): string
    {
        return trim((string) SystemSetting::getValue($key, ''));
    }
}
