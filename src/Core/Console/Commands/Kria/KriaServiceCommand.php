<?php

namespace Ludelix\Core\Console\Commands\Kria;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Kria Service Command - Create Service
 *
 * Creates a new service in app/Services
 *
 * @package Ludelix\Core\Console\Commands\Kria
 */
class KriaServiceCommand extends BaseCommand
{
    protected string $signature = 'kria:service <name>';
    protected string $description = 'Create a new service';

    public function execute(array $arguments, array $options): int
    {
        $input = $this->argument($arguments, 0);
        if (!$input) {
            $this->error('Service name is required');
            return 1;
        }
        $parts = explode('/', $input);
        $rawName = array_pop($parts);
        $dir = implode('/', $parts);
        $className = $this->normalizeClassName($rawName, 'Service');
        $namespace = 'App\\Services' . ($dir ? '\\' . $this->namespaceFromDir($dir) : '');
        $fileDir = 'app/Services' . ($dir ? '/' . $dir : '');
        $serviceFile = $fileDir . '/' . $className . '.php';
        if (file_exists($serviceFile)) {
            $this->error("Service '{$className}' already exists");
            return 1;
        }
        $this->ensureDirectoryExists($fileDir);
        $templatePath = __DIR__ . '/../Templates/Generators/service.lux';
        if (!file_exists($templatePath)) {
            $this->error('Service template not found: ' . $templatePath);
            return 1;
        }
        $template = file_get_contents($templatePath);
        $replacements = [
            '{{namespace}}' => $namespace,
            '{{className}}' => $className,
            // Adicione outros placeholders se necessário
        ];
        $content = str_replace(array_keys($replacements), array_values($replacements), $template);
        file_put_contents($serviceFile, $content);
        $this->success("Service '{$className}' created successfully!");
        $this->line("Location: {$serviceFile}");
        return 0;
    }

    protected function normalizeClassName(string $name, string $suffix): string
    {
        $name = preg_replace('/[^a-zA-Z0-9]/', '', $name);
        $name = ucfirst($name);
        if (!preg_match('/' . $suffix . '$/i', $name)) {
            $name .= $suffix;
        }
        return $name;
    }

    protected function namespaceFromDir(string $dir): string
    {
        return implode('\\', array_map('ucfirst', explode('/', $dir)));
    }

    protected function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
} 