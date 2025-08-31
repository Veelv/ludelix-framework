<?php

namespace Ludelix\Database\Evolution\Snapshots;

use Ludelix\Database\Core\ConnectionManager;

class SnapshotManager
{
    protected ConnectionManager $connectionManager;
    protected string $snapshotsPath;
    protected DiffEngine $diffEngine;

    public function __construct(
        ConnectionManager $connectionManager,
        string $snapshotsPath = 'database/snapshots'
    ) {
        $this->connectionManager = $connectionManager;
        $this->snapshotsPath = $snapshotsPath;
        $this->diffEngine = new DiffEngine($connectionManager);
        $this->ensureSnapshotsDirectory();
    }

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

    public function restore(string $snapshotId): bool
    {
        $snapshot = $this->loadSnapshot($snapshotId);
        if (!$snapshot) {
            throw new \Exception("Snapshot '{$snapshotId}' not found");
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
            
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

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

    public function delete(string $snapshotId): bool
    {
        $file = $this->snapshotsPath . '/' . $snapshotId . '.json';
        if (file_exists($file)) {
            return unlink($file);
        }
        return false;
    }

    public function getLatest(): ?array
    {
        $snapshots = $this->list();
        return $snapshots[0] ?? null;
    }

    public function diff(string $snapshot1, string $snapshot2): array
    {
        $snap1 = $this->loadSnapshot($snapshot1);
        $snap2 = $this->loadSnapshot($snapshot2);
        
        if (!$snap1 || !$snap2) {
            throw new \Exception("One or both snapshots not found");
        }

        return $this->diffEngine->compare($snap1['schema'], $snap2['schema']);
    }

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

    protected function getTables($connection): array
    {
        $stmt = $connection->query("SHOW TABLES");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    protected function getTableColumns($connection, string $table): array
    {
        $stmt = $connection->query("DESCRIBE {$table}");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function getTableIndexes($connection, string $table): array
    {
        $stmt = $connection->query("SHOW INDEX FROM {$table}");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function getTableConstraints($connection, string $table): array
    {
        // Implementation depends on database type
        return [];
    }

    protected function getTablesCount($connection): int
    {
        return count($this->getTables($connection));
    }

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

    protected function saveSnapshot(string $id, array $snapshot): void
    {
        $file = $this->snapshotsPath . '/' . $id . '.json';
        file_put_contents($file, json_encode($snapshot, JSON_PRETTY_PRINT));
    }

    protected function loadSnapshot(string $id): ?array
    {
        $file = $this->snapshotsPath . '/' . $id . '.json';
        if (!file_exists($file)) {
            return null;
        }
        return json_decode(file_get_contents($file), true);
    }

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

    protected function restoreSchema($connection, array $schema): void
    {
        foreach ($schema as $table => $definition) {
            $this->createTableFromDefinition($connection, $table, $definition);
        }
    }

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

    protected function createTableFromDefinition($connection, string $table, array $definition): void
    {
        // Simplified table creation - would need more sophisticated logic
        $columns = [];
        foreach ($definition['columns'] as $column) {
            $columns[] = $column['Field'] . ' ' . $column['Type'] . 
                        ($column['Null'] === 'NO' ? ' NOT NULL' : '') .
                        ($column['Default'] ? ' DEFAULT ' . $column['Default'] : '');
        }
        
        $sql = "CREATE TABLE {$table} (" . implode(', ', $columns) . ")";
        $connection->exec($sql);
    }

    protected function ensureSnapshotsDirectory(): void
    {
        if (!is_dir($this->snapshotsPath)) {
            mkdir($this->snapshotsPath, 0755, true);
        }
    }
}