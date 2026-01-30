<?php

declare(strict_types=1);

namespace Ludelix\Session;

/**
 * SessionInterface - Contract for session management.
 */
interface SessionInterface
{
    /**
     * Start the session.
     *
     * @return void
     */
    public function start(): void;

    /**
     * Determine if the session has been started.
     *
     * @return bool
     */
    public function isStarted(): bool;

    /**
     * Get the current session ID.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Regenerate the session ID.
     *
     * @param bool $destroy
     * @return bool
     */
    public function regenerate(bool $destroy = false): bool;

    /**
     * Get an item from the session.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set a value in the session.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, mixed $value): void;

    /**
     * Store a value in the session (alias for set).
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function put(string $key, mixed $value): void;

    /**
     * Determine if a session key exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Remove an item from the session.
     *
     * @param string $key
     * @return void
     */
    public function remove(string $key): void;

    /**
     * Forget an item from the session (alias for remove).
     *
     * @param string $key
     * @return void
     */
    public function forget(string $key): void;

    /**
     * Get all session data.
     *
     * @return array
     */
    public function all(): array;

    /**
     * Clear all session data.
     *
     * @return void
     */
    public function clear(): void;

    /**
     * Set a cookie (commonly used for 'remember me' tokens).
     *
     * @param string $name
     * @param string $value
     * @param int $minutes
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @return void
     */
    public function setCookie(string $name, string $value, int $minutes = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true): void;

    /**
     * Get a cookie value.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getCookie(string $name, mixed $default = null): mixed;

    /**
     * Determine if a cookie exists.
     *
     * @param string $name
     * @return bool
     */
    public function hasCookie(string $name): bool;

    /**
     * Remove a cookie.
     *
     * @param string $name
     * @return void
     */
    public function removeCookie(string $name): void;
}
