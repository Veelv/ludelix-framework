<?php

namespace Ludelix\Core\Console\Commands\Kria;

use Ludelix\Core\Console\Commands\Core\BaseCommand;
use Ludelix\Core\Console\Templates\Engine\TemplateEngine;

class KriaModuleCommand extends BaseCommand
{
    protected string $signature = 'kria:module <name> [--type=all] [--with-cache] [--with-events]';
    protected string $description = 'Create complete module with all components';
    
    protected TemplateEngine $templateEngine;
    protected array $config;

    public function __construct($container, $engine)
    {
        parent::__construct($container, $engine);
        
        $this->templateEngine = new TemplateEngine();
        $this->templateEngine->addPath('generators', __DIR__ . '/../../Templates/Generators');
        
        $this->config = [
            'base_path' => 'app',
            'namespace' => 'App'
        ];
    }

    public function execute(array $arguments, array $options): int
    {
        $name = $this->argument($arguments, 0);
        
        if (!$name) {
            $this->error("Usage: php mi kria:module <name> [--type=all]");
            return 1;
        }

        $type = $this->option($options, 'type', 'all');
        $withCache = $this->hasOption($options, 'with-cache');
        $withEvents = $this->hasOption($options, 'with-events');

        $this->info("Creating module: {$name}");
        $this->fireHook('kria.before_module', ['name' => $name, 'type' => $type]);

        $created = [];

        if ($type === 'all' || $type === 'repository') {
            $created[] = $this->createRepository($name, $withCache, $withEvents);
        }

        if ($type === 'all' || $type === 'service') {
            $created[] = $this->createService($name, $withEvents);
        }

        if ($type === 'all' || $type === 'entity') {
            $created[] = $this->createEntity($name);
        }

        if ($type === 'all' || $type === 'job') {
            $created[] = $this->createJob($name);
        }

        if ($type === 'all' || $type === 'console') {
            $created[] = $this->createConsole($name);
        }

        if ($type === 'all' || $type === 'middleware') {
            $created[] = $this->createMiddleware($name);
        }

        $this->success("Module {$name} created successfully!");
        $this->line("");
        $this->info("Files created:");
        foreach ($created as $file) {
            $this->line("  ✅ {$file}");
        }

        $this->fireHook('kria.after_module', ['name' => $name, 'files' => $created]);
        
        return 0;
    }

    protected function createRepository(string $name, bool $withCache = false, bool $withEvents = false): string
    {
        $className = $this->studly($name) . 'Repository';
        $filename = strtolower($name) . '.repository.php';
        $path = $this->config['base_path'] . '/Repositories/' . $filename;

        $variables = [
            'namespace' => $this->config['namespace'],
            'className' => $className,
            'entityClass' => $this->studly($name) . 'Entity',
            'withCache' => $withCache,
            'withEvents' => $withEvents,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $content = $this->templateEngine->render('repository', $variables);
        $this->writeFile($path, $content);
        
        return $path;
    }

    protected function createService(string $name, bool $withEvents = false): string
    {
        $className = $this->studly($name) . 'Service';
        $filename = strtolower($name) . '.service.php';
        $path = $this->config['base_path'] . '/Services/' . $filename;

        $variables = [
            'namespace' => $this->config['namespace'],
            'className' => $className,
            'repositoryClass' => $this->studly($name) . 'Repository',
            'entityName' => $name,
            'withEvents' => $withEvents,
            'withValidation' => true,
            'withSearch' => true,
            'withPagination' => true,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $content = $this->templateEngine->render('service', $variables);
        $this->writeFile($path, $content);
        
        return $path;
    }

    protected function createEntity(string $name): string
    {
        $className = $this->studly($name) . 'Entity';
        $filename = strtolower($name) . '.entity.php';
        $path = $this->config['base_path'] . '/Entities/' . $filename;

        $variables = [
            'namespace' => $this->config['namespace'],
            'className' => $className,
            'tableName' => strtolower($name) . 's',
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $content = $this->templateEngine->render('entity', $variables);
        $this->writeFile($path, $content);
        
        return $path;
    }

    protected function createJob(string $name): string
    {
        $className = $this->studly($name) . 'Job';
        $filename = strtolower($name) . '.job.php';
        $path = $this->config['base_path'] . '/Jobs/' . $filename;

        $variables = [
            'namespace' => $this->config['namespace'],
            'className' => $className,
            'entityName' => $name,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $content = $this->templateEngine->render('job', $variables);
        $this->writeFile($path, $content);
        
        return $path;
    }

    protected function createConsole(string $name): string
    {
        $className = $this->studly($name) . 'Command';
        $filename = strtolower($name) . '.console.php';
        $path = $this->config['base_path'] . '/Console/' . $filename;

        $variables = [
            'namespace' => $this->config['namespace'],
            'className' => $className,
            'commandName' => strtolower($name) . ':process',
            'entityName' => $name,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $content = $this->templateEngine->render('console', $variables);
        $this->writeFile($path, $content);
        
        return $path;
    }

    protected function createMiddleware(string $name): string
    {
        $className = $this->studly($name) . 'Middleware';
        $filename = strtolower($name) . '.middleware.php';
        $path = $this->config['base_path'] . '/Middleware/' . $filename;

        $variables = [
            'namespace' => $this->config['namespace'],
            'className' => $className,
            'entityName' => $name,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $content = $this->templateEngine->render('middleware', $variables);
        $this->writeFile($path, $content);
        
        return $path;
    }

    protected function writeFile(string $path, string $content): void
    {
        $directory = dirname($path);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        file_put_contents($path, $content);
        $this->fireHook('mi.file_generate', ['path' => $path, 'content' => $content]);
    }

    protected function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }
}