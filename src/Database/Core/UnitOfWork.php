<?php

namespace Ludelix\Database\Core;

use SplObjectStorage;
use Ludelix\Database\Metadata\MetadataFactory;
use Ludelix\Database\Core\ConnectionManager;
use Ludelix\Database\Core\QueryBuilder;

/**
 * Maintains a list of objects affected by a business transaction.
 *
 * The UnitOfWork coordinates the writing out of changes and resolves
 * concurrency problems. It tracks new, managed, and removed entities.
 */
class UnitOfWork
{
    /** @var SplObjectStorage Entities scheduled for insertion. */
    protected SplObjectStorage $newEntities;

    /** @var SplObjectStorage Entities managed by the persistence context. */
    protected SplObjectStorage $managedEntities;

    /** @var SplObjectStorage Entities scheduled for deletion. */
    protected SplObjectStorage $removedEntities;

    protected ConnectionManager $connectionManager;
    protected MetadataFactory $metadataFactory;
    protected EntityProcessor $entityProcessor;

    /**
     * @param ConnectionManager $connectionManager
     * @param MetadataFactory   $metadataFactory
     * @param EntityProcessor   $entityProcessor
     */
    public function __construct(
        ConnectionManager $connectionManager,
        MetadataFactory $metadataFactory,
        EntityProcessor $entityProcessor = null
    ) {
        $this->connectionManager = $connectionManager;
        $this->metadataFactory = $metadataFactory;
        // Fallback for backward compatibility if not injected via DI container immediately
        $this->entityProcessor = $entityProcessor ?? new EntityProcessor();

        $this->newEntities = new SplObjectStorage();
        $this->managedEntities = new SplObjectStorage();
        $this->removedEntities = new SplObjectStorage();
    }

    /**
     * Registers an entity as new (to be inserted).
     *
     * @param object $entity
     */
    public function registerNew(object $entity): void
    {
        $this->newEntities->attach($entity);
    }

    /**
     * Registers an entity as managed (to be checked for updates).
     *
     * @param object $entity
     */
    public function registerManaged(object $entity): void
    {
        $this->managedEntities->attach($entity);
    }

    /**
     * Registers an entity as removed (to be deleted).
     *
     * @param object $entity
     */
    public function registerRemoved(object $entity): void
    {
        $this->removedEntities->attach($entity);
    }

    /**
     * Commits all pending changes to the database.
     *
     * Executes INSERTs, UPDATEs, and DELETEs in a transaction.
     *
     * @throws \Throwable If the commit fails.
     */
    public function commit(): void
    {
        $this->connectionManager->getConnection()->beginTransaction();

        try {
            // Process insertions
            foreach ($this->newEntities as $entity) {
                $this->insertEntity($entity);
            }

            // Process updates (for managed entities)
            foreach ($this->managedEntities as $entity) {
                $this->updateEntity($entity);
            }

            // Process deletions
            foreach ($this->removedEntities as $entity) {
                $this->deleteEntity($entity);
            }

            $this->connectionManager->getConnection()->commit();
            $this->clear();

        } catch (\Throwable $e) {
            $this->connectionManager->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * Inserts a single entity into the database.
     *
     * @param object $entity
     */
    protected function insertEntity(object $entity): void
    {
        $class = get_class($entity);
        $metadata = $this->metadataFactory->getMetadata($class);

        // Process automatic attributes (UUID, Slug, Trim)
        $this->entityProcessor->processEntity($entity, $metadata);

        $data = $this->extractData($entity, $metadata);

        // Remove primary key if null (auto-increment)
        $pkName = $metadata->getPrimaryKey();
        if (isset($data[$pkName]) && $data[$pkName] === null) {
            unset($data[$pkName]);
        }

        $qb = new QueryBuilder($this->connectionManager->getConnection(), $metadata, null);
        $qb->insert($data);

        // Set ID back to entity if auto-increment
        if (empty($data[$pkName])) {
            $id = $this->connectionManager->getConnection()->lastInsertId();
            if ($id) {
                $property = $metadata->getPropertyByColumn($pkName);
                if ($property) {
                    $refProperty = new \ReflectionProperty($entity, $property);
                    $refProperty->setAccessible(true);
                    $refProperty->setValue($entity, $id);
                }
            }
        }

        $this->managedEntities->attach($entity);
    }

    /**
     * Updates an existing entity in the database.
     *
     * @param object $entity
     * @throws \RuntimeException If the entity has no ID.
     */
    protected function updateEntity(object $entity): void
    {
        $class = get_class($entity);
        $metadata = $this->metadataFactory->getMetadata($class);

        // Process automatic attributes (Trim, etc.)
        $this->entityProcessor->processEntity($entity, $metadata);

        $data = $this->extractData($entity, $metadata);
        $pkName = $metadata->getPrimaryKey();

        if (!isset($data[$pkName])) {
            throw new \RuntimeException("Cannot update entity without ID");
        }

        $id = $data[$pkName];
        unset($data[$pkName]); // Don't update PK

        $qb = new QueryBuilder($this->connectionManager->getConnection(), $metadata, null);
        $qb->where($pkName, '=', $id)->update($data);
    }

    /**
     * Deletes an entity from the database.
     *
     * @param object $entity
     */
    protected function deleteEntity(object $entity): void
    {
        $class = get_class($entity);
        $metadata = $this->metadataFactory->getMetadata($class);
        $pkColumn = $metadata->getPrimaryKey();

        // Find property based on column name
        $property = $metadata->getPropertyByColumn($pkColumn);
        $refProperty = new \ReflectionProperty($entity, $property);
        $refProperty->setAccessible(true);
        $id = $refProperty->getValue($entity);

        $qb = new QueryBuilder($this->connectionManager->getConnection(), $metadata, null);
        $qb->where($pkColumn, '=', $id)->delete();

        $this->managedEntities->detach($entity);
    }

    /**
     * Extracts data from an entity for database persistence.
     * 
     * Applies serialization/casting logic based on metadata.
     *
     * @param object $entity
     * @param mixed  $metadata
     * @return array
     */
    protected function extractData(object $entity, $metadata): array
    {
        $data = [];
        $reflection = new \ReflectionClass($entity);

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            if ($property->isInitialized($entity)) {
                $value = $property->getValue($entity);
                $propertyName = $property->getName();

                // Apply serialization
                $cast = $metadata->getCast($propertyName);
                if ($cast) {
                    $value = $this->serializeValue($value, $cast);
                }

                $column = $metadata->getColumnByProperty($propertyName);
                if ($column) {
                    $data[$column] = $value;
                }
            }
        }

        return $data;
    }

    /**
     * Serializes a value for database storage.
     *
     * @param mixed  $value
     * @param string $cast
     * @return mixed
     */
    protected function serializeValue(mixed $value, string $cast): mixed
    {
        if ($value === null)
            return null;

        return match ($cast) {
            'json', 'array', 'object' => json_encode($value),
            'bool', 'boolean' => (int) $value, // Store boolean as integer
            'datetime' => $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : $value,
            default => $value,
        };
    }

    /**
     * Clears the UnitOfWork state.
     */
    public function clear(): void
    {
        $this->newEntities = new SplObjectStorage();
        // Do not clear managed entities, they are still tracked
        // $this->managedEntities = new SplObjectStorage(); 
        $this->removedEntities = new SplObjectStorage();
    }
}