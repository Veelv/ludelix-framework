<?php

namespace Ludelix\Asset\Config;

/**
 * Vite Configuration
 * 
 * Manages Vite integration configuration
 */
class ViteConfig
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'build_directory' => 'public/assets/build',
            'manifest_file' => 'public/assets/build/manifest.json',
            'hot_file' => 'public/hot',
            'dev_server_url' => 'http://localhost:5173',
            'entry_points' => ['js/app.js'],
            'css_entry_points' => ['css/app.css'],
            'public_directory' => 'public',
            'refresh_paths' => [
                'frontend/screens/**/*.ludou',
                'app/**/*.php'
            ]
        ], $config);
    }

    /**
     * Generate Vite config array
     */
    public function toArray(): array
    {
        return [
            'build' => [
                'outDir' => $this->config['build_directory'],
                'manifest' => true,
                'rollupOptions' => [
                    'input' => $this->getInputFiles()
                ]
            ],
            'server' => [
                'host' => '0.0.0.0',
                'port' => 5173,
                'hmr' => [
                    'host' => 'localhost'
                ]
            ],
            'plugins' => $this->getPlugins(),
            'resolve' => [
                'alias' => [
                    '@' => './frontend/js',
                    '@css' => './frontend/css',
                    '@images' => './frontend/images'
                ]
            ]
        ];
    }

    /**
     * Get input files for Vite
     */
    protected function getInputFiles(): array
    {
        return array_merge(
            $this->config['entry_points'],
            $this->config['css_entry_points']
        );
    }

    /**
     * Get Vite plugins configuration
     */
    protected function getPlugins(): array
    {
        return [
            'laravel' => [
                'input' => $this->config['entry_points'],
                'refresh' => $this->config['refresh_paths']
            ]
        ];
    }

    /**
     * Generate vite.config.js content
     */
    public function generateConfigFile(): string
    {
        $config = $this->toArray();
        
        return "import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
    plugins: [
        laravel({
            input: " . json_encode($config['plugins']['laravel']['input']) . ",
            refresh: " . json_encode($config['plugins']['laravel']['refresh']) . "
        })
    ],
    build: {
        outDir: '" . $config['build']['outDir'] . "',
        manifest: true,
        rollupOptions: {
            input: " . json_encode($config['build']['rollupOptions']['input']) . "
        }
    },
    server: {
        host: '" . $config['server']['host'] . "',
        port: " . $config['server']['port'] . ",
        hmr: {
            host: '" . $config['server']['hmr']['host'] . "'
        }
    },
    resolve: {
        alias: " . json_encode($config['resolve']['alias']) . "
    }
})";
    }

    /**
     * Check if Vite is running
     */
    public function isRunning(): bool
    {
        return file_exists($this->config['hot_file']);
    }

    /**
     * Get dev server URL
     */
    public function getDevServerUrl(): string
    {
        if ($this->isRunning()) {
            return trim(file_get_contents($this->config['hot_file']));
        }
        
        return $this->config['dev_server_url'];
    }

    /**
     * Get manifest path
     */
    public function getManifestPath(): string
    {
        return $this->config['manifest_file'];
    }

    /**
     * Get build directory
     */
    public function getBuildDirectory(): string
    {
        return $this->config['build_directory'];
    }

    /**
     * Add entry point
     */
    public function addEntryPoint(string $path): self
    {
        if (!in_array($path, $this->config['entry_points'])) {
            $this->config['entry_points'][] = $path;
        }
        
        return $this;
    }

    /**
     * Add CSS entry point
     */
    public function addCssEntryPoint(string $path): self
    {
        if (!in_array($path, $this->config['css_entry_points'])) {
            $this->config['css_entry_points'][] = $path;
        }
        
        return $this;
    }

    /**
     * Add refresh path
     */
    public function addRefreshPath(string $path): self
    {
        if (!in_array($path, $this->config['refresh_paths'])) {
            $this->config['refresh_paths'][] = $path;
        }
        
        return $this;
    }
}