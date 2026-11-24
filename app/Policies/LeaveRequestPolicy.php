<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Support\AdminGuard;

class LeaveRequestPolicy
{
    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        return (int) $user->id === (int) $leaveRequest->user_id || $user->isAdmin();
    }

    public function cancel(User $user, LeaveRequest $leaveRequest): bool
    {
        return (int) $user->id === (int) $leaveRequest->user_id && $leaveRequest->status === 'pending';
    }

    public function viewAnyApprovals(User $user): bool
    {
        return $user->isAdmin();
    }

    public function review(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->canDecide($user, $leaveRequest, null);
    }

    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->canDecide($user, $leaveRequest, 'approved');
    }

    public function reject(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->canDecide($user, $leaveRequest, 'rejected');
    }

    public function downloadAttachment(User $user, LeaveRequest $leaveRequest): bool
    {
        return ((int) $user->id === (int) $leaveRequest->user_id || $user->isAdmin())
            && ! empty($leaveRequest->attachment_path);
    }

    /**
     * Administrators decide every request except their own.
     */
    private function canDecide(User $user, LeaveRequest $leaveRequest, ?string $target): bool
    {
        if (! $user->isAdmin() || (int) $user->id === (int) $leaveRequest->user_id) {
            return false;
        }

        if ($leaveRequest->user?->isAdmin() && ! AdminGuard::hasOtherActiveAdmin($leaveRequest->user)) {
            return false;
        }

        return $target === null || $leaveRequest->canTransitionTo($target);
    }
}
