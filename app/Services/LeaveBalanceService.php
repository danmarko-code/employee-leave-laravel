<?php

namespace App\Services;

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;

class LeaveBalanceService
{
    /**
     * Ensure leave balances exist for the current year, all active users, and all active leave types.
     * Existing records are left untouched.
     */
    public function ensureActiveBalances(?int $year = null): void
    {
        $year = $year ?? (int) date('Y');
        $users = User::where('is_active', true)->get();
        $types = LeaveType::where('is_active', true)->get();

        foreach ($users as $user) {
            $this->ensureBalancesForUserAndTypes($user, $types, $year);
        }
    }

    /**
     * Ensure leave balances exist for a specific user and all active leave types for the given year.
     * Existing records are left untouched.
     */
    public function ensureBalancesForUser(User $user, ?int $year = null): void
    {
        $year = $year ?? (int) date('Y');
        $types = LeaveType::where('is_active', true)->get();

        $this->ensureBalancesForUserAndTypes($user, $types, $year);
    }

    /**
     * @param  iterable<LeaveType>  $types
     */
    private function ensureBalancesForUserAndTypes(User $user, iterable $types, int $year): void
    {
        foreach ($types as $type) {
            $allocatedDays = $type->grant_mode === 'admin' ? 0 : $type->days_allowed;

            LeaveBalance::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'leave_type_id' => $type->id,
                    'year' => $year,
                ],
                [
                    'allocated_days' => $allocatedDays,
                    'used_days' => 0,
                    'pending_days' => 0,
                ]
            );
        }
    }
}
