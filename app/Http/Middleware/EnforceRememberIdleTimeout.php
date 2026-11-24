<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class EnforceRememberIdleTimeout
{
    public const ACTIVITY_COOKIE = 'remember_activity';

    public const ACTIVITY_SESSION_KEY = 'remember_activity_at';

    /**
     * Queue the activity cookie with the same flags and lifetime as the
     * remember cookie.
     */
    public static function queueActivity(int $timestamp): void
    {
        Cookie::queue(
            self::ACTIVITY_COOKIE,
            (string) $timestamp,
            (int) config('auth.remember_duration', 10080)
        );
    }

    public static function forgetActivity(): void
    {
        Cookie::queue(Cookie::forget(self::ACTIVITY_COOKIE));
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $recallerName = Auth::guard('web')->getRecallerName();
        $hasRememberCookie = $request->hasCookie($recallerName);

        if ($hasRememberCookie && Auth::check()) {
            $lastActivity = $request->cookie(self::ACTIVITY_COOKIE)
                ?? $request->session()->get(self::ACTIVITY_SESSION_KEY);

            if ($lastActivity === null) {
                $lastActivity = now()->timestamp;
                $request->session()->put(self::ACTIVITY_SESSION_KEY, $lastActivity);
                self::queueActivity($lastActivity);
            } else {
                $idleLimit = (int) config('session.lifetime', 120) * 60;

                if (now()->timestamp - (int) $lastActivity > $idleLimit) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    $request->session()->forget(self::ACTIVITY_SESSION_KEY);
                    self::forgetActivity();

                    return redirect()->route('login')
                        ->with('status', 'Your session expired. Please sign in again.');
                }
            }
        }

        $response = $next($request);

        if ($hasRememberCookie
            && ! $request->routeIs('logout', 'password.change.update')
            && Auth::check()) {
            $timestamp = now()->timestamp;
            $request->session()->put(self::ACTIVITY_SESSION_KEY, $timestamp);
            self::queueActivity($timestamp);
        }

        return $response;
    }
}
