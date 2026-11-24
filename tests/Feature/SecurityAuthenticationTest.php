<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceRememberIdleTimeout;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SecurityAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\LeaveTypeSeeder::class);
    }

    public function test_argon2id_hashing_is_configured_and_active(): void
    {
        $hash = Hash::make('SuperSecretPassword123!');
        $info = Hash::info($hash);

        $this->assertStringContainsString('argon2id', strtolower($info['algoName'] ?? ''));
    }

    public function test_same_generic_error_for_wrong_email_and_wrong_password(): void
    {
        $role = Role::where('name', 'employee')->first();
        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('CorrectPassword123!'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        // Wrong email
        $resWrongEmail = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'SomePassword123!',
        ]);
        $resWrongEmail->assertSessionHasErrors('email');
        $msgEmail = session('errors')->first('email');

        // Wrong password
        $resWrongPassword = $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'WrongPassword123!',
        ]);
        $resWrongPassword->assertSessionHasErrors('email');
        $msgPass = session('errors')->first('email');

        // Verify identical error message (no user enumeration)
        $this->assertSame($msgEmail, $msgPass);
    }

    public function test_wrong_password_6_times_triggers_lockout(): void
    {
        $role = Role::where('name', 'employee')->first();
        User::create([
            'name' => 'Target User',
            'email' => 'target@example.com',
            'password' => Hash::make('ValidPass123!@#'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        RateLimiter::clear('target@example.com|127.0.0.1');

        // 5 failed attempts
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'target@example.com',
                'password' => 'WrongPassword123!',
            ]);
            $response->assertSessionHasErrors('email');
        }

        // 6th failed attempt triggers lockout
        $res6 = $this->post('/login', [
            'email' => 'target@example.com',
            'password' => 'WrongPassword123!',
        ]);

        $res6->assertSessionHasErrors('email');
        $errorMessage = session('errors')->first('email');
        $this->assertStringContainsString('Too many login attempts', $errorMessage);

        // Audit log verify lockout recorded
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.lockout',
        ]);
    }

    public function test_logout_kills_session_and_records_audit_log(): void
    {
        $role = Role::where('name', 'employee')->first();
        $user = User::create([
            'name' => 'Logout User',
            'email' => 'logout@example.com',
            'password' => Hash::make('ValidPass123!@#'),
            'role_id' => $role->id,
            'is_active' => true,
            'remember_token' => 'old-logout-token',
        ]);

        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        $response = $this->post('/logout');
        $response->assertRedirect('/login');

        $this->assertGuest();
        $this->assertNotSame('old-logout-token', $user->fresh()->remember_token);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'auth.logout',
        ]);
    }

    public function test_forced_password_change_on_first_login(): void
    {
        $role = Role::where('name', 'employee')->first();
        $user = User::create([
            'name' => 'Temp User',
            'email' => 'temp@example.com',
            'password' => Hash::make('TemporaryPassword123!'),
            'role_id' => $role->id,
            'must_change_password' => true,
            'is_active' => true,
            'remember_token' => 'old-password-token',
        ]);

        $this->actingAs($user);

        // Attempt to navigate to dashboard
        $resDashboard = $this->get('/dashboard');
        $resDashboard->assertRedirect('/password/change');

        // Submit weak password: rejected
        $resWeak = $this->post('/password/change', [
            'current_password' => 'TemporaryPassword123!',
            'new_password' => 'weak',
            'new_password_confirmation' => 'weak',
        ]);
        $resWeak->assertSessionHasErrors('new_password');

        // Submit same password: rejected
        $resSame = $this->post('/password/change', [
            'current_password' => 'TemporaryPassword123!',
            'new_password' => 'TemporaryPassword123!',
            'new_password_confirmation' => 'TemporaryPassword123!',
        ]);
        $resSame->assertSessionHasErrors('new_password');

        // Submit valid strong password
        $resValid = $this->post('/password/change', [
            'current_password' => 'TemporaryPassword123!',
            'new_password' => 'BrandNewStrongPass2026!#',
            'new_password_confirmation' => 'BrandNewStrongPass2026!#',
        ]);
        $resValid->assertRedirect('/dashboard');

        // Verify user flag updated and audit log recorded
        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertNotSame('old-password-token', $user->remember_token);
        $this->assertTrue(Hash::check('BrandNewStrongPass2026!#', $user->password));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'password.changed',
        ]);

        // Dashboard accessible now
        $this->get('/dashboard')->assertOk();
    }

    public function test_login_with_checkbox_remember_value_succeeds(): void
    {
        $user = $this->createEmployee('remember-one@example.com');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'ValidPass123!@#',
            'remember' => '1',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        $recallerName = Auth::guard('web')->getRecallerName();
        $recaller = $this->cookieFrom($response, $recallerName);
        $activity = $this->cookieFrom($response, EnforceRememberIdleTimeout::ACTIVITY_COOKIE);

        $this->assertNotNull($recaller);
        $this->assertTrue($recaller->isHttpOnly());
        $this->assertSame('lax', $recaller->getSameSite());
        $this->assertGreaterThan(now()->timestamp, $recaller->getExpiresTime());
        $this->assertLessThanOrEqual(now()->addDays(7)->timestamp, $recaller->getExpiresTime());
        $this->assertNotNull($activity);
    }

    public function test_login_without_remember_succeeds_without_remember_cookie(): void
    {
        $user = $this->createEmployee('remember-two@example.com');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'ValidPass123!@#',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertCookieMissing(Auth::guard('web')->getRecallerName());
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rejects_invalid_remember_value(): void
    {
        $user = $this->createEmployee('remember-invalid@example.com');

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'ValidPass123!@#',
            'remember' => 'garbage',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('remember');
        $this->assertGuest();
    }

    public function test_remembered_login_requires_sign_in_after_idle_timeout(): void
    {
        $user = $this->createEmployee('remember-idle@example.com');

        $login = $this->post('/login', [
            'email' => $user->email,
            'password' => 'ValidPass123!@#',
            'remember' => '1',
        ]);

        $recaller = $this->cookieFrom($login, Auth::guard('web')->getRecallerName());
        $activity = $this->cookieFrom($login, EnforceRememberIdleTimeout::ACTIVITY_COOKIE);
        $oldToken = $user->fresh()->remember_token;

        $this->assertNotNull($recaller);
        $this->assertNotNull($activity);

        $this->flushSession();
        $this->travel(121)->minutes();

        $response = $this->withCookies([
            $recaller->getName() => $recaller->getValue(),
            $activity->getName() => $activity->getValue(),
        ])->get('/dashboard');

        $response->assertRedirect('/login');
        $response->assertCookieExpired($recaller->getName());
        $this->assertGuest();
        $this->assertNotSame($oldToken, $user->fresh()->remember_token);
    }

    public function test_logout_rotates_remember_token_and_kills_remember_cookie(): void
    {
        $user = $this->createEmployee('remember-logout@example.com');

        $login = $this->post('/login', [
            'email' => $user->email,
            'password' => 'ValidPass123!@#',
            'remember' => '1',
        ]);

        $recaller = $this->cookieFrom($login, Auth::guard('web')->getRecallerName());
        $oldToken = $user->fresh()->remember_token;

        $this->assertNotNull($recaller);

        $response = $this->withCookies([
            $recaller->getName() => $recaller->getValue(),
        ])->post('/logout');

        $response->assertRedirect('/login');
        $response->assertCookieExpired($recaller->getName());
        $this->assertGuest();
        $this->assertNotSame($oldToken, $user->fresh()->remember_token);
    }

    private function createEmployee(string $email): User
    {
        return User::create([
            'name' => 'Remember User',
            'email' => $email,
            'password' => Hash::make('ValidPass123!@#'),
            'role_id' => Role::where('name', 'employee')->value('id'),
            'is_active' => true,
        ]);
    }

    private function cookieFrom($response, string $name): ?object
    {
        return collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === $name);
    }
}
