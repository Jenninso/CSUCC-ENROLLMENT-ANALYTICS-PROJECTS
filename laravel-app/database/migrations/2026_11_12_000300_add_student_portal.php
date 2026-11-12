<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spes_applications', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('encoded_by')->constrained()->nullOnDelete();
        });

        $now = now();
        $roleId = DB::table('roles')->where('role_name', 'Student')->value('id');
        if (! $roleId) {
            $roleId = DB::table('roles')->insertGetId([
                'role_name' => 'Student',
                'description' => 'Student portal for the SPR form.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionId = DB::table('permissions')->where('key', 'student.portal')->value('id');
        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'key' => 'student.portal',
                'name' => 'Student portal',
                'module' => 'student',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

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

    public function down(): void
    {
        Schema::dropIfExists('student_status_reasons');

        Schema::table('spes_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
