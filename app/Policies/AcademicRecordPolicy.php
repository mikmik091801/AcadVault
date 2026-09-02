<?php

namespace App\Policies;

use App\Models\AcademicRecord;
use App\Models\User;

class AcademicRecordPolicy
{
    /**
     * Every role has a records screen; the controller scopes the query so
     * faculty see only their own courses and students only their own records.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin', 'registrar', 'faculty', 'student');
    }

    public function view(User $user, AcademicRecord $record): bool
    {
        if ($user->hasRole('admin', 'registrar')) {
            return true;
        }

        if ($user->isFaculty()) {
            return $record->course?->faculty_id === $user->id;
        }

        if ($user->isStudent()) {
            return $record->student?->user_id === $user->id;
        }

        return false;
    }

    /**
     * Registrars manage records outright; faculty may enter grades, but the
     * controller restricts the course list to the ones they teach.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin', 'registrar', 'faculty');
    }

    public function update(User $user, AcademicRecord $record): bool
    {
        if ($user->hasRole('admin', 'registrar')) {
            return true;
        }

        return $user->isFaculty() && $record->course?->faculty_id === $user->id;
    }

    public function delete(User $user, AcademicRecord $record): bool
    {
        return $user->hasRole('admin', 'registrar');
    }

    /**
     * Registrars (and admins) produce the signed PDF exports.
     */
    public function export(User $user, AcademicRecord $record): bool
    {
        return $user->hasRole('admin', 'registrar');
    }
}
