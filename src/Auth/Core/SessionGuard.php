<?php

namespace Ludelix\Auth\Core;

use Ludelix\Interface\Auth\GuardInterface;
use Ludelix\Interface\Auth\UserInterface;
use Ludelix\Interface\Auth\UserProviderInterface;
use Ludelix\Session\SessionManager;
use Ludelix\Session\SessionInterface;

/**
 * SessionGuard - Session-based authentication guard
 * 
 * This guard implements authentication using the Ludelix Session system to maintain
 * the authenticated user state across requests with enhanced security features.
 * 
 * @package Ludelix\Auth\Core
 */
class SessionGuard implements GuardInterface
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
     * The session manager
     *
     * @var SessionInterface
     */
    protected SessionInterface $session;

    /**
     * The session key for storing the user ID
     *
     * @var string
     */
    protected string $sessionKey = 'auth_user_id';

    /**
     * The session key for storing the user hash
     *
     * @var string
     */
    protected string $sessionHashKey = 'auth_user_hash';

    /**
     * The session key for storing login timestamp
     *
     * @var string
     */
    protected string $sessionLoginTimeKey = 'auth_login_time';

    /**
     * The session key for storing last activity
     *
     * @var string
     */
    protected string $sessionLastActivityKey = 'auth_last_activity';

    /**
     * Remember me cookie name
     *
     * @var string
     */
    protected string $rememberCookieName = 'remember_token';

    /**
     * Remember me cookie lifetime (30 days)
     *
     * @var int
     */
    protected int $rememberLifetime = 2592000; // 30 days in seconds

    /**
     * Session timeout in seconds (2 hours)
     *
     * @var int
     */
    protected int $sessionTimeout = 7200;

    /**
     * SessionGuard constructor.
     *
     * @param UserProviderInterface $provider The user provider
     * @param SessionInterface|null $session The session manager
     */
    public function __construct(UserProviderInterface $provider, ?SessionInterface $session = null)
    {
        $this->provider = $provider;
        $this->session = $session ?? SessionManager::getInstance();
        
        $this->initializeSession();
        $this->loadUserFromSession();
    }

    /**
     * Initialize session with security settings
     *
     * @return void
     */
    protected function initializeSession(): void
    {
        // Start session if not already started
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        // Check for session timeout
        $this->checkSessionTimeout();
        $this->updateLastActivity();
    }

    /**
     * Check if session has timed out
     *
     * @return void
     */
    protected function checkSessionTimeout(): void
    {
        if ($this->session->has($this->sessionLastActivityKey)) {
            $lastActivity = $this->session->get($this->sessionLastActivityKey);
            if (time() - $lastActivity > $this->sessionTimeout) {
                $this->logout();
                return;
            }
        }
    }

    /**
     * Update last activity timestamp
     *
     * @return void
     */
    protected function updateLastActivity(): void
    {
        $this->session->set($this->sessionLastActivityKey, time());
    }

    /**
     * Load the user from the session or remember me cookie
     *
     * @return void
     */
    protected function loadUserFromSession(): void
    {
        // Try to load from session first
        if ($this->session->has($this->sessionKey)) {
            $userId = $this->session->get($this->sessionKey);
            $user = $this->provider->retrieveById($userId);
            
            if ($user && $this->validateSessionUser($user)) {
                $this->user = $user;
                return;
            } else {
                // Invalid session, clear it
                $this->clearSessionData();
            }
        }

        // If no session user, try remember me
        $this->loadUserFromRememberToken();
    }

    /**
     * Validate that the session user is still valid
     *
     * @param UserInterface $user
     * @return bool
     */
    protected function validateSessionUser(UserInterface $user): bool
    {
        // Check if user is still active
        if (!$user->isActive()) {
            return false;
        }

        // Validate user hash to detect password changes
        if ($this->session->has($this->sessionHashKey)) {
            $sessionHash = $this->session->get($this->sessionHashKey);
            $currentHash = $this->generateUserHash($user);
            
            if ($sessionHash !== $currentHash) {
                return false;
            }
        }

        return true;
    }

    /**
     * Load user from remember me token
     *
     * @return void
     */
    protected function loadUserFromRememberToken(): void
    {
        $token = $this->session->getCookie($this->rememberCookieName);
        
        if (!$token) {
            return;
        }

        // Try to find user with this remember token
        $user = $this->findUserByRememberToken($token);
        
        if ($user && $user->getRememberToken() === $token && $user->isActive()) {
            $this->loginWithoutCredentials($user);
        } else {
            // Invalid token, clear cookie
            $this->clearRememberCookie();
        }
    }

    /**
     * Find user by remember token
     * This is a placeholder - implement according to your user storage
     *
     * @param string $token
     * @return UserInterface|null
     */
    protected function findUserByRememberToken(string $token): ?UserInterface
    {
        // This would need to be implemented based on your user storage
        // For now, return null as we don't have a direct way to search by token
        return null;
    }

    /**
     * Generate a hash for the user to detect changes
     *
     * @param UserInterface $user
     * @return string
     */
    protected function generateUserHash(UserInterface $user): string
    {
        return hash('sha256', $user->getId() . $user->getPasswordHash() . $user->getEmail());
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
        return $this->user !== null && $this->user->isActive();
    }

    /**
     * Authenticate a user in the session
     *
     * @param UserInterface $user The user to authenticate
     * @return void
     */
    public function login(UserInterface $user): void
    {
        if (!$user->isActive()) {
            throw new \InvalidArgumentException('Cannot login inactive user');
        }

        // Regenerate session ID for security
        $this->session->regenerate();

        // Set user and session data
        $this->user = $user;
        $this->session->set($this->sessionKey, $user->getId());
        $this->session->set($this->sessionHashKey, $this->generateUserHash($user));
        $this->session->set($this->sessionLoginTimeKey, time());
        $this->session->set($this->sessionLastActivityKey, time());
    }

    /**
     * Login user without credential validation (for remember me)
     *
     * @param UserInterface $user
     * @return void
     */
    protected function loginWithoutCredentials(UserInterface $user): void
    {
        $this->login($user);
    }

    /**
     * Login with remember me functionality
     *
     * @param UserInterface $user
     * @param bool $remember
     * @return void
     */
    public function loginWithRemember(UserInterface $user, bool $remember = false): void
    {
        $this->login($user);

        if ($remember) {
            $this->setRememberToken($user);
        }
    }

    /**
     * Set remember me token
     *
     * @param UserInterface $user
     * @return void
     */
    protected function setRememberToken(UserInterface $user): void
    {
        $token = bin2hex(random_bytes(32));
        $user->setRememberToken($token);

        // Set secure cookie using session manager
        $this->session->setCookie(
            $this->rememberCookieName,
            $token,
            time() + $this->rememberLifetime,
            '/',
            '',
            true, // secure
            true  // httponly
        );
    }

    /**
     * Log out the current user
     *
     * @return void
     */
    public function logout(): void
    {
        // Clear remember token if user exists
        if ($this->user) {
            $this->user->clearRememberToken();
        }

        // Clear session data
        $this->clearSessionData();

        // Clear remember me cookie
        $this->clearRememberCookie();

        // Clear user
        $this->user = null;

        // Regenerate session ID
        $this->session->regenerate();
    }

    /**
     * Clear session authentication data
     *
     * @return void
     */
    protected function clearSessionData(): void
    {
        $this->session->remove($this->sessionKey);
        $this->session->remove($this->sessionHashKey);
        $this->session->remove($this->sessionLoginTimeKey);
        $this->session->remove($this->sessionLastActivityKey);
    }

    /**
     * Clear remember me cookie
     *
     * @return void
     */
    protected function clearRememberCookie(): void
    {
        $this->session->removeCookie($this->rememberCookieName);
    }

    /**
     * Get session login time
     *
     * @return int|null
     */
    public function getLoginTime(): ?int
    {
        return $this->session->get($this->sessionLoginTimeKey);
    }

    /**
     * Get last activity time
     *
     * @return int|null
     */
    public function getLastActivity(): ?int
    {
        return $this->session->get($this->sessionLastActivityKey);
    }

    /**
     * Check if session is about to expire
     *
     * @param int $warningTime Time in seconds before expiration to warn
     * @return bool
     */
    public function isSessionExpiringSoon(int $warningTime = 300): bool
    {
        $lastActivity = $this->getLastActivity();
        if (!$lastActivity) {
            return false;
        }

        $timeLeft = $this->sessionTimeout - (time() - $lastActivity);
        return $timeLeft <= $warningTime && $timeLeft > 0;
    }

    /**
     * Extend session lifetime
     *
     * @return void
     */
    public function extendSession(): void
    {
        $this->updateLastActivity();
    }

    /**
     * Get remaining session time in seconds
     *
     * @return int
     */
    public function getRemainingSessionTime(): int
    {
        $lastActivity = $this->getLastActivity();
        if (!$lastActivity) {
            return 0;
        }

        $timeLeft = $this->sessionTimeout - (time() - $lastActivity);
        return max(0, $timeLeft);
    }

    /**
     * Check if user has remember me token
     *
     * @return bool
     */
    public function hasRememberToken(): bool
    {
        return $this->session->hasCookie($this->rememberCookieName) && 
               $this->user && 
               $this->user->getRememberToken();
    }

    /**
     * Force logout all sessions for a user (useful for password changes)
     *
     * @param UserInterface $user
     * @return void
     */
    public function logoutAllSessions(UserInterface $user): void
    {
        // Clear remember token
        $user->clearRememberToken();
        
        // If this is the current user, logout
        if ($this->user && $this->user->getId() === $user->getId()) {
            $this->logout();
        }
    }

    /**
     * Validate current session integrity
     *
     * @return bool
     */
    public function validateSession(): bool
    {
        if (!$this->user) {
            return false;
        }

        return $this->validateSessionUser($this->user);
    }

    /**
     * Get session timeout in seconds
     *
     * @return int
     */
    public function getSessionTimeout(): int
    {
        return $this->sessionTimeout;
    }

    /**
     * Set session timeout
     *
     * @param int $timeout Timeout in seconds
     * @return void
     */
    public function setSessionTimeout(int $timeout): void
    {
        $this->sessionTimeout = $timeout;
    }

    /**
     * Get session ID
     *
     * @return string|null
     */
    public function getSessionId(): ?string
    {
        return $this->session->getId();
    }

    /**
     * Get all session data for debugging
     *
     * @return array
     */
    public function getSessionData(): array
    {
        return [
            'user_id' => $this->session->get($this->sessionKey),
            'login_time' => $this->session->get($this->sessionLoginTimeKey),
            'last_activity' => $this->session->get($this->sessionLastActivityKey),
            'session_id' => $this->session->getId(),
            'is_authenticated' => $this->check(),
            'remaining_time' => $this->getRemainingSessionTime(),
        ];
    }
}