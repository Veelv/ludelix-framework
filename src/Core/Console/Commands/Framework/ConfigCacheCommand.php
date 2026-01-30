<?php

namespace Ludelix\Core\Console\Commands\Framework;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Config Cache Command
 *
 * Create a cache file for faster configuration loading
 */
class ConfigCacheCommand extends BaseCommand
{
    protected string $signature = 'config:cache';
    protected string $description = 'Create a cache file for faster configuration loading';

    public function execute(array $arguments, array $options): int
    {
        $this->info('Caching configuration...');
        
        // Configuration cache path
        $cachePath = getcwd() . '/cubby/cache/config.php';
        
        // Ensure cache directory exists
        $cacheDir = dirname($cachePath);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        // Load all configuration files
        $config = $this->loadConfiguration();
        
        // Serialize configuration
        $configData = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        
        // Write cache file
        if (file_put_contents($cachePath, $configData)) {
            $this->success('Configuration cached successfully.');
            return 0;
        } else {
            $this->error('Failed to cache configuration.');
            return 1;
        }
    }
    
    /**
     * Load all configuration files
     * 
     * @return array
     */
    protected function loadConfiguration(): array
    {
        $config = [];
        $configDir = getcwd() . '/config';
        
        if (!is_dir($configDir)) {
            return $config;
        }
        
        $files = glob($configDir . '/*.php');
        
        foreach ($files as $file) {
            $name = basename($file, '.php');
            $config[$name] = require $file;
        }
        
        return $config;
    }
}