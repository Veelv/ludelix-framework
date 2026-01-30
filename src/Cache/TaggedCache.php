<?php

namespace Ludelix\Cache;

use Ludelix\Interface\Cache\CacheInterface;

/**
 * Tagged Cache
 * 
 * Cache with tagging support for group invalidation
 */
class TaggedCache implements CacheInterface
{
    protected CacheInterface $cache;
    protected array $tags;

    public function __construct(CacheInterface $cache, array $tags)
    {
        $this->cache = $cache;
        $this->tags = $tags;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->isValidTag()) {
            return $default;
        }

        return $this->cache->get($this->taggedKey($key), $default);
    }

    public function put(string $key, mixed $value, int $ttl = null): bool
    {
        $this->ensureTagsExist();
        
        return $this->cache->put($this->taggedKey($key), $value, $ttl);
    }

    public function has(string $key): bool
    {
        if (!$this->isValidTag()) {
            return false;
        }

        return $this->cache->has($this->taggedKey($key));
    }

    public function forget(string $key): bool
    {
        return $this->cache->forget($this->taggedKey($key));
    }

    public function flush(): bool
    {
        foreach ($this->tags as $tag) {
            $this->invalidateTag($tag);
        }
        
        return true;
    }

    public function flushTag(string $tag): bool
    {
        return $this->invalidateTag($tag);
    }

    protected function taggedKey(string $key): string
    {
        $tagHash = md5(implode('|', $this->getTagVersions()));
        return "tagged:{$tagHash}:{$key}";
    }

    protected function getTagVersions(): array
    {
        $versions = [];
        
        foreach ($this->tags as $tag) {
            $versions[$tag] = $this->cache->get("tag:{$tag}", 1);
        }
        
        return $versions;
    }

    protected function ensureTagsExist(): void
    {
        foreach ($this->tags as $tag) {
            if (!$this->cache->has("tag:{$tag}")) {
                $this->cache->put("tag:{$tag}", 1, 86400 * 30); // 30 days
            }
        }
    }

    protected function isValidTag(): bool
    {
        foreach ($this->tags as $tag) {
            if (!$this->cache->has("tag:{$tag}")) {
                return false;
            }
        }
        
        return true;
    }

    protected function invalidateTag(string $tag): bool
    {
        $currentVersion = $this->cache->get("tag:{$tag}", 1);
        return $this->cache->put("tag:{$tag}", $currentVersion + 1, 86400 * 30);
    }
}