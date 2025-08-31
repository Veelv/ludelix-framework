<?php

namespace Ludelix\Core\Console\Commands\Seeder;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

class SeedGenerateCommand extends BaseCommand
{
    protected string $signature = 'seed:generate <table> [--count=10] [--truncate]';
    protected string $description = 'Generate fake data for a table';

    public function execute(array $arguments, array $options): int
    {
        $table = $this->argument($arguments, 0);
        $count = (int) $this->option($options, 'count', 10);
        $truncate = $this->hasOption($options, 'truncate');

        if (!$table) {
            $this->error("Table name is required");
            return 1;
        }

        $seederManager = $this->service('seeder.manager');

        try {
            if ($truncate) {
                $this->info("🗑️  Truncating table: {$table}");
                $seederManager->truncate([$table]);
            }

            $this->info("🎲 Generating {$count} fake records for table: {$table}");
            
            $generated = $seederManager->generate($table, $count);
            
            $this->success("✅ Generated {$count} records successfully!");
            
            if ($count <= 5) {
                $this->info("📋 Sample generated data:");
                foreach ($generated as $i => $record) {
                    $this->line("  Record " . ($i + 1) . ":");
                    foreach ($record as $key => $value) {
                        $displayValue = is_string($value) && strlen($value) > 50 
                            ? substr($value, 0, 47) . '...' 
                            : $value;
                        $this->line("    {$key}: {$displayValue}");
                    }
                    $this->line("");
                }
            }

            return 0;

        } catch (\Throwable $e) {
            $this->error("❌ Generation failed: " . $e->getMessage());
            return 1;
        }
    }
}