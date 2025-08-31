<?php

namespace Ludelix\Database\Seeders\Core;

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

    public function insert(array $records): self
    {
        $this->data = array_merge($this->data, $records);
        return $this;
    }

    public function create(array $record): self
    {
        $this->data[] = $record;
        return $this;
    }

    public function factory(int $count = 10, array $options = []): self
    {
        $generated = $this->manager->generate($this->table, $count, $options);
        return $this;
    }

    public function truncate(): self
    {
        $this->manager->truncate([$this->table]);
        return $this;
    }

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