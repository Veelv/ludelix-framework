<?php

namespace Ludelix\Database\Evolution\Core;

use Ludelix\Database\Core\ConnectionManager;
use Ludelix\Database\Evolution\Snapshots\SnapshotManager;
use Exception;
use Throwable;
use PDOException;

/**
 * Core manager for Database Evolutions (migrations).
 *
 * Orchestrates the application, reversion, and monitoring of schema changes.
 * Handles both YAML-based and PHP-based evolutions.
 */
class EvolutionManager
{
    /** @var ConnectionManager The database connection manager instance. */
    protected ConnectionManager $connectionManager;

    /** @var SnapshotManager The snapshot manager instance. */
    protected SnapshotManager $snapshotManager;

    /** @var EntityAnalyzer The entity analyzer for schema generation. */
    protected EntityAnalyzer $entityAnalyzer;

    /** @var string The directory path where evolution files are stored. */
    protected string $evolutionsPath;

    /** @var array<string> List of applied evolution IDs. */
    protected array $appliedEvolutions = [];

    /**
     * Initializes the Evolution Manager.
     *
     * @param ConnectionManager $connectionManager
     * @param SnapshotManager   $snapshotManager
     * @param string            $evolutionsPath    Path to evolutions directory.
     */
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

    /**
     * Applies pending evolutions.
     *
     * @param string|null $target Optional target evolution ID.
     * @param bool        $dryRun If true, only simulates execution.
     * @return array List of applied evolution IDs.
     * @throws Throwable If an evolution fails.
     */
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

    /**
     * Reverts applied evolutions based on options.
     *
     * Options:
     * - 'to-snapshot': Restore a specific snapshot.
     * - 'steps': Revert a specific number of evolutions.
     * - 'to-evolution': Revert back to a specific evolution ID.
     *
     * @param array $options Revert options.
     * @return bool True on success, false on failure.
     */
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

    /**
     * Retrieves the current status of the evolution system.
     *
     * @return array Status information (applied count, pending count, conflicts, etc.).
     */
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

    /**
     * Refreshes the database schema.
     *
     * Can operate by wiping and re-running evolutions or by synching from entity definitions.
     *
     * @param array $options Options (safe, from-entities, etc.).
     * @return bool True on success.
     */
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

    /**
     * Generates a schema definition array from an entity class.
     *
     * @param string $entityClass
     * @return array
     */
    public function generateFromEntity(string $entityClass): array
    {
        return $this->entityAnalyzer->generateEvolution($entityClass);
    }

    /**
     * Internal method to apply a single evolution within a transaction.
     *
     * @param array $evolution
     * @throws Throwable
     */
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

        } catch (Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    /**
     * Applies a YAML-defined evolution.
     *
     * @param array $evolution
     * @throws Exception
     */
    protected function applyYamlEvolution(array $evolution): void
    {
        $data = yaml_parse_file($evolution['path']);
        $forward = $data['forward'];

        foreach ($forward as $action => $config) {
            match ($action) {
                'create_table' => $this->createTable($config),
                'modify_table' => $this->modifyTable($config),
                'drop_table' => $this->dropTable($config),
                default => throw new Exception("Unknown action: {$action}")
            };
        }
    }

    /**
     * Applies a PHP-defined evolution.
     *
     * @param array $evolution
     */
    protected function applyPhpEvolution(array $evolution): void
    {
        $evolutionInstance = require $evolution['path'];
        $evolutionInstance->forward();
    }

    /**
     * Identifies pending evolutions that haven't been applied yet.
     *
     * @return array
     */
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

    /**
     * Scans the evolutions directory for valid files.
     *
     * @return array List of discovered evolutions.
     */
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

    /**
     * Parses metadata from a YAML evolution file.
     *
     * @param string $file
     * @return array
     */
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

    /**
     * Parses metadata from a PHP evolution file.
     *
     * @param string $file
     * @return array
     */
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

    /**
     * Loads the list of already applied evolutions from the database.
     */
    protected function loadAppliedEvolutions(): void
    {
        $connection = $this->connectionManager->getConnection();

        try {
            $stmt = $connection->query("SELECT evolution_id FROM ludelix_evolutions ORDER BY applied_at");
            $this->appliedEvolutions = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $this->createEvolutionsTable();
            $this->appliedEvolutions = [];
        }
    }

    /**
     * Creates the internal tracking table for evolutions.
     */
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

    /**
     * Records an evolution as applied in the database.
     *
     * @param string $evolutionId
     */
    protected function markAsApplied(string $evolutionId): void
    {
        $connection = $this->connectionManager->getConnection();
        $stmt = $connection->prepare("INSERT INTO ludelix_evolutions (evolution_id) VALUES (?)");
        $stmt->execute([$evolutionId]);
        $this->appliedEvolutions[] = $evolutionId;
    }

    /**
     * Executes table creation from config array.
     *
     * @param array $config
     */
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

    /**
     * Builds SQL string for a column definition.
     *
     * @param string $name
     * @param array $definition
     * @return string
     */
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

    /**
     * Detects conflicts between pending evolutions and current state.
     *
     * @return array
     */
    protected function detectConflicts(): array
    {
        // TODO: Implement comprehensive conflict detection logic
        return [];
    }

    /**
     * Refreshes the database schema based on entity definitions.
     *
     * @param array $options
     * @return bool
     */
    protected function refreshFromEntities(array $options): bool
    {
        // TODO: Implement schema synchronization from entity analysis
        return true;
    }

    /**
     * Drops all tables and re-applies all evolutions.
     *
     * @param array $options
     * @return bool
     */
    protected function refreshFromEvolutions(array $options): bool
    {
        // TODO: Implement full database reset and migration re-run
        return true;
    }
}