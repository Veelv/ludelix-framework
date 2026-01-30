<?php

namespace Ludelix\Interface\ORM;

interface EntityMetadataInterface
{
    public function getClassName(): string;
    public function getTableName(): string;
    public function getIdentifierFieldName(): ?string;
    public function hasAutoIncrementId(): bool;
    public function getProperties(): array;
    public function getProperty(string $name): ?object;
    public function getPropertyByColumn(string $columnName): ?object;
    public function getAssociations(): array;
    public function hasAssociation(string $name): bool;
    public function getLifecycleCallbacks(): array;
    public function hasLifecycleCallback(string $event): bool;
}