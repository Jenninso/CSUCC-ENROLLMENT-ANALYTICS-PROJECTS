<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('address')->nullable()->after('last_name');
            $table->boolean('must_change_password')->default(false)->after('blocked_reason');
            $table->timestamp('credentials_completed_at')->nullable()->after('must_change_password');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('employee_id')->nullable()->after('user_id');
            $table->string('full_name')->nullable()->after('employee_id');
            $table->string('role_name')->nullable()->after('full_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['address', 'must_change_password', 'credentials_completed_at']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['employee_id', 'full_name', 'role_name']);
        });
    }
};
