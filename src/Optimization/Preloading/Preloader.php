<?php

namespace Ludelix\Optimization\Preloading;

/**
 * PHP Preloader
 * 
 * Generates and manages PHP preloading for performance optimization
 */
class Preloader
{
    protected array $config;
    protected array $preloadedFiles = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'preload_file' => 'preload.php',
            'include_vendor' => true,
            'include_framework' => true,
            'exclude_patterns' => ['*Test.php', '*test.php'],
            'max_files' => 1000
        ], $config);
    }

    /**
     * Generate preload script
     */
    public function generate(array $paths): string
    {
        $files = $this->collectFiles($paths);
        $script = $this->generateScript($files);
        
        file_put_contents($this->config['preload_file'], $script);
        
        return $this->config['preload_file'];
    }

    /**
     * Collect files to preload
     */
    protected function collectFiles(array $paths): array
    {
        $files = [];

        foreach ($paths as $path) {
            if (is_file($path)) {
                $files[] = $path;
            } elseif (is_dir($path)) {
                $files = array_merge($files, $this->scanDirectory($path));
            }
        }

        // Remove excluded files
        $files = $this->filterFiles($files);

        // Limit number of files
        if (count($files) > $this->config['max_files']) {
            $files = array_slice($files, 0, $this->config['max_files']);
        }

        return $files;
    }

    /**
     * Scan directory for PHP files
     */
    protected function scanDirectory(string $path): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Filter files based on exclude patterns
     */
    protected function filterFiles(array $files): array
    {
        $filtered = [];

        foreach ($files as $file) {
            $exclude = false;
            
            foreach ($this->config['exclude_patterns'] as $pattern) {
                if (fnmatch($pattern, basename($file))) {
                    $exclude = true;
                    break;
                }
            }
            
            if (!$exclude) {
                $filtered[] = $file;
            }
        }

        return $filtered;
    }

    /**
     * Generate preload script content
     */
    protected function generateScript(array $files): string
    {
        $script = "<?php\n\n";
        $script .= "// PHP Preload Script\n";
        $script .= "// Generated on " . date('Y-m-d H:i:s') . "\n";
        $script .= "// Total files: " . count($files) . "\n\n";
        
        $script .= "if (function_exists('opcache_compile_file')) {\n";
        
        foreach ($files as $file) {
            $script .= "    opcache_compile_file('" . addslashes($file) . "');\n";
        }
        
        $script .= "}\n\n";
        
        // Add require_once for critical files
        $script .= "// Preload critical files\n";
        foreach ($this->getCriticalFiles($files) as $file) {
            $script .= "require_once '" . addslashes($file) . "';\n";
        }

        return $script;
    }

    /**
     * Get critical files that should be required
     */
    protected function getCriticalFiles(array $files): array
    {
        $critical = [];

        foreach ($files as $file) {
            // Framework core files
            if (str_contains($file, 'Core/Framework.php') ||
                str_contains($file, 'Core/Container.php') ||
                str_contains($file, 'Bootstrap/')) {
                $critical[] = $file;
            }
        }

        return $critical;
    }

    /**
     * Enable preloading
     */
    public function enable(): bool
    {
        if (!function_exists('opcache_compile_file')) {
            return false;
        }

        if (!file_exists($this->config['preload_file'])) {
            return false;
        }

        include $this->config['preload_file'];
        return true;
    }

    /**
     * Check if preloading is available
     */
    public function isAvailable(): bool
    {
        return function_exists('opcache_compile_file') && 
               extension_loaded('opcache') &&
               ini_get('opcache.enable');
    }

    /**
     * Get preloading statistics
     */
    public function getStats(): array
    {
        $stats = [
            'available' => $this->isAvailable(),
            'preload_file' => $this->config['preload_file'],
            'file_exists' => file_exists($this->config['preload_file']),
            'opcache_enabled' => extension_loaded('opcache'),
            'opcache_status' => null
        ];

        if (function_exists('opcache_get_status')) {
            $stats['opcache_status'] = opcache_get_status();
        }

        return $stats;
    }

    /**
     * Clear preload cache
     */
    public function clear(): void
    {
        if (file_exists($this->config['preload_file'])) {
            unlink($this->config['preload_file']);
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
}