<?php

namespace Ludelix\Core\Console\Commands\Kria;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Kria Console Command - Create Console Command
 *
 * Creates a new console command in app/Commands
 *
 * @package Ludelix\Core\Console\Commands\Kria
 */
class KriaConsoleCommand extends BaseCommand
{
    protected string $signature = 'kria:console <name>';
    protected string $description = 'Create a new console command';

    public function execute(array $arguments, array $options): int
    {
        $input = $this->argument($arguments, 0);
        if (!$input) {
            $this->error('Console command name is required');
            return 1;
        }
        $parts = explode('/', $input);
        $rawName = array_pop($parts);
        $dir = implode('/', $parts);
        $className = $this->normalizeClassName($rawName, 'Console');
        $namespace = 'App\\Commands' . ($dir ? '\\' . $this->namespaceFromDir($dir) : '');
        $fileDir = 'app/Commands' . ($dir ? '/' . $dir : '');
        $commandFile = $fileDir . '/' . $className . '.php';
        if (file_exists($commandFile)) {
            $this->error("Console command '{$className}' already exists");
            return 1;
        }
        $this->ensureDirectoryExists($fileDir);
        $content = <<<PHP
<?php

namespace {$namespace};

class {$className}
{
    // Implement console command logic here
}
PHP;
        file_put_contents($commandFile, $content);
        $this->success("Console command '{$className}' created successfully!");
        $this->line("Location: {$commandFile}");
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