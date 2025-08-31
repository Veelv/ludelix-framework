<?php

namespace Ludelix\Interface\Logging;

/**
 * LoggerInterface defines the contract for all logger implementations in the Ludelix Framework.
 * All loggers must implement these methods to ensure consistent logging behavior.
 */
interface LoggerInterface
{
    /**
     * Logs a message at the DEBUG level.
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug(string $message, array $context = []): void;

    /**
     * Logs a message at the INFO level.
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info(string $message, array $context = []): void;

    /**
     * Logs a message at the WARNING level.
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning(string $message, array $context = []): void;

    /**
     * Logs a message at the ERROR level.
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error(string $message, array $context = []): void;

    /**
     * Logs a message at the CRITICAL level.
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical(string $message, array $context = []): void;

    /**
     * Logs a message at the ALERT level.
     * @param string $message
     * @param array $context
     * @return void
     */
    public function alert(string $message, array $context = []): void;

    /**
     * Logs a message at the EMERGENCY level.
     * @param string $message
     * @param array $context
     * @return void
     */
    public function emergency(string $message, array $context = []): void;

    /**
     * Generic log method for custom levels.
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log(string $level, string $message, array $context = []): void;
} 