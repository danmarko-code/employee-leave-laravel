<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\RequestHistory;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Role $adminRole;
    protected LeaveType $annual;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\LeaveTypeSeeder::class);

        $this->adminRole = Role::where('name', 'admin')->firstOrFail();
        $this->annual = LeaveType::where('code', 'annual')->firstOrFail();
        $this->admin = $this->makeAdmin('Admin Requester');
    }

    public function test_admin_can_use_the_shared_my_leave_pages(): void
    {
        $content = $this->actingAs($this->admin)->get('/overview')->assertOk()->getContent();

        $this->assertStringContainsString('<span>My leave</span>', $content);
        $this->assertStringContainsString(route('requests'), $content);

        $this->actingAs($this->admin)->get('/apply')->assertOk();
        $this->actingAs($this->admin)->get('/requests')->assertOk();
        $this->actingAs($this->admin)->get('/my-balances')->assertOk();
    }

    public function test_admin_without_another_active_admin_cannot_submit_leave(): void
    {
        $this->actingAs($this->admin)->from('/apply')->post('/apply', $this->payload('Single admin request'))
            ->assertRedirect('/apply')
            ->assertSessionHasErrors([
                'leave_type_id' => 'No other administrator is available to review this request. Ask to have a second administrator added.',
            ]);

        $inactive = $this->makeAdmin('Inactive Reviewer');
        $inactive->update(['is_active' => false]);

        $this->actingAs($this->admin)->from('/apply')->post('/apply', $this->payload('Inactive reviewer request'))
            ->assertRedirect('/apply')
            ->assertSessionHasErrors([
                'leave_type_id' => 'No other administrator is available to review this request. Ask to have a second administrator added.',
            ]);

        $this->assertSame(0, LeaveRequest::count());
    }

    public function test_only_another_admin_reviews_and_the_balance_is_deducted_once(): void
    {
        $reviewer = $this->makeAdmin('Admin Reviewer');

        $this->actingAs($this->admin)->post('/apply', $this->payload('Admin leave for review'))
            ->assertRedirect('/requests');

        $request = $this->requestWithReason('Admin leave for review');
        $this->assertSame('pending', $request->status);

        $this->actingAs($reviewer)->get('/approvals')
            ->assertViewHas('requests', fn ($requests) => $requests->pluck('id')->contains($request->id));

        $this->actingAs($this->admin)->get('/approvals')
            ->assertViewHas('requests', fn ($requests) => ! $requests->pluck('id')->contains($request->id));

        $this->actingAs($this->admin)->get("/approvals/{$request->id}")->assertForbidden();
        $this->actingAs($this->admin)->post("/approvals/{$request->id}/approve", ['note' => 'Self approved'])->assertForbidden();
        $this->actingAs($this->admin)->post("/approvals/{$request->id}/reject", ['note' => 'Self rejected'])->assertForbidden();

        $this->actingAs($reviewer)->post("/approvals/{$request->id}/approve", ['note' => 'Approved by another administrator.'])
            ->assertRedirect('/approvals');

        $balance = LeaveBalance::where('user_id', $this->admin->id)
            ->where('leave_type_id', $this->annual->id)
            ->firstOrFail();

        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame($reviewer->id, $request->fresh()->decided_by);
        $this->assertSame(3.0, $balance->fresh()->used_days);
        $this->assertSame(0.0, $balance->fresh()->pending_days);

        $this->actingAs($reviewer)->post("/approvals/{$request->id}/approve", ['note' => 'Approved again'])
            ->assertForbidden();

        $this->assertSame(3.0, $balance->fresh()->used_days);
        $this->assertSame(1, RequestHistory::where('leave_request_id', $request->id)
            ->where('status_to', 'approved')->count());

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'leave.created',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $reviewer->id,
            'action' => 'leave.approved',
        ]);
    }

    public function test_pending_admin_request_waits_when_the_only_reviewer_is_deactivated(): void
    {
        $reviewer = $this->makeAdmin('Deactivated Reviewer');

        $this->actingAs($this->admin)->post('/apply', $this->payload('Waiting after deactivation'))
            ->assertRedirect('/requests');

        $request = $this->requestWithReason('Waiting after deactivation');
        $reviewer->update(['is_active' => false]);

        $this->actingAs($this->admin)->get("/requests/{$request->id}")
            ->assertOk()
            ->assertSee('Waiting for another administrator.');

        $this->actingAs($reviewer)->post("/approvals/{$request->id}/approve", ['note' => 'Too late'])
            ->assertForbidden();

        $this->assertSame('pending', $request->fresh()->status);
    }

    public function test_pending_admin_request_waits_when_the_only_reviewer_is_removed(): void
    {
        $reviewer = $this->makeAdmin('Removed Reviewer');

        $this->actingAs($this->admin)->post('/apply', $this->payload('Waiting after removal'))
            ->assertRedirect('/requests');

        $request = $this->requestWithReason('Waiting after removal');
        $reviewer->delete();

        $this->actingAs($this->admin)->get("/requests/{$request->id}")
            ->assertOk()
            ->assertSee('Waiting for another administrator.');

        $this->assertSame('pending', $request->fresh()->status);
    }

    public function test_admin_can_cancel_their_own_pending_request(): void
    {
        $this->makeAdmin('Cancel Reviewer');

        $this->actingAs($this->admin)->post('/apply', $this->payload('Admin cancellation'))
            ->assertRedirect('/requests');

        $request = $this->requestWithReason('Admin cancellation');

        $this->actingAs($this->admin)->post("/requests/{$request->id}/cancel")
            ->assertRedirect('/requests');

        $balance = LeaveBalance::where('user_id', $this->admin->id)
            ->where('leave_type_id', $this->annual->id)
            ->firstOrFail();

        $this->assertSame('cancelled', $request->fresh()->status);
        $this->assertSame(0.0, $balance->fresh()->pending_days);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'leave.cancelled',
        ]);
        $this->assertSame(1, AuditLog::where('user_id', $this->admin->id)
            ->where('action', 'leave.cancelled')->count());
    }

    private function makeAdmin(string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '@company.test',
            'password' => Hash::make('StrongPass123!@#'),
            'role_id' => $this->adminRole->id,
            'is_active' => true,
        ]);
    }

    private function payload(string $reason): array
    {
        $start = Carbon::now()->next(Carbon::MONDAY);

        return [
            'leave_type_id' => $this->annual->id,
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays(2)->toDateString(),
            'reason' => $reason,
        ];
    }

    private function requestWithReason(string $reason): LeaveRequest
    {
        return LeaveRequest::where('reason', $reason)->firstOrFail();
    }
}
