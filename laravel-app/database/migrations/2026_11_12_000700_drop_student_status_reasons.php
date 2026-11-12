<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('student_status_reasons');

        DB::table('roles')
            ->where('role_name', 'Student')
            ->where('description', 'like', '%status reasons%')
            ->update(['description' => 'Student portal for the SPR form.']);
    }

    public function down(): void
    {
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
    }
};
