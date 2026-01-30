<?php

namespace Ludelix\Database\Seeders\Core;

use Ludelix\Database\Core\ConnectionManager;
use Ludelix\Database\Seeders\Factories\DataFactory;
use Exception;
use PDOException;

/**
 * Manages the execution of database seeders.
 *
 * Handles running seeders, tracking executed seeders, and providing
 * data generation capabilities.
 */
class SeederManager
{
    protected ConnectionManager $connectionManager;
    protected DataFactory $factory;
    protected string $seedersPath;
    protected array $executedSeeders = [];

    public function __construct(
        ConnectionManager $connectionManager,
        string $seedersPath = 'database/seeders'
    ) {
        $this->connectionManager = $connectionManager;
        $this->factory = new DataFactory();
        $this->seedersPath = $seedersPath;
        $this->loadExecutedSeeders();
    }

    /**
     * Runs the seeders based on provided options.
     *
     * @param array $options Options like 'fresh', 'class', 'table'.
     * @return array List of executed seeder names.
     */
    public function seed(array $options = []): array
    {
        $seeders = $this->getSeeders($options);
        $executed = [];

        foreach ($seeders as $seeder) {
            if ($options['fresh'] ?? false) {
                $this->executeSeeder($seeder);
                $executed[] = $seeder['name'];
            } elseif (!$this->isExecuted($seeder['name'])) {
                $this->executeSeeder($seeder);
                $executed[] = $seeder['name'];
            }
        }

        return $executed;
    }

    /**
     * Clears execution history and runs all seeders fresh.
     *
     * @return array List of executed seeder names.
     */
    public function fresh(): array
    {
        $this->clearExecutedSeeders();
        return $this->seed(['fresh' => true]);
    }

    /**
     * Generates and inserts data for a table using a factory.
     *
     * @param string $table   Table name.
     * @param int    $count   Number of records.
     * @param array  $options Generation options.
     * @return array The generated data.
     * @throws Exception If no factory is registered for the table.
     */
    public function generate(string $table, int $count = 10, array $options = []): array
    {
        if (!$this->factory->has($table)) {
            throw new Exception("No factory registered for table: {$table}. Please register a factory first.");
        }

        $connection = $this->connectionManager->getConnection();
        $generated = [];

        for ($i = 0; $i < $count; $i++) {
            $data = $this->factory->generate($table, $options);

            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));

            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
            $stmt = $connection->prepare($sql);
            $stmt->execute($data);

            $generated[] = $data;
        }

        return $generated;
    }

    /**
     * Truncates the specified tables.
     *
     * @param array $tables List of table names.
     */
    public function truncate(array $tables): void
    {
        $connection = $this->connectionManager->getConnection();

        foreach ($tables as $table) {
            $connection->exec("TRUNCATE TABLE {$table}");
        }
    }

    /**
     * Gets the current status of seeders (total, executed, pending).
     *
     * @return array Map of status data.
     */
    public function getStatus(): array
    {
        $allSeeders = $this->scanSeeders();
        $executed = count($this->executedSeeders);
        $pending = count($allSeeders) - $executed;

        return [
            'total' => count($allSeeders),
            'executed' => $executed,
            'pending' => $pending,
            'seeders' => $allSeeders,
            'executed_list' => $this->executedSeeders
        ];
    }

    /**
     * Gets the database connection.
     *
     * @return \PDO
     */
    public function getConnection()
    {
        return $this->connectionManager->getConnection();
    }

    protected function executeSeeder(array $seeder): void
    {
        if ($seeder['format'] === 'yaml') {
            $this->executeYamlSeeder($seeder);
        } else {
            $this->executePhpSeeder($seeder);
        }

        $this->markAsExecuted($seeder['name']);
    }

    protected function executeYamlSeeder(array $seeder): void
    {
        $data = yaml_parse_file($seeder['path']);

        foreach ($data['seed'] as $table => $config) {
            if (isset($config['data'])) {
                $this->insertData($table, $config['data']);
            }

            if (isset($config['factory'])) {
                $count = $config['factory']['count'] ?? 10;
                $options = $config['factory']['options'] ?? [];
                $this->generate($table, $count, $options);
            }
        }
    }

    protected function executePhpSeeder(array $seeder): void
    {
        $seederInstance = require $seeder['path'];
        $seederInstance->seed($this);
    }

    protected function insertData(string $table, array $records): void
    {
        $connection = $this->connectionManager->getConnection();

        foreach ($records as $record) {
            $columns = implode(', ', array_keys($record));
            $placeholders = ':' . implode(', :', array_keys($record));

            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
            $stmt = $connection->prepare($sql);
            $stmt->execute($record);
        }
    }

    protected function getSeeders(array $options): array
    {
        $seeders = $this->scanSeeders();

        if (isset($options['class'])) {
            return array_filter($seeders, fn($s) => $s['name'] === $options['class']);
        }

        if (isset($options['table'])) {
            return array_filter($seeders, fn($s) => str_contains($s['name'], $options['table']));
        }

        return $seeders;
    }

    protected function scanSeeders(): array
    {
        $seeders = [];
        $files = glob($this->seedersPath . '/*');

        foreach ($files as $file) {
            if (str_ends_with($file, '.yaml') || str_ends_with($file, '.yml')) {
                $seeders[] = $this->parseYamlSeeder($file);
            } elseif (str_ends_with($file, '.php')) {
                $seeders[] = $this->parsePhpSeeder($file);
            }
        }

        return $seeders;
    }

    protected function parseYamlSeeder(string $file): array
    {
        $data = yaml_parse_file($file);
        return [
            'name' => basename($file, '.yaml'),
            'description' => $data['seeder']['description'] ?? '',
            'path' => $file,
            'format' => 'yaml'
        ];
    }

    protected function parsePhpSeeder(string $file): array
    {
        $seeder = require $file;
        return [
            'name' => basename($file, '.php'),
            'description' => $seeder->description ?? '',
            'path' => $file,
            'format' => 'php'
        ];
    }

    protected function loadExecutedSeeders(): void
    {
        $connection = $this->connectionManager->getConnection();

        try {
            $stmt = $connection->query("SELECT seeder_name FROM ludelix_seeders ORDER BY executed_at");
            $this->executedSeeders = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $this->createSeedersTable();
            $this->executedSeeders = [];
        }
    }

    protected function createSeedersTable(): void
    {
        $connection = $this->connectionManager->getConnection();
        $sql = "CREATE TABLE ludelix_seeders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            seeder_name VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $connection->exec($sql);
    }

    protected function markAsExecuted(string $seederName): void
    {
        $connection = $this->connectionManager->getConnection();
        $stmt = $connection->prepare("INSERT IGNORE INTO ludelix_seeders (seeder_name) VALUES (?)");
        $stmt->execute([$seederName]);

        if (!in_array($seederName, $this->executedSeeders)) {
            $this->executedSeeders[] = $seederName;
        }
    }

    protected function isExecuted(string $seederName): bool
    {
        return in_array($seederName, $this->executedSeeders);
    }

    protected function clearExecutedSeeders(): void
    {
        $connection = $this->connectionManager->getConnection();
        $connection->exec("DELETE FROM ludelix_seeders");
        $this->executedSeeders = [];
    }
}