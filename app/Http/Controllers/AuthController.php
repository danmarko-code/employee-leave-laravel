<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Middleware\EnforceRememberIdleTimeout;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->dashboardRoute());
        }

        return view('auth.login');
    }

    /**
     * Handle user login with throttling and account lockout.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $email = Str::lower($request->input('email'));
        $throttleKey = Str::transliterate($email . '|' . $request->ip());

        // Check if locked out (5 max attempts; 6th triggers lockout)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            AuditLog::create([
                'user_id' => User::where('email', $email)->value('id'),
                'action' => 'auth.lockout',
                'description' => "Account lockout triggered for [{$email}]. Repeated failed login attempts.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Account temporarily locked. Please try again in {$seconds} seconds.",
            ]);
        }

        $credentials = $request->only('email', 'password');
        $user = User::where('email', $email)->first();

        if ($user && Hash::check($credentials['password'], $user->password) && $user->is_active) {
            // Success: clear limiter and regenerate session ID to prevent session fixation
            RateLimiter::clear($throttleKey);
            $remember = $request->boolean('remember');
            Auth::login($user, $remember);
            $request->session()->regenerate();
            $request->session()->flash('splash', true);

            if ($remember) {
                $timestamp = now()->timestamp;
                $request->session()->put(EnforceRememberIdleTimeout::ACTIVITY_SESSION_KEY, $timestamp);
                EnforceRememberIdleTimeout::queueActivity($timestamp);
            }

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'auth.login',
                'description' => "User [{$user->email}] successfully signed in.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            if ($user->must_change_password) {
                return redirect()->route('password.change');
            }

            return redirect()->intended(route(Auth::user()->dashboardRoute()));
        }

        // Failure: increment failure count, lock for 300 seconds if threshold reached
        RateLimiter::hit($throttleKey, 300);

        AuditLog::create([
            'user_id' => $user?->id,
            'action' => 'auth.failed',
            'description' => "Failed authentication attempt for [{$email}].",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            AuditLog::create([
                'user_id' => $user?->id,
                'action' => 'auth.lockout',
                'description' => "Account lockout triggered for [{$email}] on 6th failed attempt.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Account temporarily locked. Please try again in {$seconds} seconds.",
            ]);
        }

        // Return identical generic error to prevent username/email enumeration
        throw ValidationException::withMessages([
            'email' => __('These credentials do not match our records.'),
        ]);
    }

    /**
     * Invalidate session and log user out.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'auth.logout',
                'description' => "User [{$user->email}] logged out.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        }

        Auth::logout();
        Cookie::queue(Cookie::forget(Auth::guard('web')->getRecallerName()));
        EnforceRememberIdleTimeout::forgetActivity();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Show the forced password change form.
     */
    public function showChangePassword(): View
    {
        return view('auth.change-password');
    }

    /**
     * Handle forced or profile password change.
     */
    public function updatePassword(ChangePasswordRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $user->forceFill([
            'password' => Hash::make($request->input('new_password')),
            'must_change_password' => false,
            'remember_token' => Str::random(60),
        ])->save();

        Cookie::queue(Cookie::forget(Auth::guard('web')->getRecallerName()));
        EnforceRememberIdleTimeout::forgetActivity();
        $request->session()->forget(EnforceRememberIdleTimeout::ACTIVITY_SESSION_KEY);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'password.changed',
            'description' => "Password changed successfully for [{$user->email}].",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('dashboard')->with('status', 'Password updated successfully.');
    }

    /**
     * Show forgot password view.
     */
    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle forgot password request with single-use expiring token.
     */
    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        $email = Str::lower($request->input('email'));
        $throttleKey = 'forgot|' . Str::transliterate($email . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => "Too many password reset requests. Please try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($throttleKey, 600);

        $user = User::where('email', $email)->first();

        if ($user && $user->is_active) {
            $plainToken = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => Hash::make($plainToken),
                    'created_at' => now(),
                ]
            );

            $resetUrl = route('password.reset', [
                'token' => $plainToken,
                'email' => $email,
            ]);

            Log::info("Password reset link generated for [{$email}]: {$resetUrl}");

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'password.reset_requested',
                'description' => "Password reset requested for [{$email}].",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        }

        // Generic response prevents account enumeration
        return back()->with('status', 'If your work email is registered, instructions to reset your password have been processed.');
    }

    /**
     * Show reset password view.
     */
    public function showResetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Handle password reset with single-use token consumption.
     */
    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        $email = Str::lower($request->input('email'));
        $plainToken = $request->input('token');

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record || ! Hash::check($plainToken, $record->token)) {
            throw ValidationException::withMessages([
                'email' => 'This password reset token is invalid or has expired.',
            ]);
        }

        // Expire tokens after 60 minutes
        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            throw ValidationException::withMessages([
                'email' => 'This password reset token has expired. Please request a new one.',
            ]);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Unable to locate user account.',
            ]);
        }

        $user->password = Hash::make($request->input('password'));
        $user->must_change_password = false;
        $user->save();

        // Single-use token: invalidate immediately
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'password.reset',
            'description' => "Password reset successfully completed for [{$user->email}].",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('login')->with('status', 'Password reset successfully. Please sign in with your new credentials.');
    }
}
