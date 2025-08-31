<?php

namespace Ludelix\Core\Console\Discovery;

/**
 * Command Discovery
 * 
 * Discovers commands automatically from the application
 * by scanning directories and looking for command classes.
 * 
 * @package Ludelix\Core\Console\Discovery
 */
class CommandDiscovery
{
    protected array $paths = [];
    protected array $excludePaths = [];

    public function __construct()
    {
        $this->paths = [
            'app/Commands',
            'src/Commands',
            'src/*/Commands'
        ];
        
        $this->excludePaths = [
            'vendor',
            'node_modules',
            '.git'
        ];
    }

    /**
     * Discover commands from the application
     * 
     * @return array Array of discovered commands
     */
    public function discover(): array
    {
        $commands = [];
        
        foreach ($this->paths as $path) {
            $commands = array_merge($commands, $this->scanPath($path));
        }
        
        return $commands;
    }

    /**
     * Scan a specific path for commands
     * 
     * @param string $path Path to scan
     * @return array Array of commands found
     */
    protected function scanPath(string $path): array
    {
        $commands = [];
        $fullPath = $this->resolvePath($path);
        
        if (!is_dir($fullPath)) {
            return $commands;
        }
        
        $files = $this->findCommandFiles($fullPath);
        
        foreach ($files as $file) {
            $command = $this->extractCommandFromFile($file);
            if ($command) {
                $commands[] = $command;
            }
        }
        
        return $commands;
    }

    /**
     * Find command files in a directory
     * 
     * @param string $path Directory path
     * @return array Array of file paths
     */
    protected function findCommandFiles(string $path): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $filePath = $file->getPathname();
                
                // Skip excluded paths
                if ($this->isExcluded($filePath)) {
                    continue;
                }
                
                // Check if file contains command class
                if ($this->isCommandFile($filePath)) {
                    $files[] = $filePath;
                }
            }
        }
        
        return $files;
    }

    /**
     * Check if a file path should be excluded
     * 
     * @param string $path File path
     * @return bool True if excluded
     */
    protected function isExcluded(string $path): bool
    {
        foreach ($this->excludePaths as $excludePath) {
            if (strpos($path, $excludePath) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if a file contains a command class
     * 
     * @param string $filePath File path
     * @return bool True if command file
     */
    protected function isCommandFile(string $filePath): bool
    {
        $content = file_get_contents($filePath);
        
        // Check for command class patterns
        $patterns = [
            'class.*Command',
            'implements.*CommandInterface',
            'extends.*BaseCommand'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match('/' . $pattern . '/', $content)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Extract command information from a file
     * 
     * @param string $filePath File path
     * @return array|null Command data or null
     */
    protected function extractCommandFromFile(string $filePath): ?array
    {
        $content = file_get_contents($filePath);
        
        // Extract namespace
        if (!preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            return null;
        }
        
        $namespace = $matches[1];
        
        // Extract class name
        if (!preg_match('/class\s+(\w+)/', $content, $matches)) {
            return null;
        }
        
        $className = $matches[1];
        $fullClassName = $namespace . '\\' . $className;
        
        // Extract command name from class name or file path
        $commandName = $this->extractCommandName($className, $filePath);
        
        return [
            'name' => $commandName,
            'class' => $fullClassName,
            'file' => $filePath
        ];
    }

    /**
     * Extract command name from class name or file path
     * 
     * @param string $className Class name
     * @param string $filePath File path
     * @return string Command name
     */
    protected function extractCommandName(string $className, string $filePath): string
    {
        // Remove "Command" suffix
        $name = preg_replace('/Command$/', '', $className);
        
        // Convert to kebab case
        $name = strtolower(preg_replace('/(?<!^)[A-Z]/', ':$0', $name));
        
        // If no namespace pattern found, use the name as is
        if (strpos($name, ':') === false) {
            // Try to extract from file path
            $pathParts = explode('/', $filePath);
            $commandsIndex = array_search('Commands', $pathParts);
            
            if ($commandsIndex !== false && isset($pathParts[$commandsIndex - 1])) {
                $namespace = strtolower($pathParts[$commandsIndex - 1]);
                $name = $namespace . ':' . $name;
            }
        }
        
        return $name;
    }

    /**
     * Resolve a path relative to the application root
     * 
     * @param string $path Path to resolve
     * @return string Resolved path
     */
    protected function resolvePath(string $path): string
    {
        $root = getcwd();
        
        // Handle wildcards
        if (strpos($path, '*') !== false) {
            $pattern = $root . '/' . $path;
            $matches = glob($pattern);
            return $matches[0] ?? $root . '/' . $path;
        }
        
        return $root . '/' . $path;
    }

    /**
     * Add a path to scan for commands
     * 
     * @param string $path Path to add
     */
    public function addPath(string $path): void
    {
        $this->paths[] = $path;
    }

    /**
     * Add a path to exclude from scanning
     * 
     * @param string $path Path to exclude
     */
    public function addExcludePath(string $path): void
    {
        $this->excludePaths[] = $path;
    }
} 