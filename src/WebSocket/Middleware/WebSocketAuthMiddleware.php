<?php

namespace Ludelix\WebSocket\Middleware;

/**
 * WebSocket Authentication Middleware
 * 
 * Handles authentication for WebSocket connections
 */
class WebSocketAuthMiddleware
{
    protected array $config;
    protected array $publicEvents = ['ping', 'pong', 'heartbeat'];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'token_header' => 'Authorization',
            'token_query' => 'token',
            'require_auth' => true
        ], $config);
    }

    /**
     * Handle WebSocket message authentication
     */
    public function __invoke(array $connection, array $message): bool
    {
        $event = $message['event'] ?? '';
        
        // Skip auth for public events
        if (in_array($event, $this->publicEvents)) {
            return true;
        }
        
        // Skip auth if not required
        if (!$this->config['require_auth']) {
            return true;
        }
        
        // Check if connection is authenticated
        if (!$this->isAuthenticated($connection)) {
            $this->sendAuthError($connection);
            return false;
        }
        
        return true;
    }

    /**
     * Check if connection is authenticated
     */
    protected function isAuthenticated(array $connection): bool
    {
        // Check if user is already set in connection
        if (isset($connection['user'])) {
            return true;
        }
        
        // Try to authenticate from token
        $token = $this->extractToken($connection);
        
        if (!$token) {
            return false;
        }
        
        $user = $this->validateToken($token);
        
        if ($user) {
            // Store user in connection (would need to update connection manager)
            return true;
        }
        
        return false;
    }

    /**
     * Extract authentication token
     */
    protected function extractToken(array $connection): ?string
    {
        // Try to get token from connection headers or query params
        $headers = $connection['headers'] ?? [];
        $query = $connection['query'] ?? [];
        
        // From Authorization header
        if (isset($headers[$this->config['token_header']])) {
            $authHeader = $headers[$this->config['token_header']];
            if (str_starts_with($authHeader, 'Bearer ')) {
                return substr($authHeader, 7);
            }
        }
        
        // From query parameter
        if (isset($query[$this->config['token_query']])) {
            return $query[$this->config['token_query']];
        }
        
        return null;
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
     * Send authentication error
     */
    protected function sendAuthError(array $connection): void
    {
        // Would send error message to connection
        echo "Authentication required for connection {$connection['id']}\n";
    }

    /**
     * Add public event
     */
    public function addPublicEvent(string $event): self
    {
        $this->publicEvents[] = $event;
        return $this;
    }

    /**
     * Set public events
     */
    public function setPublicEvents(array $events): self
    {
        $this->publicEvents = $events;
        return $this;
    }
}