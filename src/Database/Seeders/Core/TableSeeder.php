<?php

namespace Ludelix\Database\Seeders\Core;

/**
 * Helper class for seeding a specific table.
 */
class TableSeeder
{
    protected string $table;
    protected SeederManager $manager;
    protected array $data = [];

    public function __construct(string $table, SeederManager $manager)
    {
        $this->table = $table;
        $this->manager = $manager;
    }

    /**
     * Stacks an array of records to be inserted.
     *
     * @param array $records
     * @return self
     */
    public function insert(array $records): self
    {
        $this->data = array_merge($this->data, $records);
        return $this;
    }

    /**
     * Stacks a single record to be inserted.
     *
     * @param array $record
     * @return self
     */
    public function create(array $record): self
    {
        $this->data[] = $record;
        return $this;
    }

    /**
     * Generates records using the factory for this table.
     *
     * @param int   $count
     * @param array $options
     * @return self
     */
    public function factory(int $count = 10, array $options = []): self
    {
        $generated = $this->manager->generate($this->table, $count, $options);
        return $this;
    }

    /**
     * Truncates the table.
     *
     * @return self
     */
    public function truncate(): self
    {
        $this->manager->truncate([$this->table]);
        return $this;
    }

    /**
     * Executed the stack of inserts.
     */
    public function execute(): void
    {
        if (!empty($this->data)) {
            $connection = $this->manager->getConnection();

            foreach ($this->data as $record) {
                $columns = implode(', ', array_keys($record));
                $placeholders = ':' . implode(', :', array_keys($record));

                $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
                $stmt = $connection->prepare($sql);
                $stmt->execute($record);
            }
        }
    }
}