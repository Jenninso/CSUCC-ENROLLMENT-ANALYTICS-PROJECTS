<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ConfineStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isStudent()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'username' => 'Student accounts are not used. This sign-in is for staff only.',
            ]);
        }

        return $next($request);
    }
}
