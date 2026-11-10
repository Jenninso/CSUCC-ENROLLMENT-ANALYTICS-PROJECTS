<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\ForecastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_rejects_invalid_password(): void
    {
        $this->seedRoles();
        User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'secret123',
            'role_id' => 1,
            'status' => 'active',
        ]);

        $this->post('/login', [
            'username' => 'admin',
            'password' => 'wrong',
        ])->assertSessionHasErrors('username');
    }

    public function test_disabled_account_cannot_login(): void
    {
        $this->seedRoles();
        User::factory()->create([
            'username' => 'off',
            'email' => 'off@example.com',
            'password' => 'secret123',
            'role_id' => 1,
            'status' => 'inactive',
        ]);

        $this->post('/login', [
            'username' => 'off',
            'password' => 'secret123',
        ])->assertSessionHasErrors('username');
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_growth_rate_formula(): void
    {
        $service = app(AnalyticsService::class);
        $this->assertSame(-7.22, $service->growthRate(4500, 4850));
    }

    public function test_forecast_requires_history(): void
    {
        $result = app(ForecastService::class)->generate();
        $this->assertFalse($result['ok']);
    }

    public function test_user_role_cannot_manage_users(): void
    {
        $this->seedRoles();
        $user = User::factory()->create([
            'role_id' => 3,
            'status' => 'active',
        ]);

        $this->actingAs($user)->get('/users')->assertForbidden();
    }

    private function seedRoles(): void
    {
        Role::create(['role_name' => 'Super Administrator', 'description' => 'Super Admin']);
        Role::create(['role_name' => 'Administrator', 'description' => 'Admin']);
        Role::create(['role_name' => 'User', 'description' => 'User']);
    }
}
