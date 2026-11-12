<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventPageCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $location = (string) $response->headers->get('Location', '');
        $redirectsToLogin = $response->isRedirect() && str_contains($location, '/login');

        if ($request->user() || $redirectsToLogin) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
