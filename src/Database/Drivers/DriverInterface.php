<?php

namespace Ludelix\Database\Drivers;

use PDO;

/**
 * Interface that all database drivers must implement.
 *
 * Defines the standard methods for connecting and interacting with
 * different database systems.
 */
interface DriverInterface
{
    /**
     * Connects to the database.
     *
     * @param array $config Connection parameters (host, port, etc).
     * @return PDO The PDO connection instance.
     */
    public function connect(array $config): PDO;

    /**
     * Gets the driver name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Gets the supported features of the driver.
     *
     * @return array List of features (e.g., 'transactions', 'foreign_keys').
     */
    public function getSupportedFeatures(): array;

    /**
     * Builds the SQL for creating a new table.
     *
     * @param string $tableName The table name.
     * @param array  $columns   Column definitions.
     * @param array  $options   Table options.
     * @return string The CREATE TABLE SQL statement.
     */
    public function buildCreateTableSql(string $tableName, array $columns, array $options = []): string;

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param PDO $connection The active connection.
     * @return string
     */
    public function getLastInsertId(PDO $connection): string;

    /**
     * Checks if a table exists in the database.
     *
     * @param PDO    $connection The active connection.
     * @param string $tableName  The table name.
     * @return bool
     */
    public function tableExists(PDO $connection, string $tableName): bool;

    /**
     * Retrieves column information for a table.
     *
     * @param PDO    $connection The active connection.
     * @param string $tableName  The table name.
     * @return array List of column metadata.
     */
    public function getTableColumns(PDO $connection, string $tableName): array;
}