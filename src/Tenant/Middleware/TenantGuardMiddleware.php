<?php

namespace Ludelix\Tenant\Middleware;

use Ludelix\Tenant\Core\TenantManager;
use Ludelix\Tenant\Security\TenantGuard;
use Ludelix\PRT\Request;
use Ludelix\PRT\Response;

/**
 * Tenant Guard Middleware - Security Protection Layer
 * 
 * Provides security validation and protection for tenant requests including
 * access control, cross-tenant validation, and security policy enforcement.
 * 
 * @package Ludelix\Tenant\Middleware
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class TenantGuardMiddleware
{
    /**
     * Tenant manager instance
     */
    protected TenantManager $tenantManager;

    /**
     * Tenant security guard
     */
    protected TenantGuard $tenantGuard;

    /**
     * Middleware configuration
     */
    protected array $config;

    /**
     * Initialize tenant guard middleware
     * 
     * @param TenantManager $tenantManager Tenant management system
     * @param TenantGuard $tenantGuard Security guard system
     * @param array $config Middleware configuration
     */
    public function __construct(
        TenantManager $tenantManager,
        TenantGuard $tenantGuard,
        array $config = []
    ) {
        $this->tenantManager = $tenantManager;
        $this->tenantGuard = $tenantGuard;
        $this->config = array_merge([
            'strict_mode' => true,
            'log_violations' => true,
            'block_suspicious' => true,
            'rate_limit_enabled' => true,
            'whitelist_ips' => [],
            'blacklist_ips' => [],
        ], $config);
    }

    /**
     * Handle incoming request with security validation
     * 
     * @param Request $request Incoming HTTP request
     * @param callable $next Next middleware in pipeline
     * @return Response HTTP response
     */
    public function handle(Request $request, callable $next): Response
    {
        $tenant = $this->tenantManager->current();

        if (!$tenant) {
            if ($this->config['strict_mode']) {
                return $this->createSecurityResponse('No tenant context', 403);
            }
            return $next($request);
        }

        try {
            // Validate tenant access
            $this->tenantGuard->validateAccess($tenant, $request);

            // Check IP restrictions
            if (!$this->validateIpAccess($request)) {
                return $this->createSecurityResponse('IP access denied', 403);
            }

            // Check rate limiting
            if ($this->config['rate_limit_enabled'] && !$this->validateRateLimit($tenant, $request)) {
                return $this->createSecurityResponse('Rate limit exceeded', 429);
            }

            // Validate request data for cross-tenant access
            $this->validateRequestData($request, $tenant);

            // Add security headers to request
            $this->addSecurityHeaders($request, $tenant);

            // Process request
            $response = $next($request);

            // Add security headers to response
            $this->addResponseSecurityHeaders($response, $tenant);

            return $response;

        } catch (\Throwable $e) {
            return $this->handleSecurityException($e, $request, $tenant);
        }
    }

    /**
     * Validate IP access restrictions
     * 
     * @param Request $request HTTP request
     * @return bool True if IP is allowed
     */
    protected function validateIpAccess(Request $request): bool
    {
        $clientIp = $request->getClientIp();

        // Check blacklist first
        if (in_array($clientIp, $this->config['blacklist_ips'])) {
            return false;
        }

        // Check whitelist if configured
        if (!empty($this->config['whitelist_ips'])) {
            return in_array($clientIp, $this->config['whitelist_ips']);
        }

        return true;
    }

    /**
     * Validate rate limiting
     * 
     * @param object $tenant Current tenant
     * @param Request $request HTTP request
     * @return bool True if within rate limit
     */
    protected function validateRateLimit(object $tenant, Request $request): bool
    {
        // This would integrate with rate limiting system
        // For now, always return true
        return true;
    }

    /**
     * Validate request data for cross-tenant access
     * 
     * @param Request $request HTTP request
     * @param object $tenant Current tenant
     */
    protected function validateRequestData(Request $request, object $tenant): void
    {
        $data = $request->all();

        if (!empty($data)) {
            // Check for tenant_id in request data
            if (isset($data['tenant_id']) && $data['tenant_id'] !== $tenant->getId()) {
                throw new \Exception('Cross-tenant data access attempt detected');
            }

            // Validate nested data
            $this->validateNestedData($data, $tenant);
        }
    }

    /**
     * Validate nested data for tenant references
     * 
     * @param array $data Request data
     * @param object $tenant Current tenant
     */
    protected function validateNestedData(array $data, object $tenant): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->validateNestedData($value, $tenant);
            } elseif (is_string($value) && str_contains($key, 'tenant') && $value !== $tenant->getId()) {
                throw new \Exception("Invalid tenant reference in field: {$key}");
            }
        }
    }

    /**
     * Add security headers to request
     * 
     * @param Request $request HTTP request
     * @param object $tenant Current tenant
     */
    protected function addSecurityHeaders(Request $request, object $tenant): void
    {
        $request->setHeader('X-Tenant-Context', $tenant->getId());
        $request->setHeader('X-Security-Level', $this->config['strict_mode'] ? 'strict' : 'normal');
    }

    /**
     * Add security headers to response
     * 
     * @param Response $response HTTP response
     * @param object $tenant Current tenant
     */
    protected function addResponseSecurityHeaders(Response $response, object $tenant): void
    {
        $response->setHeader('X-Tenant-ID', $tenant->getId());
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->setHeader('X-XSS-Protection', '1; mode=block');

        // Add tenant-specific CSP if configured
        $csp = $tenant->getConfig('security.content_security_policy');
        if ($csp) {
            $response->setHeader('Content-Security-Policy', $csp);
        }
    }

    /**
     * Handle security exceptions
     * 
     * @param \Throwable $exception Security exception
     * @param Request $request HTTP request
     * @param object|null $tenant Current tenant
     * @return Response Error response
     */
    protected function handleSecurityException(\Throwable $exception, Request $request, ?object $tenant): Response
    {
        // Log security violation
        if ($this->config['log_violations']) {
            $this->logSecurityViolation($exception, $request, $tenant);
        }

        // Determine response based on exception type
        $statusCode = match (true) {
            str_contains($exception->getMessage(), 'Cross-tenant') => 403,
            str_contains($exception->getMessage(), 'Rate limit') => 429,
            str_contains($exception->getMessage(), 'IP access') => 403,
            default => 400
        };

        return $this->createSecurityResponse($exception->getMessage(), $statusCode);
    }

    /**
     * Log security violation
     * 
     * @param \Throwable $exception Security exception
     * @param Request $request HTTP request
     * @param object|null $tenant Current tenant
     */
    protected function logSecurityViolation(\Throwable $exception, Request $request, ?object $tenant): void
    {
        $logData = [
            'timestamp' => microtime(true),
            'datetime' => date('Y-m-d H:i:s'),
            'tenant_id' => $tenant?->getId(),
            'ip_address' => $request->getClientIp(),
            'user_agent' => $request->server('HTTP_USER_AGENT'),
            'uri' => $request->getUri(),
            'method' => $request->getMethod(),
            'violation_type' => $this->classifyViolation($exception->getMessage()),
            'message' => $exception->getMessage(),
            'severity' => $this->getViolationSeverity($exception->getMessage()),
        ];

        // This would integrate with logging system
        error_log('Security Violation: ' . json_encode($logData));
    }

    /**
     * Classify security violation type
     * 
     * @param string $message Exception message
     * @return string Violation type
     */
    protected function classifyViolation(string $message): string
    {
        return match (true) {
            str_contains($message, 'Cross-tenant') => 'cross_tenant_access',
            str_contains($message, 'Rate limit') => 'rate_limit_exceeded',
            str_contains($message, 'IP access') => 'ip_restriction',
            str_contains($message, 'Invalid tenant') => 'invalid_tenant_reference',
            default => 'general_security_violation'
        };
    }

    /**
     * Get violation severity level
     * 
     * @param string $message Exception message
     * @return string Severity level
     */
    protected function getViolationSeverity(string $message): string
    {
        return match (true) {
            str_contains($message, 'Cross-tenant') => 'high',
            str_contains($message, 'Invalid tenant') => 'high',
            str_contains($message, 'Rate limit') => 'medium',
            str_contains($message, 'IP access') => 'medium',
            default => 'low'
        };
    }

    /**
     * Create security error response
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @return Response Error response
     */
    protected function createSecurityResponse(string $message, int $statusCode): Response
    {
        return Response::json([
            'error' => 'Security Violation',
            'message' => $message,
            'code' => 'TENANT_SECURITY_VIOLATION',
            'timestamp' => date('Y-m-d H:i:s'),
        ], $statusCode);
    }
}