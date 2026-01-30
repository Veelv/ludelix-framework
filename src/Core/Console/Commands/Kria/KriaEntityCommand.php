<?php

namespace Ludelix\Core\Console\Commands\Kria;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Kria Entity Command - Create Entity
 *
 * Creates a new entity in app/Entities
 *
 * @package Ludelix\Core\Console\Commands\Kria
 */
class KriaEntityCommand extends BaseCommand
{
    protected string $signature = 'kria:entity <name>';
    protected string $description = 'Create a new entity';

    public function execute(array $arguments, array $options): int
    {
        $input = $this->argument($arguments, 0);
        if (!$input) {
            $this->error('Entity name is required');
            return 1;
        }
        $parts = explode('/', $input);
        $rawName = array_pop($parts);
        $dir = implode('/', $parts);
        $className = $this->normalizeClassName($rawName, 'Entity');
        $namespace = 'App\\Entities' . ($dir ? '\\' . $this->namespaceFromDir($dir) : '');
        $fileDir = 'app/Entities' . ($dir ? '/' . $dir : '');
        $entityFile = $fileDir . '/' . $className . '.php';
        if (file_exists($entityFile)) {
            $this->error("Entity '{$className}' already exists");
            return 1;
        }
        $this->ensureDirectoryExists($fileDir);
        $templatePath = __DIR__ . '/../Templates/Generators/entity.lux';
        if (!file_exists($templatePath)) {
            $this->error('Entity template not found: ' . $templatePath);
            return 1;
        }
        $template = file_get_contents($templatePath);
        $replacements = [
            '{{namespace}}' => $namespace,
            '{{className}}' => $className,
        ];
        $content = str_replace(array_keys($replacements), array_values($replacements), $template);
        file_put_contents($entityFile, $content);
        $this->success("Entity '{$className}' created successfully!");
        $this->line("Location: {$entityFile}");
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