<?php

namespace Ludelix\Interface\ORM;

interface QueryInterface
{
    public function execute(): mixed;
    public function getResult(): array;
    public function getOneOrNullResult(): ?object;
    public function getSingleResult(): object;
    public function getSingleScalarResult(): mixed;
    public function getScalarResult(): array;
    public function getArrayResult(): array;
    public function setParameter(string $key, mixed $value, ?string $type = null): self;
    public function setParameters(array $parameters): self;
    public function setMaxResults(?int $maxResults): self;
    public function setFirstResult(?int $firstResult): self;
    public function getSQL(): string;
}