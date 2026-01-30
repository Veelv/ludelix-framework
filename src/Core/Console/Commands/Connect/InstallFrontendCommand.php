<?php

namespace Ludelix\Core\Console\Commands\Connect;

use Ludelix\Core\Console\Commands\Core\BaseCommand;
use Ludelix\Core\Console\Templates\Engine\TemplateEngine;

/**
 * Install Frontend Stack Command
 * 
 * Installs frontend stack configuration (React, Vue, Svelte, Alpine, Ludou)
 */
class InstallFrontendCommand extends BaseCommand
{
    protected string $signature = 'install:frontend 
                                   {--stack= : Frontend stack (react, vue, svelte, alpine, ludou)}
                                   {--typescript : Use TypeScript}
                                   {--tailwind : Use Tailwind CSS}
                                   {--force : Overwrite existing files}';

    protected string $description = 'Install frontend stack configuration';

    private array $stacks = ['ludou', 'react', 'vue', 'svelte', 'alpine'];

    protected TemplateEngine $templateEngine;

    public function __construct($container, $engine)
    {
        parent::__construct($container, $engine);

        $this->templateEngine = new TemplateEngine();
        $this->templateEngine->addPath('frontend', __DIR__ . '/../../Templates/Frontend');
    }

    public function execute(array $arguments, array $options): int
    {
        $this->info('Ludelix Frontend Stack Installer');
        $this->line('');

        // 1. Select stack
        $stack = $this->option($options, 'stack') ?? $this->output->choice(
            'Which frontend stack would you like to use?',
            $this->stacks,
            $this->stacks[0]
        );

        if (!in_array($stack, $this->stacks)) {
            $this->error("Invalid stack: {$stack}");
            return 1;
        }

        // 2. TypeScript option (for React, Vue, Svelte)
        $typescript = false;
        if (in_array($stack, ['react', 'vue', 'svelte'])) {
            $typescript = $this->option($options, 'typescript') ??
                $this->output->confirm('Use TypeScript?', true);
        }

        // 3. Tailwind option
        $tailwind = $this->option($options, 'tailwind') ??
            $this->output->confirm('Use Tailwind CSS?', false);

        // 4. Check for existing files
        if (!$this->hasOption($options, 'force') && $this->hasExistingFiles($stack)) {
            if (!$this->output->confirm('Some files already exist. Overwrite?', false)) {
                $this->warning('Installation cancelled.');
                return 0;
            }
        }

        // 5. Install stack
        $this->line('');
        $this->info("Installing {$stack} stack...");
        $this->line('');

        $result = match ($stack) {
            'react' => $this->installReact($typescript, $tailwind),
            'vue' => $this->installVue($typescript, $tailwind),
            'svelte' => $this->installSvelte($typescript, $tailwind),
            'alpine' => $this->installAlpine($tailwind),
            'ludou' => $this->installLudou($tailwind),
        };

        if (!$result) {
            $this->error('Installation failed!');
            return 1;
        }

        // 6. Install npm dependencies
        if ($stack !== 'ludou') {
            $this->line('');
            $this->info('Installing npm dependencies...');
            $this->runCommand('npm install');
        }

        // 7. Success message
        $this->line('');
        $this->success("Frontend stack '{$stack}' installed successfully!");
        $this->line('');

        // 8. Next steps
        $this->displayNextSteps($stack);

        return 0;
    }

    protected function installReact(bool $typescript, bool $tailwind): bool
    {
        $ext = $typescript ? 'tsx' : 'jsx';

        // Copy configuration files
        $this->copyStub('react/package.json', 'package.json', [
            'APP_NAME' => $this->getAppName(),
            'BUILD_COMMAND' => $typescript ? 'tsc && vite build' : 'vite build',
            'TYPESCRIPT_DEPS' => $typescript ? $this->getTypeScriptDeps() : '',
            'TAILWIND_DEPS' => $tailwind ? $this->getTailwindDeps() : ''
        ]);

        $this->copyStub('react/vite.config.js', 'vite.config.js');

        if ($typescript) {
            $this->copyStub('react/tsconfig.json', 'tsconfig.json');
            $this->copyStub('react/tsconfig.node.json', 'tsconfig.node.json');
        }

        if ($tailwind) {
            $this->installTailwind();
        }

        // Create frontend files
        $this->copyStub("react/app.{$ext}", "frontend/js/app.{$ext}", [
            'TAILWIND_IMPORT' => $tailwind ? "import '../css/app.css'" : ''
        ]);

        $this->copyStub("react/Dashboard.{$ext}", "frontend/templates/screens/Dashboard.{$ext}", [
            'TYPESCRIPT_INTERFACE' => $typescript ? "interface DashboardProps {\n  user: {\n    name: string\n    email: string\n  }\n}\n\n" : '',
            'PROPS' => $typescript ? '{ user }: DashboardProps' : '{ user }'
        ]);

        $this->copyStub('react/app.ludou', 'frontend/templates/layouts/app.ludou');

        // Update .gitignore
        $this->updateGitignore();

        return true;
    }

    protected function installVue(bool $typescript, bool $tailwind): bool
    {
        $ext = $typescript ? 'ts' : 'js';

        $this->copyStub('vue/package.json', 'package.json', [
            'APP_NAME' => $this->getAppName(),
            'BUILD_COMMAND' => $typescript ? 'vue-tsc && vite build' : 'vite build',
            'TYPESCRIPT_DEPS' => $typescript ? $this->getVueTypeScriptDeps() : '',
            'TAILWIND_DEPS' => $tailwind ? $this->getTailwindDeps() : ''
        ]);

        $this->copyStub('vue/vite.config.js', 'vite.config.js');

        if ($typescript) {
            $this->copyStub('vue/tsconfig.json', 'tsconfig.json');
        }

        if ($tailwind) {
            $this->installTailwind();
        }

        $this->copyStub("vue/app.{$ext}", "frontend/js/app.{$ext}", [
            'TAILWIND_IMPORT' => $tailwind ? "import '../css/app.css'" : ''
        ]);

        $this->copyStub('vue/Dashboard.vue', 'frontend/templates/screens/Dashboard.vue', [
            'SCRIPT_LANG' => $typescript ? ' lang="ts"' : ''
        ]);

        $this->copyStub('vue/app.ludou', 'frontend/templates/layouts/app.ludou');

        $this->updateGitignore();

        return true;
    }

    protected function installSvelte(bool $typescript, bool $tailwind): bool
    {
        $ext = $typescript ? 'ts' : 'js';

        $this->copyStub('svelte/package.json', 'package.json', [
            'APP_NAME' => $this->getAppName(),
            'BUILD_COMMAND' => $typescript ? 'vite build' : 'vite build',
            'TYPESCRIPT_DEPS' => $typescript ? $this->getSvelteTypeScriptDeps() : '',
            'TAILWIND_DEPS' => $tailwind ? $this->getTailwindDeps() : ''
        ]);

        $this->copyStub('svelte/vite.config.js', 'vite.config.js');

        if ($typescript) {
            $this->copyStub('svelte/tsconfig.json', 'tsconfig.json');
        }

        if ($tailwind) {
            $this->installTailwind();
        }

        $this->copyStub("svelte/app.{$ext}", "frontend/js/app.{$ext}", [
            'TAILWIND_IMPORT' => $tailwind ? "import '../css/app.css'" : ''
        ]);

        $this->copyStub('svelte/Dashboard.svelte', 'frontend/templates/screens/Dashboard.svelte', [
            'SCRIPT_LANG' => $typescript ? ' lang="ts"' : ''
        ]);

        $this->copyStub('svelte/app.ludou', 'frontend/templates/layouts/app.ludou');

        $this->updateGitignore();

        return true;
    }

    protected function installAlpine(bool $tailwind): bool
    {
        $this->copyStub('alpine/package.json', 'package.json', [
            'APP_NAME' => $this->getAppName(),
            'TAILWIND_DEPS' => $tailwind ? $this->getTailwindDeps() : ''
        ]);

        if ($tailwind) {
            $this->installTailwind();
        }

        $this->copyStub('alpine/Welcome.ludou', 'frontend/templates/screens/Welcome.ludou', [
            'TAILWIND_CLASS' => $tailwind ? ' class="container mx-auto p-8"' : ''
        ]);

        $this->updateGitignore();

        return true;
    }

    protected function installLudou(bool $tailwind): bool
    {
        $this->info('Ludou stack is already installed!');
        $this->line('No additional configuration needed.');

        if ($tailwind) {
            $this->warning('Tailwind CSS requires a build step.');
            $this->line('Consider using React, Vue, or Svelte instead.');
        }

        return true;
    }

    protected function installTailwind(): void
    {
        // Tailwind v4 uses CSS-first configuration.
        // We create the CSS file with the necessary imports.
        $this->copyStub('css/app.css', 'frontend/css/app.css');
    }

    protected function copyStub(string $stub, string $destination, array $replacements = []): void
    {
        try {
            $content = $this->templateEngine->render($stub, $replacements);

            $destPath = $this->basePath($destination);
            $dir = dirname($destPath);

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($destPath, $content);

            $this->line("<fg=green>✓</> Created: {$destination}");
        } catch (\Exception $e) {
            $this->error("Failed to copy stub '{$stub}': " . $e->getMessage());
        }
    }

    protected function hasExistingFiles(string $stack): bool
    {
        $files = ['package.json', 'vite.config.js'];

        foreach ($files as $file) {
            if (file_exists($this->basePath($file))) {
                return true;
            }
        }

        return false;
    }

    protected function updateGitignore(): void
    {
        $gitignore = $this->basePath('.gitignore');
        $content = file_exists($gitignore) ? file_get_contents($gitignore) : '';

        $additions = "\n# Frontend\n/node_modules\n/public/build\n/public/hot\nnpm-debug.log\nyarn-error.log\n";

        if (!str_contains($content, '/node_modules')) {
            file_put_contents($gitignore, $content . $additions);
            $this->line("<fg=green>✓</> Updated: .gitignore");
        }
    }

    protected function runCommand(string $command): void
    {
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->warning("Command failed: {$command}");
        }
    }

    protected function displayNextSteps(string $stack): void
    {
        $this->info('Next steps:');

        if ($stack !== 'ludou') {
            $this->line('  npm run dev      # Start development server');
            $this->line('  npm run build    # Build for production');
        }

        $this->line('  php mi serve     # Start PHP server');
        $this->line('');
        $this->line('Edit your pages in: frontend/templates/screens/');
    }

    protected function getAppName(): string
    {
        return config('app.name', 'ludelix-app');
    }

    protected function getTypeScriptDeps(): string
    {
        return ',
    "@types/react": "^18.2.43",
    "@types/react-dom": "^18.2.17",
    "typescript": "^5.3.3"';
    }

    protected function getVueTypeScriptDeps(): string
    {
        return ',
    "typescript": "^5.3.3",
    "vue-tsc": "^1.8.27"';
    }

    protected function getSvelteTypeScriptDeps(): string
    {
        return ',
    "typescript": "^5.3.3",
    "svelte-check": "^3.6.0",
    "@tsconfig/svelte": "^5.0.2"';
    }

    protected function getTailwindDeps(): string
    {
        return ',
    "tailwindcss": "^4.0.0",
    "@tailwindcss/vite": "^4.0.0"';
    }

    protected function basePath(string $path = ''): string
    {
        return getcwd() . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}
