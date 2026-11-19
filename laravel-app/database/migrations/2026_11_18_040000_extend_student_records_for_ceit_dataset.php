<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_records', function (Blueprint $table) {
            $table->string('last_name', 100)->nullable()->after('student_id');
            $table->string('first_name', 100)->nullable()->after('last_name');
            $table->string('middle_name', 100)->nullable()->after('first_name');
            $table->string('ext_name', 30)->nullable()->after('middle_name');
            $table->string('email', 150)->nullable()->after('ext_name');
            $table->date('date_of_birth')->nullable()->after('age');
            $table->string('mobile', 40)->nullable()->after('date_of_birth');
            $table->string('academic_standing', 30)->nullable()->after('student_type');
            $table->string('block', 40)->nullable()->after('section');
            $table->string('scholar_code', 40)->nullable()->after('scholarship_status');
            $table->string('home_address', 255)->nullable()->after('scholar_code');
            $table->string('municipality', 100)->nullable()->after('home_address');
            $table->string('province', 100)->nullable()->after('municipality');
        });
    }

    public function down(): void
    {
        Schema::table('student_records', function (Blueprint $table) {
            $table->dropColumn([
                'last_name',
                'first_name',
                'middle_name',
                'ext_name',
                'email',
                'date_of_birth',
                'mobile',
                'academic_standing',
                'block',
                'scholar_code',
                'home_address',
                'municipality',
                'province',
            ]);
        });
    }
};
