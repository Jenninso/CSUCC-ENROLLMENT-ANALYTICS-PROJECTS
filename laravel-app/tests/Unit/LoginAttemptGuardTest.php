<?php

namespace Tests\Unit;

use App\Services\LoginAttemptGuard;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LoginAttemptGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->freezeTime();
    }

    public function test_lockouts_escalate_from_15_to_30_to_60_seconds(): void
    {
        $guard = new LoginAttemptGuard;
        $ip = '127.0.0.1';
        $email = 'staff@example.com';

        $this->failTimes($guard, $ip, $email, 2);
        $this->assertFalse($guard->isLocked($ip, $email));
        $this->assertTrue($guard->showForgotPassword($ip, $email));

        $guard->recordFailure($ip, $email);
        $this->assertSame(15, $guard->remaining($ip, $email));

        $this->travel(15)->seconds();
        $this->assertFalse($guard->isLocked($ip, $email));

        $this->failTimes($guard, $ip, $email, 3);
        $this->assertSame(30, $guard->remaining($ip, $email));

        $this->travel(30)->seconds();
        $this->failTimes($guard, $ip, $email, 3);
        $this->assertSame(60, $guard->remaining($ip, $email));
    }

    public function test_forgot_password_appears_after_two_failures(): void
    {
        $guard = new LoginAttemptGuard;
        $ip = '127.0.0.1';
        $email = 'staff@example.com';

        $guard->recordFailure($ip, $email);
        $this->assertFalse($guard->showForgotPassword($ip, $email));

        $guard->recordFailure($ip, $email);
        $this->assertTrue($guard->showForgotPassword($ip, $email));
    }

    private function failTimes(LoginAttemptGuard $guard, string $ip, string $email, int $times): void
    {
        for ($i = 0; $i < $times; $i++) {
            $guard->recordFailure($ip, $email);
        }
    }
}
