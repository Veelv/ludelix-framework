<?php

namespace Ludelix\Database\Core;

use Ludelix\Database\Metadata\MetadataFactory;
use Ludelix\Database\Repositories\BaseRepository;
use Ludelix\Database\Core\QueryBuilder;

/**
 * Manages the lifecycle of entities and their persistence.
 *
 * The EntityManager is the central access point to ORM functionality.
 * It coordinates the UnitOfWork, MetadataFactory, and ConnectionManager
 * to perform database insertions, updates, deletions, and queries.
 */
class EntityManager
{
    protected ConnectionManager $connectionManager;
    protected MetadataFactory $metadataFactory;
    protected UnitOfWork $unitOfWork;
    protected array $repositories = [];

    /**
     * @param ConnectionManager $connectionManager Service for database connections.
     * @param MetadataFactory   $metadataFactory   Service for entity metadata.
     * @param UnitOfWork        $unitOfWork        Service for tracking changes.
     */
    public function __construct(
        ConnectionManager $connectionManager,
        MetadataFactory $metadataFactory,
        UnitOfWork $unitOfWork
    ) {
        $this->connectionManager = $connectionManager;
        $this->metadataFactory = $metadataFactory;
        $this->unitOfWork = $unitOfWork;
    }

    /**
     * Finds an entity by its primary key.
     *
     * @param string $entityClass The entity class name.
     * @param mixed  $id          The primary key value.
     * @return object|null The found entity or null if not found.
     */
    public function find(string $entityClass, mixed $id): ?object
    {
        $metadata = $this->metadataFactory->getMetadata($entityClass);
        $queryBuilder = $this->createQueryBuilder($entityClass);
        $entity = $queryBuilder->where($metadata->getPrimaryKey(), '=', $id)->first();

        if ($entity) {
            $this->unitOfWork->registerManaged($entity);
        }

        return $entity;
    }

    /**
     * Finds all entities of a given class.
     *
     * @param string $entityClass The entity class name.
     * @return array List of all entities found.
     */
    public function findAll(string $entityClass): array
    {
        $entities = $this->createQueryBuilder($entityClass)->get();

        foreach ($entities as $entity) {
            $this->unitOfWork->registerManaged($entity);
        }

        return $entities;
    }

    /**
     * Finds entities matching a set of criteria.
     *
     * @param string $entityClass The entity class name.
     * @param array  $criteria    Associative array of field => value conditions.
     * @return array List of matching entities.
     */
    public function findBy(string $entityClass, array $criteria): array
    {
        $queryBuilder = $this->createQueryBuilder($entityClass);
        foreach ($criteria as $field => $value) {
            $queryBuilder->where($field, '=', $value);
        }

        $entities = $queryBuilder->get();

        foreach ($entities as $entity) {
            $this->unitOfWork->registerManaged($entity);
        }

        return $entities;
    }

    /**
     * Tells the EntityManager to manage an entity instance (schedule for insertion).
     *
     * @param object $entity The entity to persist.
     */
    public function persist(object $entity): void
    {
        $this->processEntity($entity);
        $this->unitOfWork->registerNew($entity);
    }

    /**
     * Tells the EntityManager to remove an entity instance (schedule for deletion).
     *
     * @param object $entity The entity to remove.
     */
    public function remove(object $entity): void
    {
        $this->unitOfWork->registerRemoved($entity);
    }

    /**
     * Synchronizes the in-memory state of managed objects with the database.
     */
    public function flush(): void
    {
        $this->unitOfWork->commit();
    }

    /**
     * Creates a QueryBuilder for a specific entity.
     *
     * @param string $entityClass The entity class name.
     * @param string $alias       The alias for the table (default: 'e').
     * @return QueryBuilder The configured QueryBuilder.
     */
    public function createQueryBuilder(string $entityClass, string $alias = 'e'): QueryBuilder
    {
        $metadata = $this->metadataFactory->getMetadata($entityClass);
        $connection = $this->connectionManager->getConnection();
        return new QueryBuilder($connection, $metadata, $alias);
    }

    /**
     * Gets the repository for a specific entity class.
     *
     * @param string $entityClass The entity class name.
     * @return BaseRepository The repository instance.
     */
    public function getRepository(string $entityClass): BaseRepository
    {
        if (!isset($this->repositories[$entityClass])) {
            $this->repositories[$entityClass] = new BaseRepository($this, $this->metadataFactory->getMetadata($entityClass));
        }
        return $this->repositories[$entityClass];
    }

    /**
     * Executes a callback within a database transaction.
     *
     * @param callable $callback The callback to execute. Receives the EntityManager as argument.
     * @return mixed The return value of the callback.
     * @throws \Throwable If an error occurs, the transaction is rolled back.
     */
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

    /**
     * Performs initial processing on an entity (e.g., trimming strings).
     *
     * @param object $entity The entity to process.
     */
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