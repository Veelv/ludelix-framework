<?php

namespace Ludelix\Core\Console\Commands\Core;

use Ludelix\Core\Console\Support\HelpFormatter;

class HelpCommand extends BaseCommand
{
    protected string $signature = 'help [command]';
    protected string $description = 'Display help information for the console';

    public function execute(array $arguments, array $options): int
    {
        $commandName = $this->argument($arguments, 0);
        
        if (!$commandName) {
            // Show main help
            $this->showMainHelp();
            return 0;
        }
        
        // Show help for specific command
        $this->showCommandHelp($commandName);
        return 0;
    }
    
    /**
     * Show main help information
     */
    protected function showMainHelp(): void
    {
        $helpFormatter = new HelpFormatter();
        $helpFormatter->showMainHelp();
    }
    
    /**
     * Show help for a specific command
     * 
     * @param string $commandName Command name
     */
    protected function showCommandHelp(string $commandName): void
    {
        $commands = $this->engine->getRegistry()->all();
        
        if (!isset($commands[$commandName])) {
            $this->error("Command '$commandName' not found.");
            return;
        }
        
        $commandClass = $commands[$commandName];
        $helpFormatter = new HelpFormatter();
        $helpFormatter->showCommandHelp($commandName, $commandClass);
    }
}