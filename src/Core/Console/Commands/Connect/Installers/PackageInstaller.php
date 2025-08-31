<?php

namespace Ludelix\Core\Console\Commands\Connect\Installers;

class PackageInstaller
{
    protected string $projectRoot;

    public function __construct()
    {
        $this->projectRoot = getcwd();
    }

    public function install(string $framework, array $options): void
    {
        $dependencies = $this->getDependencies($framework);
        $devDependencies = $this->getDevDependencies($framework, $options);

        $this->updatePackageJson($dependencies, $devDependencies);
    }

    protected function getDependencies(string $framework): array
    {
        $base = [
            'ludelix-connect' => '^1.0.0'
        ];

        return match($framework) {
            'react' => array_merge($base, [
                'react' => '^18.2.0',
                'react-dom' => '^18.2.0'
            ]),
            'vue' => array_merge($base, [
                'vue' => '^3.3.0'
            ]),
            'svelte' => array_merge($base, [
                'svelte' => '^4.0.0'
            ]),
            default => $base
        };
    }

    protected function getDevDependencies(string $framework, array $options): array
    {
        $base = [
            'vite' => '^4.4.0'
        ];

        // Framework-specific dev dependencies
        $frameworkDeps = match($framework) {
            'react' => [
                '@vitejs/plugin-react' => '^4.0.0'
            ],
            'vue' => [
                '@vitejs/plugin-vue' => '^4.2.0'
            ],
            'svelte' => [
                '@sveltejs/vite-plugin-svelte' => '^2.4.0'
            ],
            default => []
        };

        // TypeScript support
        if ($options['typescript']) {
            $base['typescript'] = '^5.0.0';
            
            if ($framework === 'react') {
                $base['@types/react'] = '^18.2.0';
                $base['@types/react-dom'] = '^18.2.0';
            }
        }

        // Tailwind CSS support
        if ($options['tailwind']) {
            $base['tailwindcss'] = '^3.3.0';
            $base['autoprefixer'] = '^10.4.0';
            $base['postcss'] = '^8.4.0';
        }

        return array_merge($base, $frameworkDeps);
    }

    protected function updatePackageJson(array $dependencies, array $devDependencies): void
    {
        $packagePath = $this->projectRoot . '/package.json';
        
        // Create clean package.json structure
        $package = [
            'name' => 'ludelix-app',
            'version' => '1.0.0',
            'type' => 'module',
            'scripts' => [
                'dev' => 'vite',
                'build' => 'vite build',
                'preview' => 'vite preview'
            ],
            'dependencies' => $dependencies,
            'devDependencies' => $devDependencies
        ];

        // Preserve existing package info if exists
        if (file_exists($packagePath)) {
            $existing = json_decode(file_get_contents($packagePath), true);
            if (isset($existing['name'])) $package['name'] = $existing['name'];
            if (isset($existing['version'])) $package['version'] = $existing['version'];
            if (isset($existing['description'])) $package['description'] = $existing['description'];
            if (isset($existing['private'])) $package['private'] = $existing['private'];
        }

        file_put_contents($packagePath, json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
} 