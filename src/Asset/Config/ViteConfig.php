<?php

namespace Ludelix\Asset\Config;

/**
 * ViteConfig - Manages Vite integration configuration
 * 
 * This class handles the configuration for Vite integration, including
 * build directories, manifest files, entry points, and development server settings.
 * 
 * @package Ludelix\Asset\Config
 */
class ViteConfig
{
    /**
     * The Vite configuration array
     *
     * @var array
     */
    protected array $config;

    /**
     * ViteConfig constructor.
     *
     * @param array $config The Vite configuration
     */
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
     * Generate Vite configuration array
     *
     * @return array The Vite configuration array
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
     * Get input files for Vite build
     *
     * @return array The input files
     */
    protected function getInputFiles(): array
    {
        $files = [];
        
        // Add JS entry points
        foreach ($this->config['entry_points'] as $entry) {
            $files[] = $this->config['public_directory'] . '/' . $entry;
        }
        
        // Add CSS entry points
        foreach ($this->config['css_entry_points'] as $entry) {
            $files[] = $this->config['public_directory'] . '/' . $entry;
        }
        
        return $files;
    }

    /**
     * Get Vite plugins configuration
     *
     * @return array The plugins configuration
     */
    protected function getPlugins(): array
    {
        return [
            // Add framework-specific plugins here
        ];
    }

    /**
     * Check if Vite is in development mode
     *
     * @return bool True if in development mode, false otherwise
     */
    public function isDevelopment(): bool
    {
        return file_exists($this->config['hot_file']);
    }

    /**
     * Get the development server URL
     *
     * @return string The development server URL
     */
    public function getDevServerUrl(): string
    {
        return $this->config['dev_server_url'];
    }

    /**
     * Get the manifest file path
     *
     * @return string The manifest file path
     */
    public function getManifestFile(): string
    {
        return $this->config['manifest_file'];
    }

    /**
     * Get the build directory path
     *
     * @return string The build directory path
     */
    public function getBuildDirectory(): string
    {
        return $this->config['build_directory'];
    }

    /**
     * Add an entry point to the configuration
     *
     * @param string $path The path to the entry point
     * @return $this
     */
    public function addEntryPoint(string $path): self
    {
        if (!in_array($path, $this->config['entry_points'])) {
            $this->config['entry_points'][] = $path;
        }
        
        return $this;
    }

    /**
     * Add a CSS entry point to the configuration
     *
     * @param string $path The path to the CSS entry point
     * @return $this
     */
    public function addCssEntryPoint(string $path): self
    {
        if (!in_array($path, $this->config['css_entry_points'])) {
            $this->config['css_entry_points'][] = $path;
        }
        
        return $this;
    }

    /**
     * Add a refresh path to the configuration
     *
     * @param string $path The path to watch for changes
     * @return $this
     */
    public function addRefreshPath(string $path): self
    {
        if (!in_array($path, $this->config['refresh_paths'])) {
            $this->config['refresh_paths'][] = $path;
        }
        
        return $this;
    }

    /**
     * Generate vite.config.js content
     *
     * @return string The Vite configuration file content
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
     * Check if Vite is running in development mode
     *
     * @return bool True if Vite is running, false otherwise
     */
    public function isRunning(): bool
    {
        return file_exists($this->config['hot_file']);
    }
}