<?php

namespace Ludelix\PRT;

/**
 * Session Manager
 * 
 * Manages PHP sessions with additional features
 */
class Session
{
    protected bool $started = false;
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'name' => 'LUDELIX_SESSION',
            'lifetime' => 7200,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ], $config);
    }

    /**
     * Start session
     */
    public function start(): bool
    {
        if ($this->started) {
            return true;
        }

        // Configure session
        session_name($this->config['name']);
        session_set_cookie_params([
            'lifetime' => $this->config['lifetime'],
            'path' => $this->config['path'],
            'domain' => $this->config['domain'],
            'secure' => $this->config['secure'],
            'httponly' => $this->config['httponly'],
            'samesite' => $this->config['samesite']
        ]);

        $this->started = session_start();
        return $this->started;
    }

    /**
     * Get session value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set session value
     */
    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    /**
     * Check if session has key
     */
    public function has(string $key): bool
    {
        $this->start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session key
     */
    public function remove(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    /**
     * Get all session data
     */
    public function all(): array
    {
        $this->start();
        return $_SESSION;
    }

    /**
     * Clear all session data
     */
    public function clear(): void
    {
        $this->start();
        $_SESSION = [];
    }

    /**
     * Destroy session
     */
    public function destroy(): bool
    {
        $this->start();
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        
        return session_destroy();
    }

    /**
     * Regenerate session ID
     */
    public function regenerate(bool $deleteOld = true): bool
    {
        $this->start();
        return session_regenerate_id($deleteOld);
    }

    /**
     * Get session ID
     */
    public function getId(): string
    {
        $this->start();
        return session_id();
    }

    /**
     * Set session ID
     */
    public function setId(string $id): void
    {
        if (!$this->started) {
            session_id($id);
        }
    }

    /**
     * Flash data (available for next request only)
     */
    public function flash(string $key, mixed $value): void
    {
        $this->set('_flash.' . $key, $value);
    }

    /**
     * Get flash data
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $this->get('_flash.' . $key, $default);
        $this->remove('_flash.' . $key);
        return $value;
    }

    /**
     * Check if session is started
     */
    public function isStarted(): bool
    {
        return $this->started;
    }
}