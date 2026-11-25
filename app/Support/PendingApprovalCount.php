<?php

namespace App\Support;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;

class PendingApprovalCount
{
    public function __construct(private Request $request) {}

    /**
     * Count pending requests submitted by other employees once per request.
     *
     * Administrators must not approve their own leave, so their requests are
     * excluded from both the sidebar badge and the overview stat card.
     */
    public function for(User $user): int
    {
        $key = 'pending-approvals:'.$user->id;

        if (! $this->request->attributes->has($key)) {
            $this->request->attributes->set($key, LeaveRequest::where('status', 'pending')
                ->where('user_id', '!=', $user->id)
                ->count());
        }

        return (int) $this->request->attributes->get($key);
    }
}
