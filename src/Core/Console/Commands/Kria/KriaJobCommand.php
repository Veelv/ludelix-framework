<?php

namespace Ludelix\Core\Console\Commands\Kria;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Kria Job Command - Create Job
 *
 * Creates a new job in app/Jobs
 *
 * @package Ludelix\Core\Console\Commands\Kria
 */
class KriaJobCommand extends BaseCommand
{
    protected string $signature = 'kria:job <name>';
    protected string $description = 'Create a new job';

    public function execute(array $arguments, array $options): int
    {
        $input = $this->argument($arguments, 0);
        if (!$input) {
            $this->error('Job name is required');
            return 1;
        }
        $parts = explode('/', $input);
        $rawName = array_pop($parts);
        $dir = implode('/', $parts);
        $className = $this->normalizeClassName($rawName, 'Job');
        $namespace = 'App\\Jobs' . ($dir ? '\\' . $this->namespaceFromDir($dir) : '');
        $fileDir = 'app/Jobs' . ($dir ? '/' . $dir : '');
        $jobFile = $fileDir . '/' . $className . '.php';
        if (file_exists($jobFile)) {
            $this->error("Job '{$className}' already exists");
            return 1;
        }
        $this->ensureDirectoryExists($fileDir);
        $content = <<<PHP
<?php

namespace {$namespace};

class {$className}
{
    // Implement job logic here
}
PHP;
        file_put_contents($jobFile, $content);
        $this->success("Job '{$className}' created successfully!");
        $this->line("Location: {$jobFile}");
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