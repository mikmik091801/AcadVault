<?php

namespace App\Policies;

use App\Models\EnrollmentDocument;
use App\Models\User;

class EnrollmentDocumentPolicy
{
    /**
     * A student issues their own certificate; staff can pull one for anybody.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin', 'registrar')
            || ($user->isStudent() && $user->student()->exists());
    }

    public function view(User $user, EnrollmentDocument $document): bool
    {
        if ($user->hasRole('admin', 'registrar')) {
            return true;
        }

        return $user->isStudent() && $document->student?->user_id === $user->id;
    }

    public function download(User $user, EnrollmentDocument $document): bool
    {
        return $this->view($user, $document);
    }
}
