<?php

namespace Ludelix\Ludou\Support;

/**
 * Template Utility
 * 
 * Provides utility functions for template processing
 */
class Utility
{
    public static function parseTemplateName(string $template): array
    {
        $parts = explode('.', $template);
        $name = array_pop($parts);
        $namespace = implode('/', $parts);
        
        return [
            'namespace' => $namespace,
            'name' => $name,
            'full' => $template
        ];
    }

    public static function resolveTemplatePath(string $template, array $paths): ?string
    {
        $template = str_replace('.', '/', $template);
        
        foreach ($paths as $path) {
            $fullPath = rtrim($path, '/') . '/' . $template . '.ludou';
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }
        
        return null;
    }

    public static function extractVariables(string $template): array
    {
        preg_match_all('/\$(\w+)/', $template, $matches);
        return array_unique($matches[1] ?? []);
    }

    public static function minifyTemplate(string $template): string
    {
        // Remove extra whitespace but preserve structure
        $template = preg_replace('/\s+/', ' ', $template);
        $template = preg_replace('/>\s+</', '><', $template);
        return trim($template);
    }

    public static function generateCacheKey(string $templatePath, array $context = []): string
    {
        $data = [
            'path' => $templatePath,
            'mtime' => file_exists($templatePath) ? filemtime($templatePath) : 0,
            'context' => md5(serialize($context))
        ];
        
        return md5(serialize($data));
    }

    public static function isValidTemplateName(string $name): bool
    {
        return preg_match('/^[a-zA-Z0-9._\/\-]+$/', $name) === 1;
    }

    public static function normalizeLineEndings(string $content): string
    {
        return str_replace(["\r\n", "\r"], "\n", $content);
    }

    public static function getTemplateInfo(string $templatePath): array
    {
        if (!file_exists($templatePath)) {
            return [];
        }

        return [
            'path' => $templatePath,
            'size' => filesize($templatePath),
            'modified' => filemtime($templatePath),
            'readable' => is_readable($templatePath),
            'extension' => pathinfo($templatePath, PATHINFO_EXTENSION)
        ];
    }

    public static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}
