<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WipeOperationalData extends Command
{
    protected $signature = 'app:wipe-data
                            {--force : Required confirmation flag}
                            {--all : Wipe everything including staff accounts, roles, and academic setup}';

    protected $description = 'Empty Render/app data. Use --all for a total wipe including login accounts.';

    /** @var list<string> */
    private array $operationalTables = [
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

    /** @var list<string> */
    private array $accountAndSetupTables = [
        'user_avatars',
        'user_secret_answers',
        'user_permission',
        'role_permission',
        'permissions',
        'secret_questions',
        'password_reset_tokens',
        'users',
        'roles',
        'semesters',
        'academic_years',
        'programs',
        'departments',
        'colleges',
        'system_settings',
    ];

    public function handle(): int
    {
        $all = $this->option('all') || filter_var(env('CLEAR_RENDER_DATA', false), FILTER_VALIDATE_BOOL);
        if (! $this->option('force') && ! filter_var(env('CLEAR_RENDER_DATA', false), FILTER_VALIDATE_BOOL)) {
            $this->error('Refusing to wipe. Pass --force or set CLEAR_RENDER_DATA=true.');

            return self::FAILURE;
        }

        $tables = $all
            ? array_merge($this->operationalTables, $this->accountAndSetupTables)
            : $this->operationalTables;

        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('SET session_replication_role = replica');
        } else {
            Schema::disableForeignKeyConstraints();
        }

        try {
            foreach ($tables as $table) {
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

        $this->info($all
            ? 'FULL wipe done. No accounts, no students, no academic seed data left.'
            : 'Operational data wiped. Staff accounts / roles kept.');

        return self::SUCCESS;
    }
}
