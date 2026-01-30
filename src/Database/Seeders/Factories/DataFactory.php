<?php

namespace Ludelix\Database\Seeders\Factories;

use Exception;

/**
 * registry and generator for data factories.
 */
class DataFactory
{
    protected array $generators = [];

    /**
     * Generates data for a table using a registered callback.
     *
     * @param string $table
     * @param array  $options
     * @return array
     * @throws Exception
     */
    public function generate(string $table, array $options = []): array
    {
        if (!isset($this->generators[$table])) {
            throw new Exception("No factory registered for table: {$table}. Register one using DataFactory::register()");
        }

        $generator = $this->generators[$table];
        return $generator($options);
    }

    /**
     * Registers a generator for a table.
     *
     * @param string   $table
     * @param callable $generator
     */
    public function register(string $table, callable $generator): void
    {
        $this->generators[$table] = $generator;
    }

    /**
     * Checks if a factory is registered for the table.
     *
     * @param string $table
     * @return bool
     */
    public function has(string $table): bool
    {
        return isset($this->generators[$table]);
    }

    /**
     * Gets the names of all registered factories.
     *
     * @return array
     */
    public function getRegistered(): array
    {
        return array_keys($this->generators);
    }

    /**
     * Returns a new FakeDataGenerator instance.
     *
     * @return FakeDataGenerator
     */
    public function faker(): FakeDataGenerator
    {
        return new FakeDataGenerator();
    }
}