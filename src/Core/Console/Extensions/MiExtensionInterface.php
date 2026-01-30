<?php

namespace Ludelix\Core\Console\Extensions;

use Ludelix\Core\Console\Mi;

/**
 * Mi Extension Interface
 * 
 * Interface that all Mi extensions must implement.
 * Provides methods for registering commands, hooks, and other functionality.
 * 
 * @package Ludelix\Core\Console\Extensions
 */
interface MiExtensionInterface
{
    /**
     * Get extension name
     * 
     * @return string Extension name
     */
    public function getName(): string;

    /**
     * Get extension description
     * 
     * @return string Extension description
     */
    public function getDescription(): string;

    /**
     * Get extension version
     * 
     * @return string Extension version
     */
    public function getVersion(): string;

    /**
     * Register the extension with Mi
     * 
     * @param Mi $mi Mi instance
     */
    public function register(Mi $mi): void;

    /**
     * Boot the extension
     * Called after all extensions are registered
     */
    public function boot(): void;

    /**
     * Get extension commands
     * 
     * @return array Array of command name => class pairs
     */
    public function getCommands(): array;

    /**
     * Get extension hooks
     * 
     * @return array Array of hook name => callback pairs
     */
    public function getHooks(): array;

    /**
     * Get extension configuration
     * 
     * @return array Extension configuration
     */
    public function getConfig(): array;
} 