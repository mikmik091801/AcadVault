<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin', 'registrar', 'faculty');
    }

    /**
     * Faculty are scoped to the courses they teach.
     */
    public function view(User $user, Course $course): bool
    {
        if ($user->hasRole('admin', 'registrar')) {
            return true;
        }

        return $user->isFaculty() && $course->faculty_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin', 'registrar');
    }

    public function update(User $user, Course $course): bool
    {
        return $user->hasRole('admin', 'registrar');
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->isAdmin();
    }
}
