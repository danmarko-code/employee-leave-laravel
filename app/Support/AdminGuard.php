<?php

namespace App\Support;

use App\Models\User;

/**
 * Keeps the system from losing its final active administrator account.
 */
class AdminGuard
{
    public static function activeAdminCount(): int
    {
        return User::where('is_active', true)
            ->whereHas('roleRelation', fn ($query) => $query->where('name', 'admin'))
            ->count();
    }

    public static function isLastAdmin(User $user): bool
    {
        return $user->isAdmin() && $user->is_active && self::activeAdminCount() <= 1;
    }

    public static function hasOtherActiveAdmin(User $user): bool
    {
        return User::where('id', '!=', $user->id)
            ->where('is_active', true)
            ->whereHas('roleRelation', fn ($query) => $query->where('name', 'admin'))
            ->exists();
    }
}
