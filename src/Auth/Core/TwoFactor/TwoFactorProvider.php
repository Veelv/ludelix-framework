<?php

namespace Ludelix\Auth\Core\TwoFactor;

use Ludelix\Interface\Auth\UserInterface;

/**
 * TwoFactorProvider - Provides two-factor authentication functionality
 * 
 * This class handles the retrieval and management of two-factor authentication
 * secrets for users.
 * 
 * @package Ludelix\Auth\Core\TwoFactor
 */
class TwoFactorProvider
{
    /**
     * Get the two-factor secret for a user
     *
     * @param UserInterface $user The user to get the secret for
     * @return string|null The two-factor secret or null if not set
     */
    public function getSecret(UserInterface $user): ?string
    {
        return method_exists($user, 'getTwoFactorSecret') ? $user->getTwoFactorSecret() : null;
    }

    /**
     * Set the two-factor secret for a user
     *
     * @param UserInterface $user The user to set the secret for
     * @param string $secret The two-factor secret
     * @return void
     */
    public function setSecret(UserInterface $user, string $secret): void
    {
        if (method_exists($user, 'setTwoFactorSecret')) {
            $user->setTwoFactorSecret($secret);
        }
    }

    /**
     * Clear the two-factor secret for a user
     *
     * @param UserInterface $user The user to clear the secret for
     * @return void
     */
    public function clearSecret(UserInterface $user): void
    {
        if (method_exists($user, 'clearTwoFactorSecret')) {
            $user->clearTwoFactorSecret();
        }
    }
}