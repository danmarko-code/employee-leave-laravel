<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsPositionsRequest;
use App\Http\Requests\SettingsTabRequest;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\StoreLeaveTypeRequest;
use App\Http\Requests\StorePositionRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Requests\UpdateLeaveTypeRequest;
use App\Http\Requests\UpdateLeaveTypeStatusRequest;
use App\Http\Requests\UpdatePositionRequest;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\Role;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingsController extends Controller
{
    use AuthorizesRequests;

    private const PER_PAGE = 10;

    public function index(): RedirectResponse
    {
        return redirect()->route('settings.departments');
    }

    public function departments(SettingsTabRequest $request): View
    {
        $this->authorize('viewAny', Department::class);

        $editingId = $request->integer('edit') ?: null;
        $departments = Department::withCount('users')->orderBy('name')->paginate(self::PER_PAGE);
        $roles = Role::whereIn('name', ['employee', 'admin'])->orderBy('display_name')->get();

        return view('settings.departments', compact('departments', 'editingId', 'roles'));
    }

    public function positions(SettingsPositionsRequest $request): View
    {
        $this->authorize('viewAny', Position::class);

        $editingId = $request->integer('edit') ?: null;
        $departmentId = $request->integer('department_id') ?: null;

        $query = Position::with('department')->withCount('users');

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $positions = $query->orderBy('department_id')->orderBy('name')->paginate(self::PER_PAGE)->withQueryString();
        $activeDepartments = Department::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $roles = Role::whereIn('name', ['employee', 'admin'])->orderBy('display_name')->get();

        return view('settings.positions', compact('positions', 'activeDepartments', 'departmentId', 'editingId', 'roles'));
    }

    public function leaveTypes(SettingsTabRequest $request): View
    {
        $this->authorize('viewAny', LeaveType::class);

        $editingId = $request->integer('edit') ?: null;
        $leaveTypes = LeaveType::orderBy('id')->paginate(self::PER_PAGE);
        $roles = Role::whereIn('name', ['employee', 'admin'])->orderBy('display_name')->get();

        return view('settings.leave-types', compact('leaveTypes', 'editingId', 'roles'));
    }

    public function storeDepartment(StoreDepartmentRequest $request): RedirectResponse
    {
        $this->authorize('create', Department::class);

        $department = Department::create($request->validated());

        $this->audit(
            $request,
            'department.created',
            "Created department [{$department->name}].",
            ['department_id' => $department->id]
        );

        return redirect()->route('settings.departments')->with('status', 'Department created.');
    }

    public function updateDepartment(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $this->authorize('update', $department);

        $data = $request->validated();
        $data['code'] = ! empty($data['code']) ? $data['code'] : null;

        $before = [
            'name' => $department->name,
            'code' => $department->code,
            'is_active' => $department->is_active,
        ];

        $department->update($data);

        $after = [
            'name' => $department->name,
            'code' => $department->code,
            'is_active' => $department->is_active,
        ];

        $this->audit(
            $request,
            'department.updated',
            "Updated department [{$department->name}].",
            ['department_id' => $department->id, 'before' => $before, 'after' => $after]
        );

        if ($before['is_active'] && ! $after['is_active']) {
            $this->audit(
                $request,
                'department.deactivated',
                "Deactivated department [{$department->name}].",
                ['department_id' => $department->id]
            );
        }

        return redirect()->route('settings.departments')->with('status', 'Department saved.');
    }

    public function storePosition(StorePositionRequest $request): RedirectResponse
    {
        $this->authorize('create', Position::class);

        $position = Position::create($request->validated());

        $this->audit(
            $request,
            'position.created',
            "Created position [{$position->name}].",
            ['position_id' => $position->id, 'department_id' => $position->department_id]
        );

        return redirect()->route('settings.positions')->with('status', 'Position created.');
    }

    public function updatePosition(UpdatePositionRequest $request, Position $position): RedirectResponse
    {
        $this->authorize('update', $position);

        $wasActive = $position->is_active;
        $position->update($request->validated());

        $this->audit(
            $request,
            'position.updated',
            "Updated position [{$position->name}].",
            ['position_id' => $position->id, 'department_id' => $position->department_id]
        );

        if ($wasActive && ! $position->is_active) {
            $this->audit(
                $request,
                'position.deactivated',
                "Deactivated position [{$position->name}].",
                ['position_id' => $position->id, 'department_id' => $position->department_id]
            );
        }

        return redirect()->route('settings.positions')->with('status', 'Position saved.');
    }

    public function storeLeaveType(StoreLeaveTypeRequest $request): RedirectResponse
    {
        $this->authorize('create', LeaveType::class);

        $data = $request->validated();
        $data['is_paid'] = $request->boolean('is_paid', true);
        $data['requires_doc'] = $request->boolean('requires_doc', false);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['color'] = ! empty($data['color']) ? $data['color'] : '#0F8B8D';

        $leaveType = LeaveType::create($data);

        $this->audit(
            $request,
            'leave_type.created',
            "Created leave type [{$leaveType->name}]."
        );

        return redirect()->route('settings.leave-types')->with('status', 'Leave type created.');
    }

    public function updateLeaveType(UpdateLeaveTypeRequest $request, LeaveType $leaveType): RedirectResponse
    {
        $this->authorize('update', $leaveType);

        $data = $request->validated();
        $data['is_paid'] = $request->boolean('is_paid', false);
        $data['requires_doc'] = $request->boolean('requires_doc', false);
        $data['is_active'] = $request->boolean('is_active', false);
        $data['color'] = ! empty($data['color']) ? $data['color'] : '#0F8B8D';

        $leaveType->update($data);

        $this->audit(
            $request,
            'leave_type.updated',
            "Updated leave type [{$leaveType->name}]."
        );

        return redirect()->route('settings.leave-types')->with('status', 'Leave type saved.');
    }

    public function toggleLeaveType(UpdateLeaveTypeStatusRequest $request, LeaveType $leaveType): RedirectResponse
    {
        $this->authorize('update', $leaveType);

        $active = $request->boolean('is_active');
        $leaveType->update(['is_active' => $active]);

        $this->audit(
            $request,
            'leave_type.toggled',
            ($active ? 'Activated' : 'Deactivated') . " leave type [{$leaveType->name}]."
        );

        return redirect()->route('settings.leave-types')->with('status', 'Leave type status updated.');
    }

    public function destroyLeaveType(Request $request, LeaveType $leaveType): RedirectResponse
    {
        $this->authorize('delete', $leaveType);

        if ($leaveType->requests()->exists() || $leaveType->balances()->exists()) {
            throw ValidationException::withMessages([
                'leave_type' => 'This leave type has requests or balances and cannot be deleted.',
            ]);
        }

        $name = $leaveType->name;
        $leaveType->delete();

        $this->audit(
            $request,
            'leave_type.deleted',
            "Deleted leave type [{$name}]."
        );

        return redirect()->route('settings.leave-types')->with('status', 'Leave type deleted.');
    }

    private function audit(Request $request, string $action, string $description, array $properties = []): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'description' => $description,
            'properties' => $properties,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }
}
