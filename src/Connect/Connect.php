<?php

namespace Ludelix\Connect;

/**
 * Ludelix Connect - Interactive Application Bridge
 * 
 * Acts as the coordination layer between the server-side framework and 
 * the client-side single-page application (SPA). This class provides 
 * centralized access to system versioning, configuration, and interface 
 * protocols required for the hybrid application lifecycle.
 *
 * @package Ludelix\Connect
 * @author  Ludelix Team
 * @access  public
 */
class Connect
{
    /**
     * The current version of the Connect protocol.
     * Used for asset versioning and protocol compatibility checks.
     *
     * @var string
     */
    public const VERSION = '1.0.0';

    /**
     * The singleton instance of the Connect service.
     *
     * @var Connect|null
     */
    protected static ?Connect $instance = null;

    /**
     * Retrieve the global singleton instance of the Connect service.
     * 
     * Ensures that only one instance of the Connect coordinator exists 
     * throughout the request lifecycle.
     *
     * @return Connect The singleton instance.
     */
    public static function getInstance(): Connect
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Get the current framework version string.
     * 
     * This version identifier is injected into response headers to execute 
     * client-side compatibility validation and asset cache busting.
     *
     * @return string The version string (e.g., '1.0.0').
     */
    public function getVersion(): string
    {
        return static::VERSION;
    }
}
