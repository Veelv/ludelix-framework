<?php

namespace Ludelix\Optimization\Compiler;

/**
 * Ludou Template Compiler Optimizer
 * 
 * Optimizes and compiles Ludou templates for production
 */
class LudouCompiler
{
    protected array $config;
    protected array $compiledTemplates = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'cache_path' => 'cubby/screens',
            'minify' => true,
            'remove_comments' => true,
            'optimize_expressions' => true,
            'force_compile' => false
        ], $config);
    }

    /**
     * Compile all templates
     * 
     * @return array Array of compiled paths with stats ['compiled' => [], 'cached' => []]
     */
    public function compileAll(string $templatesPath): array
    {
        $templates = $this->findTemplates($templatesPath);
        $stats = [
            'compiled' => [],
            'cached' => [],
            'total' => 0
        ];

        foreach ($templates as $template) {
            $isCached = !$this->shouldRecompile($template, $this->getCachePath($template));

            $compiledPath = $this->compile($template);

            if ($isCached && !($this->config['force_compile'] ?? false)) {
                $stats['cached'][] = $template;
            } else {
                $stats['compiled'][] = $template;
            }
        }

        $stats['total'] = count($templates);
        $this->compiledTemplates = $stats['compiled']; // Update internal tracking

        return $stats;
    }

    /**
     * Compile single template
     * 
     * @param string $templatePath
     * @param bool $force Force recompilation regardless of cache
     * @return string Cache path
     */
    public function compile(string $templatePath, bool $force = false): string
    {
        $cachePath = $this->getCachePath($templatePath);

        // Smart Compilation: Skip if cache is fresh and not forced
        if (!$force && !$this->shouldRecompile($templatePath, $cachePath)) {
            return $cachePath;
        }

        $content = file_get_contents($templatePath);
        $optimized = $this->optimize($content);

        $this->ensureDirectory(dirname($cachePath));

        // Atomic write using temp file to prevent race conditions or half-written files
        $tempPath = $cachePath . '.tmp';
        file_put_contents($tempPath, $optimized);
        rename($tempPath, $cachePath);

        // Invalidate OPcache for this file if enabled
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($cachePath, true);
        }

        return $cachePath;
    }

    /**
     * Check if template needs recompilation
     */
    protected function shouldRecompile(string $templatePath, string $cachePath): bool
    {
        // If config forces compilation (e.g. dev mode)
        if ($this->config['force_compile'] ?? false) {
            return true;
        }

        // If cache doesn't exist
        if (!file_exists($cachePath)) {
            return true;
        }

        // If template was modified after cache
        return filemtime($templatePath) > filemtime($cachePath);
    }

    /**
     * Optimize template content
     */
    protected function optimize(string $content): string
    {
        // Remove comments
        if ($this->config['remove_comments']) {
            $content = $this->removeComments($content);
        }

        // Optimize expressions
        if ($this->config['optimize_expressions']) {
            $content = $this->optimizeExpressions($content);
        }

        // Minify
        if ($this->config['minify']) {
            $content = $this->minify($content);
        }

        return $content;
    }

    /**
     * Remove comments from template
     */
    protected function removeComments(string $content): string
    {
        // Remove HTML comments
        $content = preg_replace('/<!--.*?-->/s', '', $content);

        // Remove Ludou comments
        $content = preg_replace('/#\*.*?\*#/s', '', $content);

        return $content;
    }

    /**
     * Optimize expressions
     */
    protected function optimizeExpressions(string $content): string
    {
        // Cache static expressions
        $content = preg_replace_callback('/#\[([^\]]+)\]/', function ($matches) {
            $expression = $matches[1];

            // If expression is static, evaluate it
            if ($this->isStaticExpression($expression)) {
                return $this->evaluateStaticExpression($expression);
            }

            return $matches[0];
        }, $content);

        return $content;
    }

    /**
     * Minify template content
     */
    protected function minify(string $content): string
    {
        // Remove extra whitespace
        $content = preg_replace('/\s+/', ' ', $content);

        // Remove whitespace around tags
        $content = preg_replace('/>\s+</', '><', $content);

        // Trim
        $content = trim($content);

        return $content;
    }

    /**
     * Check if expression is static
     */
    protected function isStaticExpression(string $expression): bool
    {
        // Simple check for static expressions
        return !str_contains($expression, 'service(') &&
            !str_contains($expression, '$') &&
            !str_contains($expression, 'connect(');
    }

    /**
     * Evaluate static expression
     */
    protected function evaluateStaticExpression(string $expression): string
    {
        // Simple static evaluation
        if (preg_match("/^'([^']*)'$/", $expression, $matches)) {
            return $matches[1];
        }

        return $expression;
    }

    /**
     * Find all template files
     */
    protected function findTemplates(string $path): array
    {
        $templates = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'ludou') {
                $templates[] = $file->getPathname();
            }
        }

        return $templates;
    }

    /**
     * Get cache path for template
     */
    protected function getCachePath(string $templatePath): string
    {
        $hash = md5($templatePath);
        return $this->config['cache_path'] . '/' . $hash . '.php';
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
     * Clear compiled templates
     */
    public function clear(): void
    {
        $cachePath = $this->config['cache_path'];

        if (is_dir($cachePath)) {
            $files = glob($cachePath . '/*.php');
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    /**
     * Get compilation stats
     */
    public function getStats(): array
    {
        return [
            'compiled_templates' => count($this->compiledTemplates),
            'cache_path' => $this->config['cache_path'],
            'optimizations' => [
                'minify' => $this->config['minify'],
                'remove_comments' => $this->config['remove_comments'],
                'optimize_expressions' => $this->config['optimize_expressions']
            ]
        ];
    }
}