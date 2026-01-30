<?php

namespace Ludelix\Core\Console\Commands\Core;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Start Command - Start Development Server
 * 
 * Starts the Ludelix development server with configurable host, port,
 * and environment settings for local development.
 * 
 * @package Ludelix\Core\Console\Commands\Core
 * @author Ludelix Framework Team
 * @version 2.0.0
 * @since 1.0.0
 */
class StartCommand extends BaseCommand
{
    /**
     * Command signature with arguments and options
     */
    protected string $signature = 'start [--host=127.0.0.1] [--port=8000] [--env=development]';

    /**
     * Command description
     */
    protected string $description = 'Start the Ludelix development server';

    /**
     * Execute start command
     * 
     * @param array $arguments Command arguments
     * @param array $options Command options
     * @return int Exit code
     */
    public function execute(array $arguments, array $options): int
    {
        $host = $this->option($options, 'host', '127.0.0.1');
        $port = $this->option($options, 'port', '8000');
        $env = $this->option($options, 'env', 'development');

        $this->info('🚀 Starting Ludelix Development Server...');
        $this->line('');

        // Display server information
        $this->displayServerInfo($host, $port, $env);

        // Find available port
        $availablePort = $this->findAvailablePort($host, $port);
        if (!$availablePort) {
            $this->error("No available ports found starting from {$port}");
            return 1;
        }

        if ($availablePort !== $port) {
            $this->line("Port {$port} is in use, using port {$availablePort} instead");
            $port = $availablePort;
        }

        // Set environment
        putenv("APP_ENV={$env}");

        // Check if Connect is enabled and has a framework
        $connectEnabled = $this->isConnectEnabled();
        $viteHost = 'localhost';
        $vitePort = '5173';

        if ($connectEnabled) {
            $this->info('🔗 Connect detected and enabled');
            $this->line("Frontend framework: " . $this->getConnectFramework());
            $this->line("Vite dev server: http://{$viteHost}:{$vitePort}");
            $this->line('');

            // Start Vite dev server in background
            $this->startViteDevServer($viteHost, $vitePort);
        }

        // Start PHP built-in server
        $documentRoot = $this->getDocumentRoot();
        $serverUrl = "http://{$host}:{$port}";

        $this->success("PHP Server started at: {$serverUrl}");
        $this->line("Document root: {$documentRoot}");
        $this->line("Press Ctrl+C to stop all servers");
        $this->line('');

        // Start server
        $command = "php -S {$host}:{$port} -t {$documentRoot}";

        // Execute server command
        passthru($command);

        return 0;
    }

    /**
     * Display server startup information
     * 
     * @param string $host Server host
     * @param string $port Server port
     * @param string $env Environment
     */
    protected function displayServerInfo(string $host, string $port, string $env): void
    {
        $this->info('Server Configuration:');
        $this->line("  Host: {$host}");
        $this->line("  Port: {$port}");
        $this->line("  Environment: {$env}");
        $this->line("  PHP Version: " . PHP_VERSION);
        $this->line('');
    }

    /**
     * Check if port is available
     * 
     * @param string $host Host address
     * @param string $port Port number
     * @return bool True if port is available
     */
    protected function isPortAvailable(string $host, string $port): bool
    {
        $connection = @fsockopen($host, (int) $port, $errno, $errstr, 1);

        if ($connection) {
            fclose($connection);
            return false; // Port is in use
        }

        return true; // Port is available
    }

    /**
     * Find available port starting from given port
     * 
     * @param string $host Host address
     * @param string $port Starting port
     * @return string|null Available port or null
     */
    protected function findAvailablePort(string $host, string $port): ?string
    {
        $currentPort = (int) $port;
        $maxPort = $currentPort + 100; // Try up to 100 ports

        while ($currentPort <= $maxPort) {
            if ($this->isPortAvailable($host, (string) $currentPort)) {
                return (string) $currentPort;
            }
            $currentPort++;
        }

        return null;
    }

    /**
     * Get document root path
     * 
     * @return string Document root path
     */
    protected function getDocumentRoot(): string
    {
        // Try to find public directory
        $possiblePaths = [
            getcwd() . '/public',
            getcwd() . '/web',
            getcwd() . '/www',
            getcwd()
        ];

        foreach ($possiblePaths as $path) {
            if (is_dir($path) && file_exists($path . '/index.php')) {
                return $path;
            }
        }

        return getcwd() . '/public';
    }

    /**
     * Check if Connect is enabled and configured
     * 
     * @return bool True if Connect is enabled
     */
    protected function isConnectEnabled(): bool
    {
        $configPath = getcwd() . '/config/connect.php';

        if (!file_exists($configPath)) {
            return false;
        }

        $config = require $configPath;

        return isset($config['enabled']) && $config['enabled'] === true
            && isset($config['framework']) && !empty($config['framework']);
    }

    /**
     * Get Connect framework name
     * 
     * @return string Framework name
     */
    protected function getConnectFramework(): string
    {
        $configPath = getcwd() . '/config/connect.php';

        if (!file_exists($configPath)) {
            return 'none';
        }

        $config = require $configPath;

        return $config['framework'] ?? 'none';
    }

    /**
     * Start Vite dev server in background
     * 
     * @param string $host Vite host
     * @param string $port Vite port
     */
    protected function startViteDevServer(string $host, string $port): void
    {
        if (!file_exists('package.json')) {
            $this->line('⚠️  package.json not found. Vite dev server not started.');
            return;
        }

        if (!is_dir('node_modules')) {
            $this->info('📦 Installing dependencies...');
            $this->runCommand('npm install');
        }

        $this->info("🌐 Starting Vite dev server on http://{$host}:{$port}");

        // Start Vite in background
        $command = "npm run dev -- --host {$host} --port {$port}";

        if (PHP_OS_FAMILY === 'Windows') {
            // Windows
            pclose(popen("start /B {$command}", "r"));
        } else {
            // Unix/Linux/Mac
            exec("{$command} > /dev/null 2>&1 &");
        }

        // Wait a bit for Vite to start
        sleep(2);
    }

    /**
     * Run a command and return exit code
     * 
     * @param string $command Command to run
     * @return int Exit code
     */
    protected function runCommand(string $command): int
    {
        $this->line("Executing: {$command}");

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (is_resource($process)) {
            while (!feof($pipes[1])) {
                $output = fgets($pipes[1]);
                if ($output) {
                    $this->line(trim($output));
                }
            }

            while (!feof($pipes[2])) {
                $error = fgets($pipes[2]);
                if ($error) {
                    $this->error(trim($error));
                }
            }

            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            return proc_close($process);
        }

        return 1;
    }
}