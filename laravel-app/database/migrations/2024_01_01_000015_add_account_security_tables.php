<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_id')->nullable()->unique()->after('id');
            $table->string('first_name')->nullable()->after('name');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->nullable()->after('middle_name');
            $table->timestamp('blocked_at')->nullable()->after('status');
            $table->text('blocked_reason')->nullable()->after('blocked_at');
        });

        Schema::create('secret_questions', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_secret_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('secret_question_id')->constrained('secret_questions')->cascadeOnDelete();
            $table->string('answer_hash');
            $table->timestamps();
            $table->unique(['user_id', 'secret_question_id']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('module')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->unique(['role_id', 'permission_id']);
        });

        Schema::create('user_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'permission_id']);
        });

        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('purpose');
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();
            $table->index(['email', 'purpose']);
        });

        Schema::create('registration_requests', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('email');
            $table->string('password_hash');
            $table->json('secret_answers');
            $table->string('status')->default('pending');
            $table->timestamp('otp_verified_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->index('status');
            $table->index('employee_id');
        });

        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_id')->nullable();
            $table->string('full_name');
            $table->string('role_name')->nullable();
            $table->timestamp('time_in');
            $table->timestamp('time_out')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
            $table->index('time_in');
            $table->index('role_name');
        });

        Schema::create('delete_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->json('user_snapshot');
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delete_requests');
        Schema::dropIfExists('login_logs');
        Schema::dropIfExists('registration_requests');
        Schema::dropIfExists('otps');
        Schema::dropIfExists('user_permission');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('user_secret_answers');
        Schema::dropIfExists('secret_questions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'employee_id',
                'first_name',
                'middle_name',
                'last_name',
                'blocked_at',
                'blocked_reason',
            ]);
        });
    }
};
