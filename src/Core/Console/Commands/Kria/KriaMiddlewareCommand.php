<?php

namespace Ludelix\Core\Console\Commands\Kria;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Kria Middleware Command - Create Middleware
 *
 * Creates a new middleware in app/Middleware
 *
 * @package Ludelix\Core\Console\Commands\Kria
 */
class KriaMiddlewareCommand extends BaseCommand
{
    protected string $signature = 'kria:middleware <name>';
    protected string $description = 'Create a new middleware';

    public function execute(array $arguments, array $options): int
    {
        $input = $this->argument($arguments, 0);
        if (!$input) {
            $this->error('Middleware name is required');
            return 1;
        }
        $parts = explode('/', $input);
        $rawName = array_pop($parts);
        $dir = implode('/', $parts);
        $className = $this->normalizeClassName($rawName, 'Middleware');
        $namespace = 'App\\Middleware' . ($dir ? '\\' . $this->namespaceFromDir($dir) : '');
        $fileDir = 'app/Middleware' . ($dir ? '/' . $dir : '');
        $middlewareFile = $fileDir . '/' . $className . '.php';
        if (file_exists($middlewareFile)) {
            $this->error("Middleware '{$className}' already exists");
            return 1;
        }
        $this->ensureDirectoryExists($fileDir);
        $content = <<<PHP
<?php

namespace {$namespace};

class {$className}
{
    // Implement middleware logic here
}
PHP;
        file_put_contents($middlewareFile, $content);
        $this->success("Middleware '{$className}' created successfully!");
        $this->line("Location: {$middlewareFile}");
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