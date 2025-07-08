<?php

namespace Ludelix\Core\Console\Commands;

/**
 * Make Module Command
 * 
 * Generates module files with new naming convention
 */
class MakeModuleCommand
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'base_path' => 'app',
            'namespace' => 'App'
        ], $config);
    }

    /**
     * Execute command
     */
    public function execute(array $args, array $options): int
    {
        if (empty($args[0])) {
            echo "Usage: php brow kria:module <name> [--type=all]\n";
            return 1;
        }

        $name = $args[0];
        $type = $options['type'] ?? 'all';

        echo "Creating module: {$name}\n";

        if ($type === 'all' || $type === 'repository') {
            $this->createRepository($name);
        }

        if ($type === 'all' || $type === 'service') {
            $this->createService($name);
        }

        if ($type === 'all' || $type === 'model') {
            $this->createModel($name);
        }

        if ($type === 'all' || $type === 'entity') {
            $this->createEntity($name);
        }

        if ($type === 'all' || $type === 'job') {
            $this->createJob($name);
        }

        if ($type === 'all' || $type === 'console') {
            $this->createConsole($name);
        }

        if ($type === 'all' || $type === 'middleware') {
            $this->createMiddleware($name);
        }

        echo "Module {$name} created successfully!\n";
        return 0;
    }

    /**
     * Create repository
     */
    protected function createRepository(string $name): void
    {
        $className = $this->studly($name) . 'Repository';
        $filename = strtolower($name) . '.repository.php';
        $path = $this->config['base_path'] . '/Repositories/' . $filename;

        $content = $this->getRepositoryTemplate($name, $className);
        $this->writeFile($path, $content);
        echo "Created: {$path}\n";
    }

    /**
     * Create service
     */
    protected function createService(string $name): void
    {
        $className = $this->studly($name) . 'Service';
        $filename = strtolower($name) . '.service.php';
        $path = $this->config['base_path'] . '/Services/' . $filename;

        $content = $this->getServiceTemplate($name, $className);
        $this->writeFile($path, $content);
        echo "Created: {$path}\n";
    }

    /**
     * Create model
     */
    protected function createModel(string $name): void
    {
        $className = $this->studly($name) . 'Model';
        $filename = strtolower($name) . '.model.php';
        $path = $this->config['base_path'] . '/Models/' . $filename;

        $content = $this->getModelTemplate($name, $className);
        $this->writeFile($path, $content);
        echo "Created: {$path}\n";
    }

    /**
     * Create entity
     */
    protected function createEntity(string $name): void
    {
        $className = $this->studly($name);
        $filename = strtolower($name) . '.entity.php';
        $path = $this->config['base_path'] . '/Entities/' . $filename;

        $content = $this->getEntityTemplate($name, $className);
        $this->writeFile($path, $content);
        echo "Created: {$path}\n";
    }

    /**
     * Create job
     */
    protected function createJob(string $name): void
    {
        $className = $this->studly($name) . 'Job';
        $filename = strtolower($name) . '.job.php';
        $path = $this->config['base_path'] . '/Jobs/' . $filename;

        $content = $this->getJobTemplate($name, $className);
        $this->writeFile($path, $content);
        echo "Created: {$path}\n";
    }

    /**
     * Create console command
     */
    protected function createConsole(string $name): void
    {
        $className = $this->studly($name) . 'Command';
        $filename = strtolower($name) . '.console.php';
        $path = $this->config['base_path'] . '/Console/' . $filename;

        $content = $this->getConsoleTemplate($name, $className);
        $this->writeFile($path, $content);
        echo "Created: {$path}\n";
    }

    /**
     * Create middleware
     */
    protected function createMiddleware(string $name): void
    {
        $className = $this->studly($name) . 'Middleware';
        $filename = strtolower($name) . '.middleware.php';
        $path = $this->config['base_path'] . '/Middleware/' . $filename;

        $content = $this->getMiddlewareTemplate($name, $className);
        $this->writeFile($path, $content);
        echo "Created: {$path}\n";
    }

    /**
     * Get repository template
     */
    protected function getRepositoryTemplate(string $name, string $className): string
    {
        $entityClass = $this->studly($name);
        
        return "<?php

namespace {$this->config['namespace']}\\Repositories;

use {$this->config['namespace']}\\Entities\\{$entityClass};
use Ludelix\\Database\\Core\\Repository;

/**
 * {$className}
 * 
 * Repository for {$entityClass} entity
 */
class {$className} extends Repository
{
    /**
     * Get entity class
     */
    protected function getEntityClass(): string
    {
        return {$entityClass}::class;
    }

    /**
     * Find by custom criteria
     */
    public function findByCustomCriteria(array \$criteria): array
    {
        return \$this->findBy(\$criteria);
    }

    /**
     * Get active records
     */
    public function getActive(): array
    {
        return \$this->findBy(['active' => true]);
    }

    /**
     * Search by name
     */
    public function searchByName(string \$name): array
    {
        return \$this->createQueryBuilder()
            ->where('name LIKE :name')
            ->setParameter('name', '%' . \$name . '%')
            ->getResult();
    }
}";
    }

    /**
     * Get service template
     */
    protected function getServiceTemplate(string $name, string $className): string
    {
        $repositoryClass = $this->studly($name) . 'Repository';
        
        return "<?php

namespace {$this->config['namespace']}\\Services;

use {$this->config['namespace']}\\Repositories\\{$repositoryClass};

/**
 * {$className}
 * 
 * Business logic for {$name}
 */
class {$className}
{
    protected {$repositoryClass} \$repository;

    public function __construct({$repositoryClass} \$repository)
    {
        \$this->repository = \$repository;
    }

    /**
     * Get all records
     */
    public function getAll(): array
    {
        return \$this->repository->findAll();
    }

    /**
     * Get by ID
     */
    public function getById(int \$id): ?object
    {
        return \$this->repository->find(\$id);
    }

    /**
     * Create new record
     */
    public function create(array \$data): object
    {
        // Validation logic here
        \$this->validateData(\$data);
        
        return \$this->repository->create(\$data);
    }

    /**
     * Update record
     */
    public function update(int \$id, array \$data): ?object
    {
        \$entity = \$this->repository->find(\$id);
        
        if (!\$entity) {
            return null;
        }

        \$this->validateData(\$data);
        
        return \$this->repository->update(\$entity, \$data);
    }

    /**
     * Delete record
     */
    public function delete(int \$id): bool
    {
        \$entity = \$this->repository->find(\$id);
        
        if (!\$entity) {
            return false;
        }

        return \$this->repository->delete(\$entity);
    }

    /**
     * Validate data
     */
    protected function validateData(array \$data): void
    {
        // Add validation logic here
    }
}";
    }

    /**
     * Get entity template
     */
    protected function getEntityTemplate(string $name, string $className): string
    {
        $tableName = strtolower($name) . 's';
        
        return "<?php

namespace {$this->config['namespace']}\\Entities;

use Ludelix\\Database\\Attributes\\Entity;
use Ludelix\\Database\\Attributes\\Column;
use Ludelix\\Database\\Attributes\\PrimaryKey;
use Ludelix\\Database\\Attributes\\LifecycleCallbacks;

/**
 * {$className} Entity
 */
#[Entity(table: '{$tableName}')]
#[LifecycleCallbacks(['prePersist', 'postLoad'])]
class {$className}
{
    #[PrimaryKey]
    #[Column(type: 'int', autoIncrement: true)]
    public int \$id;

    #[Column(type: 'string', length: 255)]
    public string \$name;

    #[Column(type: 'string', length: 500, nullable: true)]
    public ?string \$description = null;

    #[Column(type: 'bool', default: true)]
    public bool \$active = true;

    #[Column(type: 'datetime')]
    public \\DateTime \$createdAt;

    #[Column(type: 'datetime', nullable: true)]
    public ?\\DateTime \$updatedAt = null;

    public function __construct()
    {
        \$this->createdAt = new \\DateTime();
    }

    /**
     * Pre-persist lifecycle callback
     */
    public function prePersist(): void
    {
        \$this->name = trim(\$this->name);
        \$this->updatedAt = new \\DateTime();
    }

    /**
     * Post-load lifecycle callback
     */
    public function postLoad(): void
    {
        // Post-load logic here
    }

    /**
     * Get display name
     */
    public function getDisplayName(): string
    {
        return \$this->name;
    }

    /**
     * Check if active
     */
    public function isActive(): bool
    {
        return \$this->active;
    }
}";
    }

    /**
     * Get job template
     */
    protected function getJobTemplate(string $name, string $className): string
    {
        return "<?php

namespace {$this->config['namespace']}\\Jobs;

use Ludelix\\Queue\\Core\\Job;

/**
 * {$className}
 * 
 * Background job for {$name}
 */
class {$className} extends Job
{
    protected string \$queue = 'default';
    protected int \$maxTries = 3;
    protected int \$timeout = 60;

    /**
     * Execute the job
     */
    public function handle(): void
    {
        \$data = \$this->getData();
        
        // Job logic here
        \$this->process(\$data);
    }

    /**
     * Handle job failure
     */
    public function failed(\\Throwable \$exception): void
    {
        // Log failure or send notification
        error_log('Job failed: ' . \$exception->getMessage());
    }

    /**
     * Process job data
     */
    protected function process(array \$data): void
    {
        // Implementation here
    }
}";
    }

    /**
     * Get console template
     */
    protected function getConsoleTemplate(string $name, string $className): string
    {
        $commandName = strtolower($name) . ':process';
        
        return "<?php

namespace {$this->config['namespace']}\\Console;

/**
 * {$className}
 * 
 * Console command for {$name}
 */
class {$className}
{
    protected string \$signature = '{$commandName}';
    protected string \$description = 'Process {$name} command';

    /**
     * Execute command
     */
    public function execute(array \$args, array \$options): int
    {
        echo \"Executing {$name} command...\\n\";
        
        try {
            \$this->process(\$args, \$options);
            echo \"Command completed successfully!\\n\";
            return 0;
        } catch (\\Exception \$e) {
            echo \"Error: \" . \$e->getMessage() . \"\\n\";
            return 1;
        }
    }

    /**
     * Process command logic
     */
    protected function process(array \$args, array \$options): void
    {
        // Command implementation here
    }

    /**
     * Get command signature
     */
    public function getSignature(): string
    {
        return \$this->signature;
    }

    /**
     * Get command description
     */
    public function getDescription(): string
    {
        return \$this->description;
    }
}";
    }

    /**
     * Get middleware template
     */
    protected function getMiddlewareTemplate(string $name, string $className): string
    {
        return "<?php

namespace {$this->config['namespace']}\\Middleware;

use Ludelix\\PRT\\Request;
use Ludelix\\PRT\\Response;

/**
 * {$className}
 * 
 * Middleware for {$name}
 */
class {$className}
{
    /**
     * Handle request
     */
    public function handle(Request \$request, callable \$next): Response
    {
        // Before request processing
        \$this->before(\$request);
        
        \$response = \$next(\$request);
        
        // After request processing
        \$this->after(\$request, \$response);
        
        return \$response;
    }

    /**
     * Before request processing
     */
    protected function before(Request \$request): void
    {
        // Pre-processing logic
    }

    /**
     * After request processing
     */
    protected function after(Request \$request, Response \$response): void
    {
        // Post-processing logic
    }
}";
    }

    /**
     * Get model template (for compatibility)
     */
    protected function getModelTemplate(string $name, string $className): string
    {
        return "<?php

namespace {$this->config['namespace']}\\Models;

/**
 * {$className}
 * 
 * Model for {$name} (compatibility layer)
 */
class {$className}
{
    protected array \$data = [];
    protected array \$fillable = [];
    protected array \$hidden = [];

    public function __construct(array \$data = [])
    {
        \$this->fill(\$data);
    }

    /**
     * Fill model with data
     */
    public function fill(array \$data): self
    {
        foreach (\$data as \$key => \$value) {
            if (empty(\$this->fillable) || in_array(\$key, \$this->fillable)) {
                \$this->data[\$key] = \$value;
            }
        }
        
        return \$this;
    }

    /**
     * Get attribute
     */
    public function __get(string \$key): mixed
    {
        return \$this->data[\$key] ?? null;
    }

    /**
     * Set attribute
     */
    public function __set(string \$key, mixed \$value): void
    {
        \$this->data[\$key] = \$value;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        \$data = \$this->data;
        
        foreach (\$this->hidden as \$key) {
            unset(\$data[\$key]);
        }
        
        return \$data;
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode(\$this->toArray());
    }
}";
    }

    /**
     * Write file to disk
     */
    protected function writeFile(string $path, string $content): void
    {
        $directory = dirname($path);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        file_put_contents($path, $content);
    }

    /**
     * Convert string to StudlyCase
     */
    protected function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }
}