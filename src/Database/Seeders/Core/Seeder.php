<?php

namespace Ludelix\Database\Seeders\Core;

abstract class Seeder
{
    public string $description = '';
    protected SeederManager $manager;

    abstract public function seed(SeederManager $manager): void;

    protected function table(string $table): TableSeeder
    {
        return new TableSeeder($table, $this->manager);
    }

    protected function factory(string $table, int $count = 10): array
    {
        return $this->manager->generate($table, $count);
    }

    protected function truncate(string ...$tables): void
    {
        $this->manager->truncate($tables);
    }

    protected function call(string $seederClass): void
    {
        $seeder = new $seederClass();
        $seeder->seed($this->manager);
    }
}