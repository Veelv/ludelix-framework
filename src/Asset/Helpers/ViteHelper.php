<?php

namespace Ludelix\Asset\Helpers;

use Ludelix\Asset\Config\ViteConfig;

/**
 * ViteHelper - Professional Vite integration for Ludelix
 * 
 * Provides Laravel-like Vite functionality with Ludelix identity.
 * Handles both development (HMR) and production (manifest) modes.
 */
class ViteHelper
{
    protected ViteConfig $config;
    protected ?array $manifest = null;
    protected string $publicPath;

    public function __construct(array $config = [])
    {
        $this->config = new ViteConfig($config);
        $this->publicPath = $config['public_path'] ?? 'public';
        $this->loadManifest();
    }

    /**
     * Render Vite assets (main directive)
     * 
     * Usage: #[connect:vite ['frontend/src/main.tsx']]
     */
    public function renderVite(array $entries, array $options = []): string
    {
        if ($this->isDevelopment()) {
            return $this->renderDevelopment($entries, $options);
        }

        return $this->renderProduction($entries, $options);
    }

    /**
     * Render development mode (Vite dev server + HMR)
     */
    protected function renderDevelopment(array $entries, array $options): string
    {
        $viteUrl = $this->getViteUrl();
        $html = '';

        // Vite client for HMR
        $html .= "<script type=\"module\" src=\"{$viteUrl}/@vite/client\"></script>\n";

        // React Fast Refresh (if React detected)
        if ($this->isReact($entries)) {
            $html .= $this->renderReactRefresh($viteUrl);
        }

        // Entry points
        foreach ($entries as $entry) {
            $html .= "<script type=\"module\" src=\"{$viteUrl}/{$entry}\"></script>\n";
        }

        return $html;
    }

    /**
     * Render production mode (from manifest)
     */
    protected function renderProduction(array $entries, array $options): string
    {
        $html = '';
        $processedCss = [];

        foreach ($entries as $entry) {
            // Normalize entry path
            $entry = ltrim($entry, '/');

            if (!isset($this->manifest[$entry])) {
                continue;
            }

            $asset = $this->manifest[$entry];

            // Add CSS imports first
            if (isset($asset['css'])) {
                foreach ($asset['css'] as $css) {
                    if (!in_array($css, $processedCss)) {
                        $html .= "<link rel=\"stylesheet\" href=\"/{$css}\">\n";
                        $processedCss[] = $css;
                    }
                }
            }

            // Add JS file
            if (isset($asset['file'])) {
                $html .= "<script type=\"module\" src=\"/{$asset['file']}\"></script>\n";
            }

            // Add preload for imports
            if (isset($asset['imports'])) {
                foreach ($asset['imports'] as $import) {
                    if (isset($this->manifest[$import]['file'])) {
                        $file = $this->manifest[$import]['file'];
                        $html .= "<link rel=\"modulepreload\" href=\"/{$file}\">\n";
                    }
                }
            }
        }

        return $html;
    }

    /**
     * Render React Fast Refresh script
     */
    protected function renderReactRefresh(string $viteUrl): string
    {
        return <<<HTML
<script type="module">
import RefreshRuntime from '{$viteUrl}/@react-refresh'
RefreshRuntime.injectIntoGlobalHook(window)
window.\$RefreshReg$ = () => {}
window.\$RefreshSig$ = () => (type) => type
window.__vite_plugin_react_preamble_installed__ = true
</script>

HTML;
    }

    /**
     * Check if entries contain React files
     */
    protected function isReact(array $entries): bool
    {
        foreach ($entries as $entry) {
            if (str_ends_with($entry, '.tsx') || str_ends_with($entry, '.jsx')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if in development mode
     */
    public function isDevelopment(): bool
    {
        return file_exists($this->publicPath . '/hot');
    }

    /**
     * Get Vite dev server URL
     */
    protected function getViteUrl(): string
    {
        $hotFile = $this->publicPath . '/hot';

        if (file_exists($hotFile)) {
            return trim(file_get_contents($hotFile));
        }

        return $this->config->getDevServerUrl();
    }

    /**
     * Load Vite manifest
     */
    protected function loadManifest(): void
    {
        $manifestFile = $this->config->getManifestFile();

        if (file_exists($manifestFile)) {
            $content = file_get_contents($manifestFile);
            $this->manifest = json_decode($content, true) ?? [];
        }
    }
}
