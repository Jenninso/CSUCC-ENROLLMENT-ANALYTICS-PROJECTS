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

        Schema::create('student_status_reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('student_id', 30);
            $table->foreignId('student_record_id')->nullable()->constrained('student_records')->nullOnDelete();
            $table->string('action_type', 40);
            $table->string('subject_name', 150)->nullable();
            $table->string('from_program', 150)->nullable();
            $table->string('to_program', 150)->nullable();
            $table->string('previous_school', 180)->nullable();
            $table->text('reason');
            $table->date('effective_date')->nullable();
            $table->string('status', 20)->default('submitted');
            $table->text('staff_remarks')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'action_type']);
            $table->index('status');
        });

        $now = now();
        $roleId = DB::table('roles')->where('role_name', 'Student')->value('id');
        if (! $roleId) {
            $roleId = DB::table('roles')->insertGetId([
                'role_name' => 'Student',
                'description' => 'Student portal for the SPES form and academic status reasons.',
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
