<?php

use App\Http\Middleware\EnforceRememberIdleTimeout;
use App\Http\Middleware\EnsureAdminOverviewAccess;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\PreventBackHistory;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
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
        $middleware->appendToGroup('web', EnforceRememberIdleTimeout::class);
        $middleware->appendToGroup('web', EnsurePasswordIsChanged::class);
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'admin-overview' => EnsureAdminOverviewAccess::class,
            'no-store' => PreventBackHistory::class,
        ]);

        RedirectIfAuthenticated::redirectUsing(function (Request $request): string {
            return route($request->user()?->dashboardRoute() ?? 'dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
