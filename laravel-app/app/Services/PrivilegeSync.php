<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

class PrivilegeSync
{
    public function sync(): void
    {
        foreach (config('permissions.catalog') as $key => [$name, $module]) {
            Permission::query()->updateOrCreate(
                ['key' => $key],
                ['name' => $name, 'module' => $module]
            );
        }

        $permissions = Permission::query()->pluck('id', 'key');

        foreach (config('permissions.role_defaults') as $roleName => $keys) {
            $role = Role::query()->where('role_name', $roleName)->first();
            if (! $role) {
                continue;
            }

            $ids = $keys === ['*']
                ? $permissions->values()->all()
                : collect($keys)->map(fn ($key) => $permissions[$key] ?? null)->filter()->values()->all();

            $role->permissions()->sync($ids);
        }

        User::query()->with('role')->whereHas('permissions')->each(function (User $user) use ($permissions): void {
            $keys = config('permissions.role_defaults.'.$user->role?->role_name);
            if (! is_array($keys)) {
                return;
            }

            if ($keys === ['*']) {
                $user->permissions()->sync($permissions->values()->all());

                return;
            }

            $have = $user->permissions()->pluck('key')->all();
            $missing = array_values(array_diff($keys, $have));
            if ($missing === []) {
                return;
            }

            $ids = $user->permissions()->pluck('permissions.id')->all();
            foreach ($missing as $key) {
                if (isset($permissions[$key])) {
                    $ids[] = $permissions[$key];
                }
            }

            $user->permissions()->sync($ids);
        });
    }
}
