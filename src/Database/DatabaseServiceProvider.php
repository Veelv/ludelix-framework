<?php

namespace Ludelix\Database;

use Ludelix\Database\Core\EntityManager;
use Ludelix\Database\Core\ConnectionManager;
use Ludelix\Database\Core\UnitOfWork;
use Ludelix\Database\Metadata\MetadataFactory;
use Ludelix\Interface\DI\ServiceProviderInterface;
use Ludelix\Interface\DI\ContainerInterface;

class DatabaseServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton('db.connection_manager', function ($container) {
            return new ConnectionManager($container->get('config')->get('database', []));
        });
        
        $container->singleton('db.metadata_factory', function ($container) {
            return new MetadataFactory();
        });
        
        $container->singleton('db.unit_of_work', function ($container) {
            return new UnitOfWork();
        });
        
        $container->singleton('db', function ($container) {
            return new EntityManager(
                $container->get('db.connection_manager'),
                $container->get('db.metadata_factory'),
                $container->get('db.unit_of_work')
            );
        });
    }
    
    public function boot(ContainerInterface $container): void
    {
        // Boot logic
    }
}