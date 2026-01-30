<?php

namespace Ludelix\Interface\Bridge;

/**
 * Bridge Interface
 * 
 * Contextual service access interface
 */
interface BridgeInterface
{
    /**
     * Get service instance
     */
    public function get(string $service): mixed;

    /**
     * Check if service exists
     */
    public function has(string $service): bool;

    /**
     * Set context for service resolution
     */
    public function context(array $context): self;

    /**
     * Magic method for service access
     */
    public function __call(string $method, array $args): mixed;
}