<?php

namespace Ludelix\Session\Providers;

use Ludelix\Session\SessionManager;
use Ludelix\Foundation\Support\Providers\ServiceProvider;

class SessionServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('session', function ($app) {
            return new SessionManager($app['config']['session']);
        });

        $this->app->singleton('session.store', function ($app) {
            return $app['session']->getStore();
        });
    }
}
