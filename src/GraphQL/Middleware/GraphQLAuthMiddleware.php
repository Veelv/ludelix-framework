<?php

namespace Ludelix\GraphQL\Middleware;

/**
 * GraphQL Authentication Middleware
 * 
 * Handles authentication for GraphQL requests
 */
class GraphQLAuthMiddleware
{
    protected array $publicOperations = [];
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'header' => 'Authorization',
            'prefix' => 'Bearer ',
            'public_operations' => ['__schema', '__type']
        ], $config);
        
        $this->publicOperations = $this->config['public_operations'];
    }

    /**
     * Handle GraphQL request authentication
     */
    public function handle(array $request, callable $next): array
    {
        $query = $request['query'] ?? '';
        $operationName = $this->extractOperationName($query);
        
        // Skip auth for public operations
        if ($this->isPublicOperation($operationName, $query)) {
            return $next($request);
        }
        
        // Check authentication
        $user = $this->authenticate($request);
        
        if (!$user) {
            return [
                'data' => null,
                'errors' => [
                    [
                        'message' => 'Authentication required',
                        'extensions' => [
                            'code' => 'UNAUTHENTICATED',
                            'category' => 'authentication'
                        ]
                    ]
                ]
            ];
        }
        
        // Add user to context
        $request['context']['user'] = $user;
        
        return $next($request);
    }

    /**
     * Authenticate request
     */
    protected function authenticate(array $request): ?array
    {
        $headers = $request['headers'] ?? [];
        $authHeader = $headers[$this->config['header']] ?? '';
        
        if (empty($authHeader)) {
            return null;
        }
        
        // Extract token
        $token = $this->extractToken($authHeader);
        
        if (!$token) {
            return null;
        }
        
        // Validate token (mock implementation)
        return $this->validateToken($token);
    }

    /**
     * Extract token from header
     */
    protected function extractToken(string $authHeader): ?string
    {
        $prefix = $this->config['prefix'];
        
        if (!str_starts_with($authHeader, $prefix)) {
            return null;
        }
        
        return substr($authHeader, strlen($prefix));
    }

    /**
     * Validate authentication token
     */
    protected function validateToken(string $token): ?array
    {
        // Mock token validation - in real implementation would validate JWT/API key
        $validTokens = [
            'user123' => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            'admin456' => ['id' => 2, 'name' => 'Admin User', 'email' => 'admin@example.com', 'role' => 'admin']
        ];
        
        return $validTokens[$token] ?? null;
    }

    /**
     * Extract operation name from query
     */
    protected function extractOperationName(string $query): ?string
    {
        if (preg_match('/(?:query|mutation)\s+(\w+)/', $query, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Check if operation is public
     */
    protected function isPublicOperation(?string $operationName, string $query): bool
    {
        // Check by operation name
        if ($operationName && in_array($operationName, $this->publicOperations)) {
            return true;
        }
        
        // Check for introspection queries
        if (str_contains($query, '__schema') || str_contains($query, '__type')) {
            return true;
        }
        
        // Check for specific public operations in query
        foreach ($this->publicOperations as $publicOp) {
            if (str_contains($query, $publicOp)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Add public operation
     */
    public function addPublicOperation(string $operation): self
    {
        $this->publicOperations[] = $operation;
        return $this;
    }

    /**
     * Set public operations
     */
    public function setPublicOperations(array $operations): self
    {
        $this->publicOperations = $operations;
        return $this;
    }
}