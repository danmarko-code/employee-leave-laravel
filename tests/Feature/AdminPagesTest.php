<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\LeaveTypeSeeder;
use Database\Seeders\PositionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $employee;

    protected Role $adminRole;

    protected Role $employeeRole;

    protected LeaveType $annual;

    protected Department $department;

    protected Department $marketing;

    protected Position $position;

    protected Position $marketingPosition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(DepartmentSeeder::class);
        $this->seed(PositionSeeder::class);
        $this->seed(LeaveTypeSeeder::class);

        $this->adminRole = Role::where('name', 'admin')->firstOrFail();
        $this->employeeRole = Role::where('name', 'employee')->firstOrFail();
        $this->annual = LeaveType::where('code', 'annual')->firstOrFail();
        $this->marketing = Department::where('name', 'Marketing')->firstOrFail();
        $this->marketingPosition = Position::where('department_id', $this->marketing->id)->firstOrFail();

        $this->department = Department::create(['name' => 'Support', 'code' => 'SUP']);
        $this->position = Position::create([
            'department_id' => $this->department->id,
            'name' => 'Support Staff',
        ]);

        $this->admin = User::create([
            'name' => 'Admin One',
            'email' => 'admin.one@company.test',
            'password' => Hash::make('StrongPass123!@#'),
            'role_id' => $this->adminRole->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_active' => true,
        ]);

        $this->employee = User::create([
            'name' => 'Team Member',
            'email' => 'team.member@company.test',
            'password' => Hash::make('StrongPass123!@#'),
            'role_id' => $this->employeeRole->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_active' => true,
        ]);
    }

    public function test_only_two_roles_are_seeded(): void
    {
        $this->assertEqualsCanonicalizing(
            ['admin', 'employee'],
            Role::pluck('name')->all()
        );

        $this->assertDatabaseMissing('roles', ['name' => 'approver']);
    }

    public function test_role_seeder_is_idempotent_and_remaps_legacy_users(): void
    {
        $legacy = Role::create([
            'name' => 'approver',
            'display_name' => 'Approver',
            'description' => 'Legacy role.',
        ]);

        $user = User::create([
            'name' => 'Legacy User',
            'email' => 'legacy.user@company.test',
            'password' => Hash::make('StrongPass123!@#'),
            'role_id' => $legacy->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_active' => true,
        ]);

        $this->seed(RoleSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->assertSame(2, Role::count());
        $this->assertSame($this->employeeRole->id, $user->fresh()->role_id);
        $this->assertDatabaseMissing('roles', ['name' => 'approver']);
    }

    public function test_settings_roles_card_is_read_only(): void
    {
        $content = $this->actingAs($this->admin)
            ->get('/settings/departments')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('<h3 class="dr-h">Roles</h3>', $content);
        $this->assertStringContainsString('<b>Employee</b>', $content);
        $this->assertStringContainsString('<b>Administrator</b>', $content);
        $this->assertStringNotContainsString('Add role', $content);
        $this->assertStringNotContainsString('Create role', $content);
        $this->assertStringNotContainsString('Delete role', $content);
    }

    public function test_admin_pages_render(): void
    {
        $balance = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->annual->id,
            'year' => (int) Carbon::now()->format('Y'),
            'allocated_days' => 20,
            'used_days' => 0,
            'pending_days' => 0,
        ]);

        $urls = [
            '/dashboard', '/overview', '/applications', '/users', '/users/create',
            '/users/'.$this->employee->id, '/users/'.$this->employee->id.'/edit',
            '/approvals', '/balances', '/balances/'.$balance->id.'/edit',
            '/reports', '/activity',
            '/settings/departments', '/settings/positions', '/settings/leave-types',
        ];

        foreach ($urls as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }

        $this->actingAs($this->admin)->get('/settings')->assertRedirect('/settings/departments');
    }

    public function test_role_select_has_only_two_roles_and_no_manager_fields(): void
    {
        $content = $this->actingAs($this->admin)->get('/users/create')->assertOk()->getContent();

        $this->assertStringContainsString('>'.$this->employeeRole->display_name.'</option>', $content);
        $this->assertStringContainsString('>'.$this->adminRole->display_name.'</option>', $content);
        $this->assertStringNotContainsString('Approver', $content);
        $this->assertStringNotContainsString('>Manager</option>', $content);
        $this->assertStringNotContainsString('manager_id', $content);
        $this->assertStringNotContainsString('data-manager-id', $content);
        $this->assertStringNotContainsString('head_id', $content);
        $this->assertStringNotContainsString('headId', $content);
    }

    public function test_edit_form_keeps_dependent_positions_without_manager_fields(): void
    {
        $content = $this->actingAs($this->admin)
            ->get('/users/'.$this->employee->id.'/edit')
            ->assertOk()
            ->assertViewHas('formConfig', function (array $config): bool {
                $support = $config['departments'][(string) $this->department->id] ?? [];
                $marketing = $config['departments'][(string) $this->marketing->id] ?? [];

                return in_array(
                    ['id' => $this->position->id, 'name' => $this->position->name],
                    $support['positions'] ?? [],
                    true
                ) && ! in_array(
                    ['id' => $this->marketingPosition->id, 'name' => $this->marketingPosition->name],
                    $support['positions'] ?? [],
                    true
                ) && in_array(
                    ['id' => $this->marketingPosition->id, 'name' => $this->marketingPosition->name],
                    $marketing['positions'] ?? [],
                    true
                );
            })
            ->getContent();

        $this->assertStringNotContainsString('manager_id', $content);
        $this->assertStringNotContainsString('Approver', $content);
    }

    public function test_old_management_urls_redirect_to_settings(): void
    {
        $redirects = [
            '/organization' => '/settings/departments',
            '/roles' => '/settings/departments',
            '/leave-types' => '/settings/leave-types',
            '/types' => '/settings/leave-types',
        ];

        foreach ($redirects as $old => $new) {
            $this->actingAs($this->admin)->get($old)->assertRedirect($new);
        }
    }

    public function test_sidebar_is_role_specific_with_unique_icons(): void
    {
        $adminNav = $this->actingAs($this->admin)->get('/dashboard')->assertOk()->getContent();
        preg_match('/<nav class="nav".*?<\/nav>/s', $adminNav, $adminMatches);
        $adminSidebar = $adminMatches[0] ?? '';

        foreach (['Dashboard', 'Employees', 'Reports', 'My leave', 'Settings'] as $label) {
            $this->assertStringContainsString('<span>'.$label.'</span>', $adminSidebar);
        }

        foreach (['Applications', 'Approvals', 'Balances', 'Activity', 'Organization', 'Leave types', 'Roles'] as $label) {
            $this->assertStringNotContainsString('<span>'.$label.'</span>', $adminSidebar);
        }

        $this->assertStringContainsString('<a class="nav-item active" href="'.route('dashboard').'"', $adminSidebar);

        preg_match_all('/<svg class="ic" viewBox="0 0 24 24" aria-hidden="true">(.*?)<\/svg>/s', $adminSidebar, $matches);
        $svgs = $matches[1] ?? [];
        $this->assertCount(5, $svgs);
        $this->assertCount(5, array_unique($svgs));

        $employeeNav = $this->actingAs($this->employee)->get('/dashboard')->assertOk()->getContent();
        preg_match('/<nav class="nav".*?<\/nav>/s', $employeeNav, $employeeMatches);
        $employeeSidebar = $employeeMatches[0] ?? '';

        foreach (['Dashboard', 'My requests', 'Profile'] as $label) {
            $this->assertStringContainsString('<span>'.$label.'</span>', $employeeSidebar);
        }

        foreach (['Applications', 'Approvals', 'Balances', 'Reports', 'Settings'] as $label) {
            $this->assertStringNotContainsString('<span>'.$label.'</span>', $employeeSidebar);
        }

        preg_match_all('/<svg class="ic" viewBox="0 0 24 24" aria-hidden="true">(.*?)<\/svg>/s', $employeeSidebar, $employeeIcons);
        $this->assertCount(3, $employeeIcons[1] ?? []);
        $this->assertCount(3, array_unique($employeeIcons[1] ?? []));
    }

    public function test_non_admin_cannot_open_admin_urls(): void
    {
        $this->actingAs($this->employee)->get('/overview')->assertForbidden();

        foreach (['/applications', '/users', '/users/create', '/approvals', '/settings', '/settings/departments', '/settings/positions', '/settings/leave-types', '/balances', '/reports', '/activity'] as $url) {
            $this->actingAs($this->employee)->get($url)->assertForbidden();
        }

        $this->actingAs($this->employee)->post('/users', [])->assertForbidden();
        $this->actingAs($this->employee)->post('/settings/departments', [])->assertForbidden();
        $this->actingAs($this->employee)->get('/reports/export')->assertForbidden();
    }

    public function test_admin_creates_user_with_one_time_password_and_forced_change(): void
    {
        $response = $this->actingAs($this->admin)->post('/users', [
            'name' => 'New Hire',
            'email' => 'new.hire@company.test',
            'role_id' => $this->employeeRole->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'phone' => '+1 555 123 4567',
            'is_active' => 1,
        ]);

        $user = User::where('email', 'new.hire@company.test')->firstOrFail();
        $response->assertRedirect('/users/'.$user->id);
        $this->assertNoStoreHeaders($response);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.created',
            'description' => 'Created account for [new.hire@company.test] with role Employee. A temporary password was issued.',
        ]);

        $show = $this->actingAs($this->admin)->get(route('admin.users.show', $user));
        $show->assertOk()
            ->assertSee('Account created')
            ->assertSee('Copy')
            ->assertSee("I've saved it", false)
            ->assertSee('readonly')
            ->assertDontSee('onclick=', false)
            ->assertDontSee('onsubmit=', false);
        $this->assertNoStoreHeaders($show);

        preg_match('/id="temporary_password"[^>]*value="([^"]+)"/', $show->getContent(), $matches);
        $this->assertNotEmpty($matches[1]);
        $temporaryPassword = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        $this->assertTrue(Hash::check($temporaryPassword, $user->password));
        $this->assertSame('argon2id', Hash::info($user->password)['algoName']);
        $this->assertTrue((bool) $user->fresh()->must_change_password);
        $this->assertStringNotContainsString(
            $temporaryPassword,
            json_encode(AuditLog::where('action', 'user.created')->get()->toArray())
        );

        $this->actingAs($this->admin)->get(route('admin.users.show', $user))
            ->assertDontSee('Account created')
            ->assertDontSee($temporaryPassword);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('password.change'));
    }

    public function test_mismatched_position_is_rejected(): void
    {
        $this->actingAs($this->admin)->post('/users', [
            'name' => 'Bad Position',
            'email' => 'bad.position@company.test',
            'role_id' => $this->employeeRole->id,
            'department_id' => $this->department->id,
            'position_id' => $this->marketingPosition->id,
        ])->assertSessionHasErrors('position_id');

        $this->assertDatabaseMissing('users', ['email' => 'bad.position@company.test']);
    }

    public function test_only_fixed_roles_are_accepted(): void
    {
        $this->actingAs($this->admin)->post('/users', [
            'name' => 'Bad Role',
            'email' => 'bad.role@company.test',
            'role_id' => 9999,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ])->assertSessionHasErrors('role_id');

        $this->assertDatabaseMissing('users', ['email' => 'bad.role@company.test']);
    }

    public function test_last_admin_cannot_demote_deactivate_or_delete_self(): void
    {
        $this->actingAs($this->admin)->put("/users/{$this->admin->id}", [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role_id' => $this->employeeRole->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_active' => 1,
        ])->assertSessionHasErrors('role_id');

        $this->actingAs($this->admin)->put("/users/{$this->admin->id}", [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role_id' => $this->adminRole->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_active' => 0,
        ])->assertSessionHasErrors('is_active');

        $this->actingAs($this->admin)->delete("/users/{$this->admin->id}")->assertSessionHasErrors('user');

        $this->assertEquals($this->adminRole->id, $this->admin->fresh()->role_id);
        $this->assertTrue((bool) $this->admin->fresh()->is_active);
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_audit_log_viewer_is_filtered_and_paginated(): void
    {
        $this->actingAs($this->admin)
            ->get('/activity?from=not-a-date')
            ->assertSessionHasErrors('from');

        $this->actingAs($this->admin)
            ->get('/activity?per_page=1000')
            ->assertOk()
            ->assertViewHas('logs', fn ($logs) => $logs->perPage() === 25);

        $this->actingAs($this->admin)->post('/activity', [])->assertStatus(405);
    }

    public function test_csv_export_neutralises_formula_injection(): void
    {
        $start = Carbon::now()->next(Carbon::MONDAY);
        LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->annual->id,
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays(1)->toDateString(),
            'days' => 2,
            'reason' => '=SUM(1+1)',
            'status' => 'approved',
            'decision_note' => '@cmd',
        ]);

        $response = $this->actingAs($this->admin)->get('/reports/export');
        $response->assertOk();

        $body = $response->getContent();
        $this->assertStringContainsString("'=SUM(1+1)", $body);
        $this->assertStringContainsString("'@cmd", $body);
        $this->assertDatabaseHas('audit_logs', ['action' => 'report.exported']);
    }

    public function test_balance_update_is_audited(): void
    {
        $balance = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->annual->id,
            'year' => (int) Carbon::now()->format('Y'),
            'allocated_days' => 20,
            'used_days' => 0,
            'pending_days' => 0,
        ]);

        $this->actingAs($this->admin)
            ->put("/balances/{$balance->id}", ['allocated_days' => 25])
            ->assertRedirect('/balances');

        $this->assertEquals(25, $balance->fresh()->allocated_days);
        $this->assertDatabaseHas('audit_logs', ['action' => 'leave_balance.updated']);
    }

    public function test_admin_resets_temporary_password_and_invalidates_target_sessions(): void
    {
        DB::table('sessions')->insert([
            'id' => 'employee-session',
            'user_id' => $this->employee->id,
            'payload' => 'old-session',
            'last_activity' => now()->timestamp,
        ]);

        $oldHash = $this->employee->password;
        $oldRememberToken = $this->employee->remember_token;

        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.temporary-password.reset', $this->employee));

        $response->assertRedirect(route('admin.users.show', $this->employee));
        $this->assertNoStoreHeaders($response);
        $this->assertDatabaseMissing('sessions', ['user_id' => $this->employee->id]);

        $fresh = $this->employee->fresh();
        $this->assertNotSame($oldHash, $fresh->password);
        $this->assertNotSame($oldRememberToken, $fresh->remember_token);
        $this->assertTrue((bool) $fresh->must_change_password);
        $this->assertSame('argon2id', Hash::info($fresh->password)['algoName']);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'user.temporary_password.reset',
            'description' => 'Reset temporary password for [team.member@company.test].',
        ]);

        $show = $this->actingAs($this->admin)->get(route('admin.users.show', $this->employee));
        preg_match('/id="temporary_password"[^>]*value="([^"]+)"/', $show->getContent(), $matches);
        $this->assertNotEmpty($matches[1]);
        $newPassword = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        $this->assertTrue(Hash::check($newPassword, $fresh->password));
        $this->assertFalse(Hash::check('StrongPass123!@#', $fresh->password));
        $this->assertStringNotContainsString(
            $newPassword,
            json_encode(AuditLog::where('action', 'user.temporary_password.reset')->get()->toArray())
        );

        Auth::guard('web')->logout();
        $this->app['auth']->forgetGuards();

        $this->post('/login', [
            'email' => $this->employee->email,
            'password' => 'StrongPass123!@#',
        ])->assertSessionHasErrors('email');

        $this->post('/login', [
            'email' => $this->employee->email,
            'password' => $newPassword,
        ])->assertRedirect(route('password.change'));
    }

    public function test_temporary_password_reset_is_admin_only_and_never_for_self(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.users.temporary-password.reset', $this->admin))
            ->assertForbidden();

        $this->actingAs($this->employee)
            ->post(route('admin.users.temporary-password.reset', $this->admin))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->get(route('admin.users.temporary-password.reset', $this->employee))
            ->assertStatus(405);
    }

    public function test_temporary_password_reset_is_rate_limited(): void
    {
        $url = route('admin.users.temporary-password.reset', 999999);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->actingAs($this->admin)->post($url)->assertNotFound();
        }

        $this->actingAs($this->admin)->post($url)->assertStatus(429);
    }

    public function test_copy_and_dismiss_logic_lives_in_external_script_without_inline_handlers(): void
    {
        $script = file_get_contents(public_path('js/app.js'));

        $this->assertStringContainsString('navigator.clipboard.writeText', $script);
        $this->assertStringContainsString('data-copy-target', $script);
        $this->assertStringContainsString('data-temp-password-saved', $script);
        $this->assertStringContainsString("'Copied'", $script);
        $this->assertStringContainsString('beforeunload', $script);
    }

    private function assertNoStoreHeaders(TestResponse $response): void
    {
        $cacheControl = (string) $response->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $response->assertHeader('Pragma', 'no-cache');
    }
}
