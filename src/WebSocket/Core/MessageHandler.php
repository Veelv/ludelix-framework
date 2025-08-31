<?php

namespace Ludelix\WebSocket\Core;

/**
 * WebSocket Message Handler
 * 
 * Handles incoming WebSocket messages
 */
class MessageHandler
{
    protected array $handlers = [];
    protected array $middleware = [];

    /**
     * Add message handler
     */
    public function addHandler(string $event, callable $handler): void
    {
        $this->handlers[$event] = $handler;
    }

    /**
     * Add middleware
     */
    public function addMiddleware(callable $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    /**
     * Handle incoming message
     */
    public function handle(array $connection, array $message): mixed
    {
        // Apply middleware
        foreach ($this->middleware as $middleware) {
            $result = $middleware($connection, $message);
            if ($result === false) {
                return false; // Middleware blocked the message
            }
        }

        $event = $message['event'] ?? 'unknown';
        
        if (isset($this->handlers[$event])) {
            return $this->handlers[$event]($connection, $message);
        }

        // Default handler
        return $this->handleDefault($connection, $message);
    }

    /**
     * Default message handler
     */
    protected function handleDefault(array $connection, array $message): mixed
    {
        echo "Unhandled message from {$connection['id']}: " . json_encode($message) . "\n";
        return null;
    }

    /**
     * Validate message format
     */
    public function validateMessage(array $message): bool
    {
        return isset($message['event']) && is_string($message['event']);
    }

    /**
     * Parse message from raw data
     */
    public function parseMessage(string $rawData): ?array
    {
        $data = json_decode($rawData, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        if (!$this->validateMessage($data)) {
            return null;
        }
        
        return $data;
    }

    /**
     * Create response message
     */
    public function createResponse(string $event, array $data, ?string $requestId = null): array
    {
        $response = [
            'event' => $event,
            'data' => $data,
            'timestamp' => time()
        ];
        
        if ($requestId) {
            $response['request_id'] = $requestId;
        }
        
        return $response;
    }

    /**
     * Create error response
     */
    public function createError(string $message, int $code = 400, ?string $requestId = null): array
    {
        return $this->createResponse('error', [
            'message' => $message,
            'code' => $code
        ], $requestId);
    }

    /**
     * Get registered handlers
     */
    public function getHandlers(): array
    {
        return array_keys($this->handlers);
    }
}