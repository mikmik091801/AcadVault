<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    /**
     * Only staff browse the student directory. Faculty reach their students
     * through their own courses instead — see the class list on courses.show.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin', 'registrar');
    }

    /**
     * Staff see anyone; a student may see only their own profile.
     */
    public function view(User $user, Student $student): bool
    {
        if ($user->hasRole('admin', 'registrar')) {
            return true;
        }

        return $user->isStudent() && $student->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin', 'registrar');
    }

    public function update(User $user, Student $student): bool
    {
        return $user->hasRole('admin', 'registrar');
    }

    /**
     * Deleting a student removes their records too, so admin only.
     */
    public function delete(User $user, Student $student): bool
    {
        return $user->isAdmin();
    }
}
