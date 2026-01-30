<?php

namespace Ludelix\Auth\Core;

use Ludelix\Interface\Auth\GuardInterface;
use Ludelix\Interface\Auth\UserInterface;
use Ludelix\Interface\Auth\UserProviderInterface;

/**
 * TokenGuard - Token-based authentication guard
 * 
 * This guard implements authentication using API tokens for stateless authentication,
 * typically used for API endpoints.
 * 
 * @package Ludelix\Auth\Core
 */
class TokenGuard implements GuardInterface
{
    /**
     * The authenticated user
     *
     * @var UserInterface|null
     */
    protected ?UserInterface $user = null;

    /**
     * The user provider
     *
     * @var UserProviderInterface
     */
    protected UserProviderInterface $provider;

    /**
     * The authentication token
     *
     * @var string|null
     */
    protected ?string $token = null;

    /**
     * TokenGuard constructor.
     *
     * @param UserProviderInterface $provider The user provider
     * @param string|null $token The authentication token
     */
    public function __construct(UserProviderInterface $provider, ?string $token = null)
    {
        $this->provider = $provider;
        $this->token = $token ?? $this->resolveToken();
        $this->loadUserFromToken();
    }

    /**
     * Resolve the authentication token from the request
     *
     * @return string|null
     */
    protected function resolveToken(): ?string
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            if (preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                return trim($matches[1]);
            }
        }
        if (isset($_GET['api_token'])) {
            return $_GET['api_token'];
        }
        return null;
    }

    /**
     * Load the user from the authentication token
     *
     * @return void
     */
    protected function loadUserFromToken(): void
    {
        if ($this->token) {
            $user = $this->provider->retrieveByCredentials(['api_token' => $this->token]);
            if ($user) {
                $this->user = $user;
            }
        }
    }

    /**
     * Get the currently authenticated user
     *
     * @return UserInterface|null
     */
    public function user(): ?UserInterface
    {
        return $this->user;
    }

    /**
     * Check if a user is currently authenticated
     *
     * @return bool
     */
    public function check(): bool
    {
        return $this->user !== null;
    }

    /**
     * Authenticate a user (not implemented for stateless API)
     *
     * @param UserInterface $user The user to authenticate
     * @return void
     */
    public function login(UserInterface $user): void
    {
        // Not implemented for stateless API
    }

    /**
     * Log out the current user (not implemented for stateless API)
     *
     * @return void
     */
    public function logout(): void
    {
        // Not implemented for stateless API
    }

    /**
     * Set the authentication token
     *
     * @param string $token The authentication token
     * @return void
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
        $this->loadUserFromToken();
    }

    /**
     * Get the authentication token
     *
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->token;
    }
}