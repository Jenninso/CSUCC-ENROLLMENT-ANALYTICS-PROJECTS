<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SpesApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpesApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_save_and_print_a_spes_application(): void
    {
        $user = $this->staff();

        $this->actingAs($user)
            ->get(route('spes.create'))
            ->assertOk()
            ->assertSee('SPES application form')
            ->assertSee('15 to 30');

        $this->actingAs($user)
            ->post(route('spes.store'), $this->payload())
            ->assertRedirect();

        $application = SpesApplication::query()->first();
        $this->assertNotNull($application);
        $this->assertSame('SPES-2026-'.str_pad((string) $application->id, 4, '0', STR_PAD_LEFT), $application->reference_no);
        $this->assertSame(19, $application->age);
        $this->assertSame('Santos', $application->last_name);
        $this->assertTrue($application->has_birth_certificate);
        $this->assertFalse($application->has_photo);
        $this->assertFalse($application->previous_spes);

        $this->actingAs($user)
            ->get(route('spes.show', $application))
            ->assertOk()
            ->assertSee('SPES-2026-')
            ->assertSee('SANTOS, Maria Reyes');

        $this->actingAs($user)
            ->get(route('spes.print', $application))
            ->assertOk()
            ->assertSee('SPECIAL PROGRAM FOR THE EMPLOYMENT OF STUDENTS')
            ->assertSee('employer 40%');
    }

    public function test_applicant_outside_the_spes_age_range_is_rejected(): void
    {
        $user = $this->staff();

        $this->actingAs($user)
            ->from(route('spes.create'))
            ->post(route('spes.store'), $this->payload(['birth_date' => '2014-01-01']))
            ->assertRedirect(route('spes.create'))
            ->assertSessionHasErrors('birth_date');

        $this->assertDatabaseCount('spes_applications', 0);
    }

    private function staff(): User
    {
        $role = Role::create(['role_name' => 'Super Administrator', 'description' => 'Super Admin']);

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => 'active',
            'must_change_password' => false,
            'credentials_completed_at' => now(),
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'last_name' => 'Santos',
            'first_name' => 'Maria',
            'middle_name' => 'Reyes',
            'sex' => 'female',
            'civil_status' => 'single',
            'birth_date' => '2007-03-15',
            'place_of_birth' => 'Cabadbaran City',
            'address' => 'P-1',
            'barangay' => 'Comagascas',
            'city_municipality' => 'Cabadbaran City',
            'province' => 'Agusan del Norte',
            'contact_number' => '09171234567',
            'father_name' => 'Pedro Santos',
            'mother_name' => 'Ana Reyes',
            'parents_annual_income' => 84000,
            'number_of_siblings' => 2,
            'education_level' => 'tertiary',
            'school_name' => 'Caraga State University — Cabadbaran Campus',
            'course' => 'BS Information Technology',
            'year_level' => '2nd Year',
            'general_average' => 88.5,
            'enrollment_status' => 'enrolled',
            'application_year' => 2026,
            'work_period' => 'summer',
            'preferred_days' => 20,
            'beneficiary_type' => 'student',
            'previous_spes' => '0',
            'has_birth_certificate' => '1',
            'has_enrollment_cert' => '1',
            'has_grades' => '1',
            'has_indigency' => '0',
            'has_photo' => '0',
            'application_date' => '2026-11-12',
            'applicant_signature' => 'Maria Reyes Santos',
            'certified' => '1',
            'status' => 'submitted',
        ], $overrides);
    }
}
