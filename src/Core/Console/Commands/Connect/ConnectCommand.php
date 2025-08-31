<?php

namespace Ludelix\Core\Console\Commands\Connect;

use Ludelix\Core\Console\Commands\Core\BaseCommand;
use Ludelix\Core\Console\Commands\Connect\Generators\ReactGenerator;
use Ludelix\Core\Console\Commands\Connect\Generators\VueGenerator;
use Ludelix\Core\Console\Commands\Connect\Generators\SvelteGenerator;
use Ludelix\Core\Console\Commands\Connect\Installers\PackageInstaller;
use Ludelix\Core\Console\Commands\Connect\Installers\ConfigInstaller;
use Ludelix\Core\Console\Commands\Connect\Installers\AssetInstaller;

class ConnectCommand extends BaseCommand
{
    protected string $signature = 'connect {framework? : The frontend framework to install (react, vue, svelte)} {--tailwind : Install Tailwind CSS} {--typescript : Use TypeScript} {--ssr : Enable Server-Side Rendering} {--websocket : Enable WebSocket sync}';
    
    protected string $description = 'Install modern frontend framework with ludelix-connect';

    public function execute(array $arguments, array $options): int
    {
        $this->info('🚀 Installing modern frontend with ludelix-connect...');

        // Debug: print arguments and options
        $this->line('Arguments: ' . json_encode($arguments));
        $this->line('Options: ' . json_encode($options));

        // Get framework choice (first argument or default to react)
        $framework = $arguments[0] ?? 'react';

        // Validate framework
        if (!in_array($framework, ['react', 'vue', 'svelte'])) {
            $this->error("Invalid framework: {$framework}. Supported: react, vue, svelte");
            return 1;
        }

        $this->info("📦 Installing {$framework}...");

        // Get options
        $installOptions = [
            'tailwind' => $this->hasOption($options, 'tailwind'),
            'typescript' => $this->hasOption($options, 'typescript'),
            'ssr' => $this->hasOption($options, 'ssr'),
            'websocket' => $this->hasOption($options, 'websocket')
        ];

        try {
            $this->line('Debug: About to install packages...');
            
            // Install packages
            $this->line('Debug: Creating PackageInstaller...');
            $packageInstaller = new PackageInstaller();
            $this->line('Debug: PackageInstaller created, calling install...');
            $packageInstaller->install($framework, $installOptions);

            $this->line('Debug: Packages installed, about to install config...');
            
            // Install configuration
            $configInstaller = new ConfigInstaller();
            $configInstaller->install($framework, $installOptions);

            $this->line('Debug: Config installed, about to install assets...');
            
            // Install assets
            $assetInstaller = new AssetInstaller();
            $assetInstaller->install($framework, $installOptions);

            $this->line('Debug: Assets installed, about to generate framework files...');
            
            // Generate framework-specific files
            $this->generateFrameworkFiles($framework, $installOptions);

            $this->info('✅ Frontend installation completed successfully!');
            $this->info('📝 Next steps:');
            $this->info('   1. Run: npm install');
            $this->info('   2. Run: npm run dev');
            $this->info('   3. Visit your application');

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Installation failed: " . $e->getMessage());
            return 1;
        }
    }

    protected function generateFrameworkFiles(string $framework, array $options): void
    {
        $generator = match($framework) {
            'react' => new ReactGenerator(),
            'vue' => new VueGenerator(),
            'svelte' => new SvelteGenerator(),
            default => throw new \Exception("Unsupported framework: {$framework}")
        };

        $generator->generate($options);
    }
} 