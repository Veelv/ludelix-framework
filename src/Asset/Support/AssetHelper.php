<?php

namespace Ludelix\Asset\Support;

use Ludelix\Asset\Core\AssetManager;

/**
 * Asset Helper
 * 
 * Helper functions for asset management
 */
class AssetHelper
{
    protected static ?AssetManager $manager = null;

    /**
     * Set asset manager
     */
    public static function setManager(AssetManager $manager): void
    {
        self::$manager = $manager;
    }

    /**
     * Get asset URL
     */
    public static function asset(string $path): string
    {
        return self::$manager?->asset($path) ?? $path;
    }

    /**
     * Get CSS asset
     */
    public static function css(string $path): string
    {
        return self::$manager?->css($path) ?? "<link rel=\"stylesheet\" href=\"{$path}\">";
    }

    /**
     * Get JS asset
     */
    public static function js(string $path): string
    {
        return self::$manager?->js($path) ?? "<script src=\"{$path}\"></script>";
    }

    /**
     * Get image asset
     */
    public static function image(string $path, array $attributes = []): string
    {
        return self::$manager?->image($path, $attributes) ?? "<img src=\"{$path}\">";
    }

    /**
     * Mix compatibility
     */
    public static function mix(string $path): string
    {
        return self::$manager?->mix($path) ?? $path;
    }

    /**
     * Vite client for development
     */
    public static function viteClient(): string
    {
        return self::$manager?->viteClient() ?? '';
    }

    /**
     * Preload asset
     */
    public static function preload(string $path, string $as = 'script'): string
    {
        return self::$manager?->preload($path, $as) ?? '';
    }

    /**
     * Check if development mode
     */
    public static function isDevelopment(): bool
    {
        return self::$manager?->isDevelopment() ?? false;
    }

    /**
     * Get asset with integrity
     */
    public static function secureAsset(string $path): array
    {
        if (!self::$manager) {
            return ['url' => $path, 'integrity' => null];
        }

        return [
            'url' => self::$manager->asset($path),
            'integrity' => self::$manager->integrity($path)
        ];
    }

    /**
     * Generate asset bundle
     */
    public static function bundle(array $assets, string $type = 'js'): string
    {
        $html = '';
        
        foreach ($assets as $asset) {
            if ($type === 'css') {
                $html .= self::css($asset) . "\n";
            } else {
                $html .= self::js($asset) . "\n";
            }
        }
        
        return $html;
    }

    /**
     * Asset exists check
     */
    public static function exists(string $path): bool
    {
        if (!self::$manager) {
            return false;
        }

        $publicPath = 'public/assets/' . ltrim($path, '/');
        return file_exists($publicPath);
    }

    /**
     * Get asset size
     */
    public static function size(string $path): ?int
    {
        if (!self::$manager) {
            return null;
        }

        $publicPath = 'public/assets/' . ltrim($path, '/');
        return file_exists($publicPath) ? filesize($publicPath) : null;
    }

    /**
     * Format file size
     */
    public static function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf("%.1f", $bytes / pow(1024, $factor)) . ' ' . $units[$factor];
    }
}