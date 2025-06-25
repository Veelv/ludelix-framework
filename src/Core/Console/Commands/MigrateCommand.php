<?php

namespace Ludelix\Core\Console\Commands;

use Ludelix\Interface\Console\CommandInterface;
use Ludelix\ORM\Migrations\MigrationManager;

class MigrateCommand implements CommandInterface
{
    protected MigrationManager $migrationManager;
    
    public function __construct(MigrationManager $migrationManager)
    {
        $this->migrationManager = $migrationManager;
    }
    
    public function getName(): string { return 'migrate'; }
    public function getDescription(): string { return 'Run database migrations'; }
    public function getArguments(): array { return []; }
    public function getOptions(): array { return ['--rollback' => 'Rollback last migration']; }
    
    public function execute(array $arguments, array $options): int
    {
        if (isset($options['rollback'])) {
            $this->migrationManager->rollback();
            echo "Migration rolled back\n";
        } else {
            $this->migrationManager->migrate();
            echo "Migrations executed\n";
        }
        return 0;
    }
}