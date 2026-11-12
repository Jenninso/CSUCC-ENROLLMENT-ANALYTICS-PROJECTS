<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('spes_applications')
            ->where('reference_no', 'like', 'SPES-%')
            ->update([
                'reference_no' => DB::raw("REPLACE(reference_no, 'SPES-', 'SPIS-')"),
            ]);

        DB::table('roles')
            ->where('description', 'Student portal for the SPES form and academic status reasons.')
            ->update([
                'description' => 'Student portal for the SPIS form and academic status reasons.',
            ]);
    }

    public function down(): void
    {
        DB::table('spes_applications')
            ->where('reference_no', 'like', 'SPIS-%')
            ->update([
                'reference_no' => DB::raw("REPLACE(reference_no, 'SPIS-', 'SPES-')"),
            ]);

        DB::table('roles')
            ->where('description', 'Student portal for the SPIS form and academic status reasons.')
            ->update([
                'description' => 'Student portal for the SPES form and academic status reasons.',
            ]);
    }
};
