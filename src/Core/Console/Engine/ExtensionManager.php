<?php

namespace Ludelix\Core\Console\Engine;

use Ludelix\Core\Console\Extensions\Discovery\ExtensionDiscovery;

class ExtensionManager
{
    protected MiEngine $engine;
    protected ExtensionDiscovery $discovery;
    protected array $loadedExtensions = [];
    protected array $config = [];

    public function __construct(MiEngine $engine)
    {
        $this->engine = $engine;
        $this->discovery = new ExtensionDiscovery();
        $this->loadConfig();
    }

    public function loadExtensions(): void
    {
        if (!$this->config['auto_discovery']) {
            return;
        }

        $extensions = $this->discovery->discover();
        
        foreach ($extensions as $extension) {
            if ($this->shouldLoadExtension($extension)) {
                $this->loadExtension($extension);
            }
        }
    }

    public function loadExtension(array $extensionData): bool
    {
        try {
            $providerClass = $extensionData['provider'];
            
            if (!class_exists($providerClass)) {
                return false;
            }

            $provider = new $providerClass();
            
            // Register extension
            $provider->register($this->engine);
            
            // Boot extension if method exists
            if (method_exists($provider, 'boot')) {
                $provider->boot();
            }

            $this->loadedExtensions[$extensionData['name']] = [
                'data' => $extensionData,
                'provider' => $provider
            ];

            $this->engine->getHooks()->fire('extension.loaded', $extensionData);
            
            return true;

        } catch (\Throwable $e) {
            error_log("Failed to load extension {$extensionData['name']}: " . $e->getMessage());
            return false;
        }
    }

    public function getLoadedExtensions(): array
    {
        return $this->loadedExtensions;
    }

    public function isExtensionLoaded(string $name): bool
    {
        return isset($this->loadedExtensions[$name]);
    }

    public function getExtension(string $name): ?array
    {
        return $this->loadedExtensions[$name] ?? null;
    }

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

    protected function loadConfig(): void
    {
        $this->config = [
            'auto_discovery' => true,
            'paths' => [
                'app/Extensions',
                'vendor/*/*/ludelix-extensions'
            ],
            'enabled' => [],
            'disabled' => [],
            'hooks' => [
                'enabled' => true,
                'priority' => 'extension'
            ]
        ];

        // Try to load config file
        $configFile = getcwd() . '/config/extensions.php';
        if (file_exists($configFile)) {
            $fileConfig = require $configFile;
            $this->config = array_merge($this->config, $fileConfig);
        }
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}