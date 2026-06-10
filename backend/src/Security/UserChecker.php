<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * UserChecker - Validates user account status before authentication
 * 
 * Prevents deleted users from authenticating.
 * This is an additional security layer that complements the soft delete mechanism.
 */
class UserChecker implements UserCheckerInterface
{
    /**
     * Called before authentication (e.g., checking if account exists)
     */
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Check if user is soft deleted
        if ($user->isDeleted()) {
            throw new CustomUserMessageAccountStatusException(
                'This account has been deleted and cannot be accessed.'
            );
        }

        // Check if user account is active
        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException(
                'This account has been deactivated. Please contact an administrator.'
            );
        }
    }

    /**
     * Called after authentication (e.g., checking credentials)
     */
    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Additional post-authentication checks can be added here if needed
        // For now, we only do pre-authentication checks
    }
}
