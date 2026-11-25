<?php

namespace Tests\Feature;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\LeaveTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $employee;

    protected LeaveType $annual;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(LeaveTypeSeeder::class);

        $this->annual = LeaveType::where('code', 'annual')->firstOrFail();
        $this->admin = $this->makeUser('Navigation Admin', 'nav.admin@company.test', 'admin');
        $this->employee = $this->makeUser('Navigation Employee', 'nav.employee@company.test');
    }

    public function test_dashboard_tabs_render_as_real_links_with_matching_active_states(): void
    {
        $tabs = [
            route('dashboard') => 'Overview',
            route('approvals.index') => 'Approvals',
            route('admin.applications') => 'Applications',
            route('activity') => 'Activity',
        ];

        foreach ($tabs as $url => $label) {
            $html = $this->actingAs($this->admin)->get($url)->assertOk()->getContent();
            $tabBar = $this->tabBar($html);

            foreach ($tabs as $tabUrl => $tabLabel) {
                $this->assertStringContainsString('href="'.$tabUrl.'"', $tabBar);
                $this->assertStringContainsString('>'.$tabLabel.'</a>', $tabBar);
            }

            $active = $this->activeAnchor($tabBar);
            $this->assertNotNull($active);
            $this->assertSame($url, $active->getAttribute('href'));
            $this->assertSame($label, trim($active->textContent));
            $this->assertSame('settings-tab active', $active->getAttribute('class'));
            $this->assertSame(1, substr_count($tabBar, 'aria-current="page"'));
        }
    }

    public function test_employees_tabs_render_on_people_and_balances(): void
    {
        $balance = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->annual->id,
            'year' => (int) date('Y'),
            'allocated_days' => 20,
            'used_days' => 0,
            'pending_days' => 0,
        ]);

        $tabs = [
            route('users') => 'People',
            route('admin.balances.index') => 'Balances',
        ];

        foreach ($tabs as $url => $label) {
            $html = $this->actingAs($this->admin)->get($url)->assertOk()->getContent();
            $tabBar = $this->tabBar($html);

            $this->assertStringContainsString('aria-label="Employee sections"', $tabBar);
            $this->assertStringContainsString('href="'.route('users').'"', $tabBar);
            $this->assertStringContainsString('href="'.route('admin.balances.index').'"', $tabBar);
            $active = $this->activeAnchor($tabBar);
            $this->assertNotNull($active);
            $this->assertSame($url, $active->getAttribute('href'));
            $this->assertSame($label, trim($active->textContent));
            $this->assertSame('settings-tab active', $active->getAttribute('class'));
            $this->assertSame(1, substr_count($tabBar, 'aria-current="page"'));
        }

        foreach (["/users/{$this->employee->id}", "/balances/{$balance->id}/edit"] as $url) {
            $html = $this->actingAs($this->admin)->get($url)->assertOk()->getContent();
            $this->assertStringContainsString(
                '<a class="nav-item active" href="'.route('users').'"',
                $this->sidebar($html)
            );
        }
    }

    public function test_dashboard_item_stays_active_across_grouped_admin_pages(): void
    {
        $request = $this->pendingRequest($this->employee, 'Grouped request', now()->subDay());

        $urls = [
            route('dashboard'),
            route('overview'),
            route('approvals.index'),
            route('approvals.show', $request),
            route('admin.applications'),
            route('activity'),
            route('requests.show', $this->pendingRequest($this->admin, 'Admin own request', now())),
        ];

        foreach ($urls as $url) {
            $html = $this->actingAs($this->admin)->get($url)->assertOk()->getContent();
            $sidebar = $this->sidebar($html);

            $this->assertStringContainsString(
                '<a class="nav-item active" href="'.route('dashboard').'"',
                $sidebar
            );
            $this->assertStringNotContainsString(
                '<a class="nav-item active" href="'.route('requests').'"',
                $sidebar
            );
        }
    }

    public function test_badge_counts_only_other_pending_requests_and_hides_at_zero(): void
    {
        foreach (range(1, 3) as $day) {
            $this->pendingRequest($this->employee, 'Other request '.$day, now()->subDays($day));
        }
        $this->pendingRequest($this->admin, 'Own request', now()->subDays(4));

        DB::flushQueryLog();
        DB::enableQueryLog();

        $html = $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->getContent();

        DB::disableQueryLog();

        $this->assertStringContainsString('3 pending approvals', $html);
        $this->assertStringContainsString('<span aria-hidden="true">3</span>', $html);
        $this->assertStringNotContainsString('id="qBadge" hidden', $html);

        $countQueries = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $query): bool => str_contains($query, 'from "leave_requests"')
                && str_contains($query, 'count(*)')
                && str_contains($query, '"status" = ?')
                && str_contains($query, '"user_id" != ?'));

        $this->assertCount(1, $countQueries);

        LeaveRequest::query()->delete();
        $this->pendingRequest($this->admin, 'Own request again', now());

        $emptyHtml = $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('id="qBadge" hidden', $emptyHtml);
        $this->assertStringNotContainsString('1 pending approval', $emptyHtml);
    }

    public function test_overview_preview_shows_oldest_five_requests_from_other_users(): void
    {
        $oldest = [];

        foreach (range(1, 6) as $day) {
            $oldest[$day] = $this->pendingRequest(
                $this->employee,
                'Preview request '.$day,
                now()->subDays(10 - $day)
            );
        }

        $own = $this->pendingRequest($this->admin, 'Own preview request', now()->subDays(20));

        $response = $this->actingAs($this->admin)->get(route('dashboard'))->assertOk();

        $this->assertEqualsCanonicalizing(
            collect($oldest)->take(5)->pluck('id')->all(),
            $response->viewData('pending')->pluck('id')->all()
        );
        $this->assertNotContains($oldest[6]->id, $response->viewData('pending')->pluck('id')->all());
        $this->assertNotContains($own->id, $response->viewData('pending')->pluck('id')->all());

        $html = $response->getContent();
        $this->assertStringContainsString('Needs review', $html);
        $this->assertStringContainsString('View all approvals', $html);

        foreach (collect($oldest)->take(5) as $request) {
            $this->assertStringContainsString('href="'.route('approvals.show', $request).'"', $html);
        }

        $this->assertStringNotContainsString('href="'.route('approvals.show', $oldest[6]).'"', $html);
    }

    public function test_dashboard_does_not_render_chart_cards(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'))->assertOk();
        $html = $response->getContent();

        $this->assertArrayNotHasKey('monthlyRequests', $response->viewData());
        $this->assertArrayNotHasKey('approvedDays', $response->viewData());
        $this->assertStringNotContainsString('Requests per month', $html);
        $this->assertStringNotContainsString('Approved leave days', $html);
        $this->assertStringNotContainsString('chart-card', $html);
        $this->assertStringNotContainsString('chart-track', $html);
        $this->assertStringNotContainsString('<canvas', $html);
    }

    public function test_admin_urls_stay_available_to_admin_and_forbidden_to_employee(): void
    {
        $balance = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->annual->id,
            'year' => (int) date('Y'),
            'allocated_days' => 20,
            'used_days' => 0,
            'pending_days' => 0,
        ]);
        $request = $this->pendingRequest($this->employee, 'Access check', now()->subDay());

        $this->actingAs($this->admin)->get('/dashboard')->assertOk();
        $this->actingAs($this->employee)->get('/dashboard')->assertOk();

        $this->actingAs($this->admin)->get('/settings')->assertRedirect('/settings/departments');
        $this->actingAs($this->employee)->get('/settings')->assertForbidden();

        $adminUrls = [
            '/overview', '/applications', '/users', '/users/create',
            '/users/'.$this->employee->id, '/users/'.$this->employee->id.'/edit',
            '/approvals', '/approvals/'.$request->id, '/balances',
            '/balances/'.$balance->id.'/edit', '/reports', '/reports/export',
            '/activity', '/settings/departments', '/settings/positions', '/settings/leave-types',
        ];

        foreach ($adminUrls as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk('Admin should open '.$url);
            $this->actingAs($this->employee)->get($url)->assertForbidden('Employee should not open '.$url);
        }
    }

    protected function makeUser(string $name, string $email, string $roleName = 'employee'): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('StrongPass123!@#'),
            'role_id' => Role::where('name', $roleName)->firstOrFail()->id,
            'is_active' => true,
        ]);
    }

    protected function pendingRequest(User $user, string $reason, $createdAt): LeaveRequest
    {
        $request = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $this->annual->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'days' => 2,
            'reason' => $reason,
            'status' => 'pending',
        ]);

        DB::table('leave_requests')->where('id', $request->id)->update([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        return $request->fresh();
    }

    protected function sidebar(string $html): string
    {
        preg_match('/<nav class="nav".*?<\/nav>/s', $html, $matches);

        return $matches[0] ?? '';
    }

    protected function tabBar(string $html): string
    {
        preg_match('/<nav class="settings-tabs".*?<\/nav>/s', $html, $matches);

        return $matches[0] ?? '';
    }

    protected function activeAnchor(string $html): ?\DOMElement
    {
        $dom = new \DOMDocument;
        $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);

        foreach ($dom->getElementsByTagName('a') as $anchor) {
            if ($anchor->getAttribute('aria-current') === 'page') {
                return $anchor;
            }
        }

        return null;
    }
}
