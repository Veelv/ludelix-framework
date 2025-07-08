<?php

namespace Ludelix\Core\Console\Commands;

/**
 * Brow Command
 * 
 * Main CLI command handler for Ludelix (like Laravel's Artisan)
 */
class BrowCommand
{
    protected array $commands = [];

    public function __construct()
    {
        $this->registerCommands();
    }

    /**
     * Execute brow command
     */
    public function execute(array $argv): int
    {
        array_shift($argv); // Remove script name
        
        if (empty($argv)) {
            $this->showHelp();
            return 0;
        }

        $command = array_shift($argv);
        $args = [];
        $options = [];

        // Parse arguments and options
        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--')) {
                $parts = explode('=', substr($arg, 2), 2);
                $options[$parts[0]] = $parts[1] ?? true;
            } else {
                $args[] = $arg;
            }
        }

        return $this->runCommand($command, $args, $options);
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
            'list' => 'listCommands',
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
            echo "Run 'php brow list' to see available commands.\n";
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
        echo "🏹 Brow - Ludelix Framework CLI\n\n";
        echo "Usage:\n";
        echo "  php brow <command> [arguments] [options]\n\n";
        echo "Available Commands:\n";
        echo "  kria:module <name>      Create complete module (repository, service, entity, etc.)\n";
        echo "  kria:repository <name>  Create repository only\n";
        echo "  kria:service <name>     Create service only\n";
        echo "  kria:entity <name>      Create entity only\n";
        echo "  kria:job <name>         Create job only\n";
        echo "  kria:middleware <name>  Create middleware only\n";
        echo "  kria:console <name>     Create console command only\n";
        echo "  list                    List all commands\n";
        echo "  help                    Show this help\n\n";
        echo "Options:\n";
        echo "  --type=<type>          Specify component type for kria:module\n\n";
        echo "Examples:\n";
        echo "  php brow kria:module user\n";
        echo "  php brow kria:repository product\n";
        echo "  php brow kria:module order --type=service\n";
        
        return 0;
    }

    /**
     * List all commands
     */
    public function listCommands(): int
    {
        echo "🏹 Available Brow Commands:\n\n";
        
        $categories = [
            'Creation Commands (kria)' => [
                'kria:module' => 'Create complete module with all components',
                'kria:repository' => 'Create repository class',
                'kria:service' => 'Create service class',
                'kria:entity' => 'Create entity class',
                'kria:job' => 'Create background job',
                'kria:middleware' => 'Create middleware',
                'kria:console' => 'Create console command'
            ],
            'Utility Commands' => [
                'list' => 'List all available commands',
                'help' => 'Show help information'
            ]
        ];

        foreach ($categories as $category => $commands) {
            echo "{$category}:\n";
            foreach ($commands as $cmd => $desc) {
                echo "  " . str_pad($cmd, 20) . " {$desc}\n";
            }
            echo "\n";
        }

        return 0;
    }
}