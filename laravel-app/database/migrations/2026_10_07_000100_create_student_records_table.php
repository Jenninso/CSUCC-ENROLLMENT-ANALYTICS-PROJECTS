<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_records', function (Blueprint $table) {
            $table->id();
            $table->string('student_id', 30);
            $table->string('gender', 20);
            $table->unsignedTinyInteger('age');
            $table->foreignId('program_id')->constrained();
            $table->unsignedTinyInteger('year_level');
            $table->string('section', 20);
            $table->foreignId('academic_year_id')->constrained();
            $table->foreignId('semester_id')->constrained();
            $table->decimal('gpa', 4, 2);
            $table->decimal('average_grade', 5, 2);
            $table->decimal('attendance_percent', 5, 2);
            $table->unsignedTinyInteger('subjects_count');
            $table->unsignedTinyInteger('failed_subjects');
            $table->decimal('units_enrolled', 4, 1);
            $table->string('enrolled_status', 30);
            $table->string('scholarship_status', 30);
            $table->string('tuition_status', 30);
            $table->string('risk_level', 20);
            $table->timestamps();

            $table->unique(
                ['student_id', 'academic_year_id', 'semester_id'],
                'student_records_term_unique'
            );
            $table->index('risk_level');
            $table->index(['year_level', 'section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_records');
    }
};
