<?php

namespace Ludelix\Asset\Core;

/**
 * Asset Compiler
 * 
 * Compiles and optimizes assets
 */
class Compiler
{
    protected array $config;
    protected array $compiled = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'source_path' => 'frontend',
            'output_path' => 'public/assets',
            'minify' => true,
            'source_maps' => false,
            'cache' => true
        ], $config);
    }

    /**
     * Compile CSS files
     */
    public function compileCss(string $source, string $output = null): string
    {
        $sourcePath = $this->config['source_path'] . '/' . ltrim($source, '/');
        $outputPath = $output ?? $this->config['output_path'] . '/' . $source;
        
        if (!file_exists($sourcePath)) {
            throw new \RuntimeException("CSS source file not found: {$sourcePath}");
        }

        $content = file_get_contents($sourcePath);
        
        // Process CSS
        $content = $this->processCss($content);
        
        // Minify if enabled
        if ($this->config['minify']) {
            $content = $this->minifyCss($content);
        }
        
        // Ensure output directory exists
        $this->ensureDirectory(dirname($outputPath));
        
        // Write compiled file
        file_put_contents($outputPath, $content);
        
        $this->compiled['css'][] = $outputPath;
        
        return $outputPath;
    }

    /**
     * Compile JavaScript files
     */
    public function compileJs(string $source, string $output = null): string
    {
        $sourcePath = $this->config['source_path'] . '/' . ltrim($source, '/');
        $outputPath = $output ?? $this->config['output_path'] . '/' . $source;
        
        if (!file_exists($sourcePath)) {
            throw new \RuntimeException("JS source file not found: {$sourcePath}");
        }

        $content = file_get_contents($sourcePath);
        
        // Process JavaScript
        $content = $this->processJs($content);
        
        // Minify if enabled
        if ($this->config['minify']) {
            $content = $this->minifyJs($content);
        }
        
        // Ensure output directory exists
        $this->ensureDirectory(dirname($outputPath));
        
        // Write compiled file
        file_put_contents($outputPath, $content);
        
        $this->compiled['js'][] = $outputPath;
        
        return $outputPath;
    }

    /**
     * Process CSS content
     */
    protected function processCss(string $content): string
    {
        // Remove comments
        $content = preg_replace('/\/\*.*?\*\//s', '', $content);
        
        // Process imports (basic)
        $content = preg_replace_callback('/@import\s+["\']([^"\']+)["\'];?/', function($matches) {
            $importPath = $this->config['source_path'] . '/' . $matches[1];
            if (file_exists($importPath)) {
                return file_get_contents($importPath);
            }
            return $matches[0];
        }, $content);
        
        return $content;
    }

    /**
     * Process JavaScript content
     */
    protected function processJs(string $content): string
    {
        // Remove single-line comments
        $content = preg_replace('/\/\/.*$/m', '', $content);
        
        // Remove multi-line comments
        $content = preg_replace('/\/\*.*?\*\//s', '', $content);
        
        return $content;
    }

    /**
     * Minify CSS
     */
    protected function minifyCss(string $content): string
    {
        // Remove whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Remove unnecessary spaces
        $content = str_replace(['; ', ' {', '{ ', ' }', '} ', ': '], [';', '{', '{', '}', '}', ':'], $content);
        
        // Remove trailing semicolons
        $content = str_replace(';}', '}', $content);
        
        return trim($content);
    }

    /**
     * Minify JavaScript
     */
    protected function minifyJs(string $content): string
    {
        // Basic minification - remove extra whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Remove spaces around operators
        $content = preg_replace('/\s*([{}();,=+\-*\/])\s*/', '$1', $content);
        
        return trim($content);
    }

    /**
     * Compile all assets
     */
    public function compileAll(): array
    {
        $results = [];
        
        // Find and compile CSS files
        $cssFiles = $this->findFiles($this->config['source_path'] . '/css', '*.css');
        foreach ($cssFiles as $file) {
            $relativePath = str_replace($this->config['source_path'] . '/', '', $file);
            $results['css'][] = $this->compileCss($relativePath);
        }
        
        // Find and compile JS files
        $jsFiles = $this->findFiles($this->config['source_path'] . '/js', '*.js');
        foreach ($jsFiles as $file) {
            $relativePath = str_replace($this->config['source_path'] . '/', '', $file);
            $results['js'][] = $this->compileJs($relativePath);
        }
        
        return $results;
    }

    /**
     * Find files by pattern
     */
    protected function findFiles(string $directory, string $pattern): array
    {
        if (!is_dir($directory)) {
            return [];
        }
        
        return glob($directory . '/' . $pattern);
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
     * Get compiled files
     */
    public function getCompiled(): array
    {
        return $this->compiled;
    }

    /**
     * Clear compiled files
     */
    public function clear(): void
    {
        $this->compiled = [];
        
        // Remove compiled files
        $buildPath = $this->config['output_path'];
        if (is_dir($buildPath)) {
            $files = glob($buildPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Generate manifest file
     */
    public function generateManifest(): string
    {
        $manifest = [];
        
        foreach ($this->compiled as $type => $files) {
            foreach ($files as $file) {
                $key = str_replace($this->config['output_path'] . '/', '', $file);
                $manifest[$key] = [
                    'file' => $key,
                    'src' => $key,
                    'isEntry' => true
                ];
            }
        }
        
        $manifestPath = $this->config['output_path'] . '/manifest.json';
        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));
        
        return $manifestPath;
    }
}