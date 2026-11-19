<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-shot cleanup: remove leftover demo/synthetic operational rows.
 * Staff accounts and CEIT college/program setup stay.
 *
 * Avoids SET session_replication_role (needs superuser; fails on Render Postgres).
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'student_interventions',
        'spes_applications',
        'student_records',
        'enrollment_records',
        'alerts',
        'forecasts',
        'ai_insights',
        'audit_logs',
        'login_logs',
        'otps',
        'otp_verifications',
        'registration_requests',
        'delete_requests',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'semesters',
        'academic_years',
    ];

    public function up(): void
    {
        $existing = array_values(array_filter(
            $this->tables,
            fn (string $table): bool => Schema::hasTable($table)
        ));

        if ($existing === []) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            try {
                $quoted = implode(', ', array_map(
                    fn (string $table): string => '"'.str_replace('"', '""', $table).'"',
                    $existing
                ));
                DB::statement("TRUNCATE TABLE {$quoted} RESTART IDENTITY CASCADE");

                return;
            } catch (\Throwable) {
                // Fall through to ordered deletes if TRUNCATE is restricted.
            }
        }

        foreach ($existing as $table) {
            DB::table($table)->delete();
        }
    }

    public function down(): void
    {
        // Irreversible data wipe.
    }
};
