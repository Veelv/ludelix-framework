<?php

namespace Ludelix\Core\Console\Commands\Evolution;

use Ludelix\Core\Console\Commands\Core\BaseCommand;
use Ludelix\Database\Core\EntityManager;
use Ludelix\Database\Core\ConnectionManager;
use Ludelix\Database\Core\UnitOfWork;
use Ludelix\Database\Metadata\MetadataFactory;

class EvolveRevertCommand extends BaseCommand
{
    protected string $signature = 'evolve:revert [--steps=1] [--dry-run]';
    protected string $description = 'Revert the last evolution';

    protected EntityManager $entityManager;
    protected ConnectionManager $connectionManager;

    public function execute(array $arguments, array $options): int
    {
        $steps = (int) $this->option($options, 'steps', 1);
        $dryRun = $this->hasOption($options, 'dry-run');

        if ($dryRun) {
            $this->info("🔍 Dry run mode - no changes will be applied");
        }

        $this->info("⏪ Reverting evolutions...");
        
        try {
            // Initialize ORM
            $this->initializeORM();
            
            // Check database connection
            if (!$this->checkDatabaseConnection()) {
                $this->error("❌ Cannot connect to database. Please check your database configuration.");
                return 1;
            }
            
            // Get applied evolutions
            $appliedEvolutions = $this->getAppliedEvolutions();
            
            if (empty($appliedEvolutions)) {
                $this->info("✅ No evolutions to revert");
                return 0;
            }
            
            // Limit steps if needed
            $evolutionsToRevert = array_slice($appliedEvolutions, -$steps);
            
            if (empty($evolutionsToRevert)) {
                $this->info("✅ No evolutions to revert with the specified steps");
                return 0;
            }
            
            // Show what will be reverted
            $this->info("🔄 Will revert " . count($evolutionsToRevert) . " evolution(s):");
            foreach ($evolutionsToRevert as $evolution) {
                $this->line("  - {$evolution['name']} ({$evolution['applied_at']})");
            }
            
            if ($dryRun) {
                $this->info("✅ Dry run completed successfully");
                return 0;
            }
            
            // Actually revert evolutions
            foreach (array_reverse($evolutionsToRevert) as $evolution) {
                $this->revertEvolution($evolution);
            }
            
            $this->success("✅ Successfully reverted " . count($evolutionsToRevert) . " evolution(s)");
            return 0;
            
        } catch (\Throwable $e) {
            $this->error("❌ Failed to revert evolutions: " . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Initialize ORM components
     */
    protected function initializeORM(): void
    {
        try {
            $this->info("🔌 Initializing Ludelix ORM...");
            
            // Load database configuration
            $configPath = 'config/database.php';
            if (!file_exists($configPath)) {
                throw new \Exception("Database configuration file not found: {$configPath}");
            }
            
            $config = require $configPath;
            
            // Create ORM dependencies
            $this->connectionManager = new ConnectionManager($config);
            $metadataFactory = new MetadataFactory();
            $unitOfWork = new UnitOfWork();
            
            // Create EntityManager with dependencies
            $this->entityManager = new EntityManager(
                $this->connectionManager,
                $metadataFactory,
                $unitOfWork
            );
            
            $this->success("✅ ORM initialized successfully");
            
        } catch (\Exception $e) {
            throw new \Exception("Failed to initialize ORM: " . $e->getMessage());
        }
    }
    
    /**
     * Check database connection
     * 
     * @return bool
     */
    protected function checkDatabaseConnection(): bool
    {
        try {
            $this->info("🔌 Checking database connection...");
            
            // Try to get a connection
            $connection = $this->connectionManager->getConnection();
            
            if ($connection) {
                $this->success("✅ Database connection established successfully");
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            $this->error("Database connection failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get applied evolutions
     * 
     * @return array
     */
    protected function getAppliedEvolutions(): array
    {
        // This is a simplified implementation
        // In a real implementation, this would query the evolutions table
        return [];
    }
    
    /**
     * Revert a specific evolution
     * 
     * @param array $evolution
     * @return void
     */
    protected function revertEvolution(array $evolution): void
    {
        $this->info("⏪ Reverting evolution: {$evolution['name']}");
        // Implementation would go here
    }
}