<?php

namespace App\Policies;

use App\Models\User;

/**
 * Admin-only abilities for management screens.
 * Registered for each managed model in AppServiceProvider.
 */
class AdminPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, mixed $model = null): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, mixed $model): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, mixed $model): bool
    {
        return $user->isAdmin();
    }

    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Administrators may reset another account's password, never their own.
     */
    public function resetTemporaryPassword(User $user, User $target): bool
    {
        return $user->isAdmin() && $user->id !== $target->id;
    }
}
