<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-shot: clear SPR forms on Render after a mistaken bulk import.
 * Does not touch student_records or staff accounts.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('spes_applications')) {
            return;
        }

        // Prefer TRUNCATE for speed; fall back to DELETE if privileges block it.
        try {
            DB::statement('TRUNCATE TABLE spes_applications RESTART IDENTITY CASCADE');
        } catch (\Throwable) {
            DB::table('spes_applications')->delete();
        }
    }

    public function down(): void
    {
        // Irreversible cleanup.
    }
};
