<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->must_change_password) {
            $allowedRoutes = ['password.change', 'password.change.update', 'logout'];

            if (! $request->routeIs($allowedRoutes)) {
                return redirect()->route('password.change')
                    ->with('notice', 'Please change your temporary password to continue.');
            }
        }

        return $next($request);
    }
}
