<?php

namespace Ludelix\Auth\Core\TwoFactor;

use Ludelix\Interface\Auth\UserInterface;

/**
 * TwoFactorService - Handles two-factor authentication operations
 * 
 * This service manages the generation, verification, and management of
 * two-factor authentication for users.
 * 
 * @package Ludelix\Auth\Core\TwoFactor
 */
class TwoFactorService
{
    /**
     * Generate a two-factor secret
     *
     * @return string The generated secret
     */
    public function generateSecret(): string
    {
        return bin2hex(random_bytes(10)); // Simple example
    }

    /**
     * Verify a two-factor authentication code
     *
     * @param UserInterface $user The user to verify the code for
     * @param string $code The code to verify
     * @return bool True if the code is valid, false otherwise
     */
    public function verifyCode(UserInterface $user, string $code): bool
    {
        // 2FA code verification logic (example: TOTP)
        // Real implementation depends on chosen algorithm
        return true;
    }

    /**
     * Check if two-factor authentication is enabled for a user
     *
     * @param UserInterface $user The user to check
     * @return bool True if 2FA is enabled, false otherwise
     */
    public function isEnabled(UserInterface $user): bool
    {
        return method_exists($user, 'isTwoFactorEnabled') && $user->isTwoFactorEnabled();
    }

    /**
     * Enable two-factor authentication for a user
     *
     * @param UserInterface $user The user to enable 2FA for
     * @param string $secret The 2FA secret
     * @return void
     */
    public function enable(UserInterface $user, string $secret): void
    {
        if (method_exists($user, 'enableTwoFactor')) {
            $user->enableTwoFactor($secret);
        }
    }

    /**
     * Disable two-factor authentication for a user
     *
     * @param UserInterface $user The user to disable 2FA for
     * @return void
     */
    public function disable(UserInterface $user): void
    {
        if (method_exists($user, 'disableTwoFactor')) {
            $user->disableTwoFactor();
        }
    }
}