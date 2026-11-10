<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginLockoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        Role::create(['role_name' => 'Super Administrator', 'description' => 'Super']);
        Role::create(['role_name' => 'Administrator', 'description' => 'Admin']);
        Role::create(['role_name' => 'User', 'description' => 'User']);
        User::factory()->create([
            'username' => 'staff',
            'email' => 'staff@example.com',
            'password' => 'secret123',
            'role_id' => 3,
            'status' => 'active',
        ]);
    }

    public function test_forgot_password_is_hidden_until_two_failed_logins(): void
    {
        $this->get('/login')->assertOk()->assertDontSee('Forgot password');

        $this->from('/login')->post('/login', [
            'username' => 'staff',
            'password' => 'wrong',
        ])->assertRedirect('/login');

        $this->get('/login')->assertDontSee('Forgot password');

        $this->from('/login')->post('/login', [
            'username' => 'staff',
            'password' => 'wrong',
        ])->assertRedirect('/login');

        $this->get('/login')->assertSee('Forgot password');
    }

    public function test_third_failure_starts_a_15_second_lockout(): void
    {
        $this->failLogin(3);

        $this->get('/login')
            ->assertSee('Forgot password')
            ->assertSee('Try again in')
            ->assertSee('15s');

        $this->from('/login')->post('/login', [
            'username' => 'staff',
            'password' => 'secret123',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();

        $this->travel(15)->seconds();

        $this->post('/login', [
            'username' => 'staff',
            'password' => 'secret123',
        ])->assertRedirect();

        $this->assertAuthenticated();
    }

    public function test_lockout_escalates_to_30_then_60_seconds(): void
    {
        $this->failLogin(3);
        $this->get('/login')->assertSee('15s');

        $this->travel(15)->seconds();
        $this->failLogin(3);
        $this->get('/login')->assertSee('30s');

        $this->travel(30)->seconds();
        $this->failLogin(3);
        $this->get('/login')->assertSee('1:00');
    }

    private function failLogin(int $times): void
    {
        for ($i = 0; $i < $times; $i++) {
            $this->from('/login')->post('/login', [
                'username' => 'staff',
                'password' => 'wrong',
            ]);
        }
    }
}
