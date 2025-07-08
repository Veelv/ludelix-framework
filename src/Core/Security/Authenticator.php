<?php

namespace Ludelix\Core\Security;

use Ludelix\Security\Hashing\BcryptHasher;

/**
 * Authenticator
 * 
 * Handles user authentication
 */
class Authenticator
{
    protected BcryptHasher $hasher;
    protected array $config;
    protected ?array $user = null;

    public function __construct(BcryptHasher $hasher, array $config = [])
    {
        $this->hasher = $hasher;
        $this->config = array_merge([
            'session_key' => 'auth_user',
            'remember_key' => 'remember_token',
            'max_attempts' => 5,
            'lockout_time' => 900
        ], $config);
    }

    /**
     * Attempt login
     */
    public function attempt(array $credentials, bool $remember = false): bool
    {
        if ($this->isLocked($credentials)) {
            return false;
        }

        $user = $this->retrieveByCredentials($credentials);
        
        if (!$user || !$this->validateCredentials($user, $credentials)) {
            $this->recordFailedAttempt($credentials);
            return false;
        }

        $this->login($user, $remember);
        $this->clearFailedAttempts($credentials);
        
        return true;
    }

    /**
     * Login user
     */
    public function login(array $user, bool $remember = false): void
    {
        $this->user = $user;
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION[$this->config['session_key']] = $user['id'];
        }

        if ($remember) {
            $this->setRememberToken($user);
        }
    }

    /**
     * Logout user
     */
    public function logout(): void
    {
        $this->user = null;
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION[$this->config['session_key']]);
        }

        $this->clearRememberToken();
    }

    /**
     * Get authenticated user
     */
    public function user(): ?array
    {
        if ($this->user) {
            return $this->user;
        }

        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION[$this->config['session_key']])) {
            $this->user = $this->retrieveById($_SESSION[$this->config['session_key']]);
        }

        return $this->user;
    }

    /**
     * Check if authenticated
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Validate credentials
     */
    protected function validateCredentials(array $user, array $credentials): bool
    {
        return $this->hasher->verify($credentials['password'], $user['password']);
    }

    /**
     * Retrieve user by credentials (mock)
     */
    protected function retrieveByCredentials(array $credentials): ?array
    {
        $users = [
            ['id' => 1, 'email' => 'admin@example.com', 'password' => '$2y$12$example_hash'],
            ['id' => 2, 'email' => 'user@example.com', 'password' => '$2y$12$example_hash2']
        ];

        foreach ($users as $user) {
            if ($user['email'] === $credentials['email']) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Retrieve user by ID (mock)
     */
    protected function retrieveById(int $id): ?array
    {
        $users = [
            1 => ['id' => 1, 'email' => 'admin@example.com', 'name' => 'Admin'],
            2 => ['id' => 2, 'email' => 'user@example.com', 'name' => 'User']
        ];

        return $users[$id] ?? null;
    }

    /**
     * Set remember token
     */
    protected function setRememberToken(array $user): void
    {
        $token = $this->hasher->generateToken();
        setcookie($this->config['remember_key'], $token, time() + (30 * 24 * 60 * 60));
    }

    /**
     * Clear remember token
     */
    protected function clearRememberToken(): void
    {
        setcookie($this->config['remember_key'], '', time() - 3600);
    }

    /**
     * Record failed attempt
     */
    protected function recordFailedAttempt(array $credentials): void
    {
        $key = 'failed_attempts_' . ($credentials['email'] ?? 'unknown');
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION[$key] = ($_SESSION[$key] ?? 0) + 1;
            $_SESSION[$key . '_time'] = time();
        }
    }

    /**
     * Clear failed attempts
     */
    protected function clearFailedAttempts(array $credentials): void
    {
        $key = 'failed_attempts_' . ($credentials['email'] ?? 'unknown');
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION[$key], $_SESSION[$key . '_time']);
        }
    }

    /**
     * Get user ID
     */
    public function id(): ?int
    {
        $user = $this->user();
        return $user['id'] ?? null;
    }

    /**
     * Check if account is locked
     */
    public function isLocked(array $credentials): bool
    {
        $key = 'failed_attempts_' . ($credentials['email'] ?? 'unknown');
        
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $attempts = $_SESSION[$key] ?? 0;
        $lastAttempt = $_SESSION[$key . '_time'] ?? 0;

        if ($attempts >= $this->config['max_attempts']) {
            return (time() - $lastAttempt) < $this->config['lockout_time'];
        }

        return false;
    }
}