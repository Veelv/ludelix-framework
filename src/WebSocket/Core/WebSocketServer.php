<?php

namespace Ludelix\WebSocket\Core;

use Ludelix\WebSocket\Core\ConnectionManager;
use Ludelix\WebSocket\Core\MessageHandler;

/**
 * WebSocket Server
 * 
 * Manages WebSocket connections and message handling
 */
class WebSocketServer
{
    protected ConnectionManager $connectionManager;
    protected MessageHandler $messageHandler;
    protected array $config;
    protected bool $running = false;
    protected array $handlers = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'host' => '0.0.0.0',
            'port' => 8080,
            'max_connections' => 1000,
            'heartbeat_interval' => 30
        ], $config);
        
        $this->connectionManager = new ConnectionManager();
        $this->messageHandler = new MessageHandler();
    }

    /**
     * Start WebSocket server
     */
    public function start(): void
    {
        $this->running = true;
        
        echo "WebSocket server starting on {$this->config['host']}:{$this->config['port']}\n";
        
        // Simulate server loop
        while ($this->running) {
            $this->processConnections();
            $this->handleMessages();
            $this->sendHeartbeat();
            
            usleep(100000); // 100ms
        }
    }

    /**
     * Stop WebSocket server
     */
    public function stop(): void
    {
        $this->running = false;
        echo "WebSocket server stopped\n";
    }

    /**
     * Add message handler
     */
    public function addHandler(string $event, callable $handler): self
    {
        $this->handlers[$event] = $handler;
        $this->messageHandler->addHandler($event, $handler);
        return $this;
    }

    /**
     * Broadcast message to all connections
     */
    public function broadcast(string $event, array $data): void
    {
        $message = [
            'event' => $event,
            'data' => $data,
            'timestamp' => time()
        ];
        
        foreach ($this->connectionManager->getAllConnections() as $connection) {
            $this->sendToConnection($connection, $message);
        }
    }

    /**
     * Send message to specific connection
     */
    public function sendToConnection(array $connection, array $message): void
    {
        // Simulate sending message
        echo "Sending to {$connection['id']}: " . json_encode($message) . "\n";
    }

    /**
     * Send message to room
     */
    public function sendToRoom(string $room, string $event, array $data): void
    {
        $connections = $this->connectionManager->getConnectionsByRoom($room);
        
        $message = [
            'event' => $event,
            'data' => $data,
            'room' => $room,
            'timestamp' => time()
        ];
        
        foreach ($connections as $connection) {
            $this->sendToConnection($connection, $message);
        }
    }

    /**
     * Join connection to room
     */
    public function joinRoom(string $connectionId, string $room): void
    {
        $this->connectionManager->joinRoom($connectionId, $room);
        
        $this->sendToRoom($room, 'user_joined', [
            'connection_id' => $connectionId,
            'room' => $room
        ]);
    }

    /**
     * Leave room
     */
    public function leaveRoom(string $connectionId, string $room): void
    {
        $this->connectionManager->leaveRoom($connectionId, $room);
        
        $this->sendToRoom($room, 'user_left', [
            'connection_id' => $connectionId,
            'room' => $room
        ]);
    }

    /**
     * Process new connections
     */
    protected function processConnections(): void
    {
        // Simulate new connection
        if (rand(1, 100) === 1) {
            $connectionId = 'conn_' . uniqid();
            $connection = [
                'id' => $connectionId,
                'connected_at' => time(),
                'last_ping' => time(),
                'rooms' => []
            ];
            
            $this->connectionManager->addConnection($connection);
            echo "New connection: {$connectionId}\n";
        }
    }

    /**
     * Handle incoming messages
     */
    protected function handleMessages(): void
    {
        // Simulate incoming messages
        if (rand(1, 50) === 1) {
            $connections = $this->connectionManager->getAllConnections();
            if (!empty($connections)) {
                $connection = $connections[array_rand($connections)];
                
                $message = [
                    'event' => 'chat_message',
                    'data' => [
                        'message' => 'Hello from ' . $connection['id'],
                        'user' => $connection['id']
                    ]
                ];
                
                $this->messageHandler->handle($connection, $message);
            }
        }
    }

    /**
     * Send heartbeat to all connections
     */
    protected function sendHeartbeat(): void
    {
        static $lastHeartbeat = 0;
        
        if (time() - $lastHeartbeat >= $this->config['heartbeat_interval']) {
            $this->broadcast('heartbeat', ['timestamp' => time()]);
            $lastHeartbeat = time();
        }
    }

    /**
     * Get server statistics
     */
    public function getStats(): array
    {
        return [
            'total_connections' => $this->connectionManager->getConnectionCount(),
            'active_rooms' => count($this->connectionManager->getAllRooms()),
            'uptime' => time(),
            'handlers' => count($this->handlers)
        ];
    }
}