<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_has_no_separate_student_account(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Create a student account')
            ->assertDontSee('Student?');

        $this->get('/student/register')->assertNotFound();
    }

    public function test_a_student_account_cannot_sign_in(): void
    {
        $role = Role::firstOrCreate(['role_name' => 'Student'], ['description' => 'Student portal']);
        $student = User::factory()->create([
            'employee_id' => '2025-01001',
            'username' => '2025-01001',
            'role_id' => $role->id,
            'status' => 'active',
            'must_change_password' => false,
            'credentials_completed_at' => now(),
        ]);

        $this->post(route('login'), [
            'username' => $student->username,
            'password' => 'password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }
}
