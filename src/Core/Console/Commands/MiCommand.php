<?php

namespace Ludelix\Core\Console\Commands;

use Ludelix\Core\Console\Engine\MiEngine;
use Ludelix\Interface\DI\ContainerInterface;

/**
 * Mi Command
 * 
 * Main CLI command handler for Ludelix Framework
 */
class MiCommand
{
    protected MiEngine $engine;
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container ?? new \Ludelix\Core\Container();
        $this->engine = new MiEngine($this->container);
    }

    /**
     * Execute mi command
     */
    public function execute(array $argv): int
    {
        return $this->engine->run($argv);
    }

    /**
     * Register available commands
     */
    protected function registerCommands(): void
    {
        $this->commands = [
            'kria:module' => MakeModuleCommand::class,
            'kria:repository' => [MakeModuleCommand::class, 'repository'],
            'kria:service' => [MakeModuleCommand::class, 'service'],
            'kria:entity' => [MakeModuleCommand::class, 'entity'],
            'kria:job' => [MakeModuleCommand::class, 'job'],
            'kria:middleware' => [MakeModuleCommand::class, 'middleware'],
            'kria:console' => [MakeModuleCommand::class, 'console'],
            'cubby:link' => StorageLinkCommand::class,
            'help' => 'showHelp'
        ];
    }

    /**
     * Run specific command
     */
    protected function runCommand(string $command, array $args, array $options): int
    {
        if (!isset($this->commands[$command])) {
            echo "Command '{$command}' not found.\n";
            echo "Run 'php mi list' to see available commands.\n";
            return 1;
        }

        $handler = $this->commands[$command];

        if (is_array($handler)) {
            [$class, $type] = $handler;
            if (is_string($class)) {
                $instance = new $class();
                if ($type && method_exists($instance, 'executeType')) {
                    return $instance->executeType($type, $args, $options);
                }
                return $instance->execute($args, array_merge($options, ['type' => $type]));
            } else {
                return $class($args, $options);
            }
        }

        if (is_string($handler)) {
            if (method_exists($this, $handler)) {
                return $this->$handler($args, $options);
            }
            $instance = new $handler();
            return $instance->execute($args, $options);
        }

        if (is_string($handler) && method_exists($this, $handler)) {
            return $this->$handler($args, $options);
        }
        
        if (is_callable($handler)) {
            return call_user_func($handler, $args, $options);
        }

        return 1;
    }

    /**
     * Show help
     */
    public function showHelp(): int
    {
        echo "🏹 Mi - Ludelix Framework CLI\n\n";
        echo "Usage:\n";
        echo "  php mi <command> [arguments] [options]\n\n";
        echo "Available Commands:\n";
        echo "  kria:module <name>      Create complete module (repository, service, entity, etc.)\n";
        echo "  kria:repository <name>  Create repository only\n";
        echo "  kria:service <name>     Create service only\n";
        echo "  kria:entity <name>      Create entity only\n";
        echo "  kria:job <name>         Create job only\n";
        echo "  kria:middleware <name>  Create middleware only\n";
        echo "  kria:console <name>     Create console command only\n";
        echo "  kria:lang <locale> <name> Create language file (PHP, JSON, YAML)\n";
        echo "  cubby:link              Create symbolic link from public/ludelix to cubby/up\n";
        echo "  help                    Show this help\n\n";
        echo "Options:\n";
        echo "  --type=<type>          Specify component type for kria:module\n";
        echo "  --format=<format>      Specify file format for kria:lang (php, json, yaml, yml)\n\n";
        echo "Examples:\n";
        echo "  php mi kria:module user\n";
        echo "  php mi kria:repository product\n";
        echo "  php mi kria:module order --type=service\n";
        echo "  php mi kria:lang en messages\n";
        echo "  php mi kria:lang pt_BR users --format=yaml\n";
        
        return 0;
    }
} 