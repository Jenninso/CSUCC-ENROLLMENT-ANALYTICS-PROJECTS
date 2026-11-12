<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            $table->unsignedTinyInteger('semester_order')->default(1)->after('semester_name');
            $table->boolean('is_active')->default(false)->after('academic_year_id');
        });

        DB::table('semesters')->orderBy('id')->get()->each(function ($row) {
            $order = str_contains(strtolower($row->semester_name), 'second') ? 2 : 1;
            $yearActive = DB::table('academic_years')->where('id', $row->academic_year_id)->value('status') === 'active';

            DB::table('semesters')->where('id', $row->id)->update([
                'semester_order' => $order,
                'is_active' => $yearActive,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            $table->dropColumn(['semester_order', 'is_active']);
        });
    }
};
