<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LeaveBalanceController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LeaveController;
use Illuminate\Support\Facades\Route;

// Public Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return redirect()->route('login');
    });

    Route::get('/login', [AuthController::class, 'showLogin'])->middleware('no-store')->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');

    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// Authenticated Routes
Route::middleware(['auth', 'no-store'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/password/change', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::post('/password/change', [AuthController::class, 'updatePassword'])->name('password.change.update');

    // Employee & Shared Routes
    Route::middleware('role:employee,admin')->group(function () {
        Route::get('/dashboard', [LeaveController::class, 'dashboard'])->name('dashboard');
        Route::get('/apply', [LeaveController::class, 'create'])->name('apply');
        Route::post('/apply', [LeaveController::class, 'store'])->name('apply.store');
        Route::get('/requests', [LeaveController::class, 'index'])->name('requests');
        Route::get('/requests/{leaveRequest}', [LeaveController::class, 'show'])->name('requests.show');
        Route::post('/requests/{leaveRequest}/cancel', [LeaveController::class, 'cancel'])->name('requests.cancel');
        Route::get('/requests/{leaveRequest}/attachment', [LeaveController::class, 'downloadAttachment'])->name('requests.attachment');
        Route::get('/requests/{leaveRequest}/attachment/preview', [LeaveController::class, 'previewAttachment'])->name('requests.attachment.preview');
        Route::get('/profile', [LeaveController::class, 'profile'])->name('profile');
        Route::post('/profile', [LeaveController::class, 'updateProfile'])->name('profile.update');
    });

    // Administrator approval queue: administrators only, never their own requests.
    Route::middleware('role:admin')->group(function () {
        Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
        Route::get('/approvals/{leaveRequest}', [ApprovalController::class, 'show'])->name('approvals.show');
        Route::post('/approvals/{leaveRequest}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('/approvals/{leaveRequest}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');
    });

    // The Overview is admin-only; employees get a 403 instead of a redirect.
    Route::get('/overview', [DashboardController::class, 'overview'])
        ->middleware('admin-overview')
        ->name('overview');

    // Admin management pages: role middleware on every action.
    Route::middleware('role:admin')->group(function () {
        Route::get('/applications', [DashboardController::class, 'applications'])->name('admin.applications');

        Route::get('/users', [UserController::class, 'index'])->name('users');
        Route::get('/users/create', [UserController::class, 'create'])->name('admin.users.create');
        Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('admin.users.show');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
        Route::post('/users/{user}/temporary-password', [UserController::class, 'resetTemporaryPassword'])
            ->middleware('throttle:3,10')
            ->name('admin.users.temporary-password.reset');

        Route::get('/balances', [LeaveBalanceController::class, 'index'])->name('admin.balances.index');
        Route::get('/balances/{leaveBalance}/edit', [LeaveBalanceController::class, 'edit'])->name('admin.balances.edit');
        Route::put('/balances/{leaveBalance}', [LeaveBalanceController::class, 'update'])->name('admin.balances.update');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('admin.reports.export');

        Route::get('/activity', [AuditLogController::class, 'index'])->name('activity');

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::get('/settings/departments', [SettingsController::class, 'departments'])->name('settings.departments');
        Route::post('/settings/departments', [SettingsController::class, 'storeDepartment'])->name('settings.departments.store');
        Route::put('/settings/departments/{department}', [SettingsController::class, 'updateDepartment'])->name('settings.departments.update');
        Route::get('/settings/positions', [SettingsController::class, 'positions'])->name('settings.positions');
        Route::post('/settings/positions', [SettingsController::class, 'storePosition'])->name('settings.positions.store');
        Route::put('/settings/positions/{position}', [SettingsController::class, 'updatePosition'])->name('settings.positions.update');
        Route::get('/settings/leave-types', [SettingsController::class, 'leaveTypes'])->name('settings.leave-types');
        Route::post('/settings/leave-types', [SettingsController::class, 'storeLeaveType'])->name('settings.leave-types.store');
        Route::put('/settings/leave-types/{leaveType}', [SettingsController::class, 'updateLeaveType'])->name('settings.leave-types.update');
        Route::post('/settings/leave-types/{leaveType}/toggle', [SettingsController::class, 'toggleLeaveType'])->name('settings.leave-types.toggle');
        Route::delete('/settings/leave-types/{leaveType}', [SettingsController::class, 'destroyLeaveType'])->name('settings.leave-types.destroy');

        // Old management URLs now point at the new settings tabs.
        Route::get('/organization', fn () => redirect()->route('settings.departments'));
        Route::get('/roles', fn () => redirect()->route('settings.departments'));
        Route::get('/leave-types', fn () => redirect()->route('settings.leave-types'));
        Route::get('/types', fn () => redirect()->route('settings.leave-types'));
    });
});
