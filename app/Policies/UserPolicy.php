<?php

namespace App\Policies;

use App\Models\User;

/**
 * User administration is admin-only.
 *
 * The one nuance is self-protection: an admin may edit their own name and
 * email here, but may not change their own role or delete their own account.
 * That keeps the system from ever being left without an administrator, and
 * means every role change is made by somebody other than its subject.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Changing a role is separate from editing a profile: nobody promotes or
     * demotes themselves.
     */
    public function updateRole(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->isNot($model);
    }

    /**
     * Deleting a user cascades to their student profile and academic records,
     * so it is admin-only — and never self-service.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->isNot($model);
    }
}
