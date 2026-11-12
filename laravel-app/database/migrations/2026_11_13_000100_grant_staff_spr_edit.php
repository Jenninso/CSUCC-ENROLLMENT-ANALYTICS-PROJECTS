<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissionId = DB::table('permissions')->where('key', 'spr.edit')->value('id');
        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'key' => 'spr.edit',
                'name' => 'Edit student SPR',
                'module' => 'enrollment',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $roleIds = DB::table('roles')
            ->whereIn('role_name', ['User', 'Administrator'])
            ->pluck('id');

        $manageId = DB::table('permissions')->where('key', 'enrollment.manage')->value('id');
        if ($manageId) {
            $roleIds = $roleIds->merge(
                DB::table('role_permission')->where('permission_id', $manageId)->pluck('role_id')
            );
        }

        foreach ($roleIds->unique() as $roleId) {
            $attached = DB::table('role_permission')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->exists();
            if (! $attached) {
                DB::table('role_permission')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }

        $viewId = DB::table('permissions')->where('key', 'enrollment.view')->value('id');
        $sourceIds = collect([$viewId, $manageId])->filter()->all();
        if ($sourceIds === []) {
            return;
        }

        $userIds = DB::table('user_permission')
            ->whereIn('permission_id', $sourceIds)
            ->distinct()
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            $attached = DB::table('user_permission')
                ->where('user_id', $userId)
                ->where('permission_id', $permissionId)
                ->exists();
            if (! $attached) {
                DB::table('user_permission')->insert([
                    'user_id' => $userId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('key', 'spr.edit')->value('id');
        if (! $permissionId) {
            return;
        }

        DB::table('role_permission')->where('permission_id', $permissionId)->delete();
        DB::table('user_permission')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();
    }
};
