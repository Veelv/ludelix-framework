<?php

namespace Ludelix\Core\Console\Commands\Framework;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Config Clear Command
 *
 * Clear the configuration cache
 */
class ConfigClearCommand extends BaseCommand
{
    protected string $signature = 'config:clear';
    protected string $description = 'Clear the configuration cache';

    public function execute(array $arguments, array $options): int
    {
        $this->info('Clearing configuration cache...');
        
        // Configuration cache path
        $cachePath = getcwd() . '/cubby/cache/config.php';
        
        // Check if cache file exists
        if (!file_exists($cachePath)) {
            $this->info('Configuration cache is already clear.');
            return 0;
        }
        
        // Try to delete the cache file
        if (unlink($cachePath)) {
            $this->success('Configuration cache cleared successfully.');
            return 0;
        } else {
            $this->error('Failed to clear configuration cache.');
            return 1;
        }
    }
}