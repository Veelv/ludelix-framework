<?php

namespace Ludelix\Auth;

use Ludelix\Bootstrap\Providers\ServiceProvider;
use Ludelix\Core\Security\JwtService;
use Ludelix\Auth\Core\AuthManager;
use Ludelix\Auth\Core\JwtGuard;
use Ludelix\Auth\Core\AuthService;
use Ludelix\Auth\Core\DatabaseUserProvider;
use Ludelix\Interface\Auth\UserProviderInterface;
use Ludelix\Auth\Middleware\AuthorizeMiddleware;

/**
 * AuthServiceProvider - Registers authentication services.
 */
class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 1. Register User Provider Interface (Dynamic Selection)
        $this->container->singleton(UserProviderInterface::class, function ($container) {
            $config = $container->get('config');
            $driver = $config->get('auth.driver', 'database');

            if ($driver === 'redis') {
                return new \Ludelix\Auth\Core\RedisUserProvider(
                    $container->get('cache')->driver('redis'),
                    $config->get('auth.providers.users.prefix', 'user:')
                );
            }

            $userModel = $config->get('auth.providers.users.model', 'App\\Entities\\User');
            return new DatabaseUserProvider($userModel);
        });

        // 2. Register JWT Service
        $this->container->singleton(JwtService::class, function ($container) {
            $config = $container->get('config');
            $secret = $config->get('security.jwt.secret', $_ENV['JWT_SECRET'] ?? 'ludelix-default-secret-key-change-me');
            return new JwtService($secret);
        });

        // 3. Register Auth Manager
        $this->container->singleton(AuthManager::class, function ($container) {
            return new AuthManager();
        });

        // 4. Register JWT Guard
        $this->container->singleton('auth.guard.jwt', function ($container) {
            return new JwtGuard(
                $container->make(UserProviderInterface::class),
                $container->make(JwtService::class)
            );
        });

        // 5. Register Auth Service (Main entry point)
        $this->container->singleton('auth', function ($container) {
            return new AuthService(
                $container->get('auth.guard.jwt'),
                $container->make(UserProviderInterface::class)
            );
        });

        // 6. Register Magic Middleware
        $this->container->singleton('middleware.authorize', AuthorizeMiddleware::class);
    }

    public function boot(): void
    {
        if ($this->container->has(AuthManager::class)) {
            $manager = $this->container->get(AuthManager::class);
            $manager->addGuard('jwt', $this->container->get('auth.guard.jwt'));
        }
    }
}
