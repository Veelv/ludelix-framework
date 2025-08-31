<?php

namespace Ludelix\Asset\Core;

/**
 * AssetManager - Comprehensive asset management solution
 * 
 * This class provides a robust solution for managing web assets including:
 * - Versioned URL generation
 * - Manifest-based asset resolution
 * - Build tool integration (Vite)
 * - Cache busting
 * - Secure asset delivery
 * 
 * @package Ludelix\Asset\Core
 * @author Your Name <your@email.com>
 * @copyright 2025 Ludelix Framework
 * @license MIT
 */
class AssetManager
{
    /**
     * Configuration settings for asset management
     *
     * @var array
     */
    protected array $config;

    /**
     * Parsed asset manifest data from build process
     *
     * @var array
     */
    protected array $manifest = [];

    /**
     * Normalized public path for file system operations
     *
     * @var string
     */
    protected string $publicPath;

    /**
     * AssetManager constructor.
     *
     * Merges provided configuration with defaults and initializes the asset manager.
     * Normalizes public path and loads the asset manifest.
     *
     * @param array $config Custom configuration settings
     */
    public function __construct(array $config = [])
    {
        // Merge default configuration with provided values
        $this->config = array_merge([
            'public_path' => 'public',
            'build_path' => 'public/assets/build',
            'manifest_file' => 'public/assets/build/manifest.json',
            'url_prefix' => '/assets',
            'version' => true,  // Enable cache-busting versioning
            'secure' => false   // Use HTTP by default
        ], $config);

        // Normalize public path
        $this->publicPath = rtrim($this->config['public_path'], '/');
        
        // Load the build manifest
        $this->loadManifest();
    }

    /**
     * Get the URL for an asset path.
     *
     * Resolves asset paths through multiple strategies:
     * 1. Check Vite manifest
     * 2. Check build directory
     * 3. Fallback to direct path with versioning
     *
     * @param string $path The asset path
     * @return string The resolved asset URL
     */
    public function asset(string $path): string
    {
        // Normalize the path
        $path = ltrim($path, '/');
        
        // Check if asset exists in Vite manifest
        if (isset($this->manifest[$path])) {
            return $this->url($this->manifest[$path]['file']);
        }

        // Check if file exists in build directory
        $buildFile = $this->findInBuild($path);
        if ($buildFile) {
            return $this->url($buildFile);
        }

        // Fallback to direct path with cache-busting version
        return $this->url($path, true);
    }

    /**
     * Generate CSS link tag(s) for an asset.
     *
     * Handles CSS imports from Vite manifest and generates appropriate
     * link tags for all required stylesheets.
     *
     * @param string $path The CSS asset path
     * @return string The HTML link tag(s)
     */
    public function css(string $path): string
    {
        $url = $this->asset($path);
        $html = '';
        
        // Add CSS imports from manifest if available
        $imports = $this->getCssImports($path);
        foreach ($imports as $import) {
            $html .= "<link rel=\"stylesheet\" href=\"{$this->url($import)}\">\n";
        }
        
        // Add main CSS file
        $html .= "<link rel=\"stylesheet\" href=\"{$url}\">";
        
        return $html;
    }

    /**
     * Generate JavaScript tag(s) for an asset.
     *
     * Handles module preloading and generates appropriate script tags
     * for modern JavaScript delivery.
     *
     * @param string $path The JS asset path
     * @return string The HTML script tag(s)
     */
    public function js(string $path): string
    {
        $url = $this->asset($path);
        $html = '';
        
        // Add modulepreload links for imports
        $imports = $this->getJsImports($path);
        foreach ($imports as $import) {
            $html .= "<link rel=\"modulepreload\" href=\"{$this->url($import)}\">\n";
        }
        
        // Add main script tag
        $html .= "<script type=\"module\" src=\"{$url}\"></script>";
        
        return $html;
    }

    /**
     * Generate image tag for an asset.
     *
     * Creates a properly formatted image tag with the resolved asset URL
     * and any additional HTML attributes.
     *
     * @param string $path The image asset path
     * @param array $attributes HTML attributes for the img tag
     * @return string The complete image tag
     */
    public function image(string $path, array $attributes = []): string
    {
        $url = $this->asset($path);
        $attrs = '';
        
        // Process and format HTML attributes
        foreach ($attributes as $key => $value) {
            $attrs .= " {$key}=\"" . htmlspecialchars($value) . "\"";
        }
        
        // Generate the image tag
        return "<img src=\"{$url}\"{$attrs}>";
    }

    /**
     * Generate a URL for an asset.
     *
     * Constructs a complete URL with optional versioning and secure protocol.
     *
     * @param string $path The asset path
     * @param bool $addVersion Whether to add cache-busting version parameter
     * @return string The complete asset URL
     */
    protected function url(string $path, bool $addVersion = false): string
    {
        // Normalize path
        $path = ltrim($path, '/');
        
        // Build base URL
        $url = $this->config['url_prefix'] . '/' . $path;
        
        // Add cache-busting version if enabled
        if ($addVersion && $this->config['version']) {
            $url .= '?v=' . $this->getVersion($path);
        }
        
        // Add protocol and host if needed
        if ($this->config['secure']) {
            return 'https://' . $_SERVER['HTTP_HOST'] . $url;
        }
        
        return $url;
    }

    /**
     * Generate cache-busting version hash for an asset.
     *
     * Creates a version hash based on file content for cache busting.
     * Falls back to path-based hash if file doesn't exist.
     *
     * @param string $path The asset path
     * @return string The version hash
     */
    protected function getVersion(string $path): string
    {
        $fullPath = $this->publicPath . '/assets/' . $path;
        
        // Generate version based on file content if file exists
        if (file_exists($fullPath)) {
            return substr(md5_file($fullPath), 0, 8);
        }
        
        // Fallback to path-based version
        return substr(md5($path), 0, 8);
    }

    /**
     * Load the Vite manifest file.
     *
     * Parses the Vite manifest JSON file to map asset paths to their
     * built versions with cache-busting hashes.
     */
    protected function loadManifest(): void
    {
        $manifestFile = $this->config['manifest_file'];
        
        if (file_exists($manifestFile)) {
            $content = file_get_contents($manifestFile);
            $manifest = json_decode($content, true);
            
            // Ensure we have a valid array result
            if (is_array($manifest)) {
                $this->manifest = $manifest;
            }
        }
    }

    /**
     * Locate an asset in the build directory.
     *
     * Attempts to find the asset file by testing common extensions.
     *
     * @param string $path The asset path to search for
     * @return string|null The build path if found, null otherwise
     */
    protected function findInBuild(string $path): ?string
    {
        $buildPath = $this->config['build_path'];
        $extensions = ['', '.js', '.css', '.min.js', '.min.css'];
        
        foreach ($extensions as $ext) {
            $file = $buildPath . '/' . $path . $ext;
            if (file_exists($file)) {
                return 'build/' . $path . $ext;
            }
        }
        
        return null;
    }

    /**
     * Get CSS dependencies from manifest for a given asset.
     *
     * @param string $path The asset path
     * @return array Array of CSS import paths
     */
    protected function getCssImports(string $path): array
    {
        if (!isset($this->manifest[$path]['css'])) {
            return [];
        }
        
        return $this->manifest[$path]['css'];
    }

    /**
     * Get JavaScript dependencies from manifest for a given asset.
     *
     * @param string $path The asset path
     * @return array Array of JS import paths
     */
    protected function getJsImports(string $path): array
    {
        if (!isset($this->manifest[$path]['imports'])) {
            return [];
        }
        
        return $this->manifest[$path]['imports'];
    }

    /**
     * Determine if the application is in development mode.
     *
     * Checks for Vite hot module replacement file or environment variable.
     *
     * @return bool True if in development mode, false otherwise
     */
    public function isDevelopment(): bool
    {
        return file_exists($this->publicPath . '/hot') || 
               (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development');
    }

    /**
     * Get the Vite development server URL.
     *
     * @return string The Vite dev server URL
     */
    public function getViteUrl(): string
    {
        $hotFile = $this->publicPath . '/hot';
        
        if (file_exists($hotFile)) {
            return trim(file_get_contents($hotFile));
        }
        
        return 'http://localhost:5173';
    }

    /**
     * Generate the Vite client script tag for development.
     *
     * @return string The Vite client script tag
     */
    public function viteClient(): string
    {
        if (!$this->isDevelopment()) {
            return '';
        }
        
        $viteUrl = $this->getViteUrl();
        return "<script type=\"module\" src=\"{$viteUrl}/@vite/client\"></script>";
    }

    /**
     * Laravel Mix compatibility method.
     *
     * Provides backward compatibility for Laravel Mix-style asset paths.
     *
     * @param string $path The asset path
     * @return string The resolved asset URL
     */
    public function mix(string $path): string
    {
        return $this->asset($path);
    }

    /**
     * Get all assets of a specific type from the manifest.
     *
     * @param string|null $type Filter by asset type (e.g., 'css', 'js')
     * @return array Array of asset records from the manifest
     */
    public function getAssets(string $type = null): array
    {
        if (!$type) {
            return $this->manifest;
        }
        
        $assets = [];
        foreach ($this->manifest as $key => $asset) {
            if (str_ends_with($key, '.' . $type)) {
                $assets[$key] = $asset;
            }
        }
        
        return $assets;
    }

    /**
     * Generate a preload link for an asset.
     *
     * @param string $path The asset path
     * @param string $as Type of asset to preload (script, style, etc.)
     * @return string The preload link tag
     */
    public function preload(string $path, string $as = 'script'): string
    {
        $url = $this->asset($path);
        return "<link rel=\"preload\" href=\"{$url}\" as=\"{$as}\">";
    }

    /**
     * Generate Subresource Integrity (SRI) hash for an asset.
     *
     * @param string $path The asset path
     * @return string|null The integrity hash or null if file doesn't exist
     */
    public function integrity(string $path): ?string
    {
        $fullPath = $this->publicPath . '/assets/' . ltrim($path, '/');
        
        if (file_exists($fullPath)) {
            $content = file_get_contents($fullPath);
            return 'sha384-' . base64_encode(hash('sha384', $content, true));
        }
        
        return null;
    }
}