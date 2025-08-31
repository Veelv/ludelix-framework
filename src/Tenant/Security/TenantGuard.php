<?php

namespace Ludelix\Tenant\Security;

use Ludelix\Interface\Tenant\TenantInterface;
use Ludelix\PRT\Request;

/**
 * Tenant Guard - Security Validation System
 * 
 * Provides comprehensive security validation for tenant access,
 * cross-tenant data protection, and compliance enforcement.
 * 
 * @package Ludelix\Tenant\Security
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class TenantGuard
{
    /**
     * Security configuration
     */
    protected array $config;

    /**
     * Access log for audit trail
     */
    protected array $accessLog = [];

    /**
     * Initialize tenant security guard
     * 
     * @param array $config Security configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'strict_isolation' => true,
            'audit_enabled' => true,
            'max_failed_attempts' => 5,
            'lockout_duration' => 300, // 5 minutes
        ], $config);
    }

    /**
     * Validate tenant access permissions
     * 
     * @param TenantInterface $tenant Target tenant
     * @param Request $request HTTP request
     * @throws \Exception If access is denied
     */
    public function validateAccess(TenantInterface $tenant, Request $request): void
    {
        // Check if tenant is active
        if (!$tenant->isActive()) {
            $this->logSecurityEvent('inactive_tenant_access', $tenant, $request);
            throw new \Exception('Tenant is not active');
        }

        // Validate IP restrictions if configured
        $this->validateIpRestrictions($tenant, $request);

        // Validate domain restrictions
        $this->validateDomainRestrictions($tenant, $request);

        // Check rate limiting
        $this->validateRateLimit($tenant, $request);

        // Log successful access
        $this->logAccess($tenant, $request);
    }

    /**
     * Prevent cross-tenant data leakage
     * 
     * @param TenantInterface $currentTenant Current tenant context
     * @param mixed $data Data to validate
     * @param string $operation Operation type
     * @throws \Exception If cross-tenant access detected
     */
    public function preventDataLeakage(TenantInterface $currentTenant, mixed $data, string $operation): void
    {
        if (!$this->config['strict_isolation']) {
            return;
        }

        // This would implement data validation logic
        // to ensure data belongs to current tenant
        $this->validateDataOwnership($currentTenant, $data, $operation);
    }

    /**
     * Get security audit log
     * 
     * @return array Audit log entries
     */
    public function getAuditLog(): array
    {
        return $this->accessLog;
    }

    /**
     * Clear audit log
     * 
     * @return self Fluent interface
     */
    public function clearAuditLog(): self
    {
        $this->accessLog = [];
        return $this;
    }

    /**
     * Validate IP address restrictions
     * 
     * @param TenantInterface $tenant Target tenant
     * @param Request $request HTTP request
     * @throws \Exception If IP is restricted
     */
    protected function validateIpRestrictions(TenantInterface $tenant, Request $request): void
    {
        $allowedIps = $tenant->getConfig('security.allowed_ips', []);
        
        if (empty($allowedIps)) {
            return; // No IP restrictions
        }

        $clientIp = $request->getClientIp();
        
        if (!in_array($clientIp, $allowedIps)) {
            $this->logSecurityEvent('ip_restriction_violation', $tenant, $request);
            throw new \Exception('Access denied from IP address: ' . $clientIp);
        }
    }

    /**
     * Validate domain restrictions
     * 
     * @param TenantInterface $tenant Target tenant
     * @param Request $request HTTP request
     * @throws \Exception If domain is not allowed
     */
    protected function validateDomainRestrictions(TenantInterface $tenant, Request $request): void
    {
        $allowedDomains = $tenant->getDomain();
        $requestHost = $request->server('HTTP_HOST');
        
        if (empty($allowedDomains) || !$requestHost) {
            return;
        }

        $isAllowed = false;
        
        // Check primary domain
        if ($allowedDomains['primary'] === $requestHost) {
            $isAllowed = true;
        }
        
        // Check aliases
        if (!$isAllowed && isset($allowedDomains['aliases'])) {
            $isAllowed = in_array($requestHost, $allowedDomains['aliases']);
        }

        if (!$isAllowed) {
            $this->logSecurityEvent('domain_restriction_violation', $tenant, $request);
            throw new \Exception('Access denied from domain: ' . $requestHost);
        }
    }

    /**
     * Validate rate limiting
     * 
     * @param TenantInterface $tenant Target tenant
     * @param Request $request HTTP request
     * @throws \Exception If rate limit exceeded
     */
    protected function validateRateLimit(TenantInterface $tenant, Request $request): void
    {
        $rateLimit = $tenant->getConfig('security.rate_limit');
        
        if (!$rateLimit) {
            return; // No rate limiting configured
        }

        // This would implement rate limiting logic
        // For now, just a placeholder
    }

    /**
     * Validate data ownership for cross-tenant protection
     * 
     * @param TenantInterface $tenant Current tenant
     * @param mixed $data Data to validate
     * @param string $operation Operation type
     * @throws \Exception If data doesn't belong to tenant
     */
    protected function validateDataOwnership(TenantInterface $tenant, mixed $data, string $operation): void
    {
        // This would implement comprehensive data ownership validation
        // Checking that data belongs to the current tenant context
        
        if (is_array($data) && isset($data['tenant_id'])) {
            if ($data['tenant_id'] !== $tenant->getId()) {
                $this->logSecurityEvent('cross_tenant_access_attempt', $tenant, null, [
                    'operation' => $operation,
                    'data_tenant_id' => $data['tenant_id']
                ]);
                throw new \Exception('Cross-tenant data access denied');
            }
        }
    }

    /**
     * Log tenant access for audit trail
     * 
     * @param TenantInterface $tenant Accessed tenant
     * @param Request $request HTTP request
     */
    protected function logAccess(TenantInterface $tenant, Request $request): void
    {
        if (!$this->config['audit_enabled']) {
            return;
        }

        $this->accessLog[] = [
            'type' => 'access',
            'tenant_id' => $tenant->getId(),
            'ip_address' => $request->getClientIp(),
            'user_agent' => $request->server('HTTP_USER_AGENT'),
            'timestamp' => time(),
            'uri' => $request->getUri()
        ];
    }

    /**
     * Log security events
     * 
     * @param string $event Event type
     * @param TenantInterface $tenant Related tenant
     * @param Request|null $request HTTP request
     * @param array $extra Extra data
     */
    protected function logSecurityEvent(string $event, TenantInterface $tenant, ?Request $request, array $extra = []): void
    {
        if (!$this->config['audit_enabled']) {
            return;
        }

        $logEntry = [
            'type' => 'security_event',
            'event' => $event,
            'tenant_id' => $tenant->getId(),
            'timestamp' => time(),
        ];

        if ($request) {
            $logEntry['ip_address'] = $request->getClientIp();
            $logEntry['user_agent'] = $request->server('HTTP_USER_AGENT');
            $logEntry['uri'] = $request->getUri();
        }

        if (!empty($extra)) {
            $logEntry['extra'] = $extra;
        }

        $this->accessLog[] = $logEntry;
    }
}