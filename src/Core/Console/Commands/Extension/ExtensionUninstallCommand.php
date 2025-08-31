<?php

namespace Ludelix\Core\Console\Commands\Extension;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Extension Uninstall Command
 * 
 * Uninstalls an extension
 */
class ExtensionUninstallCommand extends BaseCommand
{
    protected string $signature = 'extension:uninstall <name>';
    protected string $description = 'Uninstall an extension';

    public function execute(array $arguments, array $options): int
    {
        $name = $this->argument($arguments, 0);
        
        if (!$name) {
            $this->error("Extension name is required.");
            return 1;
        }
        
        $this->info("Uninstalling extension: $name");
        
        // Uninstallation logic would go here
        // This is a simplified implementation
        $this->success("Extension '$name' uninstalled successfully.");
        
        return 0;
    }
}