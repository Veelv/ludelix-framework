<?php

namespace Ludelix\Database\Core;

use InvalidArgumentException;

/**
 * Validates the database configuration array structure and values.
 *
 * Ensures that the configuration provided to the ConnectionManager contains
 * all necessary fields and supported drivers before a connection is attempted.
 */
class ConfigurationValidator
{
    /**
     * Validates if the database configuration is correct.
     *
     * Checks for the validation of the 'default' connection and ensures
     * the specific driver configuration is complete.
     *
     * @param array $config The full database configuration array.
     * @throws InvalidArgumentException If the configuration is invalid or incomplete.
     */
    public static function validate(array $config): void
    {
        $default = $config['default'] ?? null;

        if (!$default) {
            throw new InvalidArgumentException(
                "No default connection defined. Configure 'default' in config/database.php"
            );
        }

        if (!isset($config['connections'][$default])) {
            throw new InvalidArgumentException(
                "Default connection '{$default}' not found in config/database.php"
            );
        }

        $connection = $config['connections'][$default];

        // Validate driver
        if (!isset($connection['driver'])) {
            throw new InvalidArgumentException(
                "Driver not specified for connection '{$default}'"
            );
        }

        // Validate driver-specific configurations
        match ($connection['driver']) {
            'mysql' => self::validateMySQLConfig($connection, $default),
            'pgsql' => self::validatePgSQLConfig($connection, $default),
            'sqlite' => self::validateSQLiteConfig($connection, $default),
            default => throw new InvalidArgumentException(
                "Driver '{$connection['driver']}' not supported"
            )
        };
    }

    /**
     * Validates MySQL connection configuration.
     *
     * @param array  $connection The connection configuration array.
     * @param string $name       The name of the connection.
     * @throws InvalidArgumentException If required fields are missing.
     */
    private static function validateMySQLConfig(array $connection, string $name): void
    {
        $required = ['host', 'port', 'database', 'username'];

        foreach ($required as $field) {
            if (!isset($connection[$field]) || empty($connection[$field])) {
                throw new InvalidArgumentException(
                    "Incomplete MySQL configuration for '{$name}'. Field '{$field}' is required."
                );
            }
        }
    }

    /**
     * Validates PostgreSQL connection configuration.
     *
     * @param array  $connection The connection configuration array.
     * @param string $name       The name of the connection.
     * @throws InvalidArgumentException If required fields are missing.
     */
    private static function validatePgSQLConfig(array $connection, string $name): void
    {
        $required = ['host', 'port', 'database', 'username'];

        foreach ($required as $field) {
            if (!isset($connection[$field]) || empty($connection[$field])) {
                throw new InvalidArgumentException(
                    "Incomplete PostgreSQL configuration for '{$name}'. Field '{$field}' is required."
                );
            }
        }
    }

    /**
     * Validates SQLite connection configuration.
     *
     * Also attempts to create the directory for the database file if it doesn't exist.
     *
     * @param array  $connection The connection configuration array.
     * @param string $name       The name of the connection.
     * @throws InvalidArgumentException If database file path is missing or directory creation fails.
     */
    private static function validateSQLiteConfig(array $connection, string $name): void
    {
        if (!isset($connection['database']) || empty($connection['database'])) {
            throw new InvalidArgumentException(
                "Incomplete SQLite configuration for '{$name}'. Field 'database' is required."
            );
        }

        // Verify if the SQLite file directory exists
        $databasePath = $connection['database'];
        if ($databasePath === ':memory:') {
            return;
        }

        $directory = dirname($databasePath);

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new InvalidArgumentException(
                    "Could not create directory for SQLite file: {$directory}"
                );
            }
        }
    }

    /**
     * Checks if the configuration is using potentially unsafe default values.
     *
     * Detects if the connection parameters match known default placeholders
     * which might indicate the user hasn't properly configured their environment.
     *
     * @param array $config The configuration array.
     * @return bool True if defaults are detected, false otherwise.
     */
    public static function isUsingDefaultValues(array $config): bool
    {
        $default = $config['default'] ?? 'mysql';
        $connection = $config['connections'][$default] ?? [];

        // If MySQL, check if using default values
        if (($connection['driver'] ?? '') === 'mysql') {
            $defaultValues = [
                'host' => 'localhost',
                'port' => '3306',
                'database' => 'ludelix_app',
                'username' => 'root',
                'password' => ''
            ];

            foreach ($defaultValues as $field => $defaultValue) {
                if (($connection[$field] ?? '') === $defaultValue) {
                    return true;
                }
            }
        }

        return false;
    }
}
