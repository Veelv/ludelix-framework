<?php

namespace Ludelix\Core\Console\Extensions\Discovery;

class PackageScanner
{
    public function scanComposerPackages(string $vendorPath): array
    {
        $packages = [];
        $installedFile = $vendorPath . '/composer/installed.json';

        if (!file_exists($installedFile)) {
            return $packages;
        }

        $installed = json_decode(file_get_contents($installedFile), true);
        
        // Handle both old and new composer formats
        $packageList = $installed['packages'] ?? $installed;

        foreach ($packageList as $package) {
            if (isset($package['name'])) {
                $package['path'] = $vendorPath . '/' . $package['name'];
                $packages[] = $package;
            }
        }

        return $packages;
    }

    public function scanPackageDirectory(string $packagePath): ?array
    {
        $composerFile = $packagePath . '/composer.json';
        
        if (!file_exists($composerFile)) {
            return null;
        }

        $composer = json_decode(file_get_contents($composerFile), true);
        
        if (!$composer) {
            return null;
        }

        $composer['path'] = $packagePath;
        return $composer;
    }

    public function isLudelixPackage(array $package): bool
    {
        // Check if it's a ludelix extension
        if (isset($package['type']) && $package['type'] === 'ludelix-extension') {
            return true;
        }

        // Check if it has ludelix extra configuration
        if (isset($package['extra']['ludelix'])) {
            return true;
        }

        // Check if package name starts with ludelix-
        if (isset($package['name']) && str_starts_with($package['name'], 'ludelix-')) {
            return true;
        }

        return false;
    }

    public function getPackageInfo(string $packagePath): ?array
    {
        $package = $this->scanPackageDirectory($packagePath);
        
        if (!$package) {
            return null;
        }

        return [
            'name' => $package['name'] ?? basename($packagePath),
            'version' => $package['version'] ?? 'dev-main',
            'description' => $package['description'] ?? '',
            'type' => $package['type'] ?? 'library',
            'authors' => $package['authors'] ?? [],
            'extra' => $package['extra'] ?? [],
            'path' => $packagePath,
            'autoload' => $package['autoload'] ?? []
        ];
    }

    public function validatePackage(array $package): array
    {
        $errors = [];

        if (empty($package['name'])) {
            $errors[] = 'Package name is required';
        }

        if (isset($package['extra']['ludelix'])) {
            $ludelix = $package['extra']['ludelix'];
            
            if (empty($ludelix['provider'])) {
                $errors[] = 'Ludelix provider class is required';
            } elseif (!class_exists($ludelix['provider'])) {
                $errors[] = "Provider class '{$ludelix['provider']}' does not exist";
            }
        }

        return $errors;
    }
}