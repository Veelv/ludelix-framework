<?php

namespace Ludelix\Core\Logging;

use Ludelix\Interface\Logging\LoggerInterface;

/**
 * FileLogger writes log messages to files in the cubby/logs directory.
 * Supports log rotation and is suitable for production environments.
 */
class FileLogger implements LoggerInterface
{
    /** @var string */
    protected string $logDir;
    /** @var string */
    protected string $logFile;
    /** @var int */
    protected int $maxFiles;
    /** @var string */
    protected string $dateFormat;

    /**
     * FileLogger constructor.
     * @param string $logDir Directory where logs are stored (default: cubby/logs)
     * @param string $logFile Log file name (default: app.log)
     * @param int $maxFiles Maximum number of log files to keep (default: 30)
     * @param string $dateFormat Date format for log entries (default: Y-m-d H:i:s)
     */
    public function __construct(string $logDir = 'cubby/logs', string $logFile = 'app.log', int $maxFiles = 30, string $dateFormat = 'Y-m-d H:i:s')
    {
        $this->logDir = $logDir;
        $this->logFile = $logFile;
        $this->maxFiles = $maxFiles;
        $this->dateFormat = $dateFormat;
        $this->ensureLogDirectory();
    }

    /** @inheritDoc */
    public function debug(string $message, array $context = []): void { $this->log('DEBUG', $message, $context); }
    /** @inheritDoc */
    public function info(string $message, array $context = []): void { $this->log('INFO', $message, $context); }
    /** @inheritDoc */
    public function warning(string $message, array $context = []): void { $this->log('WARNING', $message, $context); }
    /** @inheritDoc */
    public function error(string $message, array $context = []): void { $this->log('ERROR', $message, $context); }
    /** @inheritDoc */
    public function critical(string $message, array $context = []): void { $this->log('CRITICAL', $message, $context); }
    /** @inheritDoc */
    public function alert(string $message, array $context = []): void { $this->log('ALERT', $message, $context); }
    /** @inheritDoc */
    public function emergency(string $message, array $context = []): void { $this->log('EMERGENCY', $message, $context); }

    /**
     * Logs a message with a given level.
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $date = date($this->dateFormat);
        $contextStr = $context ? json_encode($context, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : '';
        $line = "[$date] $level: $message" . ($contextStr ? " $contextStr" : '') . "\n";
        $file = $this->getLogFile();
        if (!is_dir($this->logDir)) {
            if (!@mkdir($this->logDir, 0755, true) && !is_dir($this->logDir)) {
                throw new \RuntimeException("Não foi possível criar o diretório de log: {$this->logDir}");
            }
        }
        if (!file_exists($file)) {
            if (@file_put_contents($file, '') === false) {
                throw new \RuntimeException("Não foi possível criar o arquivo de log: $file");
            }
        }
        if (@file_put_contents($file, $line, FILE_APPEND | LOCK_EX) === false) {
            throw new \RuntimeException("Não foi possível gravar no arquivo de log: $file");
        }
        $this->rotateFiles();
    }

    /**
     * Ensures the log directory exists.
     */
    protected function ensureLogDirectory(): void
    {
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    /**
     * Gets the current log file path.
     * @return string
     */
    protected function getLogFile(): string
    {
        $date = date('Y-m-d');
        return rtrim($this->logDir, '/\\') . '/' . basename($this->logFile, '.log') . "-$date.log";
    }

    /**
     * Rotates log files if exceeding maxFiles.
     * @return void
     */
    protected function rotateFiles(): void
    {
        $pattern = rtrim($this->logDir, '/\\') . '/' . basename($this->logFile, '.log') . '-*.log';
        $files = glob($pattern);
        if (count($files) > $this->maxFiles) {
            usort($files, function($a, $b) { return filemtime($a) <=> filemtime($b); });
            $toDelete = array_slice($files, 0, count($files) - $this->maxFiles);
            foreach ($toDelete as $file) {
                @unlink($file);
            }
        }
    }
} 