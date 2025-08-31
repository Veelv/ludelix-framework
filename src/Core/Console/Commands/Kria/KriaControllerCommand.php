<?php

namespace Ludelix\Core\Console\Commands\Kria;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Kria Controller Command - Create Controller
 *
 * Creates a new controller in app/Controllers
 *
 * @package Ludelix\Core\Console\Commands\Kria
 */
class KriaControllerCommand extends BaseCommand
{
    /**
     * Command signature
     */
    protected string $signature = 'kria:controller <name> {--rest}';

    /**
     * Command description
     */
    protected string $description = 'Create a new controller (use --rest for REST API skeleton)';

    /**
     * Execute controller creation command
     *
     * @param array $arguments Command arguments
     * @param array $options Command options
     * @return int Exit code
     */
    public function execute(array $arguments, array $options): int
    {
        $input = $this->argument($arguments, 0);
        $isRest = $this->option($options, 'rest', false);
        
        if (!$input) {
            $this->error('Controller name is required');
            return 1;
        }

        $parts = explode('/', $input);
        $rawName = array_pop($parts);
        $dir = implode('/', $parts);

        $className = $this->normalizeClassName($rawName, 'Controller');
        $namespace = 'App\\Controllers' . ($dir ? '\\' . $this->namespaceFromDir($dir) : '');
        $fileDir = 'app/Controllers' . ($dir ? '/' . $dir : '');
        $controllerFile = $fileDir . '/' . $className . '.php';

        if (file_exists($controllerFile)) {
            $this->error("Controller '{$className}' already exists");
            return 1;
        }

        $this->ensureDirectoryExists($fileDir);

        if ($isRest) {
            $templatePath = __DIR__ . '/../Templates/Generators/rest_controller.lux';
            if (!file_exists($templatePath)) {
                // Try alternative path
                $templatePath = __DIR__ . '/Templates/Generators/rest_controller.lux';
                if (!file_exists($templatePath)) {
                    $this->error('REST controller template not found: ' . $templatePath);
                    return 1;
                }
            }

            $template = file_get_contents($templatePath);
            $replacements = [
                '{{namespace}}' => $namespace,
                '{{className}}' => $className,
            ];

            $content = str_replace(array_keys($replacements), array_values($replacements), $template);
        } else {
            $templatePath = __DIR__ . '/../Templates/Generators/controller.lux';
            if (!file_exists($templatePath)) {
                // Try alternative path
                $templatePath = __DIR__ . '/Templates/Generators/controller.lux';
                if (!file_exists($templatePath)) {
                    $this->error('Controller template not found: ' . $templatePath);
                    return 1;
                }
            }

            $template = file_get_contents($templatePath);
            $replacements = [
                '{{namespace}}' => $namespace,
                '{{className}}' => $className,
            ];

            $content = str_replace(array_keys($replacements), array_values($replacements), $template);
        }

        file_put_contents($controllerFile, $content);

        $this->success("Controller '{$className}' created successfully!");
        $this->line("Location: {$controllerFile}");

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
}