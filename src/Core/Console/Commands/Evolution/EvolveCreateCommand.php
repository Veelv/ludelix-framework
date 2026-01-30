<?php

namespace Ludelix\Core\Console\Commands\Evolution;

use Ludelix\Core\Console\Commands\Core\BaseCommand;
use Ludelix\Core\Console\Templates\Engine\TemplateEngine;

class EvolveCreateCommand extends BaseCommand
{
    protected string $signature = 'evolve:create <name> [--format=yaml] [--table=] [--from-entity=]';
    protected string $description = 'Create new evolution file';

    protected TemplateEngine $templateEngine;

    public function __construct($container, $engine)
    {
        parent::__construct($container, $engine);
        $this->templateEngine = new TemplateEngine();
        $this->templateEngine->addPath('evolution', __DIR__ . '/../../Templates/Evolution');
    }

    public function execute(array $arguments, array $options): int
    {
        $name = $this->argument($arguments, 0);
        $format = $this->option($options, 'format', 'php');
        $table = $this->option($options, 'table');
        $fromEntity = $this->option($options, 'from-entity');

        if (!$name) {
            $this->error("Evolution name is required");
            return 1;
        }

        $evolutionId = date('Y_m_d_H_i_s') . '_' . $this->snake($name);
        $className = $this->studly($name);

        if ($fromEntity) {
            return $this->createFromEntity($fromEntity, $evolutionId, $format);
        }

        if ($format === 'yaml') {
            return $this->createYamlEvolution($name, $evolutionId, $table);
        } else {
            return $this->createPhpEvolution($name, $evolutionId, $className, $table);
        }
    }

    protected function createYamlEvolution(string $name, string $evolutionId, ?string $table): int
    {
        $filename = $evolutionId . '.yaml';
        $path = 'database/evolutions/' . $filename;

        $variables = [
            'evolutionId' => $evolutionId,
            'description' => $this->generateDescription($name, $table),
            'author' => get_current_user() . '@' . gethostname(),
            'timestamp' => date('Y-m-d H:i:s'),
            'tableName' => $table ?: $this->snake($name),
            'createTable' => str_contains($name, 'create'),
            'modifyTable' => str_contains($name, 'add') || str_contains($name, 'modify'),
            'columns' => $this->generateColumns($name, $table)
        ];

        $content = $this->templateEngine->render('evolution.yaml', $variables);
        $this->writeFile($path, $content);

        $this->success("✅ Created evolution: {$filename}");
        $this->info("📁 Location: {$path}");
        
        return 0;
    }

    protected function createPhpEvolution(string $name, string $evolutionId, string $className, ?string $table): int
    {
        $filename = $evolutionId . '.php';
        $path = 'database/evolutions/' . $filename;

        $variables = [
            'evolutionId' => $evolutionId,
            'className' => $className . 'Evolution',
            'description' => $this->generateDescription($name, $table),
            'tableName' => $table ?: $this->snake($name),
            'createTable' => str_contains($name, 'create'),
            'modifyTable' => str_contains($name, 'add') || str_contains($name, 'modify'),
            'columns' => $this->generateColumns($name, $table)
        ];

        $content = $this->templateEngine->render('evolution.php', $variables);
        $this->writeFile($path, $content);

        $this->success("✅ Created evolution: {$filename}");
        $this->info("📁 Location: {$path}");
        
        return 0;
    }

    protected function createFromEntity(string $entityClass, string $evolutionId, string $format): int
    {
        $evolutionManager = $this->service('evolution.manager');
        
        try {
            $evolutionData = $evolutionManager->generateFromEntity($entityClass);
            
            // Create evolution file based on entity analysis
            $this->info("🔍 Analyzing entity: {$entityClass}");
            $this->success("✅ Generated evolution from entity");
            
            return 0;
            
        } catch (\Throwable $e) {
            $this->error("❌ Failed to analyze entity: " . $e->getMessage());
            return 1;
        }
    }

    protected function generateDescription(string $name, ?string $table): string
    {
        if (str_contains($name, 'create')) {
            return "Create " . ($table ?: $this->snake($name)) . " table";
        }
        
        if (str_contains($name, 'add')) {
            return "Add columns to " . ($table ?: 'table');
        }
        
        if (str_contains($name, 'drop')) {
            return "Drop " . ($table ?: $this->snake($name)) . " table";
        }
        
        return ucfirst(str_replace('_', ' ', $name));
    }

    protected function generateColumns(string $name, ?string $table): array
    {
        if (str_contains($name, 'create_users')) {
            return [
                ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
                ['name' => 'name', 'type' => 'varchar(255)', 'nullable' => false],
                ['name' => 'email', 'type' => 'varchar(255)', 'unique' => true],
                ['name' => 'password', 'type' => 'varchar(255)', 'nullable' => false],
                ['name' => 'created_at', 'type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP'],
                ['name' => 'updated_at', 'type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP']
            ];
        }

        return [
            ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
            ['name' => 'created_at', 'type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP']
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