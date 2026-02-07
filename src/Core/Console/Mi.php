<?php

namespace Ludelix\Core\Console;

use Ludelix\Interface\DI\ContainerInterface;
use Ludelix\Core\Console\Engine\MiEngine;
use Ludelix\Core\Console\Discovery\CommandDiscovery;
use Ludelix\Core\Console\Extensions\ExtensionLoader;
use Ludelix\Core\Console\Support\HelpFormatter;

/**
 * Mi - Ludelix Framework Console Application
 *
 * Main console application class that manages all commands,
 * extensions, and provides a unified interface for the CLI.
 *
 * @package Ludelix\Core\Console
 * @version 1.0.1
 */
class Mi
{
    protected $container;
    protected MiEngine $engine;
    protected CommandDiscovery $discovery;
    protected ExtensionLoader $extensionLoader;
    protected HelpFormatter $help;
    protected array $commands = [];
    protected array $extensions = [];

    public function __construct($container = null)
    {
        $this->container = $container;
        $this->engine = new MiEngine($container);
        $this->discovery = new CommandDiscovery();
        $this->extensionLoader = new ExtensionLoader($this);
        $this->help = new HelpFormatter();

        $this->initialize();
    }

    /**
     * Run the console application
     *
     * @param array $argv Command line arguments
     * @return int Exit code
     */
    public function run(array $argv): int
    {
        try {
            // Parse command line arguments
            $input = $this->parseInput($argv);

            // Handle special commands
            if ($this->handleSpecialCommands($input)) {
                return 0;
            }

            // Execute the command
            return $this->engine->run($argv);

        } catch (\Throwable $e) {
            $this->displayError($e);
            return 1;
        }
    }

    /**
     * Initialize the console application
     */
    protected function initialize(): void
    {
        // Register core commands
        $this->registerCoreCommands();

        // Discover and register commands
        $this->discoverCommands();

        // Load extensions
        $this->loadExtensions();

        // Register commands with engine
        $this->registerCommandsWithEngine();
    }

    /**
     * Register core framework commands
     */
    protected function registerCoreCommands(): void
    {
        // Kria Commands
        $this->registerCommand('kria:module', \Ludelix\Core\Console\Commands\Kria\KriaModuleCommand::class);
        $this->registerCommand('kria:repository', \Ludelix\Core\Console\Commands\Kria\KriaRepositoryCommand::class);
        $this->registerCommand('kria:service', \Ludelix\Core\Console\Commands\Kria\KriaServiceCommand::class);
        $this->registerCommand('kria:entity', \Ludelix\Core\Console\Commands\Kria\KriaEntityCommand::class);
        $this->registerCommand('kria:job', \Ludelix\Core\Console\Commands\Kria\KriaJobCommand::class);
        $this->registerCommand('kria:middleware', \Ludelix\Core\Console\Commands\Kria\KriaMiddlewareCommand::class);
        $this->registerCommand('kria:console', \Ludelix\Core\Console\Commands\Kria\KriaConsoleCommand::class);
        $this->registerCommand('kria:controller', \Ludelix\Core\Console\Commands\Kria\KriaControllerCommand::class);
        $this->registerCommand('kria:page', \Ludelix\Core\Console\Commands\Kria\KriaPageCommand::class);
        $this->registerCommand('kria:template', \Ludelix\Core\Console\Commands\Kria\KriaTemplateCommand::class);
        $this->registerCommand('kria:lang', \Ludelix\Core\Console\Commands\Kria\KriaLangCommand::class);
        $this->registerCommand('kria:resource', \Ludelix\Core\Console\Commands\Kria\KriaResourceCommand::class);
        $this->registerCommand('kria:sdk', \Ludelix\Core\Console\Commands\Kria\KriaSdkCommand::class);

        // Tenant Commands
        $this->registerCommand('tenant:create', \Ludelix\Tenant\Commands\TenantCreateCommand::class);
        $this->registerCommand('tenant:list', \Ludelix\Tenant\Commands\TenantListCommand::class);
        $this->registerCommand('tenant:switch', \Ludelix\Tenant\Commands\TenantSwitchCommand::class);
        $this->registerCommand('tenant:stats', \Ludelix\Tenant\Commands\TenantStatsCommand::class);

        // Cache Commands
        $this->registerCommand('cache:clear', \Ludelix\Cache\Commands\CacheClearCommand::class);
        $this->registerCommand('cache:cleanup', \Ludelix\Cache\Commands\CacheCleanupCommand::class);

        // Seeder Commands
        $this->registerCommand('seed', \Ludelix\Core\Console\Commands\Seeder\SeedCommand::class);
        $this->registerCommand('seed:create', \Ludelix\Core\Console\Commands\Seeder\SeedCreateCommand::class);
        $this->registerCommand('seed:status', \Ludelix\Core\Console\Commands\Seeder\SeedStatusCommand::class);
        $this->registerCommand('seed:generate', \Ludelix\Core\Console\Commands\Seeder\SeedGenerateCommand::class);

        // Evolution Commands
        $this->registerCommand('evolve:create', \Ludelix\Core\Console\Commands\Evolution\EvolveCreateCommand::class);
        $this->registerCommand('evolve:apply', \Ludelix\Core\Console\Commands\Evolution\EvolveApplyCommand::class);
        $this->registerCommand('evolve:status', \Ludelix\Core\Console\Commands\Evolution\EvolveStatusCommand::class);
        $this->registerCommand('evolve:revert', \Ludelix\Core\Console\Commands\Evolution\EvolveRevertCommand::class);
        $this->registerCommand('evolve:refresh', \Ludelix\Core\Console\Commands\Evolution\EvolveRefreshCommand::class);

        // Framework Commands
        $this->registerCommand('start', \Ludelix\Core\Console\Commands\Core\StartCommand::class);
        $this->registerCommand('route:list', \Ludelix\Core\Console\Commands\Framework\RouteListCommand::class);
        $this->registerCommand('route:cache', \Ludelix\Core\Console\Commands\Framework\RouteCacheCommand::class);
        $this->registerCommand('cubby:link', \Ludelix\Core\Console\Commands\Framework\CubbyLinkCommand::class);
        $this->registerCommand('config:cache', \Ludelix\Core\Console\Commands\Framework\ConfigCacheCommand::class);
        $this->registerCommand('config:clear', \Ludelix\Core\Console\Commands\Framework\ConfigClearCommand::class);

        // Security Commands
        $this->registerCommand('key:generate', \Ludelix\Core\Console\Commands\Security\GenerateKeyCommand::class);
        $this->registerCommand('security:logs', \Ludelix\Core\Console\Commands\Security\SecurityLogsCommand::class);

        // Extension Commands
        $this->registerCommand('extension:list', \Ludelix\Core\Console\Commands\Extension\ExtensionListCommand::class);
        $this->registerCommand('extension:install', \Ludelix\Core\Console\Commands\Extension\ExtensionInstallCommand::class);
        $this->registerCommand('extension:uninstall', \Ludelix\Core\Console\Commands\Extension\ExtensionUninstallCommand::class);

        // Connect Commands
        $this->registerCommand('connect', \Ludelix\Core\Console\Commands\Connect\ConnectCommand::class);
        $this->registerCommand('connect:build', \Ludelix\Core\Console\Commands\Connect\ConnectBuildCommand::class);
        $this->registerCommand('connect:dev', \Ludelix\Core\Console\Commands\Connect\ConnectDevCommand::class);
        $this->registerCommand('connect:install', \Ludelix\Core\Console\Commands\Connect\ConnectInstallCommand::class);

        // Help Commands
        $this->registerCommand('help', \Ludelix\Core\Console\Commands\Core\HelpCommand::class);
    }

    /**
     * Discover commands from the application
     */
    protected function discoverCommands(): void
    {
        $discoveredCommands = $this->discovery->discover();

        foreach ($discoveredCommands as $command) {
            $this->registerCommand($command['name'], $command['class']);
        }
    }

    /**
     * Load extensions
     */
    protected function loadExtensions(): void
    {
        $this->extensionLoader->load();
    }

    /**
     * Register commands with the engine
     */
    protected function registerCommandsWithEngine(): void
    {
        foreach ($this->commands as $name => $class) {
            $this->engine->registerCommand($name, $class);
        }
    }

    /**
     * Register a command
     *
     * @param string $name Command name
     * @param string $class Command class
     */
    public function registerCommand(string $name, string $class): void
    {
        $this->commands[$name] = $class;
    }

    /**
     * Register an extension
     *
     * @param string $name Extension name
     * @param array $data Extension data
     */
    public function registerExtension(string $name, array $data): void
    {
        $this->extensions[$name] = $data;
    }

    /**
     * Parse command line input
     *
     * @param array $argv Command line arguments
     * @return array Parsed input
     */
    protected function parseInput(array $argv): array
    {
        // Remove script name
        array_shift($argv);

        $command = $argv[0] ?? null;
        $arguments = array_slice($argv, 1);

        return [
            'command' => $command,
            'arguments' => $arguments,
            'raw' => $argv
        ];
    }

    /**
     * Handle special commands
     *
     * @param array $input Parsed input
     * @return bool True if handled
     */
    protected function handleSpecialCommands(array $input): bool
    {
        $command = $input['command'];

        if (!$command) {
            $this->showHelp();
            return true;
        }

        if (in_array($command, ['help', '--help', '-h'])) {
            $this->showHelp();
            return true;
        }

        if (in_array($command, ['list', '--list', '-l'])) {
            $this->listCommands();
            return true;
        }

        if (in_array($command, ['version', '--version', '-V', '-v'])) {
            $this->showVersion();
            return true;
        }

        return false;
    }

    /**
     * Show help information
     */
    protected function showHelp(): void
    {
        $this->help->showMainHelp();
    }

    /**
     * List all available commands
     */
    protected function listCommands(): void
    {
        $this->help->listCommands($this->commands, $this->extensions);
    }

    /**
     * Show version information
     */
    protected function showVersion(): void
    {
        $consoleVersion = \Ludelix\Core\Console\Version::get();
        $frameworkVersion = \Ludelix\Core\Version::get();

        echo "🏹 Mi - Ludelix Framework Console v" . $consoleVersion . "\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Framework Version: " . $frameworkVersion . "\n";
    }

    /**
     * Display error information
     *
     * @param \Throwable $e Exception
     */
    protected function displayError(\Throwable $e): void
    {
        echo "❌ Error: " . $e->getMessage() . "\n";

        if (getenv('MI_DEBUG')) {
            echo "\nStack trace:\n";
            echo $e->getTraceAsString() . "\n";
        }
    }

    /**
     * Get the engine instance
     *
     * @return MiEngine
     */
    public function getEngine(): MiEngine
    {
        return $this->engine;
    }

    /**
     * Get all registered commands
     *
     * @return array
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Get all loaded extensions
     *
     * @return array
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }
}