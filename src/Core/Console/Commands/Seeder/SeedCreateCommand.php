<?php

namespace Ludelix\Core\Console\Commands\Seeder;

use Ludelix\Core\Console\Commands\Core\BaseCommand;
use Ludelix\Core\Console\Templates\Engine\TemplateEngine;

class SeedCreateCommand extends BaseCommand
{
    protected string $signature = 'seed:create <name> [--format=yaml] [--table=] [--factory]';
    protected string $description = 'Create new seeder file';

    protected TemplateEngine $templateEngine;

    public function __construct($container, $engine)
    {
        parent::__construct($container, $engine);
        $this->templateEngine = new TemplateEngine();
        $this->templateEngine->addPath('seeder', __DIR__ . '/../../Templates/Seeder');
    }

    public function execute(array $arguments, array $options): int
    {
        $name = $this->argument($arguments, 0);
        $format = $this->option($options, 'format', 'yaml');
        $table = $this->option($options, 'table');
        $useFactory = $this->hasOption($options, 'factory');

        if (!$name) {
            $this->error("Seeder name is required");
            return 1;
        }

        if ($format === 'yaml') {
            return $this->createYamlSeeder($name, $table, $useFactory);
        } else {
            return $this->createPhpSeeder($name, $table, $useFactory);
        }
    }

    protected function createYamlSeeder(string $name, ?string $table, bool $useFactory): int
    {
        $filename = $this->studly($name) . 'Seeder.yaml';
        $path = 'database/seeders/' . $filename;

        $variables = [
            'name' => $this->studly($name),
            'description' => $this->generateDescription($name, $table),
            'author' => get_current_user() . '@' . gethostname(),
            'timestamp' => date('Y-m-d H:i:s'),
            'tableName' => $table ?: $this->snake($name),
            'useFactory' => $useFactory,
            'sampleData' => $this->generateSampleData($table ?: $this->snake($name))
        ];

        $content = $this->templateEngine->render('seeder.yaml', $variables);
        $this->writeFile($path, $content);

        $this->success("✅ Created seeder: {$filename}");
        $this->info("📁 Location: {$path}");
        
        return 0;
    }

    protected function createPhpSeeder(string $name, ?string $table, bool $useFactory): int
    {
        $className = $this->studly($name) . 'Seeder';
        $filename = $className . '.php';
        $path = 'database/seeders/' . $filename;

        $variables = [
            'className' => $className,
            'description' => $this->generateDescription($name, $table),
            'tableName' => $table ?: $this->snake($name),
            'useFactory' => $useFactory,
            'sampleData' => $this->generateSampleData($table ?: $this->snake($name))
        ];

        $content = $this->templateEngine->render('seeder.php', $variables);
        $this->writeFile($path, $content);

        $this->success("✅ Created seeder: {$filename}");
        $this->info("📁 Location: {$path}");
        
        return 0;
    }

    protected function generateDescription(string $name, ?string $table): string
    {
        if ($table) {
            return "Seed data for {$table} table";
        }
        
        return "Seed data for " . str_replace('_', ' ', $this->snake($name));
    }

    protected function generateSampleData(string $table): array
    {
        // Return generic sample structure - user will customize
        return [
            [
                'column1' => 'value1',
                'column2' => 'value2'
            ]
        ];
    }

    protected function writeFile(string $path, string $content): void
    {
        $directory = dirname($path);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        file_put_contents($path, $content);
    }

    protected function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }

    protected function snake(string $value): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $value));
    }
}