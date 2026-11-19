<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Force-empty SPR records on Render.
 * Previous clear may have been skipped if RENDER env detection failed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('spes_applications')) {
            return;
        }

        $appUrl = (string) env('APP_URL', '');
        $onRender = filled(env('RENDER_EXTERNAL_URL'))
            || filled(env('RENDER'))
            || str_contains($appUrl, 'onrender.com');

        if (! $onRender) {
            return;
        }

        try {
            DB::statement('TRUNCATE TABLE spes_applications RESTART IDENTITY CASCADE');
        } catch (\Throwable) {
            DB::table('spes_applications')->delete();
        }
    }

    public function down(): void
    {
        // Irreversible.
    }
};
