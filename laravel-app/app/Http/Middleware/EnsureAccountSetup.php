<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountSetup
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if ($request->routeIs('logout') || $request->routeIs('setup.*')) {
            return $next($request);
        }

        if ($user->mustChangePassword()) {
            return redirect()->route('setup.password');
        }

        if (! $user->hasCompletedCredentials()) {
            return redirect()->route('setup.credentials');
        }

        return $next($request);
    }
}
