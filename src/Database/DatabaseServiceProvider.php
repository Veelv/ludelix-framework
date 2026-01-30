<?php

namespace Ludelix\Database;

use Ludelix\Bootstrap\Providers\ServiceProvider;
use Ludelix\Database\Core\ConnectionManager;
use Ludelix\Database\Core\EntityManager;
use Ludelix\Database\Core\QueryBuilder;
use Ludelix\Database\Metadata\MetadataFactory;

/**
 * Service Provider for the Database Component.
 *
 * Registers the core database services (ConnectionManager, EntityManager, MetadataFactory)
 * into the service container.
 */
class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Registers database services.
     *
     * Binds the following singletons:
     * - db.connection: ConnectionManager instance.
     * - db.metadata: MetadataFactory instance.
     * - db.entity_manager: EntityManager instance.
     * - db.query: QueryBuilder factory.
     */
    public function register(): void
    {
        // Register Connection Manager
        $this->container->singleton(ConnectionManager::class, function ($app) {
            $config = $app->get('config')->get('database');
            return new ConnectionManager($config);
        });

        // Register Entity Processor
        $this->container->singleton(\Ludelix\Database\Core\EntityProcessor::class, function () {
            return new \Ludelix\Database\Core\EntityProcessor();
        });

        // Register UnitOfWork
        $this->container->singleton(\Ludelix\Database\Core\UnitOfWork::class, function ($app) {
            return new \Ludelix\Database\Core\UnitOfWork(
                $app->make(ConnectionManager::class),
                $app->make(MetadataFactory::class),
                $app->make(\Ludelix\Database\Core\EntityProcessor::class)
            );
        });

        // Register Entity Manager
        $this->container->singleton(EntityManager::class, function ($app) {
            return new EntityManager(
                $app->make(ConnectionManager::class),
                $app->make(MetadataFactory::class),
                $app->make(\Ludelix\Database\Core\UnitOfWork::class)
            );
        });

        // Register Snapshot Manager
        $this->container->singleton(\Ludelix\Database\Evolution\Snapshots\SnapshotManager::class, function ($app) {
            return new \Ludelix\Database\Evolution\Snapshots\SnapshotManager(
                $app->make(ConnectionManager::class),
                $app->basePath('database/snapshots')
            );
        });

        // Register Evolution Manager
        $this->container->singleton(\Ludelix\Database\Evolution\Core\EvolutionManager::class, function ($app) {
            return new \Ludelix\Database\Evolution\Core\EvolutionManager(
                $app->make(ConnectionManager::class),
                $app->make(\Ludelix\Database\Evolution\Snapshots\SnapshotManager::class),
                $app->basePath('database/evolutions')
            );
        });

        // Aliases
        $this->container->alias(ConnectionManager::class, 'db.connection');
        $this->container->alias(EntityManager::class, 'db.entity_manager');
        $this->container->alias(\Ludelix\Database\Evolution\Core\EvolutionManager::class, 'db.evolution');
    }

    /**
     * Bootstraps database services.
     *
     * Can be used to run initial checks or migrations if configured.
     */
    public function boot(): void
    {
        // Auto-run evolutions if configured
        if ($this->container->get('config')->get('database.auto_migrate', false)) {
            try {
                $evolutionManager = $this->container->get(\Ludelix\Database\Evolution\Core\EvolutionManager::class);
                $evolutionManager->apply();
            } catch (\Throwable $e) {
                // Log warning but don't stop boot unless critical
                error_log("Database Auto-Migration Failed: " . $e->getMessage());
            }
        }
    }
}