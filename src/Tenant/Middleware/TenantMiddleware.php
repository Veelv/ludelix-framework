<?php

namespace Ludelix\Tenant\Middleware;

use Ludelix\Tenant\Core\TenantManager;
use Ludelix\PRT\Request;
use Ludelix\PRT\Response;

/**
 * Tenant Middleware - Automatic Tenant Resolution
 * 
 * Automatically resolves and sets tenant context for incoming HTTP requests.
 * Integrates with the request/response pipeline to provide seamless
 * multi-tenant functionality.
 * 
 * @package Ludelix\Tenant\Middleware
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class TenantMiddleware
{
    /**
     * Tenant manager instance
     */
    protected TenantManager $tenantManager;

    /**
     * Middleware configuration
     */
    protected array $config;

    /**
     * Initialize tenant middleware
     * 
     * @param TenantManager $tenantManager Tenant management system
     * @param array $config Middleware configuration
     */
    public function __construct(TenantManager $tenantManager, array $config = [])
    {
        $this->tenantManager = $tenantManager;
        $this->config = array_merge([
            'required' => true,
            'fallback_tenant' => null,
            'skip_routes' => ['/health', '/status'],
            'skip_domains' => ['admin.', 'api.'],
        ], $config);
    }

    /**
     * Handle incoming request and resolve tenant context
     * 
     * @param Request $request Incoming HTTP request
     * @param callable $next Next middleware in pipeline
     * @return Response HTTP response
     */
    public function handle(Request $request, callable $next): Response
    {
        // Skip tenant resolution for certain routes
        if ($this->shouldSkipTenantResolution($request)) {
            return $next($request);
        }

        try {
            // Resolve tenant from request
            $tenant = $this->tenantManager->resolve($request);
            
            // Switch to tenant context
            $this->tenantManager->switch($tenant);
            
            // Add tenant information to request attributes
            $request->setAttribute('tenant', $tenant);
            $request->setAttribute('tenant_id', $tenant->getId());
            
        } catch (\Throwable $e) {
            // Handle tenant resolution failure
            return $this->handleResolutionFailure($request, $e, $next);
        }

        // Process request with tenant context
        $response = $next($request);
        
        // Add tenant headers to response
        $this->addTenantHeaders($response);
        
        return $response;
    }

    /**
     * Check if tenant resolution should be skipped for request
     * 
     * @param Request $request HTTP request
     * @return bool True if should skip
     */
    protected function shouldSkipTenantResolution(Request $request): bool
    {
        $path = $request->getPath();
        $host = $request->server('HTTP_HOST', '');
        
        // Skip specific routes
        foreach ($this->config['skip_routes'] as $skipRoute) {
            if (str_starts_with($path, $skipRoute)) {
                return true;
            }
        }
        
        // Skip specific domains
        foreach ($this->config['skip_domains'] as $skipDomain) {
            if (str_starts_with($host, $skipDomain)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Handle tenant resolution failure
     * 
     * @param Request $request HTTP request
     * @param \Throwable $exception Resolution exception
     * @param callable $next Next middleware
     * @return Response HTTP response
     */
    protected function handleResolutionFailure(Request $request, \Throwable $exception, callable $next): Response
    {
        // If tenant is required, return error response
        if ($this->config['required']) {
            return $this->createTenantNotFoundResponse($exception);
        }
        
        // Try fallback tenant if configured
        if ($this->config['fallback_tenant']) {
            try {
                $this->tenantManager->switch($this->config['fallback_tenant']);
                return $next($request);
            } catch (\Throwable $fallbackException) {
                return $this->createTenantNotFoundResponse($fallbackException);
            }
        }
        
        // Continue without tenant context
        return $next($request);
    }

    /**
     * Create tenant not found error response
     * 
     * @param \Throwable $exception Original exception
     * @return Response Error response
     */
    protected function createTenantNotFoundResponse(\Throwable $exception): Response
    {
        return Response::json([
            'error' => 'Tenant Not Found',
            'message' => 'Unable to resolve tenant from request',
            'code' => 'TENANT_NOT_FOUND'
        ], 404);
    }

    /**
     * Add tenant-specific headers to response
     * 
     * @param Response $response HTTP response
     */
    protected function addTenantHeaders(Response $response): void
    {
        $tenant = $this->tenantManager->current();
        
        if ($tenant) {
            $response->setHeader('X-Tenant-ID', $tenant->getId());
            $response->setHeader('X-Tenant-Name', $tenant->getName());
        }
    }
}