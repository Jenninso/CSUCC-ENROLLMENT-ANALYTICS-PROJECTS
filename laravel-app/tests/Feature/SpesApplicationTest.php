<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\SpesApplication;
use App\Models\User;
use Database\Seeders\SprSyntheticSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpesApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_save_and_print_a_spes_application(): void
    {
        $staff = $this->staff();

        $this->post(route('spr.public.store'), $this->payload())
            ->assertRedirect();

        $application = SpesApplication::query()->first();
        $this->assertNotNull($application);
        $this->assertSame('SPR-2026-'.str_pad((string) $application->id, 4, '0', STR_PAD_LEFT), $application->reference_no);
        $this->assertSame(19, $application->age);
        $this->assertSame('Santos', $application->last_name);
        $this->assertSame('stem', $application->spr_data['shs_track']);
        $this->assertSame('Cabadbaran City', $application->spr_data['home_city']);

        $this->actingAs($staff)
            ->get(route('spes.edit', $application))
            ->assertOk()
            ->assertSee('Save SPR');

        $this->actingAs($staff)
            ->put(route('spes.update', $application), $this->payload(['last_name' => 'Changed']))
            ->assertRedirect(route('spes.show', $application));

        $this->assertSame('Changed', $application->fresh()->last_name);

        $this->actingAs($staff)
            ->put(route('spes.status', $application), ['status' => 'approved', 'remarks' => 'Checked'])
            ->assertRedirect();

        $this->assertSame('approved', $application->fresh()->status);
        $this->assertSame('Changed', $application->fresh()->last_name);
        $this->assertFalse($application->fresh()->student_can_edit);

        $this->actingAs($staff)
            ->get(route('spes.show', $application))
            ->assertOk()
            ->assertSee('SPR-2026-')
            ->assertSee('Changed')
            ->assertSee('Maria')
            ->assertSee('Students do not sign in')
            ->assertSee('Edit SPR')
            ->assertDontSee('Allow one edit');

        $this->actingAs($staff)
            ->get(route('spes.print', $application))
            ->assertOk()
            ->assertSee('STUDENT PERSONAL RECORD (SPR)')
            ->assertSee('8.5in 13in')
            ->assertSee('F-ADM-001')
            ->assertSee('CHEELA MAE C. CASTRO')
            ->assertSee('Download PDF');

        $download = $this->actingAs($staff)->get(route('spes.pdf', $application));
        $download->assertOk();
        ob_start();
        $download->sendContent();
        $pdf = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame(612.0, $this->pdfPageBox($pdf)[0]);
        $this->assertSame(936.0, $this->pdfPageBox($pdf)[1]);
        $this->assertSame(1, $this->pdfPageCount($pdf));
    }

    private function pdfPageBox(string $pdf): array
    {
        preg_match('/MediaBox\s*\[\s*0\s+0\s+([0-9.]+)\s+([0-9.]+)\s*\]/', $pdf, $match);

        return [isset($match[1]) ? (float) $match[1] : 0.0, isset($match[2]) ? (float) $match[2] : 0.0];
    }

    private function pdfPageCount(string $pdf): int
    {
        preg_match('/\/Count\s+(\d+)/', $pdf, $match);

        return isset($match[1]) ? (int) $match[1] : 0;
    }

    public function test_staff_role_can_edit_a_student_spr(): void
    {
        $staff = $this->officeUser(['spr.edit', 'enrollment.view']);

        $this->post(route('spr.public.store'), $this->payload())->assertRedirect();
        $application = SpesApplication::query()->first();

        $this->actingAs($staff)
            ->get(route('spes.index'))
            ->assertOk()
            ->assertSee('Actions')
            ->assertSee(route('spes.show', $application), false)
            ->assertSee(route('spes.edit', $application), false)
            ->assertSee(route('spes.print', $application), false)
            ->assertSee(route('spes.pdf', $application), false);

        $this->actingAs($staff)
            ->put(route('spes.update', $application), $this->payload(['first_name' => 'Ana']))
            ->assertRedirect(route('spes.show', $application));

        $this->assertSame('Ana', $application->fresh()->first_name);
        $this->assertSame('Santos', $application->fresh()->last_name);
    }

    public function test_staff_without_spr_edit_cannot_change_the_form(): void
    {
        $staff = $this->officeUser(['enrollment.view']);

        $this->post(route('spr.public.store'), $this->payload())->assertRedirect();
        $application = SpesApplication::query()->first();

        $this->actingAs($staff)
            ->get(route('spes.index'))
            ->assertOk()
            ->assertSee('Actions')
            ->assertDontSee(route('spes.edit', $application), false);

        $this->actingAs($staff)
            ->put(route('spes.update', $application), $this->payload(['first_name' => 'Ana']))
            ->assertForbidden();

        $this->assertSame('Maria', $application->fresh()->first_name);
    }

    public function test_synthetic_spr_seeder_fills_statistics_without_student_records(): void
    {
        $this->assertSame(0, SpesApplication::query()->count());

        $this->seed(SprSyntheticSeeder::class);

        $this->assertSame(72, SpesApplication::query()->count());
        $this->assertSame(12, SpesApplication::query()->where('status', 'submitted')->count());
        $this->assertSame(12, SpesApplication::query()->where('status', 'approved')->count());
        $this->assertSame(12, SpesApplication::query()->where('status', 'employed')->count());
    }

    public function test_a_younger_student_can_still_save_the_spr(): void
    {
        $this->post(route('spr.public.store'), $this->payload(['birth_date' => '2014-01-01']))
            ->assertRedirect();

        $this->assertDatabaseCount('spes_applications', 1);
        $this->assertSame(\Illuminate\Support\Carbon::parse('2014-01-01')->age, SpesApplication::query()->first()->age);
    }

    public function test_a_guest_can_fill_the_shared_spr_link_and_nothing_else(): void
    {
        $this->get(route('spr.public'))
            ->assertOk()
            ->assertSee('Double-check every answer before you save')
            ->assertSee('you cannot edit this form')
            ->assertDontSee('Student Records')
            ->assertDontSee('Dashboard');

        $this->post(route('spr.public.store'), $this->payload())
            ->assertRedirect();

        $application = SpesApplication::query()->first();
        $this->assertNotNull($application);
        $this->assertNull($application->user_id);
        $this->assertSame(40, strlen((string) $application->public_token));
        $this->assertSame('Santos', $application->last_name);

        $this->get(route('spr.public.edit', $application->public_token))
            ->assertOk()
            ->assertSee('Santos')
            ->assertSee($application->reference_no);

        $this->put(route('spr.public.update', $application->public_token), $this->payload(['first_name' => 'Ana']))
            ->assertRedirect(route('spr.public.edit', $application->public_token));

        $this->assertSame('Maria', $application->fresh()->first_name);
        $this->get(route('spr.public.edit', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))->assertNotFound();
    }

    private function officeUser(array $permissionKeys): User
    {
        $role = Role::firstOrCreate(['role_name' => 'User'], ['description' => 'Staff']);
        $ids = [];
        foreach ($permissionKeys as $key) {
            $ids[] = Permission::query()->firstOrCreate(
                ['key' => $key],
                ['name' => $key, 'module' => 'enrollment']
            )->id;
        }
        $role->permissions()->sync($ids);

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => 'active',
            'must_change_password' => false,
            'credentials_completed_at' => now(),
        ]);
    }

    private function staff(): User
    {
        $role = Role::firstOrCreate(['role_name' => 'Super Administrator'], ['description' => 'Super Admin']);

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
            'student_level' => 'undergraduate',
            'admission_type' => 'new_student',
            'birth_date' => '2007-03-15',
            'sex_at_birth' => 'Female',
            'civil_status' => 'Single',
            'place_of_birth' => 'Cabadbaran City',
            'home_purok' => 'P-1',
            'home_barangay' => 'Comagascas',
            'home_city' => 'Cabadbaran City',
            'home_province' => 'Agusan del Norte',
            'mobile' => '09171234567',
            'father_name' => 'Pedro Santos',
            'mother_name' => 'Ana Reyes',
            'shs_track' => 'stem',
            'secondary_school' => 'Cabadbaran City NHS',
            'priority_1' => 'BS Information Technology',
            'preferred_campus' => 'cabadbaran',
            'signature_name' => 'Maria Reyes Santos',
            'certified' => '1',
            'status' => 'submitted',
        ], $overrides);
    }
}
