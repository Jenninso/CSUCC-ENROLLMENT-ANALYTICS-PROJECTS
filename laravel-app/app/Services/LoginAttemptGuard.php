<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class LoginAttemptGuard
{
    public const FAILURES_BEFORE_FORGOT = 2;

    public const FAILURES_PER_LOCKOUT = 3;

    /** @var list<int> */
    public const LOCKOUT_SECONDS = [15, 30, 60];

    public function remaining(string $ip, string $email): int
    {
        $until = (int) ($this->read($ip, $email)['locked_until'] ?? 0);

        return max(0, $until - now()->getTimestamp());
    }

    public function isLocked(string $ip, string $email): bool
    {
        return $this->remaining($ip, $email) > 0;
    }

    public function showForgotPassword(string $ip, string $email): bool
    {
        return ((int) ($this->read($ip, $email)['failures'] ?? 0)) >= self::FAILURES_BEFORE_FORGOT;
    }

    public function failureCount(string $ip, string $email): int
    {
        return (int) ($this->read($ip, $email)['failures'] ?? 0);
    }

    /**
     * @return array{failures: int, lockouts: int, locked_until: int, remaining: int, show_forgot: bool}
     */
    public function recordFailure(string $ip, string $email): array
    {
        $data = $this->read($ip, $email);
        $data['failures'] = ((int) $data['failures']) + 1;

        if ($data['failures'] % self::FAILURES_PER_LOCKOUT === 0) {
            $data['lockouts'] = ((int) $data['lockouts']) + 1;
            $index = min(((int) $data['lockouts']) - 1, count(self::LOCKOUT_SECONDS) - 1);
            $data['locked_until'] = now()->getTimestamp() + self::LOCKOUT_SECONDS[$index];
        }

        $this->write($ip, $email, $data);

        return $this->snapshot($ip, $email);
    }

    public function clear(string $ip, string $email): void
    {
        Cache::forget($this->key($ip, $email));
    }

    /**
     * @return array{failures: int, lockouts: int, locked_until: int, remaining: int, show_forgot: bool}
     */
    public function snapshot(string $ip, string $email): array
    {
        $data = $this->read($ip, $email);

        return [
            'failures' => (int) $data['failures'],
            'lockouts' => (int) $data['lockouts'],
            'locked_until' => (int) $data['locked_until'],
            'remaining' => $this->remaining($ip, $email),
            'show_forgot' => $this->showForgotPassword($ip, $email),
        ];
    }

    /**
     * @return array{failures: int, lockouts: int, locked_until: int}
     */
    private function read(string $ip, string $email): array
    {
        /** @var array{failures?: int, lockouts?: int, locked_until?: int} $data */
        $data = Cache::get($this->key($ip, $email), []);

        return [
            'failures' => (int) ($data['failures'] ?? 0),
            'lockouts' => (int) ($data['lockouts'] ?? 0),
            'locked_until' => (int) ($data['locked_until'] ?? 0),
        ];
    }

    /**
     * @param  array{failures: int, lockouts: int, locked_until: int}  $data
     */
    private function write(string $ip, string $email, array $data): void
    {
        Cache::put($this->key($ip, $email), $data, now()->addMinutes(30));
    }

    private function key(string $ip, string $email): string
    {
        return 'login_guard:'.sha1(strtolower(trim($email)).'|'.$ip);
    }
}
