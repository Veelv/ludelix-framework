<?php

namespace Ludelix\Database\Evolution\Snapshots;

use Ludelix\Database\Core\ConnectionManager;
use Exception;
use Throwable;

/**
 * Manages database schema snapshots.
 *
 * Handles creation, restoration, listing, and deletion of snapshots.
 * A snapshot captures the database schema and optionally data at a point in time.
 */
class SnapshotManager
{
    /** @var ConnectionManager The connection manager. */
    protected ConnectionManager $connectionManager;

    /** @var string Directory to store snapshot files. */
    protected string $snapshotsPath;

    /** @var DiffEngine The difference engine for comparing snapshots. */
    protected DiffEngine $diffEngine;

    /**
     * Initializes the SnapshotManager.
     *
     * @param ConnectionManager $connectionManager
     * @param string            $snapshotsPath Path to storage directory.
     */
    public function __construct(
        ConnectionManager $connectionManager,
        string $snapshotsPath = 'database/snapshots'
    ) {
        $this->connectionManager = $connectionManager;
        $this->snapshotsPath = $snapshotsPath;
        $this->diffEngine = new DiffEngine($connectionManager);
        $this->ensureSnapshotsDirectory();
    }

    /**
     * Creates a new snapshot of the current database state.
     *
     * @param string $name    A descriptive name for the snapshot.
     * @param array  $options Options like 'with-data' => true.
     * @return array The created snapshot structure.
     */
    public function create(string $name, array $options = []): array
    {
        $connection = $this->connectionManager->getConnection();
        $timestamp = date('Y_m_d_H_i_s');
        $snapshotId = $timestamp . '_' . $name;

        $snapshot = [
            'id' => $snapshotId,
            'name' => $name,
            'created_at' => date('Y-m-d H:i:s'),
            'schema' => $this->captureSchema($connection),
            'data' => $options['with-data'] ?? false ? $this->captureData($connection) : null,
            'metadata' => [
                'tables_count' => $this->getTablesCount($connection),
                'size' => $this->getDatabaseSize($connection)
            ]
        ];

        $this->saveSnapshot($snapshotId, $snapshot);
        return $snapshot;
    }

    /**
     * Restores the database to a previous state using a snapshot ID.
     *
     * drops current tables and recreates them from the snapshot definition.
     *
     * @param string $snapshotId The ID of the snapshot to restore.
     * @return bool True on success.
     * @throws Exception If snapshot not found.
     * @throws Throwable If restoration fails.
     */
    public function restore(string $snapshotId): bool
    {
        $snapshot = $this->loadSnapshot($snapshotId);
        if (!$snapshot) {
            throw new Exception("Snapshot '{$snapshotId}' not found");
        }

        $connection = $this->connectionManager->getConnection();

        try {
            $connection->beginTransaction();

            // Drop all tables
            $this->dropAllTables($connection);

            // Restore schema
            $this->restoreSchema($connection, $snapshot['schema']);

            // Restore data if available
            if ($snapshot['data']) {
                $this->restoreData($connection, $snapshot['data']);
            }

            $connection->commit();
            return true;

        } catch (Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    /**
     * Lists all available snapshots.
     *
     * @return array List of snapshot metadata sorted by creation date descending.
     */
    public function list(): array
    {
        $snapshots = [];
        $files = glob($this->snapshotsPath . '/*.json');

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            $snapshots[] = [
                'id' => $data['id'],
                'name' => $data['name'],
                'created_at' => $data['created_at'],
                'size' => $data['metadata']['size'] ?? 'Unknown',
                'tables' => $data['metadata']['tables_count'] ?? 0,
                'has_data' => !empty($data['data'])
            ];
        }

        usort($snapshots, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
        return $snapshots;
    }

    /**
     * Deletes a snapshot file.
     *
     * @param string $snapshotId
     * @return bool True if deleted, false if not found.
     */
    public function delete(string $snapshotId): bool
    {
        $file = $this->snapshotsPath . '/' . $snapshotId . '.json';
        if (file_exists($file)) {
            return unlink($file);
        }
        return false;
    }

    /**
     * Retrieves metadata for the most recent snapshot.
     *
     * @return array|null Snapshot metadata or null if none exist.
     */
    public function getLatest(): ?array
    {
        $snapshots = $this->list();
        return $snapshots[0] ?? null;
    }

    /**
     * Compares two snapshots and returns the schema difference.
     *
     * @param string $snapshot1 ID of the first snapshot.
     * @param string $snapshot2 ID of the second snapshot.
     * @return array Difference array.
     * @throws Exception If snapshots are not found.
     */
    public function diff(string $snapshot1, string $snapshot2): array
    {
        $snap1 = $this->loadSnapshot($snapshot1);
        $snap2 = $this->loadSnapshot($snapshot2);

        if (!$snap1 || !$snap2) {
            throw new Exception("One or both snapshots not found");
        }

        return $this->diffEngine->compare($snap1['schema'], $snap2['schema']);
    }

    /**
     * Captures the full schema definition.
     *
     * @param \PDO $connection
     * @return array
     */
    protected function captureSchema($connection): array
    {
        $schema = [];
        $tables = $this->getTables($connection);

        foreach ($tables as $table) {
            $schema[$table] = [
                'columns' => $this->getTableColumns($connection, $table),
                'indexes' => $this->getTableIndexes($connection, $table),
                'constraints' => $this->getTableConstraints($connection, $table)
            ];
        }

        return $schema;
    }

    /**
     * Captures data from all tables.
     *
     * @param \PDO $connection
     * @return array
     */
    protected function captureData($connection): array
    {
        $data = [];
        $tables = $this->getTables($connection);

        foreach ($tables as $table) {
            $stmt = $connection->query("SELECT * FROM {$table}");
            $data[$table] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        return $data;
    }

    /**
     * Gets list of table names.
     *
     * @param \PDO $connection
     * @return array
     */
    protected function getTables($connection): array
    {
        $stmt = $connection->query("SHOW TABLES");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Gets columns for a table.
     *
     * @param \PDO   $connection
     * @param string $table
     * @return array
     */
    protected function getTableColumns($connection, string $table): array
    {
        $stmt = $connection->query("DESCRIBE {$table}");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Gets indexes for a table.
     *
     * @param \PDO   $connection
     * @param string $table
     * @return array
     */
    protected function getTableIndexes($connection, string $table): array
    {
        $stmt = $connection->query("SHOW INDEX FROM {$table}");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Gets constraints for a table.
     *
     * @param \PDO   $connection
     * @param string $table
     * @return array
     */
    protected function getTableConstraints($connection, string $table): array
    {
        // Implementation might vary by driver
        return [];
    }

    /**
     * Counts tables in the current database.
     *
     * @param \PDO $connection
     * @return int
     */
    protected function getTablesCount($connection): int
    {
        return count($this->getTables($connection));
    }

    /**
     * Estimates database size in MB.
     *
     * @param \PDO $connection
     * @return string
     */
    protected function getDatabaseSize($connection): string
    {
        try {
            $stmt = $connection->query("
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'size_mb'
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            ");
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return ($result['size_mb'] ?? 0) . ' MB';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Saves snapshot data to a JSON file.
     *
     * @param string $id
     * @param array  $snapshot
     */
    protected function saveSnapshot(string $id, array $snapshot): void
    {
        $file = $this->snapshotsPath . '/' . $id . '.json';
        file_put_contents($file, json_encode($snapshot, JSON_PRETTY_PRINT));
    }

    /**
     * Loads snapshot data from a JSON file.
     *
     * @param string $id
     * @return array|null
     */
    protected function loadSnapshot(string $id): ?array
    {
        $file = $this->snapshotsPath . '/' . $id . '.json';
        if (!file_exists($file)) {
            return null;
        }
        return json_decode(file_get_contents($file), true);
    }

    /**
     * Drops all tables in the current database.
     *
     * @param \PDO $connection
     */
    protected function dropAllTables($connection): void
    {
        $tables = $this->getTables($connection);

        // Disable foreign key checks
        $connection->exec("SET FOREIGN_KEY_CHECKS = 0");

        foreach ($tables as $table) {
            $connection->exec("DROP TABLE IF EXISTS {$table}");
        }

        // Re-enable foreign key checks
        $connection->exec("SET FOREIGN_KEY_CHECKS = 1");
    }

    /**
     * Restores schema structure from definition.
     *
     * @param \PDO  $connection
     * @param array $schema
     */
    protected function restoreSchema($connection, array $schema): void
    {
        foreach ($schema as $table => $definition) {
            $this->createTableFromDefinition($connection, $table, $definition);
        }
    }

    /**
     * Restores data records into tables.
     *
     * @param \PDO  $connection
     * @param array $data
     */
    protected function restoreData($connection, array $data): void
    {
        foreach ($data as $table => $records) {
            foreach ($records as $record) {
                $columns = implode(', ', array_keys($record));
                $placeholders = ':' . implode(', :', array_keys($record));

                $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
                $stmt = $connection->prepare($sql);
                $stmt->execute($record);
            }
        }
    }

    /**
     * Creates a table from its schema definition.
     *
     * @param \PDO   $connection
     * @param string $table
     * @param array  $definition
     */
    protected function createTableFromDefinition($connection, string $table, array $definition): void
    {
        // Simplified table creation logic
        $columns = [];
        foreach ($definition['columns'] as $column) {
            $columns[] = $column['Field'] . ' ' . $column['Type'] .
                ($column['Null'] === 'NO' ? ' NOT NULL' : '') .
                ($column['Default'] ? ' DEFAULT ' . $column['Default'] : '');
        }

        $sql = "CREATE TABLE {$table} (" . implode(', ', $columns) . ")";
        $connection->exec($sql);
    }

    /**
     * Ensures the snapshots storage directory exists.
     */
    protected function ensureSnapshotsDirectory(): void
    {
        if (!is_dir($this->snapshotsPath)) {
            mkdir($this->snapshotsPath, 0755, true);
        }
    }
}