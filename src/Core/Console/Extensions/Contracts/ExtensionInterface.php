<?php

namespace Ludelix\Core\Console\Extensions\Contracts;

use Ludelix\Core\Console\Engine\MiEngine;

interface ExtensionInterface
{
    /**
     * Get extension name
     */
    public function getName(): string;

    /**
     * Get extension version
     */
    public function getVersion(): string;

    /**
     * Get extension description
     */
    public function getDescription(): string;

    /**
     * Get extension author
     */
    public function getAuthor(): string;

    /**
     * Get available commands
     */
    public function getCommands(): array;

    /**
     * Get template paths
     */
    public function getTemplates(): array;

    /**
     * Register extension with Mi engine
     */
    public function register(MiEngine $engine): void;

    /**
     * Boot extension (optional)
     */
    public function boot(): void;

    /**
     * Get extension configuration
     */
    public function getConfig(): array;

    /**
     * Check if extension is compatible with current framework version
     */
    public function isCompatible(string $frameworkVersion): bool;
}