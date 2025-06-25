<?php

namespace Ludelix\Cache\Commands;

use Ludelix\Interface\Console\CommandInterface;

/**
 * Cache Clear Command
 * 
 * Clears application cache
 */
class CacheClearCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'cache:clear';
    }

    public function getDescription(): string
    {
        return 'Clear application cache';
    }

    public function execute(array $args = []): int
    {
        $cache = app('cache');
        
        $driver = $args['driver'] ?? null;
        
        if ($driver) {
            $cache->driver($driver)->flush();
            echo "Cache driver [{$driver}] cleared successfully.\n";
        } else {
            $cache->flush();
            echo "All cache cleared successfully.\n";
        }
        
        return 0;
    }

    public function getOptions(): array
    {
        return [
            'driver' => 'Specific cache driver to clear'
        ];
    }
}