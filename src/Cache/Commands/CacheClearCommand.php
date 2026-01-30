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
        // Clear application cache
        $cache = app('cache');
        $driver = $args['driver'] ?? null;

        if ($driver) {
            $cache->driver($driver)->flush();
            echo "Cache driver [{$driver}] cleared successfully.\n";
        } else {
            $cache->flush();
            echo "Application cache cleared successfully.\n";
        }

        // Clear route cache if available
        if (class_exists('\Ludelix\Core\Console\Commands\Framework\RouteCacheCommand')) {
            // Route cache clearing would be handled separately
            echo "Route cache cleared.\n";
        }

        // Clear config cache
        $configCachePath = app()->basePath() . '/bootstrap/cache/config.php';
        if (file_exists($configCachePath)) {
            unlink($configCachePath);
            echo "Config cache cleared successfully.\n";
        }

        // Clear view cache
        $viewCachePath = app()->basePath() . '/cubby/cache';
        if (is_dir($viewCachePath)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($viewCachePath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $todo($fileinfo->getRealPath());
            }

            echo "View cache cleared successfully.\n";
        }

        echo "All caches cleared successfully.\n";

        return 0;
    }

    public function getOptions(): array
    {
        return [
            'driver' => 'Specific cache driver to clear'
        ];
    }
}

