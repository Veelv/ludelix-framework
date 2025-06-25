<?php

namespace Ludelix\Bootstrap\Providers;

use Ludelix\Interface\DI\ContainerInterface;
use Ludelix\Ludou\Core\TemplateEngine;

class LudouProvider
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function register(): void
    {
        $this->container->singleton('ludou', function ($container) {
            $app = $container->make('app');
            $paths = [
                $app->basePath() . '/resources/templates',
                $app->basePath() . '/templates'
            ];
            
            $ludou = new TemplateEngine($paths, !$app->isDebug());
            
            // Add global variables
            $ludou->addGlobal('app', $app);
            
            // Add additional functions
            $ludou->addFunction('route', function($name, $params = []) {
                return app('router')->route($name, $params);
            });
            
            $ludou->addFunction('csrf_token', function() {
                return app('csrf')->token();
            });
            
            $ludou->addFunction('date', function($format) {
                return date($format);
            });
            
            return $ludou;
        });
    }

    public function boot(): void
    {
        // Boot logic if needed
    }
}