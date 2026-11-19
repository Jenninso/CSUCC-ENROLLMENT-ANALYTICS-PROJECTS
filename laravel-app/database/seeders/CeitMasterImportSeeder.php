<?php

namespace Database\Seeders;

use App\Models\StudentRecord;
use App\Services\StudentImportService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Loads the scrubbed CEIT master file shipped with the app (no personal identifiers).
 * Skips when the database already has a full CEIT-scale student file (Render restarts).
 */
class CeitMasterImportSeeder extends Seeder
{
    /** Full CEIT master is ~12,980 term rows; treat that scale as already loaded. */
    private const TARGET_MIN_ROWS = 12000;

    public function run(): void
    {
        $path = database_path('data/CEIT_system_master.csv');
        if (! is_file($path)) {
            $this->command?->warn('CeitMasterImportSeeder: database/data/CEIT_system_master.csv not found — skipped.');

            return;
        }

        $existing = StudentRecord::query()->count();
        $force = filter_var(env('CEIT_FORCE_IMPORT', false), FILTER_VALIDATE_BOOL);
        if (! $force && $existing >= self::TARGET_MIN_ROWS) {
            $this->command?->info("CeitMasterImportSeeder: {$existing} student records already present — skipping.");

            return;
        }

        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '512M');

        $this->command?->info('CeitMasterImportSeeder: importing CEIT master file…');

        /** @var StudentImportService $importer */
        $importer = app(StudentImportService::class);
        $parsed = $importer->parse($path, 'CEIT_system_master.csv');
        if (! ($parsed['ok'] ?? false)) {
            throw new \RuntimeException('CEIT master parse failed: '.implode(' ', $parsed['errors'] ?? ['unknown error']));
        }

        $validated = $importer->validate($parsed['rows']);
        $valid = $validated['valid'] ?? [];
        $issues = $validated['issues'] ?? [];
        if ($valid === []) {
            $sample = collect($issues)->take(5)->map(fn ($i) => 'line '.($i['line'] ?? '?').': '.implode('; ', $i['errors'] ?? []))->implode(' | ');
            throw new \RuntimeException('CEIT master validation produced 0 valid rows. '.$sample);
        }

        if ($force || $existing > 0) {
            // Replace demo / partial seed data with the registrar CEIT file.
            DB::table('student_records')->delete();
        }

        $result = $importer->commit($valid);
        $this->command?->info(sprintf(
            'CeitMasterImportSeeder: imported %d, skipped %d (validation issues: %d).',
            $result['imported'] ?? 0,
            $result['skipped'] ?? 0,
            count($issues)
        ));
    }
}
