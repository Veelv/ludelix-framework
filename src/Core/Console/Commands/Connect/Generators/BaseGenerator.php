<?php

namespace Ludelix\Core\Console\Commands\Connect\Generators;

abstract class BaseGenerator
{
    protected string $projectRoot;
    protected array $options;

    public function __construct()
    {
        $this->projectRoot = getcwd();
    }

    abstract public function generate(array $options): void;

    protected function createDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    protected function writeFile(string $path, string $content): void
    {
        $this->createDirectory(dirname($path));
        file_put_contents($path, $content);
    }

    protected function updatePackageJson(array $dependencies, array $devDependencies = []): void
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

    protected function createViteConfig(string $framework, array $options): void
    {
        $config = $this->getViteConfig($framework, $options);
        $this->writeFile($this->projectRoot . '/vite.config.js', $config);
    }

    protected function createTailwindConfig(): void
    {
        $config = $this->getTailwindConfig();
        $this->writeFile($this->projectRoot . '/tailwind.config.js', $config);
    }

    protected function createTsConfig(): void
    {
        $config = $this->getTsConfig();
        $this->writeFile($this->projectRoot . '/tsconfig.json', $config);
    }

    protected function createPostCssConfig(): void
    {
        $config = $this->getPostCssConfig();
        $this->writeFile($this->projectRoot . '/postcss.config.js', $config);
    }

    protected function createCssFile(): void
    {
        $css = $this->getCssContent();
        $this->writeFile($this->projectRoot . '/frontend/css/app.css', $css);
    }

    abstract protected function getViteConfig(string $framework, array $options): string;
    abstract protected function getTailwindConfig(): string;
    abstract protected function getTsConfig(): string;
    abstract protected function getPostCssConfig(): string;
    abstract protected function getCssContent(): string;
} 