<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\DriverAssignment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DriverAssignmentPolicy
{
    /**
     * Answer (accept/reject) an offer: the assigned driver while the offer is
     * still open (FR-015/FR-016).
     */
    public function respond(User $user, DriverAssignment $assignment): Response
    {
        if ($assignment->driver_user_id !== $user->id) {
            return Response::denyAsNotFound();
        }

        if (! $assignment->isOpen()) {
            return Response::deny('This offer is no longer open.');
        }

        return Response::allow();
    }

    /**
     * Progress an accepted delivery (pickup/deliver): the assigned driver.
     */
    public function progress(User $user, DriverAssignment $assignment): Response
    {
        if ($assignment->driver_user_id !== $user->id) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }

    /**
     * Manually expire an offer: administrators only (FR-016).
     */
    public function expire(User $user, DriverAssignment $assignment): bool
    {
        return $user->role === UserRole::Admin;
    }
}
