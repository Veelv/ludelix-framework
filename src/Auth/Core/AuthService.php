<?php

namespace Ludelix\Auth\Core;

use Ludelix\Interface\Auth\UserProviderInterface;
use Ludelix\Interface\Auth\GuardInterface;
use Ludelix\Interface\Auth\UserInterface;

/**
 * AuthService - Provides high-level authentication operations
 * 
 * This service offers a simplified interface for common authentication operations
 * such as login, logout, and user checking, abstracting the underlying guard and provider.
 * 
 * @package Ludelix\Auth\Core
 */
class AuthService
{
    /**
     * The authentication guard
     *
     * @var GuardInterface
     */
    protected GuardInterface $guard;

    /**
     * The user provider
     *
     * @var UserProviderInterface
     */
    protected UserProviderInterface $provider;

    /**
     * AuthService constructor.
     *
     * @param GuardInterface $guard The authentication guard
     * @param UserProviderInterface $provider The user provider
     */
    public function __construct(GuardInterface $guard, UserProviderInterface $provider)
    {
        $this->guard = $guard;
        $this->provider = $provider;
    }

    /**
     * Get the currently authenticated user
     *
     * @return UserInterface|null
     */
    public function user(): ?UserInterface
    {
        return $this->guard->user();
    }

    /**
     * Check if a user is currently authenticated
     *
     * @return bool
     */
    public function check(): bool
    {
        return $this->guard->check();
    }

    /**
     * Attempt to authenticate a user with the given credentials
     *
     * @param array $credentials The user credentials
     * @return bool
     */
    public function login(array $credentials): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);
        if ($user && $this->provider->validateCredentials($user, $credentials)) {
            $this->guard->login($user);
            return true;
        }
        return false;
    }

    /**
     * Log out the currently authenticated user
     *
     * @return void
     */
    public function logout(): void
    {
        $this->guard->logout();
    }

    /**
     * Get the authentication guard
     *
     * @return GuardInterface
     */
    public function guard(): GuardInterface
    {
        return $this->guard;
    }

    /**
     * Set the user provider
     *
     * @param UserProviderInterface $provider The user provider
     * @return void
     */
    public function setProvider(UserProviderInterface $provider): void
    {
        $this->provider = $provider;
    }
}