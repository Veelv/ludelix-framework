<?php

namespace Ludelix\Core\Console\Extensions\Discovery;

class ExtensionDiscovery
{
    protected array $searchPaths = [];
    protected PackageScanner $scanner;

    public function __construct()
    {
        $this->scanner = new PackageScanner();
        $this->initializeSearchPaths();
    }

    public function discover(): array
    {
        $extensions = [];

        // Discover from Composer packages
        $composerExtensions = $this->discoverComposerExtensions();
        $extensions = array_merge($extensions, $composerExtensions);

        // Discover from app directories
        $appExtensions = $this->discoverAppExtensions();
        $extensions = array_merge($extensions, $appExtensions);

        return $extensions;
    }

    protected function discoverComposerExtensions(): array
    {
        $extensions = [];
        $vendorPath = getcwd() . '/vendor';

        if (!is_dir($vendorPath)) {
            return $extensions;
        }

        $packages = $this->scanner->scanComposerPackages($vendorPath);
        
        foreach ($packages as $package) {
            if ($this->isLudelixExtension($package)) {
                $extension = $this->parseComposerExtension($package);
                if ($extension) {
                    $extensions[] = $extension;
                }
            }
        }

        return $extensions;
    }

    protected function discoverAppExtensions(): array
    {
        $extensions = [];
        
        foreach ($this->searchPaths as $path) {
            if (is_dir($path)) {
                $found = $this->scanDirectory($path);
                $extensions = array_merge($extensions, $found);
            }
        }

        return $extensions;
    }

    protected function scanDirectory(string $path): array
    {
        $extensions = [];
        $directories = glob($path . '/*', GLOB_ONLYDIR);

        foreach ($directories as $dir) {
            $configFile = $dir . '/extension.php';
            if (file_exists($configFile)) {
                $config = require $configFile;
                if ($this->isValidExtensionConfig($config)) {
                    $extensions[] = $this->parseAppExtension($config, $dir);
                }
            }
        }

        return $extensions;
    }

    protected function isLudelixExtension(array $package): bool
    {
        return isset($package['type']) && $package['type'] === 'ludelix-extension';
    }

    protected function parseComposerExtension(array $package): ?array
    {
        $extra = $package['extra']['ludelix'] ?? [];
        
        if (empty($extra['provider'])) {
            return null;
        }

        return [
            'name' => $package['name'],
            'version' => $package['version'] ?? '1.0.0',
            'description' => $package['description'] ?? '',
            'author' => $this->parseAuthor($package['authors'] ?? []),
            'provider' => $extra['provider'],
            'commands' => $extra['commands'] ?? [],
            'templates' => $extra['templates'] ?? '',
            'config' => $extra['config'] ?? '',
            'type' => 'composer',
            'path' => $package['path']
        ];
    }

    protected function parseAppExtension(array $config, string $path): array
    {
        return [
            'name' => $config['name'],
            'version' => $config['version'] ?? '1.0.0',
            'description' => $config['description'] ?? '',
            'author' => $config['author'] ?? 'Unknown',
            'provider' => $config['provider'],
            'commands' => $config['commands'] ?? [],
            'templates' => $config['templates'] ?? '',
            'config' => $config['config'] ?? '',
            'type' => 'app',
            'path' => $path
        ];
    }

    protected function isValidExtensionConfig(array $config): bool
    {
        return isset($config['name']) && 
               isset($config['provider']) && 
               class_exists($config['provider']);
    }

    protected function parseAuthor(array $authors): string
    {
        if (empty($authors)) {
            return 'Unknown';
        }

        $author = $authors[0];
        return is_array($author) ? ($author['name'] ?? 'Unknown') : (string) $author;
    }

    protected function initializeSearchPaths(): void
    {
        $this->searchPaths = [
            getcwd() . '/app/Extensions',
            getcwd() . '/extensions'
        ];
    }

    public function addSearchPath(string $path): void
    {
        if (!in_array($path, $this->searchPaths)) {
            $this->searchPaths[] = $path;
        }
    }

    public function getSearchPaths(): array
    {
        return $this->searchPaths;
    }
}