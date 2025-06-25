<?php

namespace Ludelix\WebSocket\Core;

/**
 * WebSocket Connection Manager
 * 
 * Manages WebSocket connections and rooms
 */
class ConnectionManager
{
    protected array $connections = [];
    protected array $rooms = [];

    /**
     * Add new connection
     */
    public function addConnection(array $connection): void
    {
        $this->connections[$connection['id']] = $connection;
    }

    /**
     * Remove connection
     */
    public function removeConnection(string $connectionId): void
    {
        if (isset($this->connections[$connectionId])) {
            // Remove from all rooms
            $connection = $this->connections[$connectionId];
            foreach ($connection['rooms'] as $room) {
                $this->leaveRoom($connectionId, $room);
            }
            
            unset($this->connections[$connectionId]);
        }
    }

    /**
     * Get connection by ID
     */
    public function getConnection(string $connectionId): ?array
    {
        return $this->connections[$connectionId] ?? null;
    }

    /**
     * Get all connections
     */
    public function getAllConnections(): array
    {
        return $this->connections;
    }

    /**
     * Get connection count
     */
    public function getConnectionCount(): int
    {
        return count($this->connections);
    }

    /**
     * Join room
     */
    public function joinRoom(string $connectionId, string $room): void
    {
        if (!isset($this->connections[$connectionId])) {
            return;
        }

        // Add room to connection
        if (!in_array($room, $this->connections[$connectionId]['rooms'])) {
            $this->connections[$connectionId]['rooms'][] = $room;
        }

        // Add connection to room
        if (!isset($this->rooms[$room])) {
            $this->rooms[$room] = [];
        }
        
        if (!in_array($connectionId, $this->rooms[$room])) {
            $this->rooms[$room][] = $connectionId;
        }
    }

    /**
     * Leave room
     */
    public function leaveRoom(string $connectionId, string $room): void
    {
        // Remove room from connection
        if (isset($this->connections[$connectionId])) {
            $this->connections[$connectionId]['rooms'] = array_filter(
                $this->connections[$connectionId]['rooms'],
                fn($r) => $r !== $room
            );
        }

        // Remove connection from room
        if (isset($this->rooms[$room])) {
            $this->rooms[$room] = array_filter(
                $this->rooms[$room],
                fn($id) => $id !== $connectionId
            );
            
            // Remove empty room
            if (empty($this->rooms[$room])) {
                unset($this->rooms[$room]);
            }
        }
    }

    /**
     * Get connections in room
     */
    public function getConnectionsByRoom(string $room): array
    {
        if (!isset($this->rooms[$room])) {
            return [];
        }

        $connections = [];
        foreach ($this->rooms[$room] as $connectionId) {
            if (isset($this->connections[$connectionId])) {
                $connections[] = $this->connections[$connectionId];
            }
        }

        return $connections;
    }

    /**
     * Get all rooms
     */
    public function getAllRooms(): array
    {
        return array_keys($this->rooms);
    }

    /**
     * Get room count
     */
    public function getRoomCount(): int
    {
        return count($this->rooms);
    }

    /**
     * Get connections count in room
     */
    public function getRoomSize(string $room): int
    {
        return count($this->rooms[$room] ?? []);
    }

    /**
     * Check if connection is in room
     */
    public function isInRoom(string $connectionId, string $room): bool
    {
        return isset($this->rooms[$room]) && in_array($connectionId, $this->rooms[$room]);
    }

    /**
     * Update connection last activity
     */
    public function updateLastActivity(string $connectionId): void
    {
        if (isset($this->connections[$connectionId])) {
            $this->connections[$connectionId]['last_ping'] = time();
        }
    }

    /**
     * Get inactive connections
     */
    public function getInactiveConnections(int $timeout = 60): array
    {
        $inactive = [];
        $now = time();
        
        foreach ($this->connections as $connection) {
            if (($now - $connection['last_ping']) > $timeout) {
                $inactive[] = $connection;
            }
        }
        
        return $inactive;
    }
}