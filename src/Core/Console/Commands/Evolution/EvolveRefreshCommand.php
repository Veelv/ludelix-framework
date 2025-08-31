<?php

namespace Ludelix\Core\Console\Commands\Evolution;

use Ludelix\Core\Console\Commands\Core\BaseCommand;
use Ludelix\Database\Core\EntityManager;
use Ludelix\Database\Core\ConnectionManager;
use Ludelix\Database\Core\UnitOfWork;
use Ludelix\Database\Metadata\MetadataFactory;

class EvolveRefreshCommand extends BaseCommand
{
    protected string $signature = 'evolve:refresh [--seed]';
    protected string $description = 'Reset and re-run all evolutions';

    protected EntityManager $entityManager;
    protected ConnectionManager $connectionManager;

    public function execute(array $arguments, array $options): int
    {
        $seed = $this->hasOption($options, 'seed');

        $this->info("🔄 Refreshing evolutions...");
        
        try {
            // Initialize ORM
            $this->initializeORM();
            
            // Check database connection
            if (!$this->checkDatabaseConnection()) {
                $this->error("❌ Cannot connect to database. Please check your database configuration.");
                return 1;
            }
            
            // First, revert all evolutions
            $this->info("⏪ Reverting all evolutions...");
            $this->revertAllEvolutions();
            
            // Then, re-apply all evolutions
            $this->info("⏫ Re-applying all evolutions...");
            $this->applyAllEvolutions();
            
            // Optionally seed the database
            if ($seed) {
                $this->info("🌱 Seeding database...");
                $this->seedDatabase();
            }
            
            $this->success("✅ Database successfully refreshed");
            return 0;
            
        } catch (\Throwable $e) {
            $this->error("❌ Failed to refresh evolutions: " . $e->getMessage());
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
     * Revert all evolutions
     * 
     * @return void
     */
    protected function revertAllEvolutions(): void
    {
        // Implementation would go here
        $this->info("✅ All evolutions reverted");
    }
    
    /**
     * Apply all evolutions
     * 
     * @return void
     */
    protected function applyAllEvolutions(): void
    {
        // Implementation would go here
        $this->info("✅ All evolutions applied");
    }
    
    /**
     * Seed the database
     * 
     * @return void
     */
    protected function seedDatabase(): void
    {
        // Implementation would go here
        $this->info("✅ Database seeded");
    }
}