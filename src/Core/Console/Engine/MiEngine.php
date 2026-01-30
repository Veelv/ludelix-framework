<?php

namespace Ludelix\Core\Console\Engine;

use Ludelix\Interface\DI\ContainerInterface;

class MiEngine
{
    protected $container;
    protected CommandRegistry $registry;
    protected ExtensionManager $extensions;
    protected InputParser $parser;
    protected OutputFormatter $output;
    protected HookSystem $hooks;

    public function __construct($container = null)
    {
        $this->container = $container;
        $this->registry = new CommandRegistry();
        $this->extensions = new ExtensionManager($this);
        $this->parser = new InputParser();
        $this->output = new OutputFormatter();
        $this->hooks = new HookSystem();

        $this->initialize();
    }

    public function run(array $argv): int
    {
        try {
            $input = $this->parser->parse($argv);

            if (empty($input['command'])) {
                $this->showHelp();
                return 0;
            }

            $this->hooks->fire('mi.before_command', $input);

            $result = $this->executeCommand($input);

            $this->hooks->fire('mi.after_command', ['input' => $input, 'result' => $result]);

            return $result;

        } catch (\Throwable $e) {
            $this->output->error("Error: " . $e->getMessage());
            return 1;
        }
    }

    public function registerCommand(string $name, string $class): void
    {
        $this->registry->register($name, $class);
    }

    public function registerTemplate(string $name, string $path): void
    {
        // Will implement template registration
    }

    public function hook(string $event, callable $callback): void
    {
        $this->hooks->listen($event, $callback);
    }

    protected function initialize(): void
    {
        $this->registerCoreCommands();
        $this->extensions->loadExtensions();
    }

    protected function registerCoreCommands(): void
    {
        // Kria commands
        $this->registry->register('kria:module', 'Ludelix\Core\Console\Commands\Kria\KriaModuleCommand');
        $this->registry->register('kria:repository', 'Ludelix\Core\Console\Commands\Kria\KriaRepositoryCommand');
        $this->registry->register('kria:service', 'Ludelix\Core\Console\Commands\Kria\KriaServiceCommand');
        $this->registry->register('kria:entity', 'Ludelix\Core\Console\Commands\Kria\KriaEntityCommand');
        $this->registry->register('kria:page', 'Ludelix\Core\Console\Commands\Kria\KriaPageCommand');
        $this->registry->register('kria:template', 'Ludelix\Core\Console\Commands\Kria\KriaTemplateCommand');

        // Framework commands
        $this->registry->register('about', 'Ludelix\Core\Console\Commands\Core\AboutCommand');
        $this->registry->register('start', 'Ludelix\Core\Console\Commands\Core\StartCommand');
        $this->registry->register('cache:clear', 'Ludelix\Core\Console\Commands\Framework\CacheCommand');

        // Evolution commands
        $this->registry->register('evolve:create', 'Ludelix\Core\Console\Commands\Evolution\EvolveCreateCommand');
        $this->registry->register('evolve:apply', 'Ludelix\Core\Console\Commands\Evolution\EvolveApplyCommand');
        $this->registry->register('evolve:status', 'Ludelix\Core\Console\Commands\Evolution\EvolveStatusCommand');
        $this->registry->register('evolve:revert', 'Ludelix\Core\Console\Commands\Evolution\EvolveRevertCommand');
        $this->registry->register('evolve:refresh', 'Ludelix\Core\Console\Commands\Evolution\EvolveRefreshCommand');

        // Seeder commands
        $this->registry->register('seed', 'Ludelix\Core\Console\Commands\Seeder\SeedCommand');
        $this->registry->register('seed:create', 'Ludelix\Core\Console\Commands\Seeder\SeedCreateCommand');
        $this->registry->register('seed:status', 'Ludelix\Core\Console\Commands\Seeder\SeedStatusCommand');
        $this->registry->register('seed:generate', 'Ludelix\Core\Console\Commands\Seeder\SeedGenerateCommand');

        // Tenant commands
        $this->registry->register('tenant:create', 'Ludelix\Tenant\Commands\TenantCreateCommand');
        $this->registry->register('tenant:list', 'Ludelix\Tenant\Commands\TenantListCommand');
        $this->registry->register('tenant:switch', 'Ludelix\Tenant\Commands\TenantSwitchCommand');
        $this->registry->register('tenant:stats', 'Ludelix\Tenant\Commands\TenantStatsCommand');

        // Extension commands
        $this->registry->register('extension:list', 'Ludelix\Core\Console\Commands\Extension\ExtensionListCommand');
    }

    protected function executeCommand(array $input): int
    {
        $command = $this->registry->get($input['command']);
        if (!$command) {
            $this->output->error("Command '{$input['command']}' not found.");
            return 1;
        }

        $instance = new $command($this->container, $this);
        return $instance->execute($input['arguments'], $input['options']);
    }

    protected function showHelp(): void
    {
        $this->output->title("🏹 Mi - Ludelix Framework Console");
        $this->output->line("");
        $this->output->info("Usage:");
        $this->output->line("  php mi <command> [arguments] [options]");
        $this->output->line("");

        $commands = $this->registry->all();
        $this->output->section("Available Commands:");
        foreach ($commands as $name => $class) {
            $this->output->line("  " . str_pad($name, 25));
        }

        $this->output->line("");
        $this->output->info("For help with a specific command:");
        $this->output->line("  php mi <command> --help");
    }

    public function getRegistry(): CommandRegistry
    {
        return $this->registry;
    }

    public function getHooks(): HookSystem
    {
        return $this->hooks;
    }

    public function getExtensionManager(): ExtensionManager
    {
        return $this->extensions;
    }
}