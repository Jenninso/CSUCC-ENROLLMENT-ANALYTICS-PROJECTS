<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spes_applications', function (Blueprint $table) {
            $table->boolean('student_can_edit')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('spes_applications', function (Blueprint $table) {
            $table->dropColumn('student_can_edit');
        });
    }
};
