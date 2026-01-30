<?php

namespace Ludelix\Auth\Core;

use Ludelix\Interface\Auth\GuardInterface;
use Ludelix\Interface\Auth\UserInterface;
use Ludelix\Interface\Auth\UserProviderInterface;
use Ludelix\Core\Security\JwtService;

/**
 * JwtGuard - Stateless JWT authentication guard.
 * 
 * Authenticates users using JWT tokens provided in the Authorization header.
 * 
 * @package Ludelix\Auth\Core
 */
class JwtGuard implements GuardInterface
{
    protected ?UserInterface $user = null;
    protected UserProviderInterface $provider;
    protected JwtService $jwtService;

    /**
     * @param UserProviderInterface $provider   User provider to retrieve user entities.
     * @param JwtService            $jwtService JWT service for token validation.
     */
    public function __construct(UserProviderInterface $provider, JwtService $jwtService)
    {
        $this->provider = $provider;
        $this->jwtService = $jwtService;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return UserInterface|null
     */
    public function user(): ?UserInterface
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $token = $this->resolveToken();
        if (!$token) {
            return null;
        }

        $payload = $this->jwtService->validate($token);
        if (!$payload || !isset($payload['sub'])) {
            return null;
        }

        // 'sub' is the standard JWT claim for subject (User ID in our case)
        $this->user = $this->provider->retrieveById($payload['sub']);

        return $this->user;
    }

    /**
     * Check if a user is currently authenticated.
     *
     * @return bool
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Resolve token from Authorizaton header.
     *
     * @return string|null
     */
    protected function resolveToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;

        if ($header && preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Login - Not used for stateless JWT (use generateToken instead).
     */
    public function login(UserInterface $user): void
    {
        $this->user = $user;
    }

    /**
     * Logout - Stateless tokens cannot be logged out server-side without a blacklist.
     */
    public function logout(): void
    {
        $this->user = null;
    }

    /**
     * Generate a new token for the given user.
     *
     * @param UserInterface $user
     * @param int           $expiry
     * @return string
     */
    public function generateToken(UserInterface $user, int $expiry = 3600): string
    {
        return $this->jwtService->generate([
            'sub' => $user->getId(),
            'email' => $user->getEmail()
        ], $expiry);
    }
}
