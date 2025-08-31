<?php

namespace Ludelix\Core\Console\Extensions;

use Ludelix\Core\Console\Mi;

/**
 * Extension Loader
 * 
 * Loads and manages extensions for the Mi console application.
 * Extensions can provide additional commands, hooks, and functionality.
 * 
 * @package Ludelix\Core\Console\Extensions
 */
class ExtensionLoader
{
    protected Mi $mi;
    protected array $extensions = [];
    protected array $config = [];

    public function __construct(Mi $mi)
    {
        $this->mi = $mi;
        $this->loadConfig();
    }

    /**
     * Load all extensions
     */
    public function load(): void
    {
        if (!$this->config['auto_discovery']) {
            return;
        }

        $extensions = $this->discoverExtensions();
        
        foreach ($extensions as $extension) {
            if ($this->shouldLoadExtension($extension)) {
                $this->loadExtension($extension);
            }
        }
    }

    /**
     * Discover extensions from various sources
     * 
     * @return array Array of extension data
     */
    protected function discoverExtensions(): array
    {
        $extensions = [];
        
        // Discover from composer packages
        $extensions = array_merge($extensions, $this->discoverFromComposer());
        
        // Discover from local extensions directory
        $extensions = array_merge($extensions, $this->discoverFromLocal());
        
        // Discover from vendor packages
        $extensions = array_merge($extensions, $this->discoverFromVendor());
        
        return $extensions;
    }

    /**
     * Discover extensions from composer packages
     * 
     * @return array Array of extension data
     */
    protected function discoverFromComposer(): array
    {
        $extensions = [];
        $composerFile = getcwd() . '/composer.json';
        
        if (!file_exists($composerFile)) {
            return $extensions;
        }
        
        $composer = json_decode(file_get_contents($composerFile), true);
        
        if (!isset($composer['require'])) {
            return $extensions;
        }
        
        foreach ($composer['require'] as $package => $version) {
            if (strpos($package, 'ludelix-') === 0) {
                $extension = $this->discoverPackageExtension($package);
                if ($extension) {
                    $extensions[] = $extension;
                }
            }
        }
        
        return $extensions;
    }

    /**
     * Discover extensions from local extensions directory
     * 
     * @return array Array of extension data
     */
    protected function discoverFromLocal(): array
    {
        $extensions = [];
        $extensionsDir = getcwd() . '/extensions';
        
        if (!is_dir($extensionsDir)) {
            return $extensions;
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extensionsDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getBasename() === 'extension.php') {
                $extension = $this->loadLocalExtension($file->getPathname());
                if ($extension) {
                    $extensions[] = $extension;
                }
            }
        }
        
        return $extensions;
    }

    /**
     * Discover extensions from vendor packages
     * 
     * @return array Array of extension data
     */
    protected function discoverFromVendor(): array
    {
        $extensions = [];
        $vendorDir = getcwd() . '/vendor';
        
        if (!is_dir($vendorDir)) {
            return $extensions;
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($vendorDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getBasename() === 'ludelix-extension.php') {
                $extension = $this->loadVendorExtension($file->getPathname());
                if ($extension) {
                    $extensions[] = $extension;
                }
            }
        }
        
        return $extensions;
    }

    /**
     * Discover extension from a composer package
     * 
     * @param string $package Package name
     * @return array|null Extension data or null
     */
    protected function discoverPackageExtension(string $package): ?array
    {
        $vendorDir = getcwd() . '/vendor/' . $package;
        
        if (!is_dir($vendorDir)) {
            return null;
        }
        
        $extensionFile = $vendorDir . '/ludelix-extension.php';
        
        if (file_exists($extensionFile)) {
            return $this->loadVendorExtension($extensionFile);
        }
        
        return null;
    }

    /**
     * Load a local extension
     * 
     * @param string $filePath Extension file path
     * @return array|null Extension data or null
     */
    protected function loadLocalExtension(string $filePath): ?array
    {
        try {
            $extension = require $filePath;
            
            if (!is_array($extension) || !isset($extension['name'])) {
                return null;
            }
            
            $extension['type'] = 'local';
            $extension['file'] = $filePath;
            
            return $extension;
            
        } catch (\Throwable $e) {
            error_log("Failed to load local extension {$filePath}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Load a vendor extension
     * 
     * @param string $filePath Extension file path
     * @return array|null Extension data or null
     */
    protected function loadVendorExtension(string $filePath): ?array
    {
        try {
            $extension = require $filePath;
            
            if (!is_array($extension) || !isset($extension['name'])) {
                return null;
            }
            
            $extension['type'] = 'vendor';
            $extension['file'] = $filePath;
            
            return $extension;
            
        } catch (\Throwable $e) {
            error_log("Failed to load vendor extension {$filePath}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Load a specific extension
     * 
     * @param array $extensionData Extension data
     * @return bool True if loaded successfully
     */
    protected function loadExtension(array $extensionData): bool
    {
        try {
            $name = $extensionData['name'];
            
            // Check if extension is already loaded
            if (isset($this->extensions[$name])) {
                return true;
            }
            
            // Load extension provider
            if (isset($extensionData['provider'])) {
                $providerClass = $extensionData['provider'];
                
                if (!class_exists($providerClass)) {
                    return false;
                }
                
                $provider = new $providerClass();
                
                // Register extension with Mi
                if (method_exists($provider, 'register')) {
                    $provider->register($this->mi);
                }
                
                // Boot extension if method exists
                if (method_exists($provider, 'boot')) {
                    $provider->boot();
                }
            }
            
            // Register commands
            if (isset($extensionData['commands'])) {
                foreach ($extensionData['commands'] as $commandName => $commandClass) {
                    $this->mi->registerCommand($commandName, $commandClass);
                }
            }
            
            // Register extension
            $this->extensions[$name] = $extensionData;
            $this->mi->registerExtension($name, $extensionData);
            
            return true;
            
        } catch (\Throwable $e) {
            error_log("Failed to load extension {$extensionData['name']}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if an extension should be loaded
     * 
     * @param array $extension Extension data
     * @return bool True if should be loaded
     */
    protected function shouldLoadExtension(array $extension): bool
    {
        $name = $extension['name'];
        
        // Check if explicitly disabled
        if (in_array($name, $this->config['disabled'])) {
            return false;
        }

        // Check if in enabled list (if list exists and is not empty)
        if (!empty($this->config['enabled']) && !in_array($name, $this->config['enabled'])) {
            return false;
        }

        return true;
    }

    /**
     * Load configuration
     */
    protected function loadConfig(): void
    {
        $this->config = [
            'auto_discovery' => true,
            'paths' => [
                'extensions',
                'vendor/*/*/ludelix-extensions'
            ],
            'enabled' => [],
            'disabled' => []
        ];

        // Try to load config file
        $configFile = getcwd() . '/config/extensions.php';
        if (file_exists($configFile)) {
            $fileConfig = require $configFile;
            $this->config = array_merge($this->config, $fileConfig);
        }
    }

    /**
     * Get loaded extensions
     * 
     * @return array Array of loaded extensions
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * Check if an extension is loaded
     * 
     * @param string $name Extension name
     * @return bool True if loaded
     */
    public function isLoaded(string $name): bool
    {
        return isset($this->extensions[$name]);
    }

    /**
     * Get extension data
     * 
     * @param string $name Extension name
     * @return array|null Extension data or null
     */
    public function getExtension(string $name): ?array
    {
        return $this->extensions[$name] ?? null;
    }

    /**
     * Get configuration
     * 
     * @return array Configuration array
     */
    public function getConfig(): array
    {
        return $this->config;
    }
} 