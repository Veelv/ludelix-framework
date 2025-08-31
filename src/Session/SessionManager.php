<?php

namespace Ludelix\Session;

use Ludelix\Session\Store;
use Ludelix\Session\FileSessionHandler;

class SessionManager
{
    /**
     * The application instance.
     *
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
}