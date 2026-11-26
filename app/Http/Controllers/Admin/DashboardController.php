<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportFilterRequest;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Support\PendingApprovalCount;
use App\Support\Report;
use App\Support\ReportQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use AuthorizesRequests;

    public function overview(PendingApprovalCount $pendingApprovals): View
    {
        $this->authorize('view', Report::class);

        /** @var User $admin */
        $admin = auth()->user();
        $employeeCount = User::whereHas('roleRelation', fn ($query) => $query->where('name', 'employee'))->count();
        $allCount = LeaveRequest::count();
        $pendingCount = $pendingApprovals->for($admin);
        $approvedCount = LeaveRequest::where('status', 'approved')->count();
        $rejectedCount = LeaveRequest::where('status', 'rejected')->count();

        $pending = LeaveRequest::with(['user', 'leaveType'])
            ->where('status', 'pending')
            ->where('user_id', '!=', $admin->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->take(5)
            ->get();

        $currentYear = (int) date('Y');
        $activeEmployeeBalance = function (Builder $query) use ($currentYear): void {
            $query->where('year', $currentYear);
            $query->whereHas('user', function (Builder $userQuery): void {
                $userQuery->where('is_active', true);
            });
        };

        $balanceTypes = LeaveType::where('is_active', true)
            ->withCount(['balances as current_year_balance_count' => $activeEmployeeBalance])
            ->withSum(['balances as allocated_days_total' => $activeEmployeeBalance], 'allocated_days')
            ->withSum(['balances as used_days_total' => $activeEmployeeBalance], 'used_days')
            ->withSum(['balances as pending_days_total' => $activeEmployeeBalance], 'pending_days')
            ->orderBy('name')
            ->get();

        $filteredTypes = $balanceTypes->filter(function (LeaveType $leaveType): bool {
            if ($leaveType->is_statutory && (float) ($leaveType->allocated_days_total ?? 0) <= 0) {
                return false;
            }

            return true;
        });

        $balanceRings = $balanceTypes->sum('current_year_balance_count') > 0
            ? $filteredTypes->map(fn (LeaveType $leaveType): array => [
                'id' => $leaveType->id,
                'name' => $leaveType->name,
                'allocated' => (float) ($leaveType->allocated_days_total ?? 0),
                'used' => (float) ($leaveType->used_days_total ?? 0),
                'pending' => (float) ($leaveType->pending_days_total ?? 0),
                'available' => max(0, (float) ($leaveType->allocated_days_total ?? 0)
                    - (float) ($leaveType->used_days_total ?? 0)
                    - (float) ($leaveType->pending_days_total ?? 0)),
            ])->values()->all()
            : [];

        $upcomingLeave = LeaveRequest::with(['user', 'leaveType'])
            ->where('status', 'approved')
            ->where('end_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->orderBy('id')
            ->take(3)
            ->get();

        return view('admin.overview', compact(
            'employeeCount',
            'allCount',
            'pendingCount',
            'approvedCount',
            'rejectedCount',
            'pending',
            'balanceRings',
            'upcomingLeave'
        ));
    }

    public function applications(ReportFilterRequest $request): View
    {
        $filters = $request->validated();
        $query = ReportQuery::apply(
            LeaveRequest::with(['user', 'leaveType', 'decidedBy'])->orderBy('created_at', 'desc')->orderBy('id', 'desc'),
            $filters
        );

        $perPage = min(max((int) $request->query('per_page', 10), 1), 25);
        $requests = $query->paginate($perPage)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name']);
        $leaveTypes = LeaveType::orderBy('name')->get(['id', 'name']);

        return view('admin.applications', compact('requests', 'filters', 'users', 'leaveTypes'));
    }
}
