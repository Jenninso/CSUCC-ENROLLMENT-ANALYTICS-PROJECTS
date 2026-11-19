<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-shot Render/local cleanup: remove leftover demo/synthetic operational rows
 * so the dashboard starts empty. Staff accounts and CEIT college/program setup stay.
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
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('SET session_replication_role = replica');
        } else {
            Schema::disableForeignKeyConstraints();
        }

        try {
            foreach ($this->tables as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }
                DB::table($table)->delete();
            }
        } finally {
            if ($driver === 'pgsql') {
                DB::statement('SET session_replication_role = DEFAULT');
            } else {
                Schema::enableForeignKeyConstraints();
            }
        }
    }

    public function down(): void
    {
        // Irreversible data wipe.
    }
};
