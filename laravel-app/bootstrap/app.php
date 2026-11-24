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

        // Redirect to the section's index when a bound model is missing.
        // Laravel converts ModelNotFoundException → NotFoundHttpException before render callbacks,
        // so catch NotFoundHttpException and check $e->getPrevious().
        // e.g. /users/23/edit → /users  (not /users/23, which used to 404)
        // e.g. /data-management/academic-years/5 → /data-management/academic-years
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            if (! $e->getPrevious() instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return null; // Real missing route → keep the normal 404 page.
            }

            $indexSegments = [];
            foreach ($request->segments() as $segment) {
                // Stop before resource IDs so we land on the list page, not a dead /resource/{id} URL.
                if (ctype_digit($segment) || preg_match('/^[0-9a-fA-F-]{8,}$/', $segment) === 1) {
                    break;
                }
                $indexSegments[] = $segment;
            }

            $indexUrl = $indexSegments !== []
                ? url(implode('/', $indexSegments))
                : route('dashboard');

            return redirect($indexUrl)
                ->with('error', 'The record you tried to access no longer exists. It may have been removed or the database was refreshed.');
        });
    })->create();
