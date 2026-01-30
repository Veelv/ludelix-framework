<?php

namespace Ludelix\Auth\Services;

use Ludelix\Interface\Auth\UserProviderInterface;
use Ludelix\Auth\Core\AuthService;
use Ludelix\Interface\Auth\UserInterface;
use Ludelix\Auth\Core\Event\AuthEventDispatcher;
use Ludelix\Auth\Core\Event\Events\LoginEvent;
use Ludelix\Auth\Core\Event\Events\LogoutEvent;
use Ludelix\Auth\Core\Event\Events\RegisterEvent;

/**
 * AuthAccountService - Handles account-related authentication operations
 * 
 * This service provides high-level account operations such as login, logout, and registration
 * with event dispatching capabilities.
 * 
 * @package Ludelix\Auth\Services
 */
class AuthAccountService
{
    /**
     * The user provider
     *
     * @var UserProviderInterface
     */
    protected UserProviderInterface $provider;

    /**
     * The authentication service
     *
     * @var AuthService
     */
    protected AuthService $auth;

    /**
     * The event dispatcher
     *
     * @var AuthEventDispatcher
     */
    protected AuthEventDispatcher $events;

    /**
     * AuthAccountService constructor.
     *
     * @param UserProviderInterface $provider The user provider
     * @param AuthService $auth The authentication service
     * @param AuthEventDispatcher $events The event dispatcher
     */
    public function __construct(UserProviderInterface $provider, AuthService $auth, AuthEventDispatcher $events)
    {
        $this->provider = $provider;
        $this->auth = $auth;
        $this->events = $events;
    }

    /**
     * Attempt to authenticate a user with the given credentials.
     * Dispatches LoginEvent on success.
     *
     * @param array $credentials
     * @return UserInterface|null Authenticated user or null on failure
     */
    public function login(array $credentials): ?UserInterface
    {
        $user = $this->provider->retrieveByCredentials($credentials);
        if ($user && $this->provider->validateCredentials($user, $credentials)) {
            $this->auth->guard()->login($user);
            $this->events->dispatch(LoginEvent::class, new LoginEvent($user));
            return $user;
        }
        return null;
    }

    /**
     * Log out the currently authenticated user.
     * Dispatches LogoutEvent if a user was logged in.
     *
     * @return void
     */
    public function logout(): void
    {
        $user = $this->auth->user();
        if ($user) {
            $this->auth->guard()->logout();
            $this->events->dispatch(LogoutEvent::class, new LogoutEvent($user));
        }
    }

    /**
     * Register a new user with the given data.
     * Dispatches RegisterEvent on success.
     *
     * @param array $data The user data
     * @return UserInterface|null The registered user or null on failure
     */
    public function register(array $data): ?UserInterface
    {
        // Check if user already exists
        if ($this->provider->retrieveByCredentials(['email' => $data['email']])) {
            return null;
        }

        // Create the user
        $user = $this->provider->createUser($data);
        if ($user) {
            $this->events->dispatch(RegisterEvent::class, new RegisterEvent($user));
        }

        return $user;
    }

    /**
     * Send a password reset link to the user's email.
     *
     * @param string $email The user's email
     * @return bool True if successful, false otherwise
     */
    public function sendPasswordResetLink(string $email): bool
    {
        $user = $this->provider->retrieveByCredentials(['email' => $email]);
        if (!$user) {
            return false;
        }

        // Generate reset token and send email (implementation depends on your email system)
        $token = bin2hex(random_bytes(32));
        // Save token to database
        // Send email with reset link
        
        return true;
    }

    /**
     * Reset a user's password with the given token.
     *
     * @param string $token The reset token
     * @param string $password The new password
     * @return bool True if successful, false otherwise
     */
    public function resetPassword(string $token, string $password): bool
    {
        // Find user by token (implementation depends on your token storage)
        $user = null; // $this->provider->retrieveByResetToken($token);
        if (!$user) {
            return false;
        }

        // Update password
        $this->provider->updatePassword($user, $password);
        
        // Clear reset token
        // $this->provider->clearResetToken($user);
        
        return true;
    }

    /**
     * Get the user provider
     *
     * @return UserProviderInterface
     */
    public function getProvider(): UserProviderInterface
    {
        return $this->provider;
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