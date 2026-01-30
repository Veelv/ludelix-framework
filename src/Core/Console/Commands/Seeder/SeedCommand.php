<?php

namespace Ludelix\Core\Console\Commands\Seeder;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

class SeedCommand extends BaseCommand
{
    protected string $signature = 'seed [--class=] [--table=] [--fresh] [--count=10]';
    protected string $description = 'Run database seeders';

    public function execute(array $arguments, array $options): int
    {
        $seederManager = $this->service('seeder.manager');
        
        $this->info("🌱 Running database seeders...");

        try {
            if ($this->hasOption($options, 'fresh')) {
                $executed = $seederManager->fresh();
                $this->success("✅ Fresh seeding completed!");
            } else {
                $executed = $seederManager->seed($options);
                
                if (empty($executed)) {
                    $this->info("✅ No new seeders to run");
                    return 0;
                }
            }

            $this->success("✅ Executed " . count($executed) . " seeders:");
            foreach ($executed as $seeder) {
                $this->line("  • {$seeder}");
            }

            return 0;

        } catch (\Throwable $e) {
            $this->error("❌ Seeding failed: " . $e->getMessage());
            return 1;
        }
    }
}