<?php

namespace App\Providers;

use App\Models\Alert;
use App\Models\DeleteRequest;
use App\Models\RegistrationRequest;
use Database\Seeders\SprSyntheticSeeder;
use Database\Seeders\StudentRecordSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadSprStatisticsWhenEmpty();

        View::composer('layouts.app', function ($view) {
            $count = 0;
            $pendingRegistrations = 0;
            $pendingDeletes = 0;
            if (Schema::hasTable('alerts')) {
                $count = Alert::query()->where('is_read', false)->count();
            }
            $user = auth()->user();
            if ($user?->canAccess('users.approve') && Schema::hasTable('registration_requests')) {
                $pendingRegistrations = RegistrationRequest::query()->where('status', 'pending')->count();
            }
            if ($user?->isSuperAdmin() && Schema::hasTable('delete_requests')) {
                $pendingDeletes = DeleteRequest::query()->where('status', 'pending')->count();
            }
            $view->with([
                'unreadAlerts' => $count,
                'pendingRegistrations' => $pendingRegistrations,
                'pendingDeletes' => $pendingDeletes,
            ]);
        });
    }

    private function loadSprStatisticsWhenEmpty(): void
    {
        // Skip during image builds and Artisan commands. `composer install` boots the
        // app for package discovery before Postgres exists, and a missing sqlite file
        // would fail the Render build.
        if ($this->app->runningInConsole() || $this->app->runningUnitTests()) {
            return;
        }

        try {
            if (! Schema::hasTable('spes_applications') || ! Schema::hasTable('student_records')) {
                return;
            }

            $linkedToStudents = DB::table('spes_applications')->whereNotNull('student_record_id')->count();
            $hasUnmatchedDummy = DB::table('spes_applications')->where('student_id', 'like', '____-00%')->exists();
            if ($linkedToStudents > 0 && ! $hasUnmatchedDummy) {
                return;
            }

            (new StudentRecordSeeder)->run();
            (new SprSyntheticSeeder)->run();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
