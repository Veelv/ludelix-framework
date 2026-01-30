<?php

namespace Ludelix\Core\Console\Commands\Core;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * About Command - Display Framework Information
 * 
 * Shows detailed information about the Ludelix Framework including
 * version, environment, and system details.
 * 
 * @package Ludelix\Core\Console\Commands\Core
 * @author Ludelix Framework Team
 * @version 2.0.0
 * @since 1.0.0
 */
class AboutCommand extends BaseCommand
{
    /**
     * Command signature
     */
    protected string $signature = 'about';

    /**
     * Command description
     */
    protected string $description = 'Display information about the Ludelix Framework';

    /**
     * Execute about command
     * 
     * @param array $arguments Command arguments
     * @param array $options Command options
     * @return int Exit code
     */
    public function execute(array $arguments, array $options): int
    {
        $this->displayFrameworkInfo();
        $this->displayEnvironmentInfo();
        $this->displaySystemInfo();
        
        return 0;
    }

    /**
     * Display framework information
     */
    protected function displayFrameworkInfo(): void
    {
        $this->info('🏹 Ludelix Framework');
        $this->line('');
        $this->line('  Version .................. 1.0.0');
        $this->line('  Console Version .......... 2.0.0');
        $this->line('  Architecture ............. Multi-tenant');
        $this->line('  Template Engine .......... Ludou');
        $this->line('  Database ORM ............. Evolution');
        $this->line('');
    }

    /**
     * Display environment information
     */
    protected function displayEnvironmentInfo(): void
    {
        $this->info('🌍 Environment');
        $this->line('');
        $this->line('  Application Name ......... ' . ($_ENV['APP_NAME'] ?? 'Ludelix App'));
        $this->line('  Environment .............. ' . ($_ENV['APP_ENV'] ?? 'production'));
        $this->line('  Debug Mode ............... ' . (($_ENV['APP_DEBUG'] ?? 'false') === 'true' ? 'ENABLED' : 'DISABLED'));
        $this->line('  URL ...................... ' . ($_ENV['APP_URL'] ?? 'http://localhost'));
        $this->line('  Timezone ................. ' . ($_ENV['APP_TIMEZONE'] ?? 'UTC'));
        $this->line('');
    }

    /**
     * Display system information
     */
    protected function displaySystemInfo(): void
    {
        $this->info('💻 System');
        $this->line('');
        $this->line('  PHP Version .............. ' . PHP_VERSION);
        $this->line('  Operating System ......... ' . PHP_OS);
        $this->line('  Server API ............... ' . PHP_SAPI);
        $this->line('  Memory Limit ............. ' . ini_get('memory_limit'));
        $this->line('  Max Execution Time ....... ' . ini_get('max_execution_time') . 's');
        $this->line('');
        
        $this->displayExtensions();
    }

    /**
     * Display loaded extensions
     */
    protected function displayExtensions(): void
    {
        $this->info('📦 Extensions');
        $this->line('');
        
        $extensions = [
            'PDO' => extension_loaded('pdo'),
            'MySQL' => extension_loaded('pdo_mysql'),
            'SQLite' => extension_loaded('pdo_sqlite'),
            'PostgreSQL' => extension_loaded('pdo_pgsql'),
            'Redis' => extension_loaded('redis'),
            'Memcached' => extension_loaded('memcached'),
            'OpenSSL' => extension_loaded('openssl'),
            'cURL' => extension_loaded('curl'),
            'JSON' => extension_loaded('json'),
            'XML' => extension_loaded('xml'),
            'GD' => extension_loaded('gd'),
            'Imagick' => extension_loaded('imagick'),
        ];

        foreach ($extensions as $name => $loaded) {
            $status = $loaded ? '✅ LOADED' : '❌ NOT LOADED';
            $this->line('  ' . str_pad($name, 20) . ' ' . $status);
        }
        
        $this->line('');
    }
}