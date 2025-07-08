<?php

namespace Ludelix\Asset\Core;

/**
 * Asset Manager
 * 
 * Manages assets like CSS, JS, images with versioning and compilation
 */
class AssetManager
{
    protected array $config;
    protected array $manifest = [];
    protected string $publicPath;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'public_path' => 'public',
            'build_path' => 'public/assets/build',
            'manifest_file' => 'public/assets/build/manifest.json',
            'url_prefix' => '/assets',
            'version' => true,
            'secure' => false
        ], $config);

        $this->publicPath = rtrim($this->config['public_path'], '/');
        $this->loadManifest();
    }

    /**
     * Get asset URL
     */
    public function asset(string $path): string
    {
        // Remove leading slash
        $path = ltrim($path, '/');
        
        // Check if asset exists in manifest (Vite build)
        if (isset($this->manifest[$path])) {
            return $this->url($this->manifest[$path]['file']);
        }

        // Check if file exists in build directory
        $buildFile = $this->findInBuild($path);
        if ($buildFile) {
            return $this->url($buildFile);
        }

        // Fallback to direct path with versioning
        return $this->url($path, true);
    }

    /**
     * Get CSS asset
     */
    public function css(string $path): string
    {
        $url = $this->asset($path);
        
        // Add CSS imports from manifest if available
        $imports = $this->getCssImports($path);
        $html = '';
        
        foreach ($imports as $import) {
            $html .= "<link rel=\"stylesheet\" href=\"{$this->url($import)}\">\n";
        }
        
        $html .= "<link rel=\"stylesheet\" href=\"{$url}\">";
        
        return $html;
    }

    /**
     * Get JS asset
     */
    public function js(string $path): string
    {
        $url = $this->asset($path);
        
        // Add preload links for imports
        $imports = $this->getJsImports($path);
        $html = '';
        
        foreach ($imports as $import) {
            $html .= "<link rel=\"modulepreload\" href=\"{$this->url($import)}\">\n";
        }
        
        $html .= "<script type=\"module\" src=\"{$url}\"></script>";
        
        return $html;
    }

    /**
     * Get image asset
     */
    public function image(string $path, array $attributes = []): string
    {
        $url = $this->asset($path);
        $attrs = '';
        
        foreach ($attributes as $key => $value) {
            $attrs .= " {$key}=\"" . htmlspecialchars($value) . "\"";
        }
        
        return "<img src=\"{$url}\"{$attrs}>";
    }

    /**
     * Generate asset URL
     */
    protected function url(string $path, bool $addVersion = false): string
    {
        $path = ltrim($path, '/');
        $url = $this->config['url_prefix'] . '/' . $path;
        
        if ($addVersion && $this->config['version']) {
            $url .= '?v=' . $this->getVersion($path);
        }
        
        if ($this->config['secure']) {
            return 'https://' . $_SERVER['HTTP_HOST'] . $url;
        }
        
        return $url;
    }

    /**
     * Get file version for cache busting
     */
    protected function getVersion(string $path): string
    {
        $fullPath = $this->publicPath . '/assets/' . $path;
        
        if (file_exists($fullPath)) {
            return substr(md5_file($fullPath), 0, 8);
        }
        
        return substr(md5($path), 0, 8);
    }

    /**
     * Load Vite manifest
     */
    protected function loadManifest(): void
    {
        if (file_exists($this->config['manifest_file'])) {
            $content = file_get_contents($this->config['manifest_file']);
            $this->manifest = json_decode($content, true) ?: [];
        }
    }

    /**
     * Find asset in build directory
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
     * Get CSS imports from manifest
     */
    protected function getCssImports(string $path): array
    {
        if (!isset($this->manifest[$path]['css'])) {
            return [];
        }
        
        return $this->manifest[$path]['css'];
    }

    /**
     * Get JS imports from manifest
     */
    protected function getJsImports(string $path): array
    {
        if (!isset($this->manifest[$path]['imports'])) {
            return [];
        }
        
        return $this->manifest[$path]['imports'];
    }

    /**
     * Check if in development mode (Vite dev server)
     */
    public function isDevelopment(): bool
    {
        return file_exists($this->publicPath . '/hot') || 
               (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development');
    }

    /**
     * Get Vite dev server URL
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
     * Generate Vite client script for development
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
     * Mix function compatibility (Laravel Mix)
     */
    public function mix(string $path): string
    {
        return $this->asset($path);
    }

    /**
     * Get all assets of a type
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
     * Preload asset
     */
    public function preload(string $path, string $as = 'script'): string
    {
        $url = $this->asset($path);
        return "<link rel=\"preload\" href=\"{$url}\" as=\"{$as}\">";
    }

    /**
     * Generate integrity hash for asset
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