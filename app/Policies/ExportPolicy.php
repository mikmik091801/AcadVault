<?php

namespace App\Policies;

use App\Models\Export;
use App\Models\User;

class ExportPolicy
{
    /**
     * Staff see all exports; a student sees the exports of their own records.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin', 'registrar', 'student');
    }

    public function view(User $user, Export $export): bool
    {
        if ($user->hasRole('admin', 'registrar')) {
            return true;
        }

        return $user->isStudent()
            && $export->academicRecord?->student?->user_id === $user->id;
    }

    /**
     * Downloading re-renders the document, so it follows the same rule.
     */
    public function download(User $user, Export $export): bool
    {
        return $this->view($user, $export);
    }

    public function delete(User $user, Export $export): bool
    {
        return $user->isAdmin();
    }
}
