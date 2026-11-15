<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [
            \App\Http\Middleware\PreventPageCache::class,
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'setup' => \App\Http\Middleware\EnsureAccountSetup::class,
            'student.scope' => \App\Http\Middleware\ConfineStudent::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash([
            'answer_1',
            'answer_2',
            'answer_3',
        ]);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Redirect to the section's index with a friendly message when a model is not found.
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, Request $request) {
            if ($request->expectsJson()) {
                return null; // Let the default JSON handler run.
            }

            $segments = $request->segments();
            // Build an index URL from the first two URL segments (e.g. /data-management/academic-years).
            $indexUrl = count($segments) >= 2
                ? url(implode('/', array_slice($segments, 0, 2)))
                : route('dashboard');

            return redirect($indexUrl)
                ->with('error', 'The record you tried to access no longer exists. It may have been removed or the database was refreshed.');
        });
    })->create();
