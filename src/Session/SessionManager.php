<?php

namespace Ludelix\Session;

use Ludelix\Session\Store;
use Ludelix\Session\FileSessionHandler;

/**
 * SessionManager - Manages the session store and handles delegation to the session driver.
 */
class SessionManager implements SessionInterface
{
    /**
     * The singleton instance of the session manager.
     *
     * @var SessionManager|null
     */
    protected static ?SessionManager $instance = null;

    /**
     * The application instance.
     */
    protected $app;

    /**
     * The session store instance.
     *
     * @var \Ludelix\Session\Store
     */
    protected $store;

    /**
     * The session configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * Create a new session manager instance.
     *
     * @param  array  $config
     * @return void
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        static::$instance = $this;
    }

    /**
     * Get the session manager instance.
     *
     * @return SessionManager
     * @throws \RuntimeException If the session manager has not been initialized.
     */
    public static function getInstance(): SessionManager
    {
        if (static::$instance === null) {
            $framework = \Ludelix\Core\Framework::getInstance();
            if ($framework && $framework->container()->has('session')) {
                static::$instance = $framework->container()->get('session');
            } else {
                throw new \RuntimeException("SessionManager not initialized.");
            }
        }

        return static::$instance;
    }

    /**
     * Set the session manager instance.
     *
     * @param SessionManager $instance
     * @return void
     */
    public static function setInstance(SessionManager $instance): void
    {
        static::$instance = $instance;
    }

    /**
     * Get the session store instance.
     *
     * @return \Ludelix\Session\Store
     */
    public function getStore()
    {
        if (is_null($this->store)) {
            $this->store = $this->createStore();
        }

        return $this->store;
    }

    /**
     * Create the session store instance.
     *
     * @return \Ludelix\Session\Store
     */
    protected function createStore()
    {
        $handler = new FileSessionHandler(
            $this->config['files'],
            $this->config['lifetime']
        );

        session_set_save_handler($handler, true);

        session_start();

        $attributes = &$_SESSION;

        $store = new Store($this->config['cookie'], $attributes);

        return $store;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->getStore()->$method(...$parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function start(): void
    {
        $this->getStore()->start();
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted(): bool
    {
        return $this->getStore()->isStarted();
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return $this->getStore()->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function regenerate(bool $destroy = false): bool
    {
        return $this->getStore()->regenerate($destroy);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->getStore()->get($key, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value): void
    {
        $this->getStore()->set($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $key, mixed $value): void
    {
        $this->getStore()->put($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->getStore()->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $key): void
    {
        $this->getStore()->remove($key);
    }

    /**
     * {@inheritdoc}
     */
    public function forget(string $key): void
    {
        $this->getStore()->forget($key);
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->getStore()->all();
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        $this->getStore()->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function setCookie(string $name, string $value, int $minutes = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true): void
    {
        $this->getStore()->setCookie($name, $value, $minutes, $path, $domain, $secure, $httpOnly);
    }

    /**
     * {@inheritdoc}
     */
    public function getCookie(string $name, mixed $default = null): mixed
    {
        return $this->getStore()->getCookie($name, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function hasCookie(string $name): bool
    {
        return $this->getStore()->hasCookie($name);
    }

    /**
     * {@inheritdoc}
     */
    public function removeCookie(string $name): void
    {
        $this->getStore()->removeCookie($name);
    }
}