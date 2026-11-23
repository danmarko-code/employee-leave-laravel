<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportFilterRequest;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Support\Report;
use App\Support\ReportQuery;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use AuthorizesRequests;

    public function overview(): View
    {
        $this->authorize('view', Report::class);

        $employeeCount = User::whereHas('roleRelation', fn ($query) => $query->where('name', 'employee'))->count();
        $allCount = LeaveRequest::count();
        $pendingCount = LeaveRequest::where('status', 'pending')->count();
        $approvedCount = LeaveRequest::where('status', 'approved')->count();
        $rejectedCount = LeaveRequest::where('status', 'rejected')->count();

        $pending = LeaveRequest::with(['user', 'leaveType'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('admin.overview', compact(
            'employeeCount', 'allCount', 'pendingCount', 'approvedCount', 'rejectedCount', 'pending'
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
        $leaveTypes = \App\Models\LeaveType::orderBy('name')->get(['id', 'name']);

        return view('admin.applications', compact('requests', 'filters', 'users', 'leaveTypes'));
    }
}
