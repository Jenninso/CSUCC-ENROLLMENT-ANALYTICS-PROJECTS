<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfineStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user?->isStudent() && ! $request->routeIs('student.*', 'profile.*', 'logout')) {
            return redirect()->route('student.home');
        }

        return $next($request);
    }
}
