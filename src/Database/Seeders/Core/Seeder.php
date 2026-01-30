<?php

namespace Ludelix\Database\Seeders\Core;

/**
 * Base class for all database seeders.
 */
abstract class Seeder
{
    public string $description = '';
    protected SeederManager $manager;

    /**
     * Executes the seeding logic.
     *
     * @param SeederManager $manager
     */
    abstract public function seed(SeederManager $manager): void;

    /**
     * Starts building a seed operation for a specific table.
     *
     * @param string $table The table name.
     * @return TableSeeder
     */
    protected function table(string $table): TableSeeder
    {
        return new TableSeeder($table, $this->manager);
    }

    /**
     * Generates data using a registered factory.
     *
     * @param string $table The table name.
     * @param int    $count Number of records to generate.
     * @return array The generated data.
     */
    protected function factory(string $table, int $count = 10): array
    {
        return $this->manager->generate($table, $count);
    }

    /**
     * Truncates the specified tables.
     *
     * @param string ...$tables Table names.
     */
    protected function truncate(string ...$tables): void
    {
        $this->manager->truncate($tables);
    }

    /**
     * Calls another seeder.
     *
     * @param string $seederClass Fully qualified class name of the seeder.
     */
    protected function call(string $seederClass): void
    {
        /** @var Seeder $seeder */
        $seeder = new $seederClass();
        $seeder->seed($this->manager);
    }
}