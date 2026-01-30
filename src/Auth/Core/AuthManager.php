<?php

namespace Ludelix\Auth\Core;

use Ludelix\Interface\Auth\GuardInterface;
use Ludelix\Interface\Auth\UserProviderInterface;

/**
 * AuthManager - Manages authentication guards and providers
 * 
 * This class serves as the central manager for authentication guards and user providers,
 * allowing for flexible authentication configurations.
 * 
 * @package Ludelix\Auth\Core
 */
class AuthManager
{
    /**
     * Registered authentication guards
     *
     * @var array
     */
    protected array $guards = [];

    /**
     * Registered user providers
     *
     * @var array
     */
    protected array $providers = [];

    /**
     * The default guard name
     *
     * @var string
     */
    protected string $defaultGuard = 'session';

    /**
     * Register an authentication guard
     *
     * @param string $name The guard name
     * @param GuardInterface $guard The guard instance
     * @return void
     */
    public function addGuard(string $name, GuardInterface $guard): void
    {
        $this->guards[$name] = $guard;
    }

    /**
     * Get an authentication guard by name
     *
     * @param string|null $name The guard name (uses default if null)
     * @return GuardInterface|null
     */
    public function getGuard(string $name = null): ?GuardInterface
    {
        $name = $name ?: $this->defaultGuard;
        return $this->guards[$name] ?? null;
    }

    /**
     * Set the default guard name
     *
     * @param string $name The guard name
     * @return void
     */
    public function setDefaultGuard(string $name): void
    {
        $this->defaultGuard = $name;
    }

    /**
     * Register a user provider
     *
     * @param string $name The provider name
     * @param UserProviderInterface $provider The provider instance
     * @return void
     */
    public function addProvider(string $name, UserProviderInterface $provider): void
    {
        $this->providers[$name] = $provider;
    }

    /**
     * Get a user provider by name
     *
     * @param string $name The provider name
     * @return UserProviderInterface|null
     */
    public function getProvider(string $name): ?UserProviderInterface
    {
        return $this->providers[$name] ?? null;
    }
}