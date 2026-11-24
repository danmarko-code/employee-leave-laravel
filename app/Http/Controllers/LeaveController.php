<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\AuditLog;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\RequestHistory;
use App\Models\User;
use App\Support\AdminGuard;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaveController extends Controller
{
    use AuthorizesRequests;

    /**
     * Ensure the employee has initialized leave balance records for the given year.
     */
    private function ensureBalances(User $user, int $year): void
    {
        $types = LeaveType::where('is_active', true)->get();
        foreach ($types as $type) {
            LeaveBalance::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'leave_type_id' => $type->id,
                    'year' => $year,
                ],
                [
                    'allocated_days' => $type->days_allowed,
                    'used_days' => 0,
                    'pending_days' => 0,
                ]
            );
        }
    }

    /**
     * Employee Dashboard (balances, next time off, recent requests).
     */
    public function dashboard(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $currentYear = (int) date('Y');
        $this->ensureBalances($user, $currentYear);

        $balances = LeaveBalance::where('user_id', $user->id)
            ->where('year', $currentYear)
            ->with('leaveType')
            ->whereHas('leaveType', fn ($q) => $q->where('is_active', true))
            ->get();

        $recentRequests = LeaveRequest::where('user_id', $user->id)
            ->with('leaveType')
            ->orderBy('id', 'desc')
            ->take(4)
            ->get();

        $nextLeave = LeaveRequest::where('user_id', $user->id)
            ->whereIn('status', ['approved', 'pending'])
            ->where('end_date', '>=', now()->toDateString())
            ->orderBy('start_date', 'asc')
            ->first();

        return view('employee.dashboard', compact('balances', 'recentRequests', 'nextLeave'));
    }

    /**
     * Show the signed-in employee's leave balances.
     */
    public function balances(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $currentYear = (int) date('Y');
        $this->ensureBalances($user, $currentYear);

        $balances = LeaveBalance::where('user_id', $user->id)
            ->where('year', $currentYear)
            ->with('leaveType')
            ->whereHas('leaveType', fn ($q) => $q->where('is_active', true))
            ->orderBy('id')
            ->get();

        return view('employee.balances', compact('balances', 'currentYear'));
    }

    /**
     * Show leave application form.
     */
    public function create(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $currentYear = (int) date('Y');
        $this->ensureBalances($user, $currentYear);

        $leaveTypes = LeaveType::where('is_active', true)->get()->map(function ($type) use ($user, $currentYear) {
            $bal = LeaveBalance::where('user_id', $user->id)
                ->where('leave_type_id', $type->id)
                ->where('year', $currentYear)
                ->first();
            $type->available_days = $bal ? $bal->available_days : $type->days_allowed;
            return $type;
        });

        return view('employee.apply', compact('leaveTypes'));
    }

    /**
     * Store new leave request.
     */
    public function store(StoreLeaveRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $days = $request->workingDaysCount;
        $start = $request->date('start_date');
        $year = (int) $start->format('Y');

        $attachmentPath = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $ext = $file->getClientOriginalExtension();
            $storedName = Str::random(40) . ($ext ? '.' . $ext : '');
            $attachmentPath = $file->storeAs('attachments', $storedName, 'local');
        }

        $leaveRequest = DB::transaction(function () use ($user, $request, $days, $year, $attachmentPath, $attachmentName) {
            $record = LeaveRequest::create([
                'user_id' => $user->id,
                'leave_type_id' => $request->input('leave_type_id'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'days' => $days,
                'reason' => $request->input('reason'),
                'status' => 'pending',
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
            ]);

            // Update user balance pending days
            $balance = LeaveBalance::where('user_id', $user->id)
                ->where('leave_type_id', $request->input('leave_type_id'))
                ->where('year', $year)
                ->first();

            if ($balance) {
                $balance->increment('pending_days', $days);
            }

            RequestHistory::create([
                'leave_request_id' => $record->id,
                'actor_id' => $user->id,
                'status_from' => null,
                'status_to' => 'pending',
                'note' => 'Leave application submitted for review.',
            ]);

            return $record;
        });

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'leave.created',
            'description' => "Submitted leave request #{$leaveRequest->id} for {$days} working days.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('requests')->with('status', 'Request submitted for review.');
    }

    /**
     * List current employee's leave requests with status filter & hard-capped pagination.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $status = $request->query('status', 'all');

        $query = LeaveRequest::where('user_id', $user->id)
            ->with(['leaveType', 'decidedBy']);

        if ($status !== 'all' && in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
            $query->where('status', $status);
        }

        // Hard max page size to protect against memory exhaustion
        $perPage = min(max((int) $request->query('per_page', 10), 1), 25);
        $requests = $query->orderBy('id', 'desc')->paginate($perPage)->withQueryString();

        return view('employee.requests', compact('requests', 'status'));
    }

    /**
     * View request details / drawer info (strictly authorized against IDOR).
     */
    public function show(Request $request, LeaveRequest $leaveRequest): View|JsonResponse
    {
        // Enforce Policy: aborts with 403 Forbidden if another user tries to open it
        $this->authorize('view', $leaveRequest);

        $leaveRequest->load(['leaveType', 'decidedBy', 'histories.actor', 'user']);

        $waitingForAdmin = $leaveRequest->status === 'pending'
            && $leaveRequest->user->isAdmin()
            && ! AdminGuard::hasOtherActiveAdmin($leaveRequest->user);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'id' => $leaveRequest->id,
                'status' => $leaveRequest->status,
                'days' => $leaveRequest->days,
                'type_name' => $leaveRequest->leaveType->name,
                'start_date' => $leaveRequest->start_date->format('M d, Y'),
                'end_date' => $leaveRequest->end_date->format('M d, Y'),
                'reason' => $leaveRequest->reason,
                'has_attachment' => ! empty($leaveRequest->attachment_path),
                'attachment_name' => $leaveRequest->attachment_name,
                'download_url' => route('requests.attachment', $leaveRequest),
                'can_cancel' => Auth::user()->can('cancel', $leaveRequest),
                'histories' => $leaveRequest->histories->map(fn ($h) => [
                    'status_to' => ucfirst($h->status_to),
                    'date' => $h->created_at->format('M d, Y g:ia'),
                    'actor_name' => $h->actor ? $h->actor->name : 'System',
                    'note' => $h->note,
                ]),
            ]);
        }

        return view('employee.show', compact('leaveRequest', 'waitingForAdmin'));
    }

    /**
     * Cancel pending leave request.
     */
    public function cancel(Request $request, LeaveRequest $leaveRequest): RedirectResponse|JsonResponse
    {
        // Enforce Policy: must be owner AND status must be pending
        $this->authorize('cancel', $leaveRequest);

        /** @var User $user */
        $user = Auth::user();

        DB::transaction(function () use ($leaveRequest, $user) {
            $year = (int) $leaveRequest->start_date->format('Y');

            // Restore pending balance
            $balance = LeaveBalance::where('user_id', $leaveRequest->user_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id)
                ->where('year', $year)
                ->first();

            if ($balance) {
                $balance->decrement('pending_days', min((float) $balance->pending_days, (float) $leaveRequest->days));
            }

            $leaveRequest->update([
                'status' => 'cancelled',
            ]);

            RequestHistory::create([
                'leave_request_id' => $leaveRequest->id,
                'actor_id' => $user->id,
                'status_from' => 'pending',
                'status_to' => 'cancelled',
                'note' => 'Request cancelled by employee.',
            ]);
        });

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'leave.cancelled',
            'description' => "Cancelled leave request #{$leaveRequest->id}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => 'Request cancelled successfully.']);
        }

        return redirect()->route('requests')->with('status', 'Request cancelled successfully.');
    }

    /**
     * Secure file download behind Policy authorization.
     */
    public function downloadAttachment(LeaveRequest $leaveRequest): StreamedResponse
    {
        $this->authorize('downloadAttachment', $leaveRequest);

        if (! Storage::disk('local')->exists($leaveRequest->attachment_path)) {
            abort(404, 'Attachment file not found.');
        }

        return Storage::disk('local')->download(
            $leaveRequest->attachment_path,
            $leaveRequest->attachment_name ?: 'attachment'
        );
    }

    /**
     * Show employee profile.
     */
    public function profile(): View
    {
        $user = Auth::user()->load(['department', 'position', 'roleRelation']);
        return view('employee.profile', compact('user'));
    }

    /**
     * Update employee phone number.
     */
    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $user->phone = $request->input('phone');
        $user->save();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'profile.updated',
            'description' => "User updated phone number to [{$user->phone}].",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('profile')->with('status', 'Profile phone number updated successfully.');
    }
}
