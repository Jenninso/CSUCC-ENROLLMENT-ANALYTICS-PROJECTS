<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\PrivilegeSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivilegeSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_accounts_gain_the_current_spr_and_at_risk_privileges(): void
    {
        $userRole = Role::create(['role_name' => 'User', 'description' => 'Staff']);
        $adminRole = Role::create(['role_name' => 'Administrator', 'description' => 'Admin']);

        $view = Permission::query()->where('key', 'enrollment.view')->firstOrFail();

        $staff = User::factory()->create([
            'role_id' => $userRole->id,
            'status' => 'active',
            'must_change_password' => false,
            'credentials_completed_at' => now(),
        ]);
        $staff->permissions()->sync([$view->id]);

        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'status' => 'active',
            'must_change_password' => false,
            'credentials_completed_at' => now(),
        ]);
        $admin->permissions()->sync([$view->id]);

        app(PrivilegeSync::class)->sync();

        $staffKeys = $staff->permissions()->pluck('key')->all();
        $this->assertContains('spr.view', $staffKeys);
        $this->assertContains('spr.edit', $staffKeys);
        $this->assertContains('at_risk.view', $staffKeys);
        $this->assertNotContains('at_risk.manage', $staffKeys);
        $this->assertContains('enrollment.view', $staffKeys);

        $adminKeys = $admin->permissions()->pluck('key')->all();
        $this->assertContains('spr.view', $adminKeys);
        $this->assertContains('at_risk.manage', $adminKeys);
        $this->assertContains('enrollment.manage', $adminKeys);

        $this->assertTrue(
            $userRole->permissions()->where('key', 'spr.view')->exists()
        );
    }
}
