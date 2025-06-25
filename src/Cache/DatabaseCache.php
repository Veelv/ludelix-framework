<?php

namespace Ludelix\Cache;

use Ludelix\Interface\Cache\CacheInterface;

/**
 * Database Cache
 * 
 * Database-based cache with automatic cleanup
 */
class DatabaseCache implements CacheInterface
{
    protected $connection;
    protected string $table;
    protected int $defaultTtl;

    public function __construct(array $config = [])
    {
        $this->connection = $config['connection'] ?? app('db');
        $this->table = $config['table'] ?? 'cache';
        $this->defaultTtl = $config['ttl'] ?? 3600;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $record = $this->connection->table($this->table)
            ->where('key', $key)
            ->where('expires_at', '>', time())
            ->first();

        if (!$record) {
            return $default;
        }

        return unserialize($record->value);
    }

    public function put(string $key, mixed $value, int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        $expiresAt = time() + $ttl;

        return $this->connection->table($this->table)->upsert([
            'key' => $key,
            'value' => serialize($value),
            'expires_at' => $expiresAt,
            'created_at' => time()
        ], ['key']);
    }

    public function has(string $key): bool
    {
        return $this->connection->table($this->table)
            ->where('key', $key)
            ->where('expires_at', '>', time())
            ->exists();
    }

    public function forget(string $key): bool
    {
        return $this->connection->table($this->table)
            ->where('key', $key)
            ->delete() > 0;
    }

    public function flush(): bool
    {
        return $this->connection->table($this->table)->truncate();
    }

    public function cleanup(): int
    {
        return $this->connection->table($this->table)
            ->where('expires_at', '<=', time())
            ->delete();
    }

    public function size(): int
    {
        return $this->connection->table($this->table)->count();
    }
}