<?php

namespace Ludelix\Core\Console\Commands\Kria;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Kria Repository Command - Create Repository
 *
 * Creates a new repository in app/Repositories
 *
 * @package Ludelix\Core\Console\Commands\Kria
 */
class KriaRepositoryCommand extends BaseCommand
{
    /**
     * Command signature
     */
    protected string $signature = 'kria:repository <name> {--rest}';

    /**
     * Command description
     */
    protected string $description = 'Create a new repository (use --rest for REST API skeleton)';

    /**
     * Execute repository creation command
     *
     * @param array $arguments Command arguments
     * @param array $options Command options
     * @return int Exit code
     */
    public function execute(array $arguments, array $options): int
    {
        $input = $this->argument($arguments, 0);
        $isRest = $this->hasFlag($arguments, $options, 'rest');
        if (!$input) {
            $this->error('Repository name is required');
            return 1;
        }
        $parts = explode('/', $input);
        $rawName = array_pop($parts);
        $dir = implode('/', $parts);
        $className = $this->normalizeClassName($rawName, 'Repository');
        $namespace = 'App\\Repositories' . ($dir ? '\\' . $this->namespaceFromDir($dir) : '');
        $fileDir = 'app/Repositories' . ($dir ? '/' . $dir : '');
        $repoFile = $fileDir . '/' . $className . '.php';
        if (file_exists($repoFile)) {
            $this->error("Repository '{$className}' already exists");
            return 1;
        }
        $this->ensureDirectoryExists($fileDir);
        if ($isRest) {
            $templatePath = __DIR__ . '/../Templates/Generators/rest_repository.lux';
            if (!file_exists($templatePath)) {
                $this->error('REST repository template not found: ' . $templatePath);
                return 1;
            }
            $template = file_get_contents($templatePath);
            $replacements = [
                '{{namespace}}' => $namespace,
                '{{className}}' => $className,
            ];
            $content = str_replace(array_keys($replacements), array_values($replacements), $template);
        } else {
            $content = <<<PHP
<?php

namespace {$namespace};

class {$className}
{
    // Implement repository methods here
}
PHP;
        }
        file_put_contents($repoFile, $content);
        $this->success("Repository '{$className}' created successfully!");
        $this->line("Location: {$repoFile}");
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

    protected function hasFlag(array $arguments, array $options, string $key): bool
    {
        // Suporta tanto --rest quanto rest como argumento
        if (isset($options[$key]) && $options[$key]) {
            return true;
        }
        foreach ($arguments as $arg) {
            if (strtolower($arg) === $key) {
                return true;
            }
        }
        return false;
    }
}