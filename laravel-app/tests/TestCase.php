<?php

namespace Tests;

use App\Support\Username;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    protected function loginViaOtp(string $login, string $password): TestResponse
    {
        $username = str_contains($login, '@')
            ? Username::fromEmail($login)
            : Username::normalize($login);

        $this->post('/login', [
            'username' => $username,
            'password' => $password,
        ])->assertRedirect(route('login.otp'));

        $code = session('otp_hint');
        $this->assertNotEmpty($code, 'Expected development/testing OTP hint in session');

        return $this->post('/login/otp', ['otp' => $code]);
    }
}
