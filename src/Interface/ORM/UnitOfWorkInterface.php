<?php

namespace Ludelix\Interface\ORM;

interface UnitOfWorkInterface
{
    public function persist(object $entity): void;
    public function remove(object $entity): void;
    public function merge(object $entity): object;
    public function refresh(object $entity): void;
    public function detach(object $entity): void;
    public function commit(): void;
    public function clear(?string $entityClass = null): void;
    public function registerManaged(object $entity, mixed $id, array $data): void;
    public function tryGetById(string $entityClass, mixed $id): ?object;
    public function isScheduledForInsert(object $entity): bool;
    public function isScheduledForUpdate(object $entity): bool;
    public function isScheduledForDelete(object $entity): bool;
    public function isInIdentityMap(object $entity): bool;
    public function size(): int;
}