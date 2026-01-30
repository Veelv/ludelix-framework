<?php

namespace Ludelix\Database\Core;

use Ludelix\Database\Drivers\MySQLDriver;
use Ludelix\Database\Drivers\PgSQLDriver;
use Ludelix\Database\Drivers\SQLiteDriver;
use PDO;
use InvalidArgumentException;

/**
 * Manages database connections and driver instantiation.
 *
 * Handles configuration validation, connection creation, and reusing
 * active connections (singleton-per-name pattern).
 */
class ConnectionManager
{
    /** @var array<string, mixed> Cache of active connections */
    protected array $connections = [];

    /** @var array The full database configuration */
    protected array $config;

    /**
     * Registry of supported database drivers.
     * @var array<string, string> Map of driver name to Driver class FQN.
     */
    protected array $drivers = [
        'mysql' => MySQLDriver::class,
        'pgsql' => PgSQLDriver::class,
        'sqlite' => SQLiteDriver::class,
        'mongodb' => \Ludelix\Database\Drivers\MongoDBDriver::class,
    ];

    /**
     * Initializes the manager with configuration.
     *
     * Validates the configuration immediately upon instantiation.
     *
     * @param array $config Database configuration array.
     * @throws InvalidArgumentException If configuration is invalid or missing.
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        // Validate configuration before allowing connections
        try {
            ConfigurationValidator::validate($config);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                "Invalid database configuration: " . $e->getMessage()
            );
        }

        // Check if using default values and warn
        if (ConfigurationValidator::isUsingDefaultValues($config)) {
            throw new InvalidArgumentException(
                "Database configuration not found. " .
                "Configure database variables in your .env file or " .
                "explicitly define the configuration in config/database.php. "
            );
        }
    }

    /**
     * Retrieves an active database connection instance.
     *
     * If the connection does not exist, it will be created and cached.
     *
     * @param string|null $name The connection name (null for default).
     * @return mixed The connection instance (PDO or MongoDBManager).
     */
    public function getConnection(string $name = null): mixed
    {
        $name = $name ?: $this->config['default'];

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->createConnection($name);
        }

        return $this->connections[$name];
    }

    /**
     * Registers a custom database driver.
     *
     * Allows extending the ORM with support for other databases.
     *
     * @param string $driverName  The driver identifier (e.g., 'oracle').
     * @param string $driverClass The fully qualified class name of the driver.
     * @return self
     */
    public function extend(string $driverName, string $driverClass): self
    {
        $this->drivers[$driverName] = $driverClass;
        return $this;
    }

    /**
     * Creates a new connection based on configuration.
     *
     * @param string $name The connection name to create.
     * @return mixed The new connection instance.
     * @throws InvalidArgumentException If connection config is missing or driver is unsupported.
     */
    protected function createConnection(string $name): mixed
    {
        if (!isset($this->config['connections'][$name])) {
            throw new InvalidArgumentException("Connection [{$name}] not configured.");
        }

        $config = $this->config['connections'][$name];
        $driverName = $config['driver'];

        if (!isset($this->drivers[$driverName])) {
            throw new InvalidArgumentException("Unsupported driver: {$driverName}");
        }

        $driverClass = $this->drivers[$driverName];

        /** @var \Ludelix\Database\Drivers\DriverInterface $driver */
        $driver = new $driverClass();

        return $driver->connect($config);
    }
}