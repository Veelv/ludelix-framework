<?php

namespace Ludelix\Fluid;

use Ludelix\Fluid\Core\{Config, Compiler, Parser, Theme, Generator, AdvancedGenerator};
use Ludelix\Fluid\Integration\LudouHook;
use Ludelix\Interface\DI\ContainerInterface;

/**
 * Fluid Service Provider
 *
 * This provider is responsible for bootstrapping the Fluid framework components,
 * including configuration, parser, compiler, generators, and integration hooks.
 *
 * @package Ludelix\Fluid
 */
class FluidServiceProvider
{
    /**
     * @var ContainerInterface The dependency injection container instance.
     */
    protected ContainerInterface $container;

    /**
     * Create a new service provider instance.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Register services in the container.
     *
     * @return void
     */
    public function register(): void
    {
        // Register the configuration singleton
        $this->container->singleton(Config::class, function ($container) {
            return new Config();
        });

        // Register the parser
        $this->container->singleton(Parser::class, function ($container) {
            return new Parser($container->make(Config::class));
        });

        // Register the compiler with the complete list of utilities
        $this->container->singleton(Compiler::class, function ($container) {
            $config = $container->make(Config::class);
            return new Compiler(
                $config,
                $config->getUtilities()
            );
        });

        // Register the basic generator
        $this->container->singleton(Generator::class, function ($container) {
            $config = $container->make(Config::class);
            return new Generator($config);
        });

        // Register the AdvancedGenerator for state and responsiveness support
        $this->container->singleton(AdvancedGenerator::class, function ($container) {
            $config = $container->make(Config::class);
            return new AdvancedGenerator($config);
        });

        // Register the theme manager
        $this->container->singleton(Theme::class, function ($container) {
            return new Theme($container->make(Config::class));
        });

        // Register the Ludou hook with AdvancedGenerator
        $this->container->singleton(LudouHook::class, function ($container) {
            return new LudouHook(
                $container->make(Parser::class),
                $container->make(Compiler::class),
                $container->make(AdvancedGenerator::class)
            );
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Boot logic if needed
    }
}
