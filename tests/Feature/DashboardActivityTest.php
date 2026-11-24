<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardActivityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\LeaveTypeSeeder::class);

        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $employeeRole = Role::where('name', 'employee')->firstOrFail();

        $this->admin = User::create([
            'name' => 'Admin One',
            'email' => 'admin@company.test',
            'password' => Hash::make('StrongPass123!@#'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $this->employee = User::create([
            'name' => 'Team Member',
            'email' => 'team@company.test',
            'password' => Hash::make('StrongPass123!@#'),
            'role_id' => $employeeRole->id,
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 23; $i++) {
            AuditLog::create([
                'user_id' => $this->employee->id,
                'action' => 'leave.created',
                'description' => 'Activity entry ' . $i,
                'created_at' => now()->subMinutes(30 - $i),
            ]);
        }
    }

    public function test_admin_dashboard_shows_twenty_newest_entries_in_a_scrolling_card(): void
    {
        $response = $this->actingAs($this->admin)->get(route('overview'));

        $response->assertOk();
        $response->assertViewHas('recentActivity', fn ($rows) => $rows->count() === 20);
        $response->assertSee('Recent activity');
        $response->assertSee('View all activity');
        $response->assertSee('class="activity-scroll"', false);
        $response->assertSee('class="dashboard"', false);
        $response->assertSee('class="dashboard-panels"', false);
        $response->assertSee('role="region"', false);
        $response->assertSee('aria-label="Recent activity"', false);
        $response->assertSee('tabindex="0"', false);

        $html = $response->getContent();
        $this->assertSame(20, substr_count($html, 'class="act-row"'));
        preg_match('/<main[^>]*>(.*?)<\/main>/s', $html, $matches);
        $dashboardHtml = $matches[1] ?? $html;
        $this->assertStringNotContainsString('style=', $dashboardHtml);
        $this->assertStringNotContainsString('<script>', $dashboardHtml);

        $rows = $response->original->getData()['recentActivity'];
        $rows->each(fn ($log) => $this->assertTrue($log->relationLoaded('user')));
        $this->assertSame('Activity entry 23', $rows->first()->description);
        $this->assertSame('Activity entry 4', $rows->last()->description);
    }

    public function test_dashboard_layout_and_activity_css_match_the_polished_design(): void
    {
        $this->actingAs($this->admin)->get(route('overview'))->assertOk()
            ->assertSee('Waiting for review')
            ->assertSee('Recent activity')
            ->assertSee('View all activity');

        $css = file_get_contents(public_path('css/app.css'));

        $this->assertStringContainsString('repeat(auto-fit,minmax(160px,1fr))', $css);
        $this->assertStringContainsString('grid-template-columns:minmax(0,2fr) minmax(0,1fr)', $css);
        $this->assertStringContainsString('@media (max-width:1099px)', $css);
        $this->assertStringContainsString('background:#fff;border-bottom:1px solid var(--line)', $css);
        $this->assertStringContainsString('overscroll-behavior:contain', $css);
        $this->assertStringContainsString('.activity-scroll:focus-visible', $css);
    }

    public function test_auth_events_are_hidden_on_the_dashboard_but_kept_in_the_full_log(): void
    {
        foreach (['auth.login', 'auth.logout', 'auth.failed', 'auth.lockout'] as $action) {
            AuditLog::create([
                'user_id' => $this->employee->id,
                'action' => $action,
                'description' => 'Sign-in noise ' . $action,
                'created_at' => now(),
            ]);
        }

        $dashboard = $this->actingAs($this->admin)->get(route('overview'))->assertOk();

        $dashboard->assertViewHas('recentActivity', fn ($rows) => $rows->count() === 20
            && $rows->every(fn ($log) => ! str_starts_with($log->action, 'auth.')));
        $dashboard->assertDontSee('Sign-in noise auth.login');
        $dashboard->assertDontSee('Signed in');
        $dashboard->assertDontSee('Sign-in failed');

        $fullLog = $this->actingAs($this->admin)->get(route('activity'))->assertOk();

        $fullLog->assertViewHas('logs', fn ($logs) => $logs->total() === 27
            && $logs->getCollection()->contains(fn ($log) => $log->action === 'auth.login')
            && $logs->getCollection()->contains(fn ($log) => $log->action === 'auth.failed'));
        $fullLog->assertSee('Sign-in noise auth.login');
        $fullLog->assertSee('Sign-in noise auth.failed');
    }

    public function test_action_codes_are_shown_as_readable_labels(): void
    {
        foreach (['leave.approved', 'leave.rejected', 'user.created', 'department.updated', 'custom.action_name'] as $action) {
            AuditLog::create([
                'user_id' => $this->admin->id,
                'action' => $action,
                'description' => 'Polished event recorded.',
                'created_at' => now(),
            ]);
        }

        $html = $this->actingAs($this->admin)->get(route('overview'))->assertOk()->getContent();

        foreach (['Approved', 'Rejected', 'User created', 'Department updated', 'Custom Action Name'] as $label) {
            $this->assertStringContainsString($label, $html);
        }

        foreach (['leave.approved', 'leave.rejected', 'user.created', 'department.updated', 'custom.action_name'] as $code) {
            $this->assertStringNotContainsString($code, $html);
        }
    }

    public function test_activity_details_drop_brackets_and_do_not_repeat_the_actor(): void
    {
        AuditLog::create([
            'user_id' => $this->admin->id,
            'action' => 'user.updated',
            'description' => 'Updated account for [admin@company.test] and [Team Member].',
            'created_at' => now(),
        ]);

        $html = $this->actingAs($this->admin)->get(route('overview'))->assertOk()->getContent();

        $this->assertStringContainsString('Updated account for and Team Member.', $html);
        $this->assertStringNotContainsString('[admin@company.test]', $html);
        $this->assertStringNotContainsString('[Team Member]', $html);
    }

    public function test_full_user_names_are_shown_in_dashboard_tables(): void
    {
        $leaveType = \App\Models\LeaveType::firstOrFail();

        \App\Models\LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'days' => 2,
            'reason' => 'Rest',
            'status' => 'pending',
        ]);

        AuditLog::create([
            'user_id' => $this->admin->id,
            'action' => 'leave.approved',
            'description' => 'Approved the request.',
            'created_at' => now(),
        ]);

        $html = $this->actingAs($this->admin)->get(route('overview'))->assertOk()->getContent();

        $this->assertStringContainsString('<b>Team Member</b>', $html);
        $this->assertStringContainsString('>Admin One</td>', $html);
        $this->assertStringNotContainsString('<b>admin</b>', $html);
    }

    public function test_empty_dashboard_activity_state_is_shown(): void
    {
        AuditLog::query()->delete();

        $response = $this->actingAs($this->admin)->get(route('overview'));

        $response->assertOk();
        $response->assertSee('No activity yet.');
        $this->assertSame(0, substr_count($response->getContent(), 'class="act-row"'));
    }

    public function test_employee_dashboard_receives_no_activity_data(): void
    {
        $response = $this->actingAs($this->employee)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('employee.dashboard');
        $this->assertArrayNotHasKey('recentActivity', $response->original->getData());
        $response->assertDontSee('Recent activity');
        $response->assertDontSee('View all activity');
        $response->assertDontSee('Activity entry 23');
    }

    public function test_activity_page_marks_dashboard_as_active_in_the_sidebar(): void
    {
        $html = $this->actingAs($this->admin)->get(route('activity'))->assertOk()->getContent();

        $this->assertStringContainsString('<a class="nav-item active" href="' . route('overview') . '"', $html);
        $this->assertStringNotContainsString('<span>Activity</span>', $html);
    }

    public function test_employee_gets_forbidden_on_the_full_activity_page(): void
    {
        $this->actingAs($this->employee)->get(route('activity'))->assertRedirect(route('dashboard'));
    }

    public function test_full_activity_page_keeps_filters_and_capped_pagination(): void
    {
        $this->actingAs($this->admin)
            ->get('/activity?per_page=1000')
            ->assertOk()
            ->assertViewHas('logs', fn ($logs) => $logs->perPage() === 25 && $logs->total() === 23);

        $this->actingAs($this->admin)
            ->get('/activity?action=leave.created&user_id=' . $this->employee->id)
            ->assertOk()
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 23);

        $this->actingAs($this->admin)
            ->get('/activity?from=not-a-date')
            ->assertSessionHasErrors('from');
    }

    public function test_activity_output_is_escaped_and_never_shows_sensitive_fields(): void
    {
        $unsafe = User::create([
            'name' => '<script>alert(1)</script>',
            'email' => 'unsafe@company.test',
            'password' => Hash::make('StrongPass123!@#'),
            'role_id' => Role::where('name', 'employee')->firstOrFail()->id,
            'is_active' => true,
        ]);

        AuditLog::create([
            'user_id' => $unsafe->id,
            'action' => 'user.created',
            'description' => '<script>alert(2)</script> RAW-TOKEN-XYZ',
            'properties' => ['password' => 'SUPER-SECRET-PW', 'session_id' => 'RAW-SESSION-XYZ'],
            'created_at' => now(),
        ]);

        $html = $this->actingAs($this->admin)->get(route('overview'))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<script>alert(2)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(2)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('SUPER-SECRET-PW', $html);
        $this->assertStringNotContainsString('RAW-SESSION-XYZ', $html);
    }
}
