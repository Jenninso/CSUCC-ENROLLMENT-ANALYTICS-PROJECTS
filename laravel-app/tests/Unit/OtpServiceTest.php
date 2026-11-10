<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_hashed_single_use_six_digit_otp(): void
    {
        $otp = app(OtpService::class);
        $issued = $otp->issue('person@example.com', 'registration');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $issued);
        $this->assertDatabaseHas('otp_verifications', [
            'email' => 'person@example.com',
            'purpose' => 'registration',
        ]);

        $row = \App\Models\OtpVerification::query()->first();
        $this->assertTrue(Hash::check($issued, $row->otp_hash));
        $this->assertNotSame($issued, $row->otp_hash);

        $this->assertTrue($otp->verifyOtp('person@example.com', 'registration', $issued)['ok']);
        $this->assertFalse($otp->verifyOtp('person@example.com', 'registration', $issued)['ok']);
    }

    public function test_rejects_expired_and_locked_codes(): void
    {
        config(['otp.max_attempts' => 2, 'otp.expires_minutes' => 5]);
        $otp = app(OtpService::class);
        $code = $otp->issue('lock@example.com', 'login');

        $this->assertSame('Invalid verification code.', $otp->verifyOtp('lock@example.com', 'login', '111111')['message']);
        $locked = $otp->verifyOtp('lock@example.com', 'login', '222222');
        $this->assertSame('Too many attempts. Please request a new code.', $locked['message']);

        $fresh = $otp->issue('expire@example.com', 'login');
        \App\Models\OtpVerification::query()->where('email', 'expire@example.com')->update([
            'expires_at' => now()->subMinute(),
            'invalidated_at' => null,
            'verified_at' => null,
            'attempts' => 0,
        ]);
        $this->assertSame(
            'The verification code has expired. Please request a new code.',
            $otp->verifyOtp('expire@example.com', 'login', $fresh)['message']
        );
    }

    public function test_login_requires_otp_only_on_first_time_setup(): void
    {
        $role = \App\Models\Role::create(['role_name' => 'User', 'description' => 'User']);
        $user = User::factory()->create([
            'username' => 'mfa',
            'email' => 'mfa@example.com',
            'password' => 'secret123',
            'role_id' => $role->id,
            'status' => 'active',
            'must_change_password' => true,
            'credentials_completed_at' => null,
        ]);

        $this->post('/login', [
            'username' => 'mfa',
            'password' => 'secret123',
        ])->assertRedirect(route('login.otp'));
        $this->assertGuest();

        $code = session('otp_hint');
        $this->assertNotEmpty($code);
        $this->post('/login/otp', ['otp' => $code]);
        $this->assertAuthenticated();

        Auth::logout();
        $user->update([
            'must_change_password' => false,
            'credentials_completed_at' => now(),
        ]);

        $this->post('/login', [
            'username' => 'mfa',
            'password' => 'secret123',
        ])->assertRedirect();
        $this->assertAuthenticated();
        $this->assertFalse(session()->has('login_otp'));
    }
}
