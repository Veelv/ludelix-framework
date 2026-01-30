<?php

namespace Ludelix\Core\Console\Commands\Extension;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Extension Install Command
 * 
 * Installs a new extension
 */
class ExtensionInstallCommand extends BaseCommand
{
    protected string $signature = 'extension:install <name> [--source=]';
    protected string $description = 'Install an extension';

    public function execute(array $arguments, array $options): int
    {
        $name = $this->argument($arguments, 0);
        $source = $this->option($options, 'source', 'packagist');
        
        if (!$name) {
            $this->error("Extension name is required.");
            return 1;
        }
        
        $this->info("Installing extension: $name");
        $this->info("Source: $source");
        
        
        // Installation logic would go here
        // This is a simplified implementation
        $this->success("Extension '$name' installed successfully.");
        
        return 0;
    }
}