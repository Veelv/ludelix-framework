<?php

namespace Ludelix\Optimization\Preloading;

/**
 * Preload List Generator
 * 
 * Analyzes application usage and generates optimal preload lists
 */
class PreloadListGenerator
{
    protected array $config;
    protected array $usageStats = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'stats_file' => 'cubby/cache/usage_stats.json',
            'min_usage_count' => 5,
            'analyze_dependencies' => true
        ], $config);
    }

    /**
     * Analyze application usage
     */
    public function analyze(string $logPath): array
    {
        $stats = $this->parseUsageLogs($logPath);
        $this->usageStats = $stats;
        
        $this->saveStats($stats);
        
        return $stats;
    }

    /**
     * Generate preload list based on usage
     */
    public function generateList(): array
    {
        $stats = $this->loadStats();
        
        if (empty($stats)) {
            return $this->getDefaultPreloadList();
        }

        $preloadList = [];

        // Add frequently used files
        foreach ($stats['files'] as $file => $count) {
            if ($count >= $this->config['min_usage_count']) {
                $preloadList[] = $file;
            }
        }

        // Add dependencies if enabled
        if ($this->config['analyze_dependencies']) {
            $preloadList = $this->addDependencies($preloadList);
        }

        // Sort by usage frequency
        usort($preloadList, function($a, $b) use ($stats) {
            $countA = $stats['files'][$a] ?? 0;
            $countB = $stats['files'][$b] ?? 0;
            return $countB - $countA;
        });

        return $preloadList;
    }

    /**
     * Parse usage logs
     */
    protected function parseUsageLogs(string $logPath): array
    {
        $stats = [
            'files' => [],
            'classes' => [],
            'functions' => [],
            'total_requests' => 0
        ];

        if (!file_exists($logPath)) {
            return $stats;
        }

        $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $data = json_decode($line, true);
            
            if (!$data) {
                continue;
            }

            $stats['total_requests']++;

            // Track file usage
            if (isset($data['included_files'])) {
                foreach ($data['included_files'] as $file) {
                    if (!isset($stats['files'][$file])) {
                        $stats['files'][$file] = 0;
                    }
                    $stats['files'][$file]++;
                }
            }

            // Track class usage
            if (isset($data['declared_classes'])) {
                foreach ($data['declared_classes'] as $class) {
                    if (!isset($stats['classes'][$class])) {
                        $stats['classes'][$class] = 0;
                    }
                    $stats['classes'][$class]++;
                }
            }
        }

        return $stats;
    }

    /**
     * Add file dependencies to preload list
     */
    protected function addDependencies(array $files): array
    {
        $dependencies = [];

        foreach ($files as $file) {
            $deps = $this->analyzeDependencies($file);
            $dependencies = array_merge($dependencies, $deps);
        }

        return array_unique(array_merge($files, $dependencies));
    }

    /**
     * Analyze file dependencies
     */
    protected function analyzeDependencies(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }

        $content = file_get_contents($file);
        $dependencies = [];

        // Find use statements
        if (preg_match_all('/^use\s+([^;]+);/m', $content, $matches)) {
            foreach ($matches[1] as $use) {
                $className = trim($use);
                $filePath = $this->classToFilePath($className);
                
                if ($filePath && file_exists($filePath)) {
                    $dependencies[] = $filePath;
                }
            }
        }

        // Find require/include statements
        if (preg_match_all('/(require|include)(?:_once)?\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
            foreach ($matches[2] as $includePath) {
                $fullPath = $this->resolveIncludePath($includePath, dirname($file));
                
                if ($fullPath && file_exists($fullPath)) {
                    $dependencies[] = $fullPath;
                }
            }
        }

        return $dependencies;
    }

    /**
     * Convert class name to file path
     */
    protected function classToFilePath(string $className): ?string
    {
        // Simple PSR-4 mapping
        $className = ltrim($className, '\\');
        $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
        
        // Try common paths
        $paths = [
            'src/' . $fileName,
            'app/' . $fileName,
            'vendor/' . $fileName
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return realpath($path);
            }
        }

        return null;
    }

    /**
     * Resolve include path
     */
    protected function resolveIncludePath(string $includePath, string $baseDir): ?string
    {
        if (str_starts_with($includePath, '/')) {
            return $includePath;
        }

        $fullPath = $baseDir . DIRECTORY_SEPARATOR . $includePath;
        return file_exists($fullPath) ? realpath($fullPath) : null;
    }

    /**
     * Get default preload list
     */
    protected function getDefaultPreloadList(): array
    {
        return [
            'src/Core/Framework.php',
            'src/Core/Container.php',
            'src/Bootstrap/Runtime/EnvironmentLoader.php',
            'src/Bootstrap/Runtime/ServiceRegistrar.php',
            'src/Routing/Router.php',
            'src/PRT/Request.php',
            'src/PRT/Response.php'
        ];
    }

    /**
     * Save usage statistics
     */
    protected function saveStats(array $stats): void
    {
        $this->ensureDirectory(dirname($this->config['stats_file']));
        file_put_contents($this->config['stats_file'], json_encode($stats, JSON_PRETTY_PRINT));
    }

    /**
     * Load usage statistics
     */
    protected function loadStats(): array
    {
        if (!file_exists($this->config['stats_file'])) {
            return [];
        }

        $content = file_get_contents($this->config['stats_file']);
        return json_decode($content, true) ?: [];
    }

    /**
     * Ensure directory exists
     */
    protected function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Get generation statistics
     */
    public function getStats(): array
    {
        $stats = $this->loadStats();
        
        return [
            'total_requests' => $stats['total_requests'] ?? 0,
            'tracked_files' => count($stats['files'] ?? []),
            'tracked_classes' => count($stats['classes'] ?? []),
            'stats_file' => $this->config['stats_file'],
            'stats_exists' => file_exists($this->config['stats_file'])
        ];
    }
}