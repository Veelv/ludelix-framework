<?php

namespace Ludelix\Core\Console\Commands\Kria;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Kria Resource Command - Create API Resource Transformer
 *
 * Creates a new resource transformer in app/Resources
 *
 * @package Ludelix\Core\Console\Commands\Kria
 */
class KriaResourceCommand extends BaseCommand
{
    /**
     * Command signature
     */
    protected string $signature = 'kria:resource <name>';

    /**
     * Command description
     */
    protected string $description = 'Create a new API resource transformer';

    /**
     * Execute resource creation command
     *
     * @param array $arguments Command arguments
     * @param array $options Command options
     * @return int Exit code
     */
    public function execute(array $arguments, array $options): int
    {
        $input = $this->argument($arguments, 0);

        if (!$input) {
            $this->error('Resource name is required');
            return 1;
        }

        $parts = explode('/', $input);
        $rawName = array_pop($parts);
        $dir = implode('/', $parts);

        $className = $this->normalizeClassName($rawName, 'Resource');
        $namespace = 'App\\Resources' . ($dir ? '\\' . $this->namespaceFromDir($dir) : '');
        $fileDir = 'app/Resources' . ($dir ? '/' . $dir : '');
        $resourceFile = $fileDir . '/' . $className . '.php';

        if (file_exists($resourceFile)) {
            $this->error("Resource '{$className}' already exists");
            return 1;
        }

        $this->ensureDirectoryExists($fileDir);

        $templatePath = __DIR__ . '/../Templates/Generators/resource.lux';
        if (!file_exists($templatePath)) {
            // Backup path check
            $templatePath = dirname(__DIR__) . '/Templates/Generators/resource.lux';
            if (!file_exists($templatePath)) {
                $this->error('Resource template not found: ' . $templatePath);
                return 1;
            }
        }

        $template = file_get_contents($templatePath);
        $replacements = [
            '{{namespace}}' => $namespace,
            '{{className}}' => $className,
        ];

        $content = str_replace(array_keys($replacements), array_values($replacements), $template);

        file_put_contents($resourceFile, $content);

        $this->success("Resource '{$className}' created successfully!");
        $this->line("Location: {$resourceFile}");

        return 0;
    }

    /**
     * Ensure directory exists
     *
     * @param string $path Directory path
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Normalize class name
     */
    protected function normalizeClassName(string $name, string $suffix): string
    {
        $name = preg_replace('/[^a-zA-Z0-9]/', '', $name);
        $name = ucfirst($name);
        if (!preg_match('/' . $suffix . '$/i', $name)) {
            $name .= $suffix;
        }
        return $name;
    }

    /**
     * Get namespace from directory path
     */
    protected function namespaceFromDir(string $dir): string
    {
        return implode('\\', array_map('ucfirst', explode('/', $dir)));
    }
}
