<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spes_applications', function (Blueprint $table) {
            $table->json('spr_data')->nullable()->after('remarks');
        });

        DB::table('spes_applications')
            ->where('reference_no', 'like', 'SPIS-%')
            ->update(['reference_no' => DB::raw("REPLACE(reference_no, 'SPIS-', 'SPR-')")]);

        DB::table('roles')
            ->where('description', 'Student portal for the SPIS form and academic status reasons.')
            ->update(['description' => 'Student portal for the SPR form and academic status reasons.']);
    }

    public function down(): void
    {
        DB::table('spes_applications')
            ->where('reference_no', 'like', 'SPR-%')
            ->update(['reference_no' => DB::raw("REPLACE(reference_no, 'SPR-', 'SPIS-')")]);

        Schema::table('spes_applications', function (Blueprint $table) {
            $table->dropColumn('spr_data');
        });
    }
};
