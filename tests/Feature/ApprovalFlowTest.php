<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
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

class ApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $employee;
    protected User $otherDepartmentEmployee;
    protected Department $team;
    protected LeaveType $annual;
    protected Role $adminRole;
    protected Role $employeeRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\LeaveTypeSeeder::class);

        $this->team = Department::create(['name' => 'Support', 'code' => 'SUP']);
        $otherTeam = Department::create(['name' => 'Finance', 'code' => 'FIN']);

        $this->adminRole = Role::where('name', 'admin')->firstOrFail();
        $this->employeeRole = Role::where('name', 'employee')->firstOrFail();

        $this->admin = $this->makeUser('Admin One', $this->adminRole, $this->team);
        $this->employee = $this->makeUser('Team Member', $this->employeeRole, $this->team);
        $this->otherDepartmentEmployee = $this->makeUser('Other Member', $this->employeeRole, $otherTeam);

        $this->annual = LeaveType::where('code', 'annual')->firstOrFail();
    }

    private function makeUser(string $name, Role $role, Department $department): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '@company.test',
            'password' => Hash::make('StrongPass123!@#'),
            'role_id' => $role->id,
            'department_id' => $department->id,
            'is_active' => true,
        ]);
    }

    private function requestFor(User $owner, string $reason = 'Family event'): LeaveRequest
    {
        $start = Carbon::now()->next(Carbon::MONDAY);

        return LeaveRequest::create([
            'user_id' => $owner->id,
            'leave_type_id' => $this->annual->id,
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays(2)->toDateString(),
            'days' => 3,
            'reason' => $reason,
            'status' => 'pending',
        ]);
    }

    private function balanceFor(User $owner, LeaveRequest $request): LeaveBalance
    {
        return LeaveBalance::create([
            'user_id' => $owner->id,
            'leave_type_id' => $this->annual->id,
            'year' => (int) $request->start_date->format('Y'),
            'allocated_days' => 20,
            'used_days' => 0,
            'pending_days' => 3,
        ]);
    }

    public function test_employee_cannot_open_approval_pages(): void
    {
        $request = $this->requestFor($this->employee);

        $this->actingAs($this->employee)->get('/approvals')->assertRedirect(route('dashboard'));
        $this->actingAs($this->employee)->get("/approvals/{$request->id}")->assertRedirect(route('dashboard'));
        $this->actingAs($this->employee)->post("/approvals/{$request->id}/approve", ['note' => 'Yes'])->assertRedirect(route('dashboard'));
        $this->actingAs($this->employee)->post("/approvals/{$request->id}/reject", ['note' => 'No'])->assertRedirect(route('dashboard'));
    }

    public function test_admin_cannot_approve_or_reject_own_request(): void
    {
        $approveRequest = $this->requestFor($this->admin, 'Self approval attempt');
        $rejectRequest = $this->requestFor($this->admin, 'Self rejection attempt');
        $balance = $this->balanceFor($this->admin, $approveRequest);
        $balance->update(['pending_days' => 6]);

        $this->actingAs($this->admin)
            ->post("/approvals/{$approveRequest->id}/approve", ['note' => 'Self approved'])
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->post("/approvals/{$rejectRequest->id}/reject", ['note' => 'Self rejected'])
            ->assertForbidden();

        $this->assertSame('pending', $approveRequest->fresh()->status);
        $this->assertSame('pending', $rejectRequest->fresh()->status);
        $this->assertSame(0.0, $balance->fresh()->used_days);
        $this->assertSame(6.0, $balance->fresh()->pending_days);
        $this->assertSame(0, AuditLog::whereIn('action', ['leave.approved', 'leave.rejected'])->count());
    }

    public function test_another_admin_can_decide_an_admin_request(): void
    {
        $secondAdmin = $this->makeUser('Second Admin', $this->adminRole, $this->team);
        $request = $this->requestFor($this->admin, 'Needs another administrator');
        $balance = $this->balanceFor($this->admin, $request);

        $this->actingAs($secondAdmin)
            ->post("/approvals/{$request->id}/approve", ['note' => 'Reviewed by a different administrator.'])
            ->assertRedirect('/approvals');

        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame($secondAdmin->id, $request->fresh()->decided_by);
        $this->assertSame(3.0, $balance->fresh()->used_days);
        $this->assertSame(0.0, $balance->fresh()->pending_days);
    }

    public function test_double_click_approve_deducts_only_once(): void
    {
        $request = $this->requestFor($this->employee);
        $balance = $this->balanceFor($this->employee, $request);

        $this->actingAs($this->admin)
            ->post("/approvals/{$request->id}/approve", ['note' => 'Approved'])
            ->assertRedirect('/approvals');

        $this->actingAs($this->admin)
            ->post("/approvals/{$request->id}/approve", ['note' => 'Approved again'])
            ->assertForbidden();

        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame(0.0, $balance->fresh()->pending_days);
        $this->assertSame(3.0, $balance->fresh()->used_days);
        $this->assertSame(1, RequestHistory::where('leave_request_id', $request->id)->where('status_to', 'approved')->count());
        $this->assertSame(1, AuditLog::where('action', 'leave.approved')->count());
    }

    public function test_admin_can_review_request_with_remarks(): void
    {
        $request = $this->requestFor($this->employee, '<script>alert(1)</script>');
        $balance = $this->balanceFor($this->employee, $request);

        $this->actingAs($this->admin)
            ->post("/approvals/{$request->id}/reject", [])
            ->assertSessionHasErrors('note');

        $this->actingAs($this->admin)
            ->post("/approvals/{$request->id}/reject", ['note' => 'Not enough leave left.'])
            ->assertRedirect('/approvals');

        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertSame(0.0, $balance->fresh()->pending_days);
        $this->assertSame(0.0, $balance->fresh()->used_days);
        $this->assertDatabaseHas('audit_logs', ['action' => 'leave.rejected', 'user_id' => $this->admin->id]);

        $this->actingAs($this->admin)
            ->get("/approvals/{$request->id}")
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_admin_can_review_request_from_another_department(): void
    {
        $request = $this->requestFor($this->otherDepartmentEmployee, 'Cross department marker');
        $balance = $this->balanceFor($this->otherDepartmentEmployee, $request);

        $this->actingAs($this->admin)
            ->get("/approvals/{$request->id}")
            ->assertOk()
            ->assertSee('Cross department marker');

        $this->actingAs($this->admin)
            ->post("/approvals/{$request->id}/approve", ['note' => 'Reviewed from another department.'])
            ->assertRedirect('/approvals');

        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame(3.0, $balance->fresh()->used_days);
    }

    public function test_employee_sees_only_own_requests(): void
    {
        $own = $this->requestFor($this->employee, 'Own request marker');
        $other = $this->requestFor($this->otherDepartmentEmployee, 'Other request marker');
        $adminRequest = $this->requestFor($this->admin, 'Admin request marker');

        $this->actingAs($this->employee)
            ->get('/requests')
            ->assertOk()
            ->assertViewHas('requests', function ($requests) use ($own) {
                return $requests->total() === 1 && $requests->first()->id === $own->id;
            });

        $this->actingAs($this->employee)
            ->get("/requests/{$own->id}")
            ->assertOk()
            ->assertSee('Own request marker');

        $this->actingAs($this->employee)->get("/requests/{$other->id}")->assertForbidden();
        $this->actingAs($this->employee)->get("/requests/{$adminRequest->id}")->assertForbidden();
    }

    public function test_page_size_is_hard_capped(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->requestFor($this->employee, 'Team event ' . $i);
        }

        $this->actingAs($this->admin)
            ->get('/approvals?per_page=1000')
            ->assertOk()
            ->assertViewHas('requests', fn ($requests) => $requests->perPage() === 25);
    }
}
