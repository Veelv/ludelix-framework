<?php

namespace Ludelix\Database\Repositories;

interface RepositoryInterface
{
    public function find(mixed $id): ?object;
    public function findAll(): array;
    public function findBy(array $criteria): array;
    public function save(object $entity): void;
    public function delete(object $entity): void;
}