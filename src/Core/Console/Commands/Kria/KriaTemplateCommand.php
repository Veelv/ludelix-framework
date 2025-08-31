<?php

namespace Ludelix\Core\Console\Commands\Kria;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Kria Template Command - Create Complete Template Structure
 * 
 * Creates a complete template with page, repository, model, service and route
 * 
 * @package Ludelix\Core\Console\Commands\Kria
 * @author Ludelix Framework Team
 * @version 2.0.0
 * @since 1.0.0
 */
class KriaTemplateCommand extends BaseCommand
{
    /**
     * Command signature
     */
    protected string $signature = 'kria:template <name>';

    /**
     * Command description
     */
    protected string $description = 'Create complete template structure (page, model, repository, service, route)';

    /**
     * Execute template creation command
     * 
     * @param array $arguments Command arguments
     * @param array $options Command options
     * @return int Exit code
     */
    public function execute(array $arguments, array $options): int
    {
        $input = $this->argument($arguments, 0);
        if (!$input) {
            $this->error('Template name is required');
            return 1;
        }
        $parts = explode('/', $input);
        $rawName = array_pop($parts);
        $dir = implode('/', $parts);
        $templateName = $rawName; // Para página, manter como usuário digitou
        $entityName = $this->normalizeClassName($rawName, 'Entity');
        $repoName = $this->normalizeClassName($rawName, 'Repository');
        $serviceName = $this->normalizeClassName($rawName, 'Service');
        $namespaceDir = $dir ? $this->namespaceFromDir($dir) : '';
        $entityNamespace = 'App\\Entities' . ($namespaceDir ? '\\' . $namespaceDir : '');
        $repoNamespace = 'App\\Repositories' . ($namespaceDir ? '\\' . $namespaceDir : '');
        $serviceNamespace = 'App\\Services' . ($namespaceDir ? '\\' . $namespaceDir : '');
        $entityDir = 'app/Entities' . ($dir ? '/' . $dir : '');
        $repoDir = 'app/Repositories' . ($dir ? '/' . $dir : '');
        $serviceDir = 'app/Services' . ($dir ? '/' . $dir : '');
        if ($this->checkExistingComponents($templateName, $entityDir, $entityName, $repoDir, $repoName, $serviceDir, $serviceName)) {
            return 1;
        }
        $this->createPage($templateName, $dir);
        $this->createEntity($entityName, $entityDir, $entityNamespace);
        $this->createRepository($repoName, $repoDir, $repoNamespace);
        $this->createService($serviceName, $serviceDir, $serviceNamespace);
        $this->addRoute($templateName, $repoName, $repoNamespace, $dir);
        $this->line('');
        $this->success("Template '{$templateName}' created successfully!");
        $this->displayCreatedFiles($templateName, $entityDir, $entityName, $repoDir, $repoName, $serviceDir, $serviceName);
        return 0;
    }

    /**
     * Format name
     * 
     * @param string $name Raw name
     * @return string Formatted name
     */
    protected function formatName(string $name): string
    {
        return ucfirst(strtolower($name));
    }

    /**
     * Normalize class name
     * 
     * @param string $name Raw name
     * @param string $suffix Suffix to add if not present
     * @return string Normalized class name
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
     * Namespace from directory
     * 
     * @param string $dir Directory path
     * @return string Namespace
     */
    protected function namespaceFromDir(string $dir): string
    {
        return implode('\\', array_map('ucfirst', explode('/', $dir)));
    }

    /**
     * Check if components already exist
     * 
     * @param string $name Template name
     * @param string $entityDir Entity directory
     * @param string $entityName Entity name
     * @param string $repoDir Repository directory
     * @param string $repoName Repository name
     * @param string $serviceDir Service directory
     * @param string $serviceName Service name
     * @return bool True if any component exists
     */
    protected function checkExistingComponents(string $name, string $entityDir, string $entityName, string $repoDir, string $repoName, string $serviceDir, string $serviceName): bool
    {
        $components = [
            'Page' => "frontend/templates/screens/{$name}.ludou",
            'Entity' => $entityDir . '/' . $entityName . '.php',
            'Repository' => $repoDir . '/' . $repoName . '.php',
            'Service' => $serviceDir . '/' . $serviceName . '.php',
        ];
        $existing = [];
        foreach ($components as $type => $path) {
            if (file_exists($path)) {
                $existing[] = "{$type}: {$path}";
            }
        }
        if (!empty($existing)) {
            $this->error('The following components already exist:');
            foreach ($existing as $item) {
                $this->line("  - {$item}");
            }
            return true;
        }
        return false;
    }

    /**
     * Create page component
     * 
     * @param string $name Template name
     * @param string $dir Directory
     */
    protected function createPage(string $name, string $dir = ''): void
    {
        $this->line("Creating page...");
        $pageName = $name;
        $pageDir = 'frontend/templates/screens' . ($dir ? '/' . $dir : '');
        $pageFile = $pageDir . '/' . $pageName . '.ludou';
        $this->ensureDirectoryExists($pageDir);
        $templatePath = __DIR__ . '/../Templates/Generators/page.lux';
        if (!file_exists($templatePath)) {
            $this->error('Page template not found: ' . $templatePath);
            return;
        }
        $template = file_get_contents($templatePath);
        $replacements = [
            '{{title}}' => $pageName,
        ];
        $content = str_replace(array_keys($replacements), array_values($replacements), $template);
        file_put_contents($pageFile, $content);
    }

    /**
     * Create entity component
     * 
     * @param string $className Entity class name
     * @param string $dir Entity directory
     * @param string $namespace Entity namespace
     */
    protected function createEntity(string $className, string $dir, string $namespace): void
    {
        $this->line("Creating entity...");
        $entityFile = $dir . '/' . $className . '.php';
        $this->ensureDirectoryExists($dir);
        $content = <<<PHP
<?php

namespace {$namespace};

class {$className}
{
    // Define entity properties and methods here
}
PHP;
        file_put_contents($entityFile, $content);
    }

    /**
     * Create repository component
     * 
     * @param string $className Repository class name
     * @param string $dir Repository directory
     * @param string $namespace Repository namespace
     */
    protected function createRepository(string $className, string $dir, string $namespace): void
    {
        $this->line("Creating repository...");
        $repoFile = $dir . '/' . $className . '.php';
        $this->ensureDirectoryExists($dir);
        $templatePath = __DIR__ . '/../Templates/Generators/rest_repository.lux';
        if (!file_exists($templatePath)) {
            $this->error('REST repository template not found: ' . $templatePath);
            return;
        }
        $template = file_get_contents($templatePath);
        $replacements = [
            '{{namespace}}' => $namespace,
            '{{className}}' => $className,
        ];
        $content = str_replace(array_keys($replacements), array_values($replacements), $template);
        file_put_contents($repoFile, $content);
    }

    /**
     * Create service component
     * 
     * @param string $className Service class name
     * @param string $dir Service directory
     * @param string $namespace Service namespace
     */
    protected function createService(string $className, string $dir, string $namespace): void
    {
        $this->line("Creating service...");
        $serviceFile = $dir . '/' . $className . '.php';
        $this->ensureDirectoryExists($dir);
        $content = <<<PHP
<?php

namespace {$namespace};

class {$className}
{
    // Implement service methods here
}
PHP;
        file_put_contents($serviceFile, $content);
    }

    /**
     * Add route to web.php
     * 
     * @param string $templateName Template name
     * @param string $repoName Repository name
     * @param string $repoNamespace Repository namespace
     * @param string $dir Directory
     */
    protected function addRoute(string $templateName, string $repoName, string $repoNamespace, string $dir = ''): void
    {
        $this->line("Adding routes...");
        $routeWebFile = 'routes/web.php';
        $routeApiFile = 'routes/api.php';
        $routeName = strtolower($templateName);
        $repoClass = $repoNamespace . '\\' . $repoName;
        // Rota web
        if (file_exists($routeWebFile)) {
            $webRoute = "\n// {$templateName} Web Route\nBridge::route()->get('/{$routeName}', [{$repoClass}::class, 'index'])->name('{$routeName}.index');\n";
            file_put_contents($routeWebFile, $webRoute, FILE_APPEND);
        } else {
            $this->warning("Web route file not found: {$routeWebFile}");
        }
        // Rota API resourceful
        if (file_exists($routeApiFile)) {
            $apiRoute = "\n// {$templateName} API Resource\nBridge::route()->apiResource('{$routeName}', {$repoClass}::class);\n";
            file_put_contents($routeApiFile, $apiRoute, FILE_APPEND);
        } else {
            $this->warning("API route file not found: {$routeApiFile}");
        }
    }

    /**
     * Display created files
     * 
     * @param string $templateName Template name
     * @param string $entityDir Entity directory
     * @param string $entityName Entity name
     * @param string $repoDir Repository directory
     * @param string $repoName Repository name
     * @param string $serviceDir Service directory
     * @param string $serviceName Service name
     */
    protected function displayCreatedFiles(string $templateName, string $entityDir, string $entityName, string $repoDir, string $repoName, string $serviceDir, string $serviceName): void
    {
        $this->line('Created files:');
        $this->line("  📄 frontend/templates/screens/{$templateName}.ludou");
        $this->line("  🏗️  {$entityDir}/{$entityName}.php");
        $this->line("  📦 {$repoDir}/{$repoName}.php");
        $this->line("  ⚙️  {$serviceDir}/{$serviceName}.php");
        $this->line("  🛣️  routes/web.php (routes added)");
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
}