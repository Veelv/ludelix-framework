<?php

namespace Ludelix\Database\Evolution\Core;

use Ludelix\Database\Core\ConnectionManager;
use Ludelix\Database\Evolution\Snapshots\SnapshotManager;

class EvolutionManager
{
    protected ConnectionManager $connectionManager;
    protected SnapshotManager $snapshotManager;
    protected EntityAnalyzer $entityAnalyzer;
    protected string $evolutionsPath;
    protected array $appliedEvolutions = [];

    public function __construct(
        ConnectionManager $connectionManager,
        SnapshotManager $snapshotManager,
        string $evolutionsPath = 'database/evolutions'
    ) {
        $this->connectionManager = $connectionManager;
        $this->snapshotManager = $snapshotManager;
        $this->entityAnalyzer = new EntityAnalyzer();
        $this->evolutionsPath = $evolutionsPath;
        $this->loadAppliedEvolutions();
    }

    public function apply(string $target = null, bool $dryRun = false): array
    {
        $pending = $this->getPendingEvolutions();
        $applied = [];

        if ($target) {
            $pending = $this->filterToTarget($pending, $target);
        }

        foreach ($pending as $evolution) {
            if ($dryRun) {
                $applied[] = $evolution['id'] . ' (dry-run)';
                continue;
            }

            $this->applyEvolution($evolution);
            $applied[] = $evolution['id'];
        }

        return $applied;
    }

    public function revert(array $options): bool
    {
        if (isset($options['to-snapshot'])) {
            return $this->snapshotManager->restore($options['to-snapshot']);
        }

        if (isset($options['steps'])) {
            return $this->revertSteps((int) $options['steps']);
        }

        if (isset($options['to-evolution'])) {
            return $this->revertToEvolution($options['to-evolution']);
        }

        return false;
    }

    public function getStatus(): array
    {
        $applied = count($this->appliedEvolutions);
        $pending = count($this->getPendingEvolutions());
        $conflicts = $this->detectConflicts();
        $lastSnapshot = $this->snapshotManager->getLatest();

        return [
            'applied' => $applied,
            'pending' => $pending,
            'conflicts' => count($conflicts),
            'last_snapshot' => $lastSnapshot ? $lastSnapshot['name'] : null,
            'pending_evolutions' => $this->getPendingEvolutions(),
            'conflicts_list' => $conflicts
        ];
    }

    public function refresh(array $options = []): bool
    {
        if ($options['safe'] ?? false) {
            $this->snapshotManager->create('before_refresh_' . date('Y_m_d_H_i_s'));
        }

        if ($options['from-entities'] ?? false) {
            return $this->refreshFromEntities($options);
        }

        return $this->refreshFromEvolutions($options);
    }

    public function generateFromEntity(string $entityClass): array
    {
        return $this->entityAnalyzer->generateEvolution($entityClass);
    }

    protected function applyEvolution(array $evolution): void
    {
        $connection = $this->connectionManager->getConnection();
        
        try {
            $connection->beginTransaction();
            
            if ($evolution['format'] === 'yaml') {
                $this->applyYamlEvolution($evolution);
            } else {
                $this->applyPhpEvolution($evolution);
            }
            
            $this->markAsApplied($evolution['id']);
            $connection->commit();
            
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    protected function applyYamlEvolution(array $evolution): void
    {
        $data = yaml_parse_file($evolution['path']);
        $forward = $data['forward'];

        foreach ($forward as $action => $config) {
            match($action) {
                'create_table' => $this->createTable($config),
                'modify_table' => $this->modifyTable($config),
                'drop_table' => $this->dropTable($config),
                default => throw new \Exception("Unknown action: {$action}")
            };
        }
    }

    protected function applyPhpEvolution(array $evolution): void
    {
        $evolutionInstance = require $evolution['path'];
        $evolutionInstance->forward();
    }

    protected function getPendingEvolutions(): array
    {
        $allEvolutions = $this->scanEvolutions();
        $pending = [];

        foreach ($allEvolutions as $evolution) {
            if (!in_array($evolution['id'], $this->appliedEvolutions)) {
                $pending[] = $evolution;
            }
        }

        return $pending;
    }

    protected function scanEvolutions(): array
    {
        $evolutions = [];
        $files = glob($this->evolutionsPath . '/*');

        foreach ($files as $file) {
            if (str_ends_with($file, '.yaml') || str_ends_with($file, '.yml')) {
                $evolutions[] = $this->parseYamlEvolution($file);
            } elseif (str_ends_with($file, '.php')) {
                $evolutions[] = $this->parsePhpEvolution($file);
            }
        }

        usort($evolutions, fn($a, $b) => strcmp($a['id'], $b['id']));
        return $evolutions;
    }

    protected function parseYamlEvolution(string $file): array
    {
        $data = yaml_parse_file($file);
        return [
            'id' => $data['evolution']['id'],
            'description' => $data['evolution']['description'],
            'path' => $file,
            'format' => 'yaml'
        ];
    }

    protected function parsePhpEvolution(string $file): array
    {
        $evolution = require $file;
        return [
            'id' => $evolution->id,
            'description' => $evolution->description,
            'path' => $file,
            'format' => 'php'
        ];
    }

    protected function loadAppliedEvolutions(): void
    {
        $connection = $this->connectionManager->getConnection();
        
        try {
            $stmt = $connection->query("SELECT evolution_id FROM ludelix_evolutions ORDER BY applied_at");
            $this->appliedEvolutions = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            $this->createEvolutionsTable();
            $this->appliedEvolutions = [];
        }
    }

    protected function createEvolutionsTable(): void
    {
        $connection = $this->connectionManager->getConnection();
        $sql = "CREATE TABLE ludelix_evolutions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            evolution_id VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $connection->exec($sql);
    }

    protected function markAsApplied(string $evolutionId): void
    {
        $connection = $this->connectionManager->getConnection();
        $stmt = $connection->prepare("INSERT INTO ludelix_evolutions (evolution_id) VALUES (?)");
        $stmt->execute([$evolutionId]);
        $this->appliedEvolutions[] = $evolutionId;
    }

    protected function createTable(array $config): void
    {
        $connection = $this->connectionManager->getConnection();
        $tableName = $config['name'];
        $columns = [];

        foreach ($config['columns'] as $name => $definition) {
            $columns[] = $this->buildColumnDefinition($name, $definition);
        }

        $sql = "CREATE TABLE {$tableName} (" . implode(', ', $columns) . ")";
        $connection->exec($sql);
    }

    protected function buildColumnDefinition(string $name, array $definition): string
    {
        $sql = "{$name} " . strtoupper($definition['type']);
        
        if ($definition['nullable'] ?? false) {
            $sql .= ' NULL';
        } else {
            $sql .= ' NOT NULL';
        }

        if (isset($definition['default'])) {
            $sql .= " DEFAULT {$definition['default']}";
        }

        if ($definition['auto_increment'] ?? false) {
            $sql .= ' AUTO_INCREMENT';
        }

        if ($definition['primary'] ?? false) {
            $sql .= ' PRIMARY KEY';
        }

        return $sql;
    }

    protected function detectConflicts(): array
    {
        // Implementar detecção de conflitos
        return [];
    }

    protected function refreshFromEntities(array $options): bool
    {
        // Implementar refresh a partir de entities
        return true;
    }

    protected function refreshFromEvolutions(array $options): bool
    {
        // Drop all tables and reapply evolutions
        return true;
    }
}