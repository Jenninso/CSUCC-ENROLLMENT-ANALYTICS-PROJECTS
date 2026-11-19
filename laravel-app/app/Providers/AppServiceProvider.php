<?php

namespace App\Providers;

use App\Models\Alert;
use App\Models\DeleteRequest;
use App\Models\RegistrationRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
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
        $renderUrl = env('RENDER_EXTERNAL_URL');
        if (is_string($renderUrl) && str_starts_with($renderUrl, 'https://')) {
            URL::forceRootUrl(rtrim($renderUrl, '/'));
            URL::forceScheme('https');
        }

        View::composer('layouts.app', function ($view) {
            $count = 0;
            $recentAlerts = collect();
            $pendingRegistrations = 0;
            $pendingDeletes = 0;
            if (Schema::hasTable('alerts')) {
                $count = Alert::query()->where('is_read', false)->count();
                $recentAlerts = Alert::query()
                    ->where('is_read', false)
                    ->latest()
                    ->limit(6)
                    ->get(['id', 'title', 'severity', 'created_at']);
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
                'recentAlerts' => $recentAlerts,
                'pendingRegistrations' => $pendingRegistrations,
                'pendingDeletes' => $pendingDeletes,
            ]);
        });
    }
}
