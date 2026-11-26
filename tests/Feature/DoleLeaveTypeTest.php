<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Services\LeaveBalanceService;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\LeaveTypeSeeder;
use Database\Seeders\PositionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DoleLeaveTypeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(DepartmentSeeder::class);
        $this->seed(PositionSeeder::class);

        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $employeeRole = Role::where('name', 'employee')->firstOrFail();
        $dept = Department::firstOrFail();
        $pos = Position::firstOrFail();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin.dole@company.test',
            'password' => Hash::make('StrongPass123!@#'),
            'role_id' => $adminRole->id,
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'is_active' => true,
        ]);

        $this->employee = User::create([
            'name' => 'Employee User',
            'email' => 'employee.dole@company.test',
            'password' => Hash::make('StrongPass123!@#'),
            'role_id' => $employeeRole->id,
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'is_active' => true,
        ]);
    }

    public function test_seeder_run_twice_creates_no_duplicates(): void
    {
        $seeder = new LeaveTypeSeeder;

        $seeder->run();
        $countAfterFirst = LeaveType::count();
        $this->assertSame(12, $countAfterFirst);

        $seeder->run();
        $countAfterSecond = LeaveType::count();
        $this->assertSame(12, $countAfterSecond);

        $expectedCodes = [
            'annual', 'sick', 'personal', 'unpaid',
            'sil', 'maternity', 'maternity_miscarriage', 'maternity_unpaid',
            'paternity', 'solo_parent', 'vawc', 'women_special',
        ];

        foreach ($expectedCodes as $code) {
            $this->assertDatabaseHas('leave_types', ['code' => $code]);
        }
    }

    public function test_balance_service_creates_zero_day_rows_for_admin_types_and_full_rows_for_automatic_types_and_leaves_existing_rows_untouched(): void
    {
        $year = (int) date('Y');

        $autoType = LeaveType::create([
            'name' => 'Test Auto Leave',
            'code' => 'test_auto',
            'days_allowed' => 15,
            'is_paid' => true,
            'requires_doc' => false,
            'is_active' => true,
            'day_basis' => 'working',
            'grant_mode' => 'automatic',
            'color' => '#018002',
        ]);

        $adminType = LeaveType::create([
            'name' => 'Test Admin Leave',
            'code' => 'test_admin',
            'days_allowed' => 105,
            'is_paid' => true,
            'requires_doc' => true,
            'is_active' => true,
            'day_basis' => 'calendar',
            'grant_mode' => 'admin',
            'color' => '#B8962A',
        ]);

        // Pre-existing row for auto type with custom allocated and used days
        $existing = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $autoType->id,
            'year' => $year,
            'allocated_days' => 10,
            'used_days' => 3,
            'pending_days' => 1,
        ]);

        $service = app(LeaveBalanceService::class);
        $service->ensureBalancesForUser($this->employee, $year);

        // Untouched existing balance
        $existingFresh = $existing->fresh();
        $this->assertEquals(10.0, $existingFresh->allocated_days);
        $this->assertEquals(3.0, $existingFresh->used_days);
        $this->assertEquals(1.0, $existingFresh->pending_days);

        // Admin type balance created with 0 allocated days
        $adminBal = LeaveBalance::where('user_id', $this->employee->id)
            ->where('leave_type_id', $adminType->id)
            ->where('year', $year)
            ->first();

        $this->assertNotNull($adminBal);
        $this->assertEquals(0.0, $adminBal->allocated_days);
        $this->assertEquals(0.0, $adminBal->used_days);
        $this->assertEquals(0.0, $adminBal->pending_days);

        // Admin user also gets 0 allocated days for admin type and full for auto type
        $service->ensureActiveBalances($year);

        $adminUserBal = LeaveBalance::where('user_id', $this->admin->id)
            ->where('leave_type_id', $autoType->id)
            ->where('year', $year)
            ->first();

        $this->assertNotNull($adminUserBal);
        $this->assertEquals(15.0, $adminUserBal->allocated_days);
    }

    public function test_zero_allocation_ring_renders_with_no_division_by_zero(): void
    {
        $rendered = view('partials.balance-ring', [
            'name' => 'Maternity Leave',
            'allocated' => 0,
            'avail' => 0,
            'pending' => 0,
            'used' => 0,
        ])->render();

        $this->assertStringContainsString('>0</strong>', $rendered);
        $this->assertStringContainsString('days left', $rendered);
        $this->assertStringContainsString('Maternity Leave', $rendered);
        $this->assertStringContainsString('0 days this year', $rendered);
    }

    public function test_invalid_day_basis_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)->post('/settings/leave-types', [
            'name' => 'Invalid Day Basis Type',
            'code' => 'invalid_basis_type',
            'days_allowed' => 5,
            'day_basis' => 'solar',
            'grant_mode' => 'automatic',
        ]);

        $response->assertSessionHasErrors('day_basis');

        $validType = LeaveType::create([
            'name' => 'Valid Type',
            'code' => 'valid_type',
            'days_allowed' => 5,
            'day_basis' => 'working',
            'grant_mode' => 'automatic',
            'is_active' => true,
        ]);

        $updateResponse = $this->actingAs($this->admin)->put("/settings/leave-types/{$validType->id}", [
            'name' => 'Valid Type',
            'code' => 'valid_type',
            'days_allowed' => 5,
            'day_basis' => 'hourly',
            'grant_mode' => 'automatic',
        ]);

        $updateResponse->assertSessionHasErrors('day_basis');
    }

    public function test_apply_page_disables_admin_grant_types_with_zero_remaining(): void
    {
        $adminType = LeaveType::create([
            'name' => 'Maternity Leave',
            'code' => 'maternity',
            'days_allowed' => 105,
            'is_paid' => true,
            'requires_doc' => true,
            'is_active' => true,
            'day_basis' => 'calendar',
            'grant_mode' => 'admin',
            'color' => '#B8962A',
        ]);

        $response = $this->actingAs($this->employee)->get('/apply');
        $response->assertOk();
        $response->assertSee('Ask HR to grant this leave');
        $response->assertSee('disabled', false);
    }

    public function test_statutory_types_with_zero_allocated_are_hidden_from_rings_until_allocated(): void
    {
        $year = (int) date('Y');

        $statutoryType = LeaveType::create([
            'name' => 'Solo Parent Leave',
            'code' => 'solo_parent_test',
            'days_allowed' => 7,
            'is_paid' => true,
            'requires_doc' => true,
            'is_active' => true,
            'day_basis' => 'working',
            'grant_mode' => 'admin',
            'is_statutory' => true,
            'color' => '#3B4F25',
        ]);

        $balance = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $statutoryType->id,
            'year' => $year,
            'allocated_days' => 0,
            'used_days' => 0,
            'pending_days' => 0,
        ]);

        // Employee dashboard: ring hidden when allocated is 0
        $dashResponse = $this->actingAs($this->employee)->get('/dashboard');
        $dashResponse->assertOk();
        $dashResponse->assertDontSee('Solo Parent Leave');

        // Admin overview: ring hidden when total allocated is 0
        $overviewResponse = $this->actingAs($this->admin)->get(route('overview'));
        $overviewResponse->assertOk();
        $rings = collect($overviewResponse->viewData('balanceRings'));
        $this->assertNull($rings->firstWhere('name', 'Solo Parent Leave'));

        // Allocate days to the employee
        $balance->update(['allocated_days' => 7]);

        // Employee dashboard: ring appears once allocated > 0
        $dashAfter = $this->actingAs($this->employee)->get('/dashboard');
        $dashAfter->assertOk();
        $dashAfter->assertSee('Solo Parent Leave');

        // Admin overview: ring appears once allocated > 0
        $overviewAfter = $this->actingAs($this->admin)->get(route('overview'));
        $overviewAfter->assertOk();
        $ringsAfter = collect($overviewAfter->viewData('balanceRings'));
        $this->assertNotNull($ringsAfter->firstWhere('name', 'Solo Parent Leave'));
    }
}
