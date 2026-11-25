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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminOverviewRingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected LeaveType $annual;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(LeaveTypeSeeder::class);

        $this->annual = LeaveType::where('code', 'annual')->firstOrFail();
        $this->admin = $this->makeUser('Admin Overview', 'admin.overview@company.test', 'admin');
    }

    public function test_rings_use_current_year_active_employee_totals_and_link_to_filtered_balances(): void
    {
        $employee = $this->makeUser('Active Employee', 'active.employee@company.test');
        $inactive = $this->makeUser('Inactive Employee', 'inactive.employee@company.test', 'employee', false);
        $currentYear = (int) date('Y');
        $hiddenType = LeaveType::create([
            'name' => 'Hidden Leave',
            'code' => 'hidden',
            'days_allowed' => 99,
            'is_paid' => true,
            'requires_doc' => false,
            'is_active' => false,
            'color' => '#000000',
        ]);

        LeaveBalance::create([
            'user_id' => $employee->id,
            'leave_type_id' => $this->annual->id,
            'year' => $currentYear,
            'allocated_days' => 200,
            'used_days' => 70,
            'pending_days' => 10,
        ]);
        LeaveBalance::create([
            'user_id' => $inactive->id,
            'leave_type_id' => $this->annual->id,
            'year' => $currentYear,
            'allocated_days' => 999,
            'used_days' => 0,
            'pending_days' => 0,
        ]);
        LeaveBalance::create([
            'user_id' => $employee->id,
            'leave_type_id' => $this->annual->id,
            'year' => $currentYear - 1,
            'allocated_days' => 999,
            'used_days' => 0,
            'pending_days' => 0,
        ]);
        LeaveBalance::create([
            'user_id' => $employee->id,
            'leave_type_id' => $hiddenType->id,
            'year' => $currentYear,
            'allocated_days' => 999,
            'used_days' => 0,
            'pending_days' => 0,
        ]);

        $response = $this->actingAs($this->admin)->get(route('overview'))->assertOk();
        $rings = $response->viewData('balanceRings');
        $annual = collect($rings)->firstWhere('name', 'Annual Leave');

        $this->assertCount(4, $rings);
        $this->assertSame(200.0, $annual['allocated']);
        $this->assertSame(70.0, $annual['used']);
        $this->assertSame(10.0, $annual['pending']);
        $this->assertSame(120.0, $annual['available']);

        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'class="card bal stretched"'));
        $this->assertStringContainsString('Annual Leave: 120 days left of 200 allocated, 10 pending, 70 used', $html);
        $this->assertStringContainsString('200 days allocated this year', $html);
        $this->assertStringContainsString('href="'.route('admin.balances.index', ['leave_type_id' => $this->annual->id]).'"', $html);
        $this->assertStringNotContainsString('999 days allocated this year', $html);

        preg_match('/<main[^>]*>(.*?)<\/main>/s', $html, $matches);
        $main = $matches[1] ?? $html;
        $this->assertStringNotContainsString('<canvas', $main);
        $this->assertStringNotContainsString('style=', $main);
        $this->assertStringNotContainsString('<script>', $main);
    }

    public function test_ring_available_days_never_go_below_zero(): void
    {
        $employee = $this->makeUser('Busy Employee', 'busy.employee@company.test');

        LeaveBalance::create([
            'user_id' => $employee->id,
            'leave_type_id' => $this->annual->id,
            'year' => (int) date('Y'),
            'allocated_days' => 10,
            'used_days' => 7,
            'pending_days' => 8,
        ]);

        $html = $this->actingAs($this->admin)->get(route('overview'))->assertOk()->getContent();
        $this->assertStringContainsString('Annual Leave: 0 days left of 10 allocated, 8 pending, 7 used', $html);
    }

    public function test_empty_year_shows_one_balance_message_with_a_balances_link(): void
    {
        $response = $this->actingAs($this->admin)->get(route('overview'))->assertOk();

        $this->assertSame([], $response->viewData('balanceRings'));

        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'class="card bal empty-bal"'));
        $this->assertStringContainsString('No balances for this year yet', $html);
        $this->assertStringContainsString('href="'.route('admin.balances.index').'"', $html);
    }

    public function test_whos_out_next_orders_limits_and_marks_requests(): void
    {
        $onLeaveNow = $this->makeUser('On Leave Now', 'now@company.test');
        $firstFuture = $this->makeUser('First Future', 'future.one@company.test');
        $secondFuture = $this->makeUser('Second Future', 'future.two@company.test');
        $fourth = $this->makeUser('Fourth Future', 'future.four@company.test');
        $rejected = $this->makeUser('Rejected Person', 'rejected@company.test');
        $expired = $this->makeUser('Expired Person', 'expired@company.test');

        $ongoing = $this->makeRequest($onLeaveNow, now()->subDay(), now()->addDay());
        $first = $this->makeRequest($firstFuture, now()->addDays(2), now()->addDays(3));
        $second = $this->makeRequest($secondFuture, now()->addDays(4), now()->addDays(5));
        $this->makeRequest($fourth, now()->addDays(6), now()->addDays(7));
        $this->makeRequest($rejected, now()->addDay(), now()->addDays(2), 'rejected');
        $this->makeRequest($expired, now()->subDays(5), now()->subDay());

        $response = $this->actingAs($this->admin)->get(route('overview'))->assertOk();
        $upcoming = $response->viewData('upcomingLeave');

        $this->assertSame([$ongoing->id, $first->id, $second->id], $upcoming->pluck('id')->all());
        $this->assertTrue($upcoming->every(fn (LeaveRequest $request): bool => $request->relationLoaded('user')
            && $request->relationLoaded('leaveType')));

        $html = $response->getContent();
        $this->assertStringContainsString('Who\'s out next', $html);
        $this->assertStringContainsString('On Leave Now', $html);
        $this->assertStringContainsString('First Future', $html);
        $this->assertStringContainsString('Second Future', $html);
        $this->assertStringContainsString('2 working days', $html);
        $this->assertSame(1, substr_count($html, 'On leave now'));
        $this->assertStringNotContainsString('Fourth Future', $html);
        $this->assertStringNotContainsString('Rejected Person', $html);
        $this->assertStringNotContainsString('Expired Person', $html);
        $this->assertStringNotContainsString('Nobody is on leave soon.', $html);
    }

    public function test_whos_out_next_has_an_empty_state(): void
    {
        $html = $this->actingAs($this->admin)->get(route('overview'))->assertOk()->getContent();

        $this->assertStringContainsString('Nobody is on leave soon.', $html);
    }

    public function test_employee_cannot_open_admin_overview(): void
    {
        $employee = $this->makeUser('Plain Employee', 'plain.employee@company.test');

        $response = $this->actingAs($employee)->get(route('overview'));

        $response->assertForbidden();
        $response->assertDontSee('Annual Leave:');
        $response->assertDontSee('Who\'s out next');
    }

    public function test_employee_dashboard_keeps_the_shared_ring_markup(): void
    {
        $employee = $this->makeUser('Ring Employee', 'ring.employee@company.test');

        LeaveBalance::create([
            'user_id' => $employee->id,
            'leave_type_id' => $this->annual->id,
            'year' => (int) date('Y'),
            'allocated_days' => 20,
            'used_days' => 3,
            'pending_days' => 2,
        ]);

        $response = $this->actingAs($employee)->get(route('dashboard'))->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('<article class="card bal">', $html);
        $this->assertStringContainsString('stroke="var(--lagoon)"', $html);
        $this->assertStringContainsString('stroke="var(--sun)"', $html);
        $this->assertStringContainsString('stroke="#b8c5ba"', $html);
        $this->assertStringContainsString('<i class="available"></i>Available<b>15</b>', $html);
        $this->assertStringContainsString('<i class="pending"></i>Pending<b>2</b>', $html);
        $this->assertStringContainsString('<i class="used"></i>Used<b>3</b>', $html);
        $this->assertStringContainsString('>15</strong>', $html);
        $this->assertStringContainsString('20 days this year', $html);
        $this->assertStringContainsString('Next time off', $html);
        $this->assertStringNotContainsString('days allocated this year', $html);
        $this->assertArrayNotHasKey('balanceRings', $response->original->getData());
        $this->assertArrayNotHasKey('upcomingLeave', $response->original->getData());
    }

    protected function makeUser(string $name, string $email, string $roleName = 'employee', bool $active = true): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('StrongPass123!@#'),
            'role_id' => Role::where('name', $roleName)->firstOrFail()->id,
            'is_active' => $active,
        ]);
    }

    protected function makeRequest(User $user, Carbon $start, Carbon $end, string $status = 'approved'): LeaveRequest
    {
        return LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $this->annual->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'days' => 2,
            'reason' => 'Planned time off',
            'status' => $status,
        ]);
    }
}
