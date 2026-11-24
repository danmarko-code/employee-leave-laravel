<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\User;
use App\Policies\AdminPolicy;
use App\Support\Report;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::guard('web')->setRememberDuration((int) config('auth.remember_duration', 10080));

        Gate::policy(User::class, AdminPolicy::class);
        Gate::policy(Department::class, AdminPolicy::class);
        Gate::policy(Position::class, AdminPolicy::class);
        Gate::policy(LeaveType::class, AdminPolicy::class);
        Gate::policy(LeaveBalance::class, AdminPolicy::class);
        Gate::policy(AuditLog::class, AdminPolicy::class);
        Gate::policy(Report::class, AdminPolicy::class);
    }
}
