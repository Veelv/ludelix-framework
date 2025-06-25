<?php

namespace Ludelix\Core;

/**
 * Logger - Advanced Logging System
 * 
 * High-performance logging system with multiple drivers, structured logging,
 * and enterprise-grade features for comprehensive application monitoring.
 * 
 * @package Ludelix\Core
 * @author Ludelix Framework Team
 * @version 2.0.0
 */
class Logger
{
    protected array $config = [];
    protected array $drivers = [];
    protected string $defaultDriver = 'file';
    protected array $levels = [
        'emergency' => 0,
        'alert' => 1,
        'critical' => 2,
        'error' => 3,
        'warning' => 4,
        'notice' => 5,
        'info' => 6,
        'debug' => 7,
    ];

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->defaultDriver = $config['default'] ?? 'file';
        $this->initializeDrivers();
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        if (!isset($this->levels[$level])) {
            throw new \InvalidArgumentException("Invalid log level: {$level}");
        }

        $record = [
            'level' => $level,
            'message' => $this->interpolate($message, $context),
            'context' => $context,
            'timestamp' => microtime(true),
            'datetime' => date('Y-m-d H:i:s'),
            'memory' => memory_get_usage(true),
            'pid' => getmypid(),
        ];

        $this->writeToDrivers($record);
    }

    protected function writeToDrivers(array $record): void
    {
        foreach ($this->drivers as $driver) {
            $driver->write($record);
        }
    }

    protected function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (is_scalar($val) || (is_object($val) && method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        return strtr($message, $replace);
    }

    protected function initializeDrivers(): void
    {
        $drivers = $this->config['drivers'] ?? ['file' => []];
        
        foreach ($drivers as $name => $config) {
            $this->drivers[$name] = $this->createDriver($name, $config);
        }
    }

    protected function createDriver(string $name, array $config): object
    {
        return match($name) {
            'file' => new FileLogDriver($config),
            'syslog' => new SyslogLogDriver($config),
            'null' => new NullLogDriver($config),
            default => throw new \InvalidArgumentException("Unknown log driver: {$name}")
        };
    }
}

/**
 * File Log Driver
 */
class FileLogDriver
{
    protected string $logPath;
    protected string $dateFormat;
    protected int $maxFiles;

    public function __construct(array $config)
    {
        $this->logPath = $config['path'] ?? sys_get_temp_dir() . '/ludelix.log';
        $this->dateFormat = $config['date_format'] ?? 'Y-m-d H:i:s';
        $this->maxFiles = $config['max_files'] ?? 30;
        
        $this->ensureLogDirectory();
    }

    public function write(array $record): void
    {
        $logLine = $this->formatRecord($record);
        $logFile = $this->getLogFile();
        
        file_put_contents($logFile, $logLine . PHP_EOL, FILE_APPEND | LOCK_EX);
        $this->rotateFiles();
    }

    protected function formatRecord(array $record): string
    {
        $context = !empty($record['context']) ? ' ' . json_encode($record['context']) : '';
        
        return sprintf(
            '[%s] %s.%s: %s%s',
            $record['datetime'],
            strtoupper($record['level']),
            $record['pid'],
            $record['message'],
            $context
        );
    }

    protected function getLogFile(): string
    {
        $directory = dirname($this->logPath);
        $filename = basename($this->logPath, '.log');
        $date = date('Y-m-d');
        
        return "{$directory}/{$filename}-{$date}.log";
    }

    protected function ensureLogDirectory(): void
    {
        $directory = dirname($this->logPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    protected function rotateFiles(): void
    {
        $directory = dirname($this->logPath);
        $filename = basename($this->logPath, '.log');
        $pattern = "{$directory}/{$filename}-*.log";
        
        $files = glob($pattern);
        if (count($files) > $this->maxFiles) {
            usort($files, function($a, $b) {
                return filemtime($a) <=> filemtime($b);
            });
            
            $filesToDelete = array_slice($files, 0, count($files) - $this->maxFiles);
            foreach ($filesToDelete as $file) {
                unlink($file);
            }
        }
    }
}

/**
 * Syslog Driver
 */
class SyslogLogDriver
{
    protected string $ident;
    protected int $facility;

    public function __construct(array $config)
    {
        $this->ident = $config['ident'] ?? 'ludelix';
        $this->facility = $config['facility'] ?? LOG_USER;
        
        openlog($this->ident, LOG_PID | LOG_ODELAY, $this->facility);
    }

    public function write(array $record): void
    {
        $priority = $this->getPriority($record['level']);
        syslog($priority, $record['message']);
    }

    protected function getPriority(string $level): int
    {
        return match($level) {
            'emergency' => LOG_EMERG,
            'alert' => LOG_ALERT,
            'critical' => LOG_CRIT,
            'error' => LOG_ERR,
            'warning' => LOG_WARNING,
            'notice' => LOG_NOTICE,
            'info' => LOG_INFO,
            'debug' => LOG_DEBUG,
            default => LOG_INFO
        };
    }
}

/**
 * Null Driver (for testing)
 */
class NullLogDriver
{
    public function __construct(array $config) {}
    
    public function write(array $record): void
    {
        // Do nothing
    }
}