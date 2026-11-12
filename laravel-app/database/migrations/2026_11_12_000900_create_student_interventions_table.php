<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_interventions', function (Blueprint $table) {
            $table->id();
            $table->string('student_id', 30);
            $table->foreignId('student_record_id')->nullable()->constrained('student_records')->nullOnDelete();
            $table->string('intervention_type', 40);
            $table->text('notes')->nullable();
            $table->date('provided_on');
            $table->string('outcome', 20)->default('pending');
            $table->decimal('gpa_at_record', 4, 2)->nullable();
            $table->decimal('attendance_at_record', 5, 2)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('student_id');
            $table->index('outcome');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_interventions');
    }
};
