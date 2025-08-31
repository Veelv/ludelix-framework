<?php

namespace Ludelix\Database\Core;

use Ludelix\Database\Metadata\MetadataFactory;
use Ludelix\Database\Repositories\BaseRepository;
use Ludelix\Database\Core\QueryBuilder;

class EntityManager
{
    protected ConnectionManager $connectionManager;
    protected MetadataFactory $metadataFactory;
    protected UnitOfWork $unitOfWork;
    protected array $repositories = [];
    
    public function __construct(
        ConnectionManager $connectionManager,
        MetadataFactory $metadataFactory,
        UnitOfWork $unitOfWork
    ) {
        $this->connectionManager = $connectionManager;
        $this->metadataFactory = $metadataFactory;
        $this->unitOfWork = $unitOfWork;
    }
    
    public function find(string $entityClass, mixed $id): ?object
    {
        $metadata = $this->metadataFactory->getMetadata($entityClass);
        $queryBuilder = $this->createQueryBuilder($entityClass);
        return $queryBuilder->where($metadata->getPrimaryKey(), '=', $id)->first();
    }
    
    public function findAll(string $entityClass): array
    {
        return $this->createQueryBuilder($entityClass)->get();
    }
    
    public function findBy(string $entityClass, array $criteria): array
    {
        $queryBuilder = $this->createQueryBuilder($entityClass);
        foreach ($criteria as $field => $value) {
            $queryBuilder->where($field, '=', $value);
        }
        return $queryBuilder->get();
    }
    
    public function persist(object $entity): void
    {
        $this->processEntity($entity);
        $this->unitOfWork->registerNew($entity);
    }
    
    public function remove(object $entity): void
    {
        $this->unitOfWork->registerRemoved($entity);
    }
    
    public function flush(): void
    {
        $this->unitOfWork->commit();
    }
    
    public function createQueryBuilder(string $entityClass, string $alias = 'e'): QueryBuilder
    {
        $metadata = $this->metadataFactory->getMetadata($entityClass);
        $connection = $this->connectionManager->getConnection();
        return new QueryBuilder($connection, $metadata, $alias);
    }
    
    public function getRepository(string $entityClass): BaseRepository
    {
        if (!isset($this->repositories[$entityClass])) {
            $this->repositories[$entityClass] = new BaseRepository($this, $this->metadataFactory->getMetadata($entityClass));
        }
        return $this->repositories[$entityClass];
    }
    
    public function transaction(callable $callback): mixed
    {
        $this->connectionManager->getConnection()->beginTransaction();
        try {
            $result = $callback($this);
            $this->connectionManager->getConnection()->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->connectionManager->getConnection()->rollback();
            throw $e;
        }
    }
    
    protected function processEntity(object $entity): void
    {
        $reflection = new \ReflectionClass($entity);
        
        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            
            if ($property->isInitialized($entity)) {
                $value = $property->getValue($entity);
                
                if (is_string($value)) {
                    $property->setValue($entity, trim($value));
                }
            }
        }
    }
}