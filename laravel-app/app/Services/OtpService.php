<?php

namespace App\Services;

use App\Mail\OtpCodeMail;
use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public bool $lastSendSucceeded = false;

    public ?string $lastSendError = null;

    public ?string $lastPlainCode = null;

    /**
     * @return array{ok: bool, code?: string, message?: string, reason?: string, otp?: OtpVerification, cooldown?: int, expires_at?: string, last_sent_at?: string}
     */
    public function generateOtp(
        string $email,
        string $purpose,
        ?User $user = null,
        ?Request $request = null,
    ): array {
        $email = strtolower(trim($email));
        $purpose = $this->normalizePurpose($purpose);
        $minutes = (int) config('otp.expires_minutes', 5);
        $maxAttempts = (int) config('otp.max_attempts', 5);
        $ip = $request?->ip();

        $rate = $this->assertCanRequest($email, $purpose, $ip);
        if (! $rate['ok']) {
            $this->audit('otp_rate_limited', $email, $purpose, $rate['message'] ?? 'Rate limited', $request, $user);

            return $rate;
        }

        $this->invalidateOtp($email, $purpose);

        $code = $this->secureCode();
        $otp = OtpVerification::create([
            'user_id' => $user?->id,
            'email' => $email,
            'purpose' => $purpose,
            'otp_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($minutes),
            'attempts' => 0,
            'max_attempts' => $maxAttempts,
            'last_sent_at' => now(),
            'ip_address' => $ip,
        ]);

        $this->rememberRequest($email, $purpose, $ip);
        $this->lastPlainCode = $code;
        $this->audit('otp_requested', $email, $purpose, 'OTP issued for '.$purpose, $request, $user);

        return [
            'ok' => true,
            'code' => $code,
            'otp' => $otp,
            'expires_at' => $otp->expires_at?->toIso8601String(),
            'last_sent_at' => $otp->last_sent_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    public function sendOtp(string $email, string $code, string $purpose, ?int $minutes = null): array
    {
        $minutes ??= (int) config('otp.expires_minutes', 5);
        $purpose = $this->normalizePurpose($purpose);
        $sent = $this->mail($email, $code, $purpose, $minutes);

        return $sent
            ? ['ok' => true]
            : ['ok' => false, 'message' => $this->lastSendError ?: 'The verification email could not be sent.'];
    }

    /**
     * Generate + send. Backward-compatible helper used by existing controllers.
     */
    public function issue(string $email, string $purpose, ?User $user = null, ?Request $request = null): string
    {
        $generated = $this->generateOtp($email, $purpose, $user, $request);
        if (! ($generated['ok'] ?? false)) {
            $this->lastSendSucceeded = false;
            $this->lastSendError = $generated['message'] ?? 'Unable to issue verification code.';
            $this->lastPlainCode = null;

            return '';
        }

        $code = (string) ($generated['code'] ?? '');
        $this->sendOtp($email, $code, $purpose);

        return $code;
    }

    /**
     * @return array{ok: bool, message?: string, reason?: string, cooldown?: int, expires_at?: string, last_sent_at?: string, code?: string}
     */
    public function resendOtp(string $email, string $purpose, ?User $user = null, ?Request $request = null): array
    {
        $email = strtolower(trim($email));
        $purpose = $this->normalizePurpose($purpose);
        $active = $this->activeOtp($email, $purpose);

        if ($active && $active->last_sent_at) {
            $cooldown = (int) config('otp.resend_cooldown_seconds', 60);
            $elapsed = $active->last_sent_at->diffInSeconds(now());
            if ($elapsed < $cooldown) {
                $wait = $cooldown - $elapsed;
                $this->audit('otp_rate_limited', $email, $purpose, 'Resend cooldown active', $request, $user);

                return [
                    'ok' => false,
                    'reason' => 'cooldown',
                    'cooldown' => $wait,
                    'message' => 'Please wait before requesting another code.',
                ];
            }
        }

        $result = $this->generateOtp($email, $purpose, $user, $request);
        if (! ($result['ok'] ?? false)) {
            return $result;
        }

        $send = $this->sendOtp($email, (string) $result['code'], $purpose);
        if (! ($send['ok'] ?? false)) {
            return array_merge($result, $send);
        }

        return $result;
    }

    /**
     * @return array{ok: bool, message?: string, reason?: string}
     */
    public function verifyOtp(string $email, string $purpose, string $code, ?Request $request = null, ?User $user = null): array
    {
        $email = strtolower(trim($email));
        $purpose = $this->normalizePurpose($purpose);
        $code = preg_replace('/\D+/', '', $code) ?? '';

        $otp = $this->latestOtp($email, $purpose);

        if (! $otp || $otp->isInvalidated()) {
            $this->audit('otp_failed', $email, $purpose, 'OTP missing or invalidated', $request, $user);

            return ['ok' => false, 'reason' => 'missing', 'message' => 'Invalid verification code.'];
        }

        if ($otp->isVerified()) {
            $this->audit('otp_failed', $email, $purpose, 'OTP already used', $request, $user);

            return ['ok' => false, 'reason' => 'used', 'message' => 'Invalid verification code.'];
        }

        if ($otp->isExpired()) {
            $otp->update(['invalidated_at' => now()]);
            $this->audit('otp_failed', $email, $purpose, 'OTP expired', $request, $user);

            return ['ok' => false, 'reason' => 'expired', 'message' => 'The verification code has expired. Please request a new code.'];
        }

        if ($otp->isLocked()) {
            $this->audit('otp_failed', $email, $purpose, 'OTP attempt limit reached', $request, $user);

            return ['ok' => false, 'reason' => 'locked', 'message' => 'Too many attempts. Please request a new code.'];
        }

        $otp->increment('attempts');
        $otp->refresh();

        if (! Hash::check($code, $otp->otp_hash)) {
            if ($otp->isLocked()) {
                $otp->update(['invalidated_at' => now()]);
                $this->audit('otp_failed', $email, $purpose, 'OTP locked after failed attempts', $request, $user);

                return ['ok' => false, 'reason' => 'locked', 'message' => 'Too many attempts. Please request a new code.'];
            }

            $this->audit('otp_failed', $email, $purpose, 'OTP mismatch', $request, $user);

            return ['ok' => false, 'reason' => 'invalid', 'message' => 'Invalid verification code.'];
        }

        $otp->update([
            'verified_at' => now(),
            'invalidated_at' => now(),
        ]);

        $this->audit('otp_verified', $email, $purpose, 'OTP verified for '.$purpose, $request, $user);

        return ['ok' => true];
    }

    /**
     * Backward-compatible bool verify used by older callers.
     */
    public function verify(string $email, string $purpose, string $code): bool
    {
        return (bool) ($this->verifyOtp($email, $purpose, $code)['ok'] ?? false);
    }

    public function invalidateOtp(string $email, string $purpose): int
    {
        $email = strtolower(trim($email));
        $purpose = $this->normalizePurpose($purpose);

        return OtpVerification::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->whereNull('invalidated_at')
            ->update(['invalidated_at' => now()]);
    }

    public function revealForLocal(?string $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }

        if (app()->environment('testing')) {
            return $code;
        }

        if (config('otp.dev_reveal') && ! app()->environment('production')) {
            return $code;
        }

        if ($this->lastSendSucceeded) {
            return null;
        }

        if (config('otp.dev_reveal') || (config('app.debug') && ! app()->environment('production'))) {
            return $code;
        }

        return null;
    }

    public function purposeLabel(string $purpose): string
    {
        $purpose = $this->normalizePurpose($purpose);

        return (string) (config('otp.purposes.'.$purpose) ?? 'verification');
    }

    public function normalizePurpose(string $purpose): string
    {
        $purpose = strtolower(trim($purpose));
        $aliases = config('otp.purpose_aliases', []);

        return $aliases[$purpose] ?? $purpose;
    }

    public function activeOtp(string $email, string $purpose): ?OtpVerification
    {
        $otp = $this->latestOtp($email, $purpose);

        return ($otp && $otp->isActive()) ? $otp : null;
    }

    public function latestOtp(string $email, string $purpose): ?OtpVerification
    {
        $email = strtolower(trim($email));
        $purpose = $this->normalizePurpose($purpose);

        return OtpVerification::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->latest('id')
            ->first();
    }

    public function resendCooldownSeconds(string $email, string $purpose): int
    {
        $otp = $this->latestOtp($email, $purpose);
        if (! $otp?->last_sent_at) {
            return 0;
        }

        $cooldown = (int) config('otp.resend_cooldown_seconds', 60);
        $elapsed = $otp->last_sent_at->diffInSeconds(now());

        return max(0, $cooldown - $elapsed);
    }

    public function expiresInSeconds(string $email, string $purpose): int
    {
        $otp = $this->activeOtp($email, $purpose);
        if (! $otp?->expires_at) {
            return 0;
        }

        return max(0, now()->diffInSeconds($otp->expires_at, false));
    }

    private function secureCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * @return array{ok: bool, message?: string, reason?: string, cooldown?: int}
     */
    private function assertCanRequest(string $email, string $purpose, ?string $ip): array
    {
        $window = max(1, (int) config('otp.request_window_minutes', 15));
        $maxEmail = max(1, (int) config('otp.max_requests_per_window', 5));
        $maxIp = max(1, (int) config('otp.max_requests_per_ip', 20));

        $emailKey = 'otp:req:email:'.sha1($email.'|'.$purpose);
        $emailCount = (int) Cache::get($emailKey, 0);
        if ($emailCount >= $maxEmail) {
            return [
                'ok' => false,
                'reason' => 'rate_limited',
                'message' => 'Please wait before requesting another code.',
            ];
        }

        if ($ip) {
            $ipKey = 'otp:req:ip:'.sha1($ip);
            $ipCount = (int) Cache::get($ipKey, 0);
            if ($ipCount >= $maxIp) {
                return [
                    'ok' => false,
                    'reason' => 'rate_limited',
                    'message' => 'Please wait before requesting another code.',
                ];
            }
        }

        return ['ok' => true];
    }

    private function rememberRequest(string $email, string $purpose, ?string $ip): void
    {
        $window = max(1, (int) config('otp.request_window_minutes', 15));
        $ttl = now()->addMinutes($window);

        $emailKey = 'otp:req:email:'.sha1($email.'|'.$purpose);
        Cache::put($emailKey, ((int) Cache::get($emailKey, 0)) + 1, $ttl);

        if ($ip) {
            $ipKey = 'otp:req:ip:'.sha1($ip);
            Cache::put($ipKey, ((int) Cache::get($ipKey, 0)) + 1, $ttl);
        }
    }

    private function mail(string $email, string $code, string $purpose, int $minutes): bool
    {
        $label = $this->purposeLabel($purpose);
        $this->lastSendSucceeded = false;
        $this->lastSendError = null;

        try {
            $mailer = (string) config('mail.default');
            if (app()->environment('testing') || in_array($mailer, ['log', 'array'], true)) {
                Mail::mailer($mailer === 'smtp' ? 'array' : $mailer)->to($email)->send(new OtpCodeMail($code, $label, $minutes));
                $this->lastSendSucceeded = true;

                return true;
            }

            $settings = app(MailSettings::class);
            if (! $settings->isConfigured()) {
                throw new \RuntimeException('SMTP is not configured. Open Settings and save the Gmail address and App Password used to send OTP.');
            }

            $settings->apply(true);
            try {
                Mail::to($email)->send(new OtpCodeMail($code, $label, $minutes));
            } catch (\Throwable $e) {
                if (! $this->isCertificateError($e)) {
                    throw $e;
                }

                Log::warning('OTP SMTP certificate check failed, retrying without peer verification: '.$e->getMessage());
                $settings->apply(false);
                Mail::to($email)->send(new OtpCodeMail($code, $label, $minutes));
            }

            $this->lastSendSucceeded = true;

            return true;
        } catch (\Throwable $e) {
            $this->lastSendError = $this->friendlyError($e);
            Log::warning('OTP email failed: '.$e->getMessage());

            return false;
        }
    }

    private function audit(string $action, string $email, string $purpose, string $details, ?Request $request, ?User $user): void
    {
        try {
            app(AuditLogger::class)->log(
                $action,
                'authentication',
                $details.' ('.$email.', '.$purpose.')',
                $request,
                $user
            );
        } catch (\Throwable) {
            // Audit must never break OTP flow.
        }
    }

    private function isCertificateError(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'certificate verify failed')
            || str_contains($message, 'unable to connect with starttls')
            || str_contains($message, 'ssl operation failed');
    }

    private function friendlyError(\Throwable $e): string
    {
        if ($this->isCertificateError($e)) {
            return 'Could not make a secure connection to the mail server. Check internet access and Settings → Email / OTP sender.';
        }

        return $e->getMessage();
    }
}
