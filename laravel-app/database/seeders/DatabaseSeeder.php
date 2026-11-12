<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\SecretQuestion;
use App\Services\AccountService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $super = Role::create([
            'role_name' => 'Super Administrator',
            'description' => 'Full control: accounts, privileges, deletions, and all modules.',
        ]);
        $admin = Role::create([
            'role_name' => 'Administrator',
            'description' => 'Approves registrations, updates accounts, and requests deletions.',
        ]);
        $userRole = Role::create([
            'role_name' => 'User',
            'description' => 'Standard staff access based on assigned privileges.',
        ]);
        Role::create([
            'role_name' => 'Student',
            'description' => 'Student portal for the SPES form and academic status reasons.',
        ]);

        foreach (config('permissions.catalog') as $key => [$name, $module]) {
            Permission::create(['key' => $key, 'name' => $name, 'module' => $module]);
        }

        $permissions = Permission::query()->pluck('id', 'key');
        foreach (config('permissions.role_defaults') as $roleName => $keys) {
            $role = Role::query()->where('role_name', $roleName)->first();
            if (! $role) {
                continue;
            }
            $ids = $keys === ['*'] ? $permissions->values()->all() : collect($keys)->map(fn ($k) => $permissions[$k] ?? null)->filter()->all();
            $role->permissions()->sync($ids);
        }

        foreach (config('secret_questions') as $question) {
            SecretQuestion::query()->firstOrCreate(
                ['question' => $question],
                ['is_active' => true]
            );
        }

        $accounts = app(AccountService::class);
        $hash = $accounts->hashAnswer('sample');
        $qIds = SecretQuestion::query()->orderBy('id')->limit(3)->pluck('id')->all();

        $seedUsers = [
            [
                'employee_id' => 'EMP-0001',
                'first_name' => 'Maria',
                'middle_name' => 'Santos',
                'last_name' => 'Reyes',
                'username' => 'superadmin',
                'email' => 'superadmin@enrollment.com',
                'role_id' => $super->id,
            ],
            [
                'employee_id' => 'EMP-0002',
                'first_name' => 'Jose',
                'middle_name' => 'Cruz',
                'last_name' => 'Dela Cruz',
                'username' => 'admin',
                'email' => 'admin@enrollment.com',
                'role_id' => $admin->id,
            ],
            [
                'employee_id' => 'EMP-0003',
                'first_name' => 'Ana',
                'middle_name' => 'Lopez',
                'last_name' => 'Garcia',
                'username' => 'user',
                'email' => 'user@enrollment.com',
                'role_id' => $userRole->id,
            ],
        ];

        foreach ($seedUsers as $row) {
            $accounts->createUser($row + [
                'password' => 'password',
                'status' => 'active',
                'must_change_password' => false,
                'credentials_completed_at' => now(),
            ], [
                ['question_id' => $qIds[0], 'answer_hash' => $hash],
                ['question_id' => $qIds[1], 'answer_hash' => $hash],
                ['question_id' => $qIds[2], 'answer_hash' => $hash],
            ]);
        }

        $this->call(EnrollmentDemoSeeder::class);
        $this->call(CeitBsitScopeSeeder::class);
        $this->call(StudentRecordSeeder::class);
    }
}
