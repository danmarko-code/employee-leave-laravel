<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetTemporaryPasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Services\LeaveBalanceService;
use App\Support\AdminGuard;
use App\Support\TemporaryPassword;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $q = trim((string) $request->query('q', ''));
        $role = (string) $request->query('role', 'all');

        $query = User::with(['roleRelation', 'department', 'position']);

        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%')
                    ->orWhereHas('position', fn ($positionQuery) => $positionQuery
                        ->where('name', 'like', '%'.$q.'%'));
            });
        }

        if (in_array($role, ['admin', 'employee'], true)) {
            $query->whereHas('roleRelation', fn ($inner) => $inner->where('name', $role));
        } else {
            $role = 'all';
        }

        $perPage = min(max((int) $request->query('per_page', 10), 1), 25);
        $users = $query->orderBy('id', 'desc')->paginate($perPage)->withQueryString();
        $roles = Role::whereIn('name', ['employee', 'admin'])->orderBy('display_name')->get();

        return view('admin.users.index', compact('users', 'q', 'role', 'roles'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', $this->userFormData());
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $plainPassword = TemporaryPassword::generate();
        $role = Role::findOrFail($data['role_id']);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::driver('argon2id')->make($plainPassword),
            'role_id' => $role->id,
            'department_id' => $data['department_id'],
            'position_id' => $data['position_id'],
            'phone' => ! empty($data['phone']) ? $data['phone'] : null,
            'is_active' => $request->boolean('is_active', true),
            'must_change_password' => true,
        ]);

        if ($user->is_active) {
            app(LeaveBalanceService::class)->ensureBalancesForUser($user);
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user.created',
            'description' => "Created account for [{$user->email}] with role {$role->display_name}. A temporary password was issued.",
            'properties' => [
                'role' => $role->name,
                'role_id' => $role->id,
                'department_id' => $user->department_id,
                'position_id' => $user->position_id,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        // Flashed for exactly one request; the profile page pulls it immediately.
        $request->session()->flash('temporary_password', $plainPassword);

        return $this->noStore(redirect()->route('admin.users.show', $user));
    }

    public function show(Request $request, User $user): View|Response
    {
        $this->authorize('view', $user);

        $temporaryPassword = $request->session()->pull('temporary_password');
        $user->load(['roleRelation', 'department', 'position']);
        $history = $user->leaveRequests()->with('leaveType')->orderBy('id', 'desc')->take(8)->get();
        $balances = $user->leaveBalances()->with('leaveType')->orderBy('year', 'desc')->get();
        $data = compact('user', 'temporaryPassword', 'history', 'balances');

        if (! $temporaryPassword) {
            return view('admin.users.show', $data);
        }

        return $this->noStore(response()->view('admin.users.show', $data));
    }

    public function resetTemporaryPassword(ResetTemporaryPasswordRequest $request, User $user): RedirectResponse
    {
        $this->authorize('resetTemporaryPassword', $user);

        $plainPassword = TemporaryPassword::generate();

        $user->forceFill([
            'password' => Hash::driver('argon2id')->make($plainPassword),
            'must_change_password' => true,
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('sessions')->where('user_id', $user->id)->delete();

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user.temporary_password.reset',
            'description' => "Reset temporary password for [{$user->email}].",
            'properties' => [
                'target_user_id' => $user->id,
                'target_email' => $user->email,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        $request->session()->flash('temporary_password', $plainPassword);

        return $this->noStore(redirect()->route('admin.users.show', $user));
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', array_merge(
            ['user' => $user],
            $this->userFormData()
        ));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $role = Role::findOrFail($data['role_id']);

        $before = [
            'role_id' => $user->role_id,
            'department_id' => $user->department_id,
            'position_id' => $user->position_id,
        ];

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'role_id' => $role->id,
            'department_id' => $data['department_id'],
            'position_id' => $data['position_id'],
            'phone' => ! empty($data['phone']) ? $data['phone'] : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $after = [
            'role_id' => $user->role_id,
            'department_id' => $user->department_id,
            'position_id' => $user->position_id,
        ];

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user.updated',
            'description' => "Updated account for [{$user->email}].",
            'properties' => ['before' => $before, 'after' => $after],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        if ($before !== $after) {
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'user.assignment.updated',
                'description' => "Updated assignment for [{$user->email}].",
                'properties' => ['before' => $before, 'after' => $after],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        }

        return redirect()->route('admin.users.show', $user)->with('status', 'Account saved.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if (AdminGuard::isLastAdmin($user)) {
            throw ValidationException::withMessages([
                'user' => 'The last active administrator account cannot be deleted.',
            ]);
        }

        $email = $user->email;
        $actorId = $request->user()->id;
        $user->delete();

        AuditLog::create([
            'user_id' => $actorId,
            'action' => 'user.deleted',
            'description' => "Deleted account [{$email}].",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('admin.users.index')->with('status', 'Account deleted.');
    }

    /**
     * Keep a response carrying a one-time password out of browser caches.
     *
     * @template T of \Symfony\Component\HttpFoundation\Response
     *
     * @param  T  $response
     * @return T
     */
    private function noStore($response)
    {
        return $response
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    /**
     * One escaped JSON payload keeps the two dependent selects in sync.
     */
    private function userFormData(): array
    {
        $roles = Role::whereIn('name', ['employee', 'admin'])->orderBy('display_name')->get();
        $employeeRoleId = Role::where('name', 'employee')->value('id');
        $departments = Department::where('is_active', true)
            ->with('activePositions:id,department_id,name')
            ->orderBy('name')
            ->get();

        $formConfig = [
            'departments' => $departments->mapWithKeys(function (Department $department) {
                return [(string) $department->id => [
                    'positions' => $department->activePositions
                        ->map(fn ($position) => ['id' => (int) $position->id, 'name' => $position->name])
                        ->values()
                        ->all(),
                ]];
            })->all(),
        ];

        return compact('roles', 'departments', 'employeeRoleId', 'formConfig');
    }
}
