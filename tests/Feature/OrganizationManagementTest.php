<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrganizationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Department $it;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
        $this->seed(\Database\Seeders\PositionSeeder::class);
        $this->seed(\Database\Seeders\LeaveTypeSeeder::class);

        $this->it = Department::where('name', 'IT')->firstOrFail();
        $position = Position::where('department_id', $this->it->id)->firstOrFail();
        $adminRole = Role::where('name', 'admin')->firstOrFail();

        $this->admin = User::create([
            'name' => 'Admin One',
            'email' => 'admin@company.test',
            'password' => Hash::make('StrongPass123!@#'),
            'role_id' => $adminRole->id,
            'department_id' => $this->it->id,
            'position_id' => $position->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_adds_renames_and_deactivates_departments_and_positions(): void
    {
        $this->actingAs($this->admin)->post('/settings/departments', [
            'name' => 'Legal',
            'code' => 'LEG',
        ])->assertRedirect('/settings/departments');

        $legal = Department::where('name', 'Legal')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['action' => 'department.created']);

        $this->actingAs($this->admin)->put("/settings/departments/{$legal->id}", [
            'name' => 'Legal Affairs',
            'code' => 'LEG',
            'is_active' => 1,
        ])->assertRedirect('/settings/departments');

        $this->assertSame('Legal Affairs', $legal->fresh()->name);

        $this->actingAs($this->admin)->post('/settings/positions', [
            'department_id' => $legal->id,
            'name' => 'Legal Assistant',
        ])->assertRedirect('/settings/positions');

        $position = Position::where('department_id', $legal->id)->where('name', 'Legal Assistant')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['action' => 'position.created']);

        $this->actingAs($this->admin)->put("/settings/positions/{$position->id}", [
            'name' => 'Legal Assistant',
            'is_active' => 0,
        ])->assertRedirect('/settings/positions');

        $this->assertFalse((bool) $position->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'position.deactivated']);
    }

    public function test_in_use_departments_and_positions_cannot_be_deactivated(): void
    {
        $position = $this->admin->position;

        $this->actingAs($this->admin)->put("/settings/positions/{$position->id}", [
            'name' => $position->name,
            'is_active' => 0,
        ])->assertSessionHasErrors('is_active');

        $this->actingAs($this->admin)->put("/settings/departments/{$this->it->id}", [
            'name' => $this->it->name,
            'code' => $this->it->code,
            'is_active' => 0,
        ])->assertSessionHasErrors('is_active');

        $this->assertTrue((bool) $position->fresh()->is_active);
        $this->assertTrue((bool) $this->it->fresh()->is_active);
    }

    public function test_settings_tabs_show_only_their_own_list_with_ten_rows(): void
    {
        $this->actingAs($this->admin)
            ->get('/settings/departments?per_page=100')
            ->assertOk()
            ->assertViewIs('settings.departments')
            ->assertViewHas('departments', fn ($departments) => $departments->perPage() === 10
                && $departments->getCollection()->contains(fn ($department) => $department->name === 'IT'));

        $this->actingAs($this->admin)
            ->get('/settings/positions?per_page=100')
            ->assertOk()
            ->assertViewIs('settings.positions')
            ->assertViewHas('positions', fn ($positions) => $positions->perPage() === 10
                && $positions->count() === 10);

        $this->actingAs($this->admin)
            ->get('/settings/leave-types?per_page=100')
            ->assertOk()
            ->assertViewIs('settings.leave-types')
            ->assertViewHas('leaveTypes', fn ($leaveTypes) => $leaveTypes->perPage() === 10
                && $leaveTypes->getCollection()->contains(fn ($type) => $type->code === 'annual'));
    }

    public function test_invalid_add_reopens_panel_with_errors(): void
    {
        $response = $this->actingAs($this->admin)
            ->from('/settings/departments')
            ->post('/settings/departments', ['name' => '', 'code' => 'BAD'])
            ->assertRedirect('/settings/departments')
            ->assertSessionHasErrors('name');

        $sessionCookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));

        $this->actingAs($this->admin)
            ->withUnencryptedCookie($sessionCookie->getName(), $sessionCookie->getValue())
            ->get('/settings/departments')
            ->assertOk()
            ->assertSee('<details class="add-panel" open>', false)
            ->assertSee('The name field is required.');
    }

    public function test_positions_filter_rejects_unknown_department(): void
    {
        $this->actingAs($this->admin)
            ->get('/settings/positions?department_id=99999')
            ->assertSessionHasErrors('department_id');
    }

    public function test_leave_type_changes_keep_audit_logs(): void
    {
        $type = LeaveType::where('code', 'annual')->firstOrFail();

        $this->actingAs($this->admin)->post('/settings/leave-types', [
            'name' => 'Study Leave',
            'code' => 'study_leave',
            'days_allowed' => 5,
            'is_paid' => 1,
            'is_active' => 1,
        ])->assertRedirect('/settings/leave-types');

        $this->assertDatabaseHas('audit_logs', ['action' => 'leave_type.created']);

        $this->actingAs($this->admin)->put("/settings/leave-types/{$type->id}", [
            'name' => $type->name,
            'code' => $type->code,
            'days_allowed' => 25,
            'is_paid' => 1,
            'is_active' => 1,
        ])->assertRedirect('/settings/leave-types');

        $this->assertSame(25, $type->fresh()->days_allowed);
        $this->assertDatabaseHas('audit_logs', ['action' => 'leave_type.updated']);
    }
}
