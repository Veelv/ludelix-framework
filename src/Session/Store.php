<?php

namespace Ludelix\Session;

/**
 * Store - Handles the session data storage and retrieval.
 */
class Store implements SessionInterface
{
    /**
     * Determine if the session store is started.
     *
     * @var bool
     */
    protected $started = true;

    /**
     * The session name.
     *
     * @var string
     */
    protected $name;

    /**
     * The session attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Create a new session store.
     *
     * @param  string  $name
     * @param  array   $attributes
     * @return void
     */
    public function __construct($name, array &$attributes = [])
    {
        $this->name = $name;
        $this->attributes = &$attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->attributes, $key, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value): void
    {
        $this->put($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $key, mixed $value): void
    {
        data_set($this->attributes, $key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->attributes;
    }

    /**
     * Flash a key / value pair to the session.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function flash($key, $value)
    {
        $this->put($key, $value);
        $this->push('_flash.new', $key);
        $this->removeFromOldFlashData([$key]);
    }

    /**
     * Age the flash data for the session.
     *
     * @return void
     */
    public function ageFlashData()
    {
        $this->forget($this->get('_flash.old', []));
        $this->put('_flash.old', $this->get('_flash.new', []));
        $this->put('_flash.new', []);
    }

    /**
     * Remove the given keys from the old flash data.
     *
     * @param  array  $keys
     * @return void
     */
    protected function removeFromOldFlashData(array $keys)
    {
        $this->put('_flash.old', array_diff($this->get('_flash.old', []), $keys));
    }

    /**
     * Get the old input from the session.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getOldInput($key = null, $default = null)
    {
        return $this->get("_old_input.{$key}", $default);
    }

    /**
     * Determine if the session has old input for a given key.
     *
     * @param  string|null  $key
     * @return bool
     */
    public function hasOldInput($key = null)
    {
        return $this->has("_old_input" . ($key ? '.' . $key : ''));
    }

    /**
     * Set the old input for the session.
     *
     * @param  array  $input
     * @return void
     */
    public function setOldInput(array $input)
    {
        $this->put('_old_input', $input);
    }

    /**
     * {@inheritdoc}
     */
    public function forget(string $key): void
    {
        data_forget($this->attributes, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $key): void
    {
        $this->forget($key);
    }

    /**
     * Push a value onto a session array.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function push($key, $value)
    {
        $array = $this->get($key, []);
        $array[] = $value;
        $this->put($key, $array);
    }

    /**
     * Save the session data to storage.
     *
     * @return void
     */
    public function save()
    {
        $this->ageFlashData();
        // The session data is already in the $_SESSION superglobal
        // because we passed it by reference.
        session_write_close();
    }

    /**
     * {@inheritdoc}
     */
    public function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->started = true;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted(): bool
    {
        return $this->started && session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return session_id();
    }

    /**
     * {@inheritdoc}
     */
    public function regenerate(bool $destroy = false): bool
    {
        return session_regenerate_id($destroy);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        $this->attributes = [];
    }

    /**
     * {@inheritdoc}
     */
    public function setCookie(string $name, string $value, int $minutes = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true): void
    {
        $expire = $minutes > 0 ? time() + ($minutes * 60) : 0;
        setcookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
    }

    /**
     * {@inheritdoc}
     */
    public function getCookie(string $name, mixed $default = null): mixed
    {
        return $_COOKIE[$name] ?? $default;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCookie(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function removeCookie(string $name): void
    {
        setcookie($name, '', time() - 3600, '/');
        unset($_COOKIE[$name]);
    }
}
