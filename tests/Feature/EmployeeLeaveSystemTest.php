<?php

namespace Tests\Feature;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeLeaveSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $alice;
    protected User $bob;
    protected LeaveType $annual;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\LeaveTypeSeeder::class);

        $role = Role::where('name', 'employee')->first();
        $this->annual = LeaveType::where('code', 'annual')->first();

        $this->alice = User::create([
            'name' => 'Alice Employee',
            'email' => 'alice@company.test',
            'password' => Hash::make('StrongPass123!@#'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->bob = User::create([
            'name' => 'Bob Employee',
            'email' => 'bob@company.test',
            'password' => Hash::make('StrongPass123!@#'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_employee_can_view_dashboard_and_balances(): void
    {
        $response = $this->actingAs($this->alice)->get('/dashboard');
        $response->assertOk();
        $response->assertSee('My leave');
        $response->assertSee($this->annual->name);
    }

    public function test_employee_can_apply_for_leave_with_working_days_and_balance_deduction(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('doctor_note.pdf', 500, 'application/pdf');

        $start = Carbon::now()->next(Carbon::MONDAY);
        $end = (clone $start)->addDays(2); // Mon, Tue, Wed = 3 working days

        $response = $this->actingAs($this->alice)->post('/apply', [
            'leave_type_id' => $this->annual->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'reason' => 'Annual family vacation',
            'attachment' => $file,
            'status' => 'approved', // Malicious attempt to self-approve
            'user_id' => $this->bob->id, // Malicious attempt to submit for Bob
        ]);

        $response->assertRedirect('/requests');

        // Check database: user_id is Alice, status is pending (whitelisting enforced)
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $this->alice->id,
            'leave_type_id' => $this->annual->id,
            'days' => 3,
            'status' => 'pending',
            'attachment_name' => 'doctor_note.pdf',
        ]);

        // Check pending days deducted from balance
        $balance = LeaveBalance::where('user_id', $this->alice->id)
            ->where('leave_type_id', $this->annual->id)
            ->first();

        $this->assertEquals(3, $balance->pending_days);

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->alice->id,
            'action' => 'leave.created',
        ]);
    }

    public function test_idor_protection_prevents_viewing_another_employees_request(): void
    {
        // Bob creates a request
        $start = Carbon::now()->next(Carbon::MONDAY);
        $bobsRequest = LeaveRequest::create([
            'user_id' => $this->bob->id,
            'leave_type_id' => $this->annual->id,
            'start_date' => $start->toDateString(),
            'end_date' => (clone $start)->addDays(1)->toDateString(),
            'days' => 2,
            'reason' => 'Bob confidential appointment',
            'status' => 'pending',
        ]);

        // Alice attempts to access Bob's request directly by URL
        $response = $this->actingAs($this->alice)->get("/requests/{$bobsRequest->id}");

        // Must be 403 Forbidden!
        $response->assertForbidden();
    }

    public function test_idor_protection_prevents_cancelling_another_employees_request(): void
    {
        $start = Carbon::now()->next(Carbon::MONDAY);
        $bobsRequest = LeaveRequest::create([
            'user_id' => $this->bob->id,
            'leave_type_id' => $this->annual->id,
            'start_date' => $start->toDateString(),
            'end_date' => (clone $start)->addDays(1)->toDateString(),
            'days' => 2,
            'reason' => 'Bob personal break',
            'status' => 'pending',
        ]);

        // Alice tries to cancel Bob's request
        $response = $this->actingAs($this->alice)->post("/requests/{$bobsRequest->id}/cancel");

        $response->assertForbidden();
        $this->assertEquals('pending', $bobsRequest->fresh()->status);
    }

    public function test_employee_can_cancel_own_pending_request(): void
    {
        $start = Carbon::now()->next(Carbon::MONDAY);
        $aliceRequest = LeaveRequest::create([
            'user_id' => $this->alice->id,
            'leave_type_id' => $this->annual->id,
            'start_date' => $start->toDateString(),
            'end_date' => (clone $start)->addDays(1)->toDateString(),
            'days' => 2,
            'reason' => 'Alice time off',
            'status' => 'pending',
        ]);

        // Alice cancels own request
        $response = $this->actingAs($this->alice)->post("/requests/{$aliceRequest->id}/cancel");
        $response->assertRedirect('/requests');

        $this->assertEquals('cancelled', $aliceRequest->fresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->alice->id,
            'action' => 'leave.cancelled',
        ]);
    }

    public function test_employee_profile_update_allows_phone_only(): void
    {
        $response = $this->actingAs($this->alice)->post('/profile', [
            'phone' => '+1-555-888-9999',
            'email' => 'hacked@malicious.test',
            'role' => 'admin',
        ]);

        $response->assertRedirect('/profile');

        $this->alice->refresh();
        $this->assertEquals('+1-555-888-9999', $this->alice->phone);
        $this->assertEquals('alice@company.test', $this->alice->email);
        $this->assertEquals('employee', $this->alice->role);
    }

    public function test_weekend_spanning_range_counts_only_working_days(): void
    {
        $start = Carbon::now()->next(Carbon::FRIDAY);
        $end = (clone $start)->addDays(3); // Fri, Sat, Sun, Mon = 2 working days

        $response = $this->actingAs($this->alice)->post('/apply', [
            'leave_type_id' => $this->annual->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'reason' => 'Two working days across the weekend',
        ]);

        $response->assertRedirect('/requests');

        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $this->alice->id,
            'start_date' => $start->toDateString() . ' 00:00:00',
            'end_date' => $end->toDateString() . ' 00:00:00',
            'days' => 2,
        ]);
    }

    public function test_end_before_start_is_rejected(): void
    {
        $start = Carbon::now()->addDay();
        $end = Carbon::now()->toDateString();

        $response = $this->actingAs($this->alice)->post('/apply', [
            'leave_type_id' => $this->annual->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end,
            'reason' => 'Invalid date range',
        ]);

        $response->assertSessionHasErrors('end_date');

        $this->assertDatabaseMissing('leave_requests', [
            'user_id' => $this->alice->id,
            'start_date' => $start->toDateString(),
        ]);
    }

    public function test_past_start_date_is_rejected(): void
    {
        $start = Carbon::now()->subDay();

        $response = $this->actingAs($this->alice)->post('/apply', [
            'leave_type_id' => $this->annual->id,
            'start_date' => $start->toDateString(),
            'end_date' => $start->toDateString(),
            'reason' => 'Past date request',
        ]);

        $response->assertSessionHasErrors('start_date');
    }

    public function test_server_ignores_client_submitted_day_count(): void
    {
        $start = Carbon::now()->next(Carbon::MONDAY);
        $end = (clone $start)->addDays(2); // Mon, Tue, Wed = 3 working days

        $response = $this->actingAs($this->alice)->post('/apply', [
            'leave_type_id' => $this->annual->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'reason' => 'Client tried to spoof the day count',
            'days' => 99,
            'working_days' => 99,
            'workingDaysCount' => 99,
        ]);

        $response->assertRedirect('/requests');

        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $this->alice->id,
            'days' => 3,
        ]);

        $balance = LeaveBalance::where('user_id', $this->alice->id)
            ->where('leave_type_id', $this->annual->id)
            ->first();

        $this->assertEquals(3, $balance->pending_days);
    }

    public function test_apply_page_uses_local_date_helpers_without_utc_shifting(): void
    {
        $response = $this->actingAs($this->alice)->get('/apply');

        $response->assertOk();
        $response->assertSee('js/date-utils.js', false);
        $response->assertDontSee('toISOString', false);
        $response->assertDontSee('getUTC', false);
    }
}
