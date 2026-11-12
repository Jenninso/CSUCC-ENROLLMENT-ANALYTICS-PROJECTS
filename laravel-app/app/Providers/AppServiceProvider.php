<?php

namespace App\Providers;

use App\Models\Alert;
use App\Models\DeleteRequest;
use App\Models\RegistrationRequest;
use Database\Seeders\SprSyntheticSeeder;
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
        if ($this->app->runningUnitTests() || ! Schema::hasTable('spes_applications')) {
            return;
        }

        try {
            if (DB::table('spes_applications')->count() > 0) {
                return;
            }

            (new SprSyntheticSeeder)->run();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
