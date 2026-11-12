<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spes_applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 30)->unique();
            $table->foreignId('student_record_id')->nullable()->constrained('student_records')->nullOnDelete();
            $table->string('student_id', 30)->nullable();
            $table->string('last_name', 80);
            $table->string('first_name', 80);
            $table->string('middle_name', 80)->nullable();
            $table->string('name_extension', 20)->nullable();
            $table->string('sex', 20);
            $table->string('civil_status', 20);
            $table->date('birth_date');
            $table->unsignedTinyInteger('age');
            $table->string('place_of_birth', 150);
            $table->string('address', 255);
            $table->string('barangay', 100);
            $table->string('city_municipality', 100);
            $table->string('province', 100);
            $table->string('zip_code', 10)->nullable();
            $table->string('contact_number', 30);
            $table->string('email', 150)->nullable();
            $table->string('father_name', 150)->nullable();
            $table->string('father_occupation', 100)->nullable();
            $table->string('mother_name', 150)->nullable();
            $table->string('mother_occupation', 100)->nullable();
            $table->decimal('parents_annual_income', 12, 2);
            $table->unsignedTinyInteger('number_of_siblings')->default(0);
            $table->string('education_level', 30);
            $table->string('school_name', 180);
            $table->string('school_address', 255)->nullable();
            $table->string('course', 150)->nullable();
            $table->string('year_level', 30);
            $table->decimal('general_average', 5, 2)->nullable();
            $table->string('enrollment_status', 30);
            $table->unsignedSmallInteger('application_year');
            $table->string('work_period', 20);
            $table->unsignedTinyInteger('preferred_days');
            $table->string('beneficiary_type', 30);
            $table->boolean('previous_spes')->default(false);
            $table->unsignedSmallInteger('previous_year')->nullable();
            $table->string('preferred_assignment', 150)->nullable();
            $table->string('skills', 255)->nullable();
            $table->boolean('has_birth_certificate')->default(false);
            $table->boolean('has_enrollment_cert')->default(false);
            $table->boolean('has_grades')->default(false);
            $table->boolean('has_indigency')->default(false);
            $table->boolean('has_photo')->default(false);
            $table->date('application_date');
            $table->string('applicant_signature', 150);
            $table->string('status', 30)->default('submitted');
            $table->text('remarks')->nullable();
            $table->foreignId('encoded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index(['application_year', 'work_period']);
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spes_applications');
    }
};
