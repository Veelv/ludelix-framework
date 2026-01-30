<?php

namespace Ludelix\Interface\ORM;

interface ConnectionInterface
{
    public function connect(): void;
    public function disconnect(): void;
    public function isConnected(): bool;
    public function prepare(string $sql): \PDOStatement;
    public function execute(string $sql, array $params = []): bool;
    public function query(string $sql): \PDOStatement;
    public function lastInsertId(): string;
    public function beginTransaction(): bool;
    public function commit(): bool;
    public function rollback(): bool;
    public function isTransactionActive(): bool;
    public function close(): void;
}