<?php

namespace Ludelix\Core\Console\Support;

/**
 * Help Formatter
 * 
 * Formats help information and command listings for the Mi console.
 * Provides beautiful and organized output for users.
 * 
 * @package Ludelix\Core\Console\Support
 */
class HelpFormatter
{
    protected array $colors = [
        'title' => "\033[1;36m",
        'section' => "\033[1;33m",
        'command' => "\033[1;32m",
        'description' => "\033[0;37m",
        'error' => "\033[1;31m",
        'success' => "\033[1;32m",
        'info' => "\033[1;34m",
        'warning' => "\033[1;33m",
        'reset' => "\033[0m"
    ];

    /**
     * Show main help information
     */
    public function showMainHelp(): void
    {
        $this->title("🏹 Mi - Ludelix Framework Console");
        $this->line("");
        $this->info("Usage:");
        $this->line("  php mi <command> [arguments] [options]");
        $this->line("");
        
        $this->section("Available Commands:");
        
        $categories = [
            'Kria' => [
                'kria:module' => 'Create complete module (repository, service, entity, etc.)',
                'kria:repository' => 'Create repository only',
                'kria:service' => 'Create service only',
                'kria:entity' => 'Create entity only',
                'kria:page' => 'Create Ludou page',
                'kria:template' => 'Create complete template (page, model, repository, service, route)',
                'kria:lang' => 'Create language file (PHP, JSON, YAML)',
                'kria:job' => 'Create job only',
                'kria:middleware' => 'Create middleware only',
                'kria:console' => 'Create console command only'
            ],
            'Tenant' => [
                'tenant:create' => 'Create new tenant with configuration options',
                'tenant:list' => 'List all tenants',
                'tenant:switch' => 'Switch to a specific tenant',
                'tenant:stats' => 'Show tenant statistics'
            ],
            'Cache' => [
                'cache:clear' => 'Clear application cache',
                'cache:cleanup' => 'Clean up expired cache entries'
            ],
            'Seeder' => [
                'seed' => 'Run database seeders',
                'seed:create' => 'Create a new seeder',
                'seed:status' => 'Show seeder status',
                'seed:generate' => 'Generate seeder data'
            ],
            'Evolution' => [
                'evolve:create' => 'Create a new evolution',
                'evolve:apply' => 'Apply pending evolutions',
                'evolve:status' => 'Show evolution status',
                'evolve:revert' => 'Revert last evolution',
                'evolve:refresh' => 'Refresh evolution cache'
            ],
            'Framework' => [
                'about' => 'Display framework information',
                'start' => 'Start development server',
                'route:list' => 'Lista todas as rotas registradas na aplicação',
                'route:cache' => 'Gerenciar cache de rotas da aplicação'
            ],
            'Security' => [
                'key:generate' => 'Generate application encryption key',
                'security:logs' => 'Show security logs and statistics'
            ],
            'Cubby' => [
                'cubby:link' => 'Cria um link simbólico [public/up] para [cubby/up] para servir arquivos públicos de upload'
            ],
            'Extensions' => [
                'extension:list' => 'List installed extensions',
                'extension:install' => 'Install an extension',
                'extension:uninstall' => 'Uninstall an extension'
            ]
        ];
        
        foreach ($categories as $category => $commands) {
            $this->line("  " . $this->color('section', $category) . ":");
            foreach ($commands as $command => $description) {
                $this->line("    " . $this->color('command', str_pad($command, 20)) . " " . $this->color('description', $description));
            }
            $this->line("");
        }
        
        $this->section("Options:");
        $this->line("  --help, -h          Show this help message");
        $this->line("  --version, -V       Show version information");
        $this->line("  --list              List all available commands");
        $this->line("  --debug             Enable debug mode");
        $this->line("");
        
        $this->section("Examples:");
        $this->line("  php mi kria:module user");
        $this->line("  php mi kria:lang en messages");
        $this->line("  php mi kria:lang pt_BR users --format=yaml");
        $this->line("  php mi tenant:create mytenant --domain=example.com");
        $this->line("  php mi cache:clear");
        $this->line("  php mi seed");
        $this->line("");
        
        $this->info("For help with a specific command:");
        $this->line("  php mi <command> --help");
    }

    /**
     * List all available commands
     * 
     * @param array $commands Registered commands
     * @param array $extensions Loaded extensions
     */
    public function listCommands(array $commands, array $extensions): void
    {
        $this->title("🏹 Available Commands");
        $this->line("");
        
        // Group commands by category
        $categories = $this->groupCommandsByCategory($commands);
        
        foreach ($categories as $category => $categoryCommands) {
            $this->section($category);
            
            foreach ($categoryCommands as $name => $class) {
                $description = $this->getCommandDescription($class);
                $this->line("  " . $this->color('command', str_pad($name, 25)) . " " . $this->color('description', $description));
            }
            
            $this->line("");
        }
        
        // Show extensions if any
        if (!empty($extensions)) {
            $this->section("Extensions:");
            foreach ($extensions as $name => $data) {
                $this->line("  " . $this->color('info', $name) . " - " . ($data['description'] ?? 'No description'));
            }
            $this->line("");
        }
        
        $this->info("Total commands: " . count($commands));
        if (!empty($extensions)) {
            $this->info("Total extensions: " . count($extensions));
        }
    }

    /**
     * Group commands by category
     * 
     * @param array $commands Commands to group
     * @return array Grouped commands
     */
    protected function groupCommandsByCategory(array $commands): array
    {
        $categories = [];
        
        foreach ($commands as $name => $class) {
            $category = $this->getCommandCategory($name);
            $categories[$category][$name] = $class;
        }
        
        // Sort categories
        ksort($categories);
        
        return $categories;
    }

    /**
     * Get command category from name
     * 
     * @param string $name Command name
     * @return string Category name
     */
    protected function getCommandCategory(string $name): string
    {
        if (strpos($name, 'kria:') === 0) {
            return 'Kria';
        }
        
        if (strpos($name, 'tenant:') === 0) {
            return 'Tenant';
        }
        
        if (strpos($name, 'cache:') === 0) {
            return 'Cache';
        }
        
        if (strpos($name, 'seed') === 0) {
            return 'Seeder';
        }
        
        if (strpos($name, 'evolve:') === 0) {
            return 'Evolution';
        }
        
        if (strpos($name, 'extension:') === 0) {
            return 'Extensions';
        }
        
        if (in_array($name, ['about', 'start'])) {
            return 'Framework';
        }
        
        if (strpos($name, 'route:') === 0) {
            return 'Framework';
        }
        
        if (strpos($name, 'cubby:') === 0) {
            return 'Cubby';
        }
        
        if (strpos($name, 'key:') === 0 || strpos($name, 'security:') === 0) {
            return 'Security';
        }
        
        if (in_array($name, ['serve', 'config:cache', 'config:clear'])) {
            return 'Framework';
        }
        
        return 'Other';
    }

    /**
     * Get command description
     * 
     * @param string $class Command class
     * @return string Description
     */
    protected function getCommandDescription(string $class): string
    {
        if (!class_exists($class)) {
            return 'Class not found';
        }
        
        try {
            $reflection = new \ReflectionClass($class);
            
            // Check for description property
            if ($reflection->hasProperty('description')) {
                $property = $reflection->getProperty('description');
                $property->setAccessible(true);
                $description = $property->getValue();
                if ($description) {
                    return $description;
                }
            }
            
            // Check for getDescription method
            if ($reflection->hasMethod('getDescription')) {
                $method = $reflection->getMethod('getDescription');
                if ($method->isPublic()) {
                    return $method->invoke(null);
                }
            }
            
            // Try to extract from docblock
            $docblock = $reflection->getDocComment();
            if ($docblock) {
                $lines = explode("\n", $docblock);
                foreach ($lines as $line) {
                    $line = trim($line, " \t\n\r\0\x0B*");
                    if ($line && !str_starts_with($line, '@') && !str_starts_with($line, '/')) {
                        return $line;
                    }
                }
            }
            
        } catch (\Throwable $e) {
            // Ignore reflection errors
        }
        
        return 'No description available';
    }

    /**
     * Show command help
     * 
     * @param string $command Command name
     * @param string $class Command class
     */
    public function showCommandHelp(string $command, string $class): void
    {
        $this->title("Command: " . $command);
        $this->line("");
        
        $description = $this->getCommandDescription($class);
        $this->info("Description:");
        $this->line("  " . $description);
        $this->line("");
        
        // Try to get signature
        try {
            $reflection = new \ReflectionClass($class);
            if ($reflection->hasProperty('signature')) {
                $property = $reflection->getProperty('signature');
                $property->setAccessible(true);
                $signature = $property->getValue();
                if ($signature) {
                    $this->info("Usage:");
                    $this->line("  php mi " . $signature);
                    $this->line("");
                }
            }
        } catch (\Throwable $e) {
            // Ignore reflection errors
        }
        
        $this->info("Examples:");
        $this->line("  php mi " . $command);
        $this->line("  php mi " . $command . " --help");
    }

    /**
     * Show error message
     * 
     * @param string $message Error message
     */
    public function error(string $message): void
    {
        echo $this->color('error', "❌ " . $message) . "\n";
    }

    /**
     * Show success message
     * 
     * @param string $message Success message
     */
    public function success(string $message): void
    {
        echo $this->color('success', "✅ " . $message) . "\n";
    }

    /**
     * Show info message
     * 
     * @param string $message Info message
     */
    public function info(string $message): void
    {
        echo $this->color('info', "ℹ️  " . $message) . "\n";
    }

    /**
     * Show warning message
     * 
     * @param string $message Warning message
     */
    public function warning(string $message): void
    {
        echo $this->color('warning', "⚠️  " . $message) . "\n";
    }

    /**
     * Show title
     * 
     * @param string $title Title text
     */
    public function title(string $title): void
    {
        echo $this->color('title', $title) . "\n";
    }

    /**
     * Show section
     * 
     * @param string $section Section text
     */
    public function section(string $section): void
    {
        echo $this->color('section', $section) . "\n";
    }

    /**
     * Show line
     * 
     * @param string $line Line text
     */
    public function line(string $line = ''): void
    {
        echo $line . "\n";
    }

    /**
     * Apply color to text
     * 
     * @param string $color Color name
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function color(string $color, string $text): string
    {
        if (!isset($this->colors[$color])) {
            return $text;
        }
        
        return $this->colors[$color] . $text . $this->colors['reset'];
    }
} 