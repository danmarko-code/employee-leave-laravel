<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\LeaveTypeSeeder;
use Database\Seeders\RoleSeeder;
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

        $this->seed(RoleSeeder::class);
        $this->seed(LeaveTypeSeeder::class);

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
                'description' => 'Activity entry '.$i,
                'created_at' => now()->subMinutes(30 - $i),
            ]);
        }
    }

    public function test_admin_dashboard_does_not_show_recent_activity(): void
    {
        $response = $this->actingAs($this->admin)->get(route('overview'));

        $response->assertOk();
        $response->assertDontSee('Recent activity');
        $response->assertDontSee('View all activity');
        $response->assertDontSee('Activity entry 23');
        $this->assertArrayNotHasKey('recentActivity', $response->original->getData());
        $this->assertSame(0, substr_count($response->getContent(), 'class="act-row"'));
    }

    public function test_dashboard_layout_css_matches_the_polished_design(): void
    {
        $html = $this->actingAs($this->admin)->get(route('overview'))->assertOk()
            ->assertSee('Needs review')
            ->assertDontSee('Recent activity')
            ->assertDontSee('View all activity')
            ->getContent();

        $css = file_get_contents(public_path('css/app.css'));

        $this->assertStringContainsString('--space-4:24px', $css);
        $this->assertStringContainsString('container-type:inline-size', $css);
        $this->assertStringContainsString('@container (min-width:820px){.dashboard-stats{grid-template-columns:repeat(5,minmax(0,1fr))}}', $css);
        $this->assertStringContainsString('.dashboard-context{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr))', $css);
        $this->assertStringContainsString('.dashboard .bal-grid{grid-template-columns:repeat(6,minmax(0,1fr))}', $css);
        $this->assertStringContainsString('.dashboard .bal-grid>a.bal{grid-column:span 2;height:100%;align-items:center}', $css);
        $this->assertStringContainsString('.bal-ring-slot{display:contents}', $css);
        $this->assertStringContainsString('.dashboard .bal-grid>a.bal.stretched{display:flex;flex-direction:column;', $css);
        $this->assertStringContainsString('.dashboard .bal-grid>a.bal:last-of-type{grid-column:span 3}', $css);
        $this->assertStringContainsString('.who-next{grid-column:span 3;height:100%;', $css);
        $this->assertStringContainsString('.dashboard-context{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr));gap:var(--space-3);align-items:stretch}', $css);
        $this->assertStringNotContainsString('.bal-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,1fr));gap:var(--space-3);align-items:start}', $css);
        $this->assertStringContainsString('@container (max-width:620px)', $css);
        $this->assertStringContainsString('.who-next-list{display:grid;', $css);
        $this->assertStringNotContainsString('grid-template-columns:minmax(0,2fr) minmax(0,1fr)', $css);
        $this->assertStringNotContainsString('dashboard-charts', $css);
        $this->assertStringNotContainsString('activity-card', $css);

        $this->assertLessThan(strpos($html, 'class="bal-grid"'), strpos($html, 'id="review-heading"'));
        $this->assertStringNotContainsString('chart-card', $html);
    }

    public function test_auth_events_stay_in_the_full_log(): void
    {
        foreach (['auth.login', 'auth.logout', 'auth.failed', 'auth.lockout'] as $action) {
            AuditLog::create([
                'user_id' => $this->employee->id,
                'action' => $action,
                'description' => 'Sign-in noise '.$action,
                'created_at' => now(),
            ]);
        }

        $fullLog = $this->actingAs($this->admin)->get(route('activity'))->assertOk();

        $fullLog->assertViewHas('logs', fn ($logs) => $logs->total() === 27
            && $logs->getCollection()->contains(fn ($log) => $log->action === 'auth.login')
            && $logs->getCollection()->contains(fn ($log) => $log->action === 'auth.failed'));
        $fullLog->assertSee('Sign-in noise auth.login');
        $fullLog->assertSee('Sign-in noise auth.failed');
    }

    public function test_full_user_names_are_shown_in_dashboard_tables(): void
    {
        $leaveType = LeaveType::firstOrFail();

        LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'days' => 2,
            'reason' => 'Rest',
            'status' => 'pending',
        ]);

        $html = $this->actingAs($this->admin)->get(route('overview'))->assertOk()->getContent();

        $this->assertStringContainsString('<b>Team Member</b>', $html);
        $this->assertStringNotContainsString('<b>admin</b>', $html);
    }

    public function test_empty_activity_page_state_is_shown(): void
    {
        AuditLog::query()->delete();

        $this->actingAs($this->admin)->get(route('activity'))->assertOk()
            ->assertSee('No activity matches these filters.');
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

        $this->assertStringContainsString('<a class="nav-item active" href="'.route('dashboard').'"', $html);
        $this->assertStringNotContainsString('<span>Activity</span>', $html);
    }

    public function test_employee_gets_forbidden_on_the_full_activity_page(): void
    {
        $this->actingAs($this->employee)->get(route('activity'))->assertForbidden();
    }

    public function test_full_activity_page_keeps_filters_and_capped_pagination(): void
    {
        $this->actingAs($this->admin)
            ->get('/activity?per_page=1000')
            ->assertOk()
            ->assertViewHas('logs', fn ($logs) => $logs->perPage() === 25 && $logs->total() === 23);

        $this->actingAs($this->admin)
            ->get('/activity?action=leave.created&user_id='.$this->employee->id)
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

        $html = $this->actingAs($this->admin)->get(route('activity'))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<script>alert(2)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(2)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('SUPER-SECRET-PW', $html);
        $this->assertStringNotContainsString('RAW-SESSION-XYZ', $html);
    }
}
