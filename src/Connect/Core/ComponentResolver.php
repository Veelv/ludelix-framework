<?php

namespace Ludelix\Connect\Core;

use Ludelix\Connect\Exceptions\ComponentNotFoundException;
use Ludelix\Cache\CacheManager;
use Psr\Log\LoggerInterface;

/**
 * Component Resolver - Advanced Component Discovery and Resolution
 * 
 * Provides sophisticated component resolution capabilities including:
 * - Multi-path component discovery with priority ordering
 * - Intelligent caching with invalidation strategies
 * - Framework-agnostic component detection (React, Vue, Svelte)
 * - Lazy loading and code splitting support
 * - Hot module replacement integration
 * - Component dependency analysis
 * - Performance optimization through predictive loading
 * 
 * @package Ludelix\Connect\Core
 * @author Ludelix Framework Team
 * @version 1.0.0
 */
class ComponentResolver
{
    protected ConnectManager $manager;
    protected CacheManager $cache;
    protected LoggerInterface $logger;
    protected array $config;
    
    protected array $componentCache = [];
    protected array $resolvedPaths = [];
    protected array $supportedExtensions = ['.jsx', '.tsx', '.vue', '.svelte', '.js', '.ts'];
    protected array $frameworkDetectors = [];

    public function __construct(
        ConnectManager $manager,
        CacheManager $cache,
        LoggerInterface $logger,
        array $config = []
    ) {
        $this->manager = $manager;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->config = $config;
        
        $this->initializeFrameworkDetectors();
    }

    /**
     * Check if component exists in any resolver path
     */
    public function exists(string $component): bool
    {
        $cacheKey = "connect.component.exists.{$component}";
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }
        
        $exists = $this->resolveComponentPath($component) !== null;
        
        $this->cache->put($cacheKey, $exists, 300); // 5 minutes cache
        
        return $exists;
    }

    /**
     * Resolve component to full file path
     */
    public function resolve(string $component): ?string
    {
        $cacheKey = "connect.component.path.{$component}";
        
        if ($cached = $this->cache->get($cacheKey)) {
            if (file_exists($cached)) {
                return $cached;
            }
            $this->cache->forget($cacheKey);
        }
        
        $path = $this->resolveComponentPath($component);
        
        if ($path) {
            $this->cache->put($cacheKey, $path, 3600); // 1 hour cache
        }
        
        return $path;
    }

    /**
     * Get component metadata including framework type
     */
    public function getMetadata(string $component): array
    {
        $path = $this->resolve($component);
        
        if (!$path) {
            throw new ComponentNotFoundException("Component '{$component}' not found");
        }
        
        $cacheKey = "connect.component.metadata.{$component}";
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }
        
        $metadata = [
            'path' => $path,
            'framework' => $this->detectFramework($path),
            'size' => filesize($path),
            'modified' => filemtime($path),
            'ssr_compatible' => $this->isSSRCompatible($path),
            'dependencies' => $this->analyzeDependencies($path),
        ];
        
        $this->cache->put($cacheKey, $metadata, 3600);
        
        return $metadata;
    }

    /**
     * Check if component supports SSR
     */
    public function supportsSSR(string $component): bool
    {
        try {
            $metadata = $this->getMetadata($component);
            return $metadata['ssr_compatible'] ?? false;
        } catch (ComponentNotFoundException $e) {
            return false;
        }
    }

    /**
     * Get all available components
     */
    public function getAllComponents(): array
    {
        $cacheKey = 'connect.components.all';
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }
        
        $components = [];
        
        foreach ($this->manager->getResolverPaths() as $pathConfig) {
            $components = array_merge($components, $this->scanDirectory($pathConfig['path']));
        }
        
        $components = array_unique($components);
        
        $this->cache->put($cacheKey, $components, 1800); // 30 minutes
        
        return $components;
    }

    /**
     * Preload components for performance
     */
    public function preloadComponents(array $components): void
    {
        foreach ($components as $component) {
            if (!isset($this->componentCache[$component])) {
                $this->componentCache[$component] = $this->resolve($component);
            }
        }
    }

    /**
     * Clear component resolution cache
     */
    public function clearCache(string $component = null): void
    {
        if ($component) {
            $this->cache->forget("connect.component.exists.{$component}");
            $this->cache->forget("connect.component.path.{$component}");
            $this->cache->forget("connect.component.metadata.{$component}");
            unset($this->componentCache[$component]);
        } else {
            $this->cache->flush();
            $this->componentCache = [];
        }
    }

    protected function resolveComponentPath(string $component): ?string
    {
        foreach ($this->manager->getResolverPaths() as $pathConfig) {
            $basePath = $pathConfig['path'];
            
            foreach ($this->supportedExtensions as $extension) {
                $fullPath = $basePath . '/' . $component . $extension;
                
                if (file_exists($fullPath)) {
                    return $fullPath;
                }
            }
        }
        
        return null;
    }

    protected function detectFramework(string $path): string
    {
        $content = file_get_contents($path);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        foreach ($this->frameworkDetectors as $framework => $detector) {
            if ($detector($content, $extension)) {
                return $framework;
            }
        }
        
        return 'unknown';
    }

    protected function isSSRCompatible(string $path): bool
    {
        $content = file_get_contents($path);
        
        // Check for browser-only APIs that break SSR
        $browserOnlyAPIs = [
            'window.',
            'document.',
            'localStorage',
            'sessionStorage',
            'navigator.',
        ];
        
        foreach ($browserOnlyAPIs as $api) {
            if (strpos($content, $api) !== false) {
                return false;
            }
        }
        
        return true;
    }

    protected function analyzeDependencies(string $path): array
    {
        $content = file_get_contents($path);
        $dependencies = [];
        
        // Extract import statements
        preg_match_all('/import\s+.*?\s+from\s+[\'"]([^\'"]+)[\'"]/', $content, $matches);
        
        if (!empty($matches[1])) {
            $dependencies = array_unique($matches[1]);
        }
        
        return $dependencies;
    }

    protected function scanDirectory(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }
        
        $components = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = '.' . $file->getExtension();
                
                if (in_array($extension, $this->supportedExtensions)) {
                    $relativePath = str_replace($directory . '/', '', $file->getPathname());
                    $componentName = pathinfo($relativePath, PATHINFO_FILENAME);
                    $components[] = $componentName;
                }
            }
        }
        
        return $components;
    }

    protected function initializeFrameworkDetectors(): void
    {
        $this->frameworkDetectors = [
            'react' => function($content, $extension) {
                return in_array($extension, ['jsx', 'tsx']) || 
                       strpos($content, 'React') !== false ||
                       strpos($content, 'jsx') !== false;
            },
            'vue' => function($content, $extension) {
                return $extension === 'vue' ||
                       strpos($content, '<template>') !== false ||
                       strpos($content, 'Vue') !== false;
            },
            'svelte' => function($content, $extension) {
                return $extension === 'svelte' ||
                       strpos($content, '<script>') !== false && 
                       strpos($content, '<style>') !== false;
            },
        ];
    }
}