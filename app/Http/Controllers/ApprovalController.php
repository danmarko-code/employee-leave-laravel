<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApproveLeaveRequest;
use App\Http\Requests\RejectLeaveRequest;
use App\Models\AuditLog;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\RequestHistory;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    use AuthorizesRequests;

    /**
     * Administrators review every request except their own.
     */
    private function reviewQuery(User $user)
    {
        return LeaveRequest::where('user_id', '!=', $user->id)
            ->with(['user', 'leaveType']);
    }

    /**
     * Pending approvals from all employees, with hard-capped pagination.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('viewAnyApprovals', LeaveRequest::class);

        $base = $this->reviewQuery($user);
        $pendingCount = (clone $base)->where('status', 'pending')->count();
        $decidedCount = (clone $base)->whereIn('status', ['approved', 'rejected'])->count();

        $status = $request->query('status', 'pending');

        if (! in_array($status, ['pending', 'approved', 'rejected', 'all'], true)) {
            $status = 'pending';
        }

        $query = clone $base;

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $perPage = min(max((int) $request->query('per_page', 10), 1), 25);
        $requests = $query->orderBy('created_at', 'desc')->orderBy('id', 'desc')
            ->paginate($perPage)->withQueryString();

        return view('approvals.index', compact('requests', 'status', 'pendingCount', 'decidedCount'));
    }

    /**
     * Review one request, including remarks and the balance impact.
     */
    public function show(Request $request, LeaveRequest $leaveRequest): View
    {
        $this->authorize('review', $leaveRequest);

        $leaveRequest->load(['user', 'leaveType', 'decidedBy', 'histories.actor']);

        $year = (int) $leaveRequest->start_date->format('Y');
        $balance = LeaveBalance::where('user_id', $leaveRequest->user_id)
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->where('year', $year)
            ->first();

        $availableBefore = $balance
            ? max(0, (float) $balance->available_days)
            : (float) $leaveRequest->leaveType->days_allowed;

        $availableAfter = max(0, $availableBefore - (float) $leaveRequest->days);

        return view('approvals.show', compact('leaveRequest', 'availableBefore', 'availableAfter'));
    }

    /**
     * Approve a request from the team.
     */
    public function approve(ApproveLeaveRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        return $this->decide($request, $leaveRequest, 'approved');
    }

    /**
     * Reject a request from the team.
     */
    public function reject(RejectLeaveRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        return $this->decide($request, $leaveRequest, 'rejected');
    }

    /**
     * Apply a decision once, with a row lock around the status change and balance update.
     */
    private function decide(Request $request, LeaveRequest $leaveRequest, string $status): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorize($status === 'approved' ? 'approve' : 'reject', $leaveRequest);

        $note = trim((string) $request->input('note', ''));

        DB::transaction(function () use ($leaveRequest, $user, $status, $note) {
            // Lock the request row so a second click cannot decide it again.
            $locked = LeaveRequest::query()->whereKey($leaveRequest->id)->lockForUpdate()->firstOrFail();

            if (! $locked->canTransitionTo($status)) {
                throw ValidationException::withMessages([
                    'note' => 'This request has already been decided.',
                ]);
            }

            if (! Gate::forUser($user)->allows($status === 'approved' ? 'approve' : 'reject', $locked)) {
                throw ValidationException::withMessages([
                    'note' => 'You cannot decide this request.',
                ]);
            }

            $year = (int) $locked->start_date->format('Y');
            $balance = LeaveBalance::where('user_id', $locked->user_id)
                ->where('leave_type_id', $locked->leave_type_id)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($balance) {
                $pending = max(0, (float) $balance->pending_days - (float) $locked->days);
                $used = (float) $balance->used_days + ($status === 'approved' ? (float) $locked->days : 0);

                $balance->update([
                    'pending_days' => $pending,
                    'used_days' => $used,
                ]);
            }

            $locked->update([
                'status' => $status,
                'decided_by' => $user->id,
                'decided_at' => now(),
                'decision_note' => $note !== '' ? $note : null,
            ]);

            RequestHistory::create([
                'leave_request_id' => $locked->id,
                'actor_id' => $user->id,
                'status_from' => 'pending',
                'status_to' => $status,
                'note' => $note !== ''
                    ? $note
                    : ($status === 'approved' ? 'Approved by reviewer.' : 'Rejected by reviewer.'),
            ]);
        });

        AuditLog::create([
            'user_id' => $user->id,
            'action' => $status === 'approved' ? 'leave.approved' : 'leave.rejected',
            'description' => ucfirst($status) . " leave request #{$leaveRequest->id}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('approvals.index')->with(
            'status',
            $status === 'approved' ? 'Request approved.' : 'Request rejected.'
        );
    }
}
