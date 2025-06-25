<?php

namespace Ludelix\Core\Console\Commands;

use Ludelix\Interface\Console\CommandInterface;

class MakeRepositoryCommand implements CommandInterface
{
    public function getName(): string { return 'make:repository'; }
    public function getDescription(): string { return 'Create a new repository class'; }
    public function getArguments(): array { return ['name' => 'Repository name']; }
    public function getOptions(): array { return ['--force' => 'Overwrite existing files']; }
    
    public function execute(array $arguments, array $options): int
    {
        $name = $arguments['name'] ?? null;
        if (!$name) {
            echo "Error: Repository name is required\n";
            return 1;
        }
        
        $className = str_replace('Repository', '', $name) . 'Repository';
        $entityName = str_replace('Repository', '', $name);
        $path = app_path("Repositories/{$className}.php");
        
        if (file_exists($path) && !isset($options['force'])) {
            echo "Error: Repository already exists. Use --force to overwrite.\n";
            return 1;
        }
        
        $content = "<?php

namespace App\\Repositories;

use App\\Entities\\{$entityName};
use Ludelix\\ORM\\Repository\\Repository;

class {$className} extends Repository
{
    public function findActive(): array
    {
        return \$this->findBy(['active' => true]);
    }
}";
        
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        file_put_contents($path, $content);
        echo "Repository created: {$path}\n";
        return 0;
    }
}