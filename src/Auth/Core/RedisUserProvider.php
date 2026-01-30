<?php

namespace Ludelix\Auth\Core;

use Ludelix\Interface\Auth\UserProviderInterface;
use Ludelix\Interface\Auth\UserInterface;
use Ludelix\Cache\RedisCache;

/**
 * RedisUserProvider - Retrieves users from Redis cache.
 * 
 * Used for high-performance authentication where user data is cached.
 */
class RedisUserProvider implements UserProviderInterface
{
    protected RedisCache $redis;
    protected string $prefix;

    /**
     * @param RedisCache $redis  Redis cache instance.
     * @param string     $prefix Prefix for user keys in redis.
     */
    public function __construct(RedisCache $redis, string $prefix = 'user:')
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
    }

    /**
     * Retrieve a user by their unique identifier from Redis.
     */
    public function retrieveById(int|string $id): ?UserInterface
    {
        return $this->redis->get($this->prefix . $id);
    }

    /**
     * Retrieve by credentials - Typically would still need DB or a secondary index in Redis.
     * For this simple implementation, we assume users are indexed by ID in Redis.
     */
    public function retrieveByCredentials(array $credentials): ?UserInterface
    {
        // Redis as a primary store for login is rare without secondary indexes.
        // Usually, you'd use DB for login and Redis for subsequent stateless checks.
        return null;
    }

    public function validateCredentials(UserInterface $user, array $credentials): bool
    {
        return password_verify($credentials['password'], $user->getPasswordHash());
    }

    public function createUser(array $data): ?UserInterface
    {
        return null;
    }
    public function retrieveByToken(int|string $id, string $token): ?UserInterface
    {
        return null;
    }
    public function updateRememberToken(UserInterface $user, string $token): void
    {
    }
}
