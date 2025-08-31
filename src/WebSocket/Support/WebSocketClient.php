<?php

namespace Ludelix\WebSocket\Support;

/**
 * WebSocket Client
 * 
 * Client for connecting to WebSocket servers
 */
class WebSocketClient
{
    protected string $url;
    protected array $headers;
    protected bool $connected = false;
    protected array $handlers = [];

    public function __construct(string $url, array $headers = [])
    {
        $this->url = $url;
        $this->headers = $headers;
    }

    /**
     * Connect to WebSocket server
     */
    public function connect(): bool
    {
        echo "Connecting to WebSocket server: {$this->url}\n";
        
        // Simulate connection
        $this->connected = true;
        
        return $this->connected;
    }

    /**
     * Disconnect from server
     */
    public function disconnect(): void
    {
        if ($this->connected) {
            echo "Disconnecting from WebSocket server\n";
            $this->connected = false;
        }
    }

    /**
     * Send message to server
     */
    public function send(string $event, array $data = []): bool
    {
        if (!$this->connected) {
            return false;
        }

        $message = [
            'event' => $event,
            'data' => $data,
            'timestamp' => time()
        ];

        echo "Sending message: " . json_encode($message) . "\n";
        
        return true;
    }

    /**
     * Add event handler
     */
    public function on(string $event, callable $handler): self
    {
        $this->handlers[$event] = $handler;
        return $this;
    }

    /**
     * Remove event handler
     */
    public function off(string $event): self
    {
        unset($this->handlers[$event]);
        return $this;
    }

    /**
     * Simulate receiving message
     */
    public function simulateReceive(string $event, array $data): void
    {
        if (isset($this->handlers[$event])) {
            $this->handlers[$event]($data);
        }
    }

    /**
     * Join room
     */
    public function joinRoom(string $room): bool
    {
        return $this->send('join_room', ['room' => $room]);
    }

    /**
     * Leave room
     */
    public function leaveRoom(string $room): bool
    {
        return $this->send('leave_room', ['room' => $room]);
    }

    /**
     * Send chat message
     */
    public function sendChatMessage(string $message, ?string $room = null): bool
    {
        $data = ['message' => $message];
        
        if ($room) {
            $data['room'] = $room;
        }
        
        return $this->send('chat_message', $data);
    }

    /**
     * Send ping
     */
    public function ping(): bool
    {
        return $this->send('ping', ['timestamp' => time()]);
    }

    /**
     * Check if connected
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * Get connection URL
     */
    public function getUrl(): string
    {
        return $this->url;
    }
}