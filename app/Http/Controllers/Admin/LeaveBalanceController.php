<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveBalanceFilterRequest;
use App\Http\Requests\UpdateLeaveBalanceRequest;
use App\Models\AuditLog;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LeaveBalanceController extends Controller
{
    use AuthorizesRequests;

    public function index(LeaveBalanceFilterRequest $request): View
    {
        $filters = $request->validated();

        $query = LeaveBalance::with(['user', 'leaveType']);

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['leave_type_id'])) {
            $query->where('leave_type_id', $filters['leave_type_id']);
        }

        if (! empty($filters['year'])) {
            $query->where('year', $filters['year']);
        }

        $perPage = min(max((int) $request->query('per_page', 10), 1), 25);
        $balances = $query->orderBy('year', 'desc')->orderBy('id', 'desc')->paginate($perPage)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name']);
        $leaveTypes = LeaveType::orderBy('name')->get(['id', 'name']);

        return view('admin.balances.index', compact('balances', 'filters', 'users', 'leaveTypes'));
    }

    public function edit(LeaveBalance $leaveBalance): View
    {
        $this->authorize('update', $leaveBalance);

        return view('admin.balances.edit', compact('leaveBalance'));
    }

    public function update(UpdateLeaveBalanceRequest $request, LeaveBalance $leaveBalance): RedirectResponse
    {
        $leaveBalance->update($request->validated());

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'leave_balance.updated',
            'description' => "Updated allocated days to {$leaveBalance->allocated_days} for user #{$leaveBalance->user_id}, leave type #{$leaveBalance->leave_type_id}, year {$leaveBalance->year}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('admin.balances.index')->with('status', 'Balance updated.');
    }
}
