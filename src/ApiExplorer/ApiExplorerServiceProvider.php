<?php

namespace Ludelix\ApiExplorer;

use Ludelix\Bootstrap\Providers\ServiceProvider;
use Ludelix\Routing\Core\Router;
use Ludelix\ApiExplorer\Http\Controller\ExplorerController;

/**
 * Service Provider for Ludelix API Explorer.
 *
 * Registers the essential routes and services required to provide the
 * interactive API documentation and testing interface.
 */
class ApiExplorerServiceProvider extends ServiceProvider
{
    /**
     * Bootstraps the API Explorer component.
     *
     * Registers the explorer's routes into the application's router
     * and ensures the documentation interface is accessible.
     */
    public function boot(): void
    {
        // Only enable in non-production environments normally, 
        // but for now we enable it generally for testing.

        /** @var Router $router */
        $router = $this->container->make(Router::class);

        $router->group(['prefix' => 'api-explorer'], function (Router $router) {
            $router->get('/', ExplorerController::class . '@index');
            $router->get('/json', ExplorerController::class . '@json');
        });
    }

    /**
     * Registers the API Explorer services in the container.
     *
     * This component primarily uses class-based resolution for its
     * controllers and scanners.
     */
    public function register(): void
    {
        $this->container->singleton(\Ludelix\ApiExplorer\Validation\AttributeValidator::class, function ($container) {
            return new \Ludelix\ApiExplorer\Validation\AttributeValidator($container->make(\Ludelix\Validation\Core\Validator::class));
        });

        $this->container->singleton('middleware.api_validation', \Ludelix\ApiExplorer\Validation\AttributeValidationMiddleware::class);
    }
}
