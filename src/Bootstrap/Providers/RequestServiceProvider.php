<?php

namespace Ludelix\Bootstrap\Providers;

use Ludelix\PRT\RequestHandler;

class RequestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('request.handler', function ($container) {
            return new RequestHandler($container);
        });
    }

    public function boot(): void
    {
        // Configure middleware if needed
        if ($this->container->has('request.handler')) {
            $handler = $this->container->get('request.handler');
            
            // Add default middleware
            $this->addDefaultMiddleware($handler);
        }
    }

    protected function addDefaultMiddleware(RequestHandler $handler): void
    {
        // Add common middleware here
        // $handler->addMiddleware('csrf');
        // $handler->addMiddleware('cors');
    }
}