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
                $app->basePath() . '/frontend/templates/screens'
            ];
            $ludou = new TemplateEngine($paths, !$app->isDebug());
            $ludou->addGlobal('app', $app);
            
            // Add environment-aware globals for templates
            $ludou->addGlobal('title', env('APP_NAME', 'Ludelix Framework'));
            $ludou->addGlobal('version', '1.0.0');
            $ludou->addGlobal('environment', env('APP_ENV', 'development'));
            $ludou->addGlobal('php_version', PHP_VERSION);
            $ludou->addGlobal('server', 'Built-in');

            $ludou->addGlobal('app_url', env('APP_URL', 'http://localhost'));
            $ludou->addGlobal('app_timezone', env('APP_TIMEZONE', 'UTC'));
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