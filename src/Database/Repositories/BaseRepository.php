<?php

namespace Ludelix\Database\Repositories;

use Ludelix\Database\Core\EntityManager;
use Ludelix\Database\Metadata\EntityMetadata;
use Ludelix\Translation\Support\TranslatableTrait;

/**
 * Base Repository implementation with built-in translation support.
 *
 * Provides generic methods for finding, saving, and deleting entities,
 * automatically handling translation context if the entity supports it.
 */
class BaseRepository
{
    use TranslatableTrait;

    protected EntityManager $entityManager;
    protected EntityMetadata $metadata;
    protected string $entityClass;

    /**
     * @param EntityManager  $entityManager
     * @param EntityMetadata $metadata
     */
    public function __construct(EntityManager $entityManager, EntityMetadata $metadata)
    {
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
        $this->entityClass = $metadata->getClassName();

        // Set default translation namespace based on entity class
        $this->setTranslationNamespace($this->getDefaultTranslationNamespace());
    }

    /**
     * Generates the default translation namespace based on the entity class name.
     * 
     * Converts 'App\Models\UserProfile' to 'entities.user_profile'.
     *
     * @return string
     */
    protected function getDefaultTranslationNamespace(): string
    {
        $className = $this->entityClass;
        $parts = explode('\\', $className);
        $entityName = end($parts);

        // Convert CamelCase to snake_case
        $key = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $entityName));

        return 'entities.' . $key;
    }

    /**
     * Finds an entity by its identifier.
     * Applies translation context if supported.
     *
     * @param mixed $id
     * @return object|null
     */
    public function find(mixed $id): ?object
    {
        $entity = $this->entityManager->find($this->entityClass, $id);

        if ($entity && method_exists($entity, 'setTranslator')) {
            $entity->setTranslator($this->translator);
            $entity->setLocale($this->currentLocale);
        }

        return $entity;
    }

    /**
     * Finds all entities of the repository's type.
     * Applies translation context if supported.
     *
     * @return array
     */
    public function findAll(): array
    {
        $entities = $this->entityManager->findAll($this->entityClass);

        foreach ($entities as $entity) {
            if (method_exists($entity, 'setTranslator')) {
                $entity->setTranslator($this->translator);
                $entity->setLocale($this->currentLocale);
            }
        }

        return $entities;
    }

    /**
     * Finds entities matching a set of criteria.
     * Applies translation context if supported.
     *
     * @param array $criteria
     * @return array
     */
    public function findBy(array $criteria): array
    {
        $entities = $this->entityManager->findBy($this->entityClass, $criteria);

        foreach ($entities as $entity) {
            if (method_exists($entity, 'setTranslator')) {
                $entity->setTranslator($this->translator);
                $entity->setLocale($this->currentLocale);
            }
        }

        return $entities;
    }

    /**
     * Finds a single entity matching the criteria.
     *
     * @param array $criteria
     * @return object|null
     */
    public function findOneBy(array $criteria): ?object
    {
        $results = $this->findBy($criteria);
        return $results[0] ?? null;
    }

    /**
     * Persists and flushes an entity.
     * Sets the translator on the entity before saving.
     *
     * @param object $entity
     */
    public function save(object $entity): void
    {
        // Set translator on entity before saving
        if (method_exists($entity, 'setTranslator')) {
            $entity->setTranslator($this->translator);
            $entity->setLocale($this->currentLocale);
        }

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    /**
     * Removes and flushes an entity.
     *
     * @param object $entity
     */
    public function delete(object $entity): void
    {
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }

    /**
     * Creates a QueryBuilder for the repository's entity.
     *
     * @param string $alias Table alias (default: 'e').
     * @return \Ludelix\Database\Core\QueryBuilder
     */
    public function createQueryBuilder(string $alias = 'e')
    {
        return $this->entityManager->createQueryBuilder($this->entityClass, $alias);
    }

    /**
     * Gets a translated error message.
     *
     * @param string $key        Translation key suffix.
     * @param array  $parameters Replacement parameters.
     * @return string
     */
    public function getTranslatedError(string $key, array $parameters = []): string
    {
        return $this->trans('errors.' . $key, $parameters);
    }

    /**
     * Gets a translated success message.
     *
     * @param string $key        Translation key suffix.
     * @param array  $parameters Replacement parameters.
     * @return string
     */
    public function getTranslatedSuccess(string $key, array $parameters = []): string
    {
        return $this->trans('success.' . $key, $parameters);
    }

    /**
     * Gets a translated validation message.
     *
     * @param string $key        Translation key suffix.
     * @param array  $parameters Replacement parameters.
     * @return string
     */
    public function getTranslatedValidation(string $key, array $parameters = []): string
    {
        return $this->trans('validation.' . $key, $parameters);
    }
}