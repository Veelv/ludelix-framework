<?php

namespace Ludelix\Core\Console\Commands\Seeder;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

class SeedStatusCommand extends BaseCommand
{
    protected string $signature = 'seed:status';
    protected string $description = 'Show seeder status';

    public function execute(array $arguments, array $options): int
    {
        $seederManager = $this->service('seeder.manager');
        $status = $seederManager->getStatus();

        $this->line("");
        $this->info("🌱 Seeder Status");
        $this->line("├── Total: {$status['total']} seeders");
        $this->line("├── Executed: {$status['executed']} seeders");
        $this->line("└── Pending: {$status['pending']} seeders");
        $this->line("");

        if (!empty($status['seeders'])) {
            $this->info("📋 Available Seeders:");
            foreach ($status['seeders'] as $seeder) {
                $status_icon = in_array($seeder['name'], $status['executed_list']) ? '✅' : '⏳';
                $this->line("├── {$status_icon} {$seeder['name']} - {$seeder['description']}");
            }
            $this->line("");
        }

        if ($status['pending'] > 0) {
            $this->info("💡 Run 'php mi seed' to execute pending seeders");
        } else {
            $this->success("✅ All seeders have been executed!");
        }

        return 0;
    }
}