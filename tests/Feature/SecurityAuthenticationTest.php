<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        ]);

        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        $response = $this->post('/logout');
        $response->assertRedirect('/login');

        $this->assertGuest();
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
        $this->assertTrue(Hash::check('BrandNewStrongPass2026!#', $user->password));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'password.changed',
        ]);

        // Dashboard accessible now
        $this->get('/dashboard')->assertOk();
    }
}
