<?php

namespace Ludelix\Routing\Compilers;

use Ludelix\Routing\Core\RouteCollection;
use Ludelix\Routing\Exceptions\RouteCompilationException;
use Ludelix\Interface\Logging\LoggerInterface;

/**
 * Route Compiler - Advanced Route Compilation System
 * 
 * High-performance route compilation system that optimizes route matching
 * through intelligent compilation strategies and caching mechanisms.
 * 
 * @package Ludelix\Routing\Compilers
 * @author Ludelix Framework Team
 * @version 1.0.0
 */
class RouteCompiler
{
    protected LoggerInterface $logger;
    protected array $config;
    protected string $cacheDir;
    protected bool $debugMode;

    public function __construct(LoggerInterface $logger, array $config = [])
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->cacheDir = $config['cache_dir'] ?? sys_get_temp_dir() . '/ludelix/routes';
        $this->debugMode = $config['debug'] ?? false;
        
        $this->ensureCacheDirectory();
    }

    public function compile(RouteCollection $routes, array $options = []): bool
    {
        try {
            $startTime = microtime(true);
            
            // Compile routes to optimized PHP code
            $compiledCode = $this->generateCompiledRoutes($routes);
            
            // Write compiled routes to cache file
            $cacheFile = $this->getCacheFile($options);
            $success = file_put_contents($cacheFile, $compiledCode, LOCK_EX) !== false;
            
            $duration = microtime(true) - $startTime;
            
            $this->logger->info('Routes compiled successfully', [
                'route_count' => $routes->count(),
                'cache_file' => $cacheFile,
                'compilation_time' => $duration,
                'file_size' => filesize($cacheFile)
            ]);

            return $success;
            
        } catch (\Throwable $e) {
            $this->logger->error('Route compilation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new RouteCompilationException(
                'Failed to compile routes: ' . $e->getMessage(),
                '',
                0,
                $e
            );
        }
    }

    public function isCompiled(array $options = []): bool
    {
        $cacheFile = $this->getCacheFile($options);
        return file_exists($cacheFile);
    }

    public function getCacheFile(array $options = []): string
    {
        $hash = md5(serialize($options));
        return $this->cacheDir . "/routes_{$hash}.php";
    }

    public function clearCache(): bool
    {
        $pattern = $this->cacheDir . '/routes_*.php';
        $files = glob($pattern);
        
        $cleared = 0;
        foreach ($files as $file) {
            if (unlink($file)) {
                $cleared++;
            }
        }

        $this->logger->info('Route cache cleared', [
            'files_cleared' => $cleared
        ]);

        return $cleared > 0;
    }

    protected function generateCompiledRoutes(RouteCollection $routes): string
    {
        $code = "<?php\n\n";
        $code .= "// Auto-generated route cache file\n";
        $code .= "// Generated at: " . date('Y-m-d H:i:s') . "\n\n";
        
        $code .= "return [\n";
        $code .= "    'routes' => [\n";
        
        foreach ($routes->all() as $index => $route) {
            $code .= $this->compileRoute($route, $index);
        }
        
        $code .= "    ],\n";
        $code .= "    'named_routes' => [\n";
        
        foreach ($routes->all() as $route) {
            if ($name = $route->getName()) {
                $code .= "        " . var_export($name, true) . " => " . $this->getRouteIndex($route, $routes) . ",\n";
            }
        }
        
        $code .= "    ],\n";
        $code .= "    'compiled_at' => " . time() . ",\n";
        $code .= "];\n";

        return $code;
    }

    protected function compileRoute($route, int $index): string
    {
        $data = [
            'methods' => $route->getMethods(),
            'path' => $route->getPath(),
            'regex' => $route->getCompiledRegex(),
            'parameters' => $route->getParameterNames(),
            'handler' => $this->serializeHandler($route->getHandler()),
            'middleware' => $route->getMiddleware(),
            'name' => $route->getName(),
            'constraints' => $route->getConstraints(),
            'options' => $route->getOptions()
        ];

        $code = "        {$index} => [\n";
        foreach ($data as $key => $value) {
            $code .= "            " . var_export($key, true) . " => " . var_export($value, true) . ",\n";
        }
        $code .= "        ],\n";

        return $code;
    }

    protected function serializeHandler(mixed $handler): mixed
    {
        if (is_string($handler) || is_array($handler)) {
            return $handler;
        }

        if (is_callable($handler)) {
            // For closures, we need to serialize them (requires opis/closure or similar)
            // For now, we'll throw an exception
            throw new RouteCompilationException('Closures cannot be cached. Use controller classes instead.');
        }

        return $handler;
    }

    protected function getRouteIndex($route, RouteCollection $routes): int
    {
        foreach ($routes->all() as $index => $r) {
            if ($r === $route) {
                return $index;
            }
        }
        return -1;
    }

    protected function ensureCacheDirectory(): void
    {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
}