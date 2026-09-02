<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

/**
 * Enrolling is the one place a student writes to the system.
 *
 * They may add a class for themselves and ask to drop one, but the drop only
 * takes effect when a registrar approves it — so `requestDrop` belongs to the
 * student and `review` belongs to staff, and neither can do the other's half.
 */
class EnrollmentPolicy
{
    /**
     * Students see their own classes; staff see the whole register.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin', 'registrar', 'student');
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        if ($user->hasRole('admin', 'registrar')) {
            return true;
        }

        return $this->owns($user, $enrollment);
    }

    /**
     * Only a student enrolls, and only into their own profile — the controller
     * takes the student from the session, never from the request.
     */
    public function create(User $user): bool
    {
        return $user->isStudent() && $user->student()->exists();
    }

    /**
     * A student may ask to drop a class they currently hold. Asking twice is
     * blocked here rather than in the controller so the button and the route
     * agree.
     */
    public function requestDrop(User $user, Enrollment $enrollment): bool
    {
        return $this->owns($user, $enrollment) && $enrollment->isEnrolled();
    }

    /**
     * Approving or declining a drop is the registrar's decision.
     */
    public function review(User $user, Enrollment $enrollment): bool
    {
        return $user->hasRole('admin', 'registrar') && $enrollment->isDropPending();
    }

    private function owns(User $user, Enrollment $enrollment): bool
    {
        return $user->isStudent()
            && $enrollment->student?->user_id === $user->id;
    }
}
