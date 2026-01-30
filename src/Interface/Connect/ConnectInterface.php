<?php

namespace Ludelix\Interface\Connect;

/**
 * Connect Interface
 * 
 * Defines the contract for LudelixConnect - the advanced SPA integration system
 * that replaces Inertia.js with superior features including SSR, WebSocket sync,
 * hot reload, and intelligent component resolution.
 * 
 * @package Ludelix\Interface\Connect
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
interface ConnectInterface
{
    /**
     * Render a component with props for SPA integration
     * 
     * @param string $component Component name to render
     * @param array $props Component properties
     * @param array $shared Shared application state
     * @return mixed Response object for SPA or HTML
     */
    public function component(string $component, array $props = [], array $shared = []): mixed;

    /**
     * Set shared properties available to all components
     * 
     * @param array $shared Shared state data
     * @return ConnectInterface Fluent interface
     */
    public function share(array $shared): ConnectInterface;

    /**
     * Enable Server-Side Rendering for the component
     * 
     * @param bool $enabled SSR enabled state
     * @return ConnectInterface Fluent interface
     */
    public function ssr(bool $enabled = true): ConnectInterface;

    /**
     * Set the root template for SPA mounting
     * 
     * @param string $template Template name
     * @return ConnectInterface Fluent interface
     */
    public function rootTemplate(string $template): ConnectInterface;

    /**
     * Configure WebSocket synchronization
     * 
     * @param array $config WebSocket configuration
     * @return ConnectInterface Fluent interface
     */
    public function websocket(array $config = []): ConnectInterface;

    /**
     * Check if current request is from LudelixConnect
     * 
     * @return bool True if Connect request
     */
    public function isConnectRequest(): bool;

    /**
     * Get the current page version for cache busting
     * 
     * @return string Version hash
     */
    public function getVersion(): string;
}