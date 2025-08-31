<?php

namespace Ludelix\Database\Seeders\Factories;

class DataFactory
{
    protected array $generators = [];

    public function generate(string $table, array $options = []): array
    {
        if (!isset($this->generators[$table])) {
            throw new \Exception("No factory registered for table: {$table}. Register one using DataFactory::register()");
        }
        
        $generator = $this->generators[$table];
        return $generator($options);
    }

    public function register(string $table, callable $generator): void
    {
        $this->generators[$table] = $generator;
    }

    public function has(string $table): bool
    {
        return isset($this->generators[$table]);
    }

    public function getRegistered(): array
    {
        return array_keys($this->generators);
    }

    public function faker(): FakeDataGenerator
    {
        return new FakeDataGenerator();
    }
}