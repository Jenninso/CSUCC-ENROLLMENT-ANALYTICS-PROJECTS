<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained();
            $table->foreignId('semester_id')->constrained();
            $table->foreignId('college_id')->constrained();
            $table->foreignId('department_id')->constrained();
            $table->foreignId('program_id')->constrained();
            $table->integer('year_level');
            $table->string('enrollment_type');
            $table->integer('enrollment_count');
            $table->timestamps();

            $table->unique([
                'academic_year_id',
                'semester_id',
                'program_id',
                'year_level',
                'enrollment_type',
            ], 'enrollment_records_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_records');
    }
};
