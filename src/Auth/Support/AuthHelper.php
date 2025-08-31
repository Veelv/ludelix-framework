<?php

namespace Ludelix\Auth\Support;

use Ludelix\Bridge\Bridge;
use Ludelix\Interface\Auth\UserInterface;

/**
 * AuthHelper - Provides helper functions for authentication
 * 
 * This class provides static methods to easily access common authentication
 * functionality throughout the application.
 * 
 * @package Ludelix\Auth\Support
 */
class AuthHelper
{
    /**
     * Get the authentication service instance
     *
     * @return \Ludelix\Auth\Core\AuthService
     */
    public static function auth(): \Ludelix\Auth\Core\AuthService
    {
        return Bridge::auth();
    }

    /**
     * Get the currently authenticated user
     *
     * @return UserInterface|null
     */
    public static function user(): ?UserInterface
    {
        return self::auth()->user();
    }

    /**
     * Check if a user is currently authenticated
     *
     * @return bool
     */
    public static function check(): bool
    {
        return self::auth()->check();
    }

    /**
     * Check if the current user is a guest (not authenticated)
     *
     * @return bool
     */
    public static function guest(): bool
    {
        return !self::auth()->check();
    }
}