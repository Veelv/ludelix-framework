<?php

namespace Ludelix\Cache\Commands;

use Ludelix\Interface\Console\CommandInterface;
use Ludelix\Core\Framework;
use Ludelix\Core\Console\Engine\MiEngine;
use Ludelix\Core\Console\Commands\Framework\RouteCacheCommand;

/**
 * Cache Clear Command
 * 
 * Clears application cache
 */
class CacheClearCommand implements CommandInterface
{
    private Framework $framework;
    private MiEngine $engine;

    public function __construct(Framework $framework, MiEngine $engine)
    {
        $this->framework = $framework;
        $this->engine = $engine;
    }

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
        $cache = $this->framework->container()->get('cache');
        $driver = $args['driver'] ?? null;
        
        if ($driver) {
            $cache->driver($driver)->flush();
            echo "Cache driver [{$driver}] cleared successfully.\n";
        } else {
            $cache->flush();
            echo "Application cache cleared successfully.\n";
        }

        // Clear route cache
        $routeCacheCommand = new RouteCacheCommand($this->framework->container(), $this->engine);
        $routeCacheCommand->execute([], ['clear' => true]);

        // Clear config cache
        $configCachePath = $this->framework->basePath() . ('/bootstrap/cache/config.php');
        if (file_exists($configCachePath)) {
            unlink($configCachePath);
            echo "Config cache cleared successfully.\n";
        }

        // Clear view cache
        $viewCachePath = $this->framework->basePath() . ('/cubby/cache');
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

