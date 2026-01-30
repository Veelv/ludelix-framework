<?php

namespace Ludelix\Cache\Commands;

use Ludelix\Interface\Console\CommandInterface;

/**
 * Cache Cleanup Command
 * 
 * Removes expired cache entries
 */
class CacheCleanupCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'cache:cleanup';
    }

    public function getDescription(): string
    {
        return 'Remove expired cache entries';
    }

    public function execute(array $args = []): int
    {
        $cache = app('cache');
        
        if (method_exists($cache->driver(), 'cleanup')) {
            $removed = $cache->driver()->cleanup();
            echo "Removed {$removed} expired cache entries.\n";
        } else {
            echo "Cache driver does not support cleanup.\n";
        }
        
        return 0;
    }

    public function getOptions(): array
    {
        return [];
    }
}