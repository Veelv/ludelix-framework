<?php

namespace Ludelix\Auth\Core;

use Ludelix\Interface\Auth\UserInterface;

/**
 * RememberMeService - Handles "Remember Me" functionality
 * 
 * This service manages persistent authentication tokens that allow users
 * to remain logged in across sessions.
 * 
 * @package Ludelix\Auth\Core
 */
class RememberMeService
{
    /**
     * The cookie name for the remember me token
     *
     * @var string
     */
    protected string $cookieName = 'remember_me_token';

    /**
     * The token expiration time (30 days)
     *
     * @var int
     */
    protected int $expire = 2592000; // 30 days

    /**
     * Create a remember me token for a user
     *
     * @param UserInterface $user The user to create a token for
     * @return string The generated token
     */
    public function createToken(UserInterface $user): string
    {
        $token = bin2hex(random_bytes(32));
        setcookie($this->cookieName, $token, time() + $this->expire, '/', '', true, true);
        // Here you should persist the token in the database linked to the user
        return $token;
    }

    /**
     * Validate a remember me token and retrieve the associated user
     *
     * @param string $token The token to validate
     * @return UserInterface|null The user if token is valid, null otherwise
     */
    public function validateToken(string $token): ?UserInterface
    {
        // Search for user by persisted token
        // Example: $user = ...
        return null; // Implementation depends on repository
    }

    /**
     * Remove the remember me token
     *
     * @return void
     */
    public function forgetToken(): void
    {
        setcookie($this->cookieName, '', time() - 3600, '/', '', true, true);
        // Remove token from database
    }
}