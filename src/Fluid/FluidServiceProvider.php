<?php

namespace Ludelix\Fluid;

use Ludelix\Fluid\Core\{Config, Compiler, Parser, Theme, Generator, AdvancedGenerator};
use Ludelix\Fluid\Integration\LudouHook;
use Ludelix\Interface\DI\ContainerInterface;

class FluidServiceProvider
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function register(): void
    {
        // Registrar configuração
        $this->container->singleton(Config::class, function ($container) {
            return new Config();
        });

        // Registrar parser  
        $this->container->singleton(Parser::class, function ($container) {
            return new Parser($container->make(Config::class));
        });

        // Registrar compiler com a lista completa de utilities
        $this->container->singleton(Compiler::class, function ($container) {
            $config = $container->make(Config::class);
            return new Compiler(
                $config,
                $config->getUtilities()
            );
        });

        // Registrar generator básico
        $this->container->singleton(Generator::class, function ($container) {
            $config = $container->make(Config::class);
            return new Generator($config);
        });

        // Registrar AdvancedGenerator para suporte a estados e responsividade
        $this->container->singleton(AdvancedGenerator::class, function ($container) {
            $config = $container->make(Config::class);
            return new AdvancedGenerator($config);
        });

        // Registrar theme manager
        $this->container->singleton(Theme::class, function ($container) {
            return new Theme($container->make(Config::class));
        });

        // Registrar Ludou hook com AdvancedGenerator
        $this->container->singleton(LudouHook::class, function ($container) {
            return new LudouHook(
                $container->make(Parser::class),
                $container->make(Compiler::class),
                $container->make(AdvancedGenerator::class)
            );
        });
    }
    
    public function boot(): void
    {
        // Boot logic if needed
    }
}
