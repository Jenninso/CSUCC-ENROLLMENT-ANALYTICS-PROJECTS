<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WipeOperationalData extends Command
{
    protected $signature = 'app:wipe-data {--force : Required confirmation flag}';

    protected $description = 'Empty operational analytics data (students, enrollment, SPR, alerts). Keeps staff login accounts.';

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
    ];

    public function handle(): int
    {
        if (! $this->option('force') && ! filter_var(env('CLEAR_RENDER_DATA', false), FILTER_VALIDATE_BOOL)) {
            $this->error('Refusing to wipe. Pass --force or set CLEAR_RENDER_DATA=true.');

            return self::FAILURE;
        }

        $driver = DB::getDriverName();
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
                $this->line("cleared {$table}");
            }
        } finally {
            if ($driver === 'pgsql') {
                DB::statement('SET session_replication_role = DEFAULT');
            } else {
                Schema::enableForeignKeyConstraints();
            }
        }

        $this->info('Operational data wiped. Staff accounts / roles kept.');

        return self::SUCCESS;
    }
}
