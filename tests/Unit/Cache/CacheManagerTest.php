<?php

namespace Ludelix\Tests\Unit\Cache;

use PHPUnit\Framework\TestCase;
use Ludelix\Cache\CacheManager;
use Ludelix\Cache\FileCache;

class CacheManagerTest extends TestCase
{
    protected CacheManager $cache;

    protected function setUp(): void
    {
        $config = [
            'default' => 'file',
            'drivers' => [
                'file' => [
                    'enabled' => true,
                    'path' => sys_get_temp_dir() . '/ludelix_test_cache',
                    'ttl' => 3600,
                ]
            ]
        ];
        
        $this->cache = new CacheManager($config);
    }

    public function testPutAndGet(): void
    {
        $this->assertTrue($this->cache->put('test_key', 'test_value'));
        $this->assertEquals('test_value', $this->cache->get('test_key'));
    }

    public function testHas(): void
    {
        $this->cache->put('exists_key', 'value');
        
        $this->assertTrue($this->cache->has('exists_key'));
        $this->assertFalse($this->cache->has('missing_key'));
    }

    public function testForget(): void
    {
        $this->cache->put('forget_key', 'value');
        $this->assertTrue($this->cache->has('forget_key'));
        
        $this->cache->forget('forget_key');
        $this->assertFalse($this->cache->has('forget_key'));
    }

    public function testRemember(): void
    {
        $value = $this->cache->remember('remember_key', function() {
            return 'computed_value';
        });
        
        $this->assertEquals('computed_value', $value);
        $this->assertEquals('computed_value', $this->cache->get('remember_key'));
    }

    public function testDefaultValue(): void
    {
        $this->assertEquals('default', $this->cache->get('missing_key', 'default'));
    }

    public function testFlush(): void
    {
        $this->cache->put('key1', 'value1');
        $this->cache->put('key2', 'value2');
        
        $this->cache->flush();
        
        $this->assertFalse($this->cache->has('key1'));
        $this->assertFalse($this->cache->has('key2'));
    }

    public function testDriverSelection(): void
    {
        $fileDriver = $this->cache->driver('file');
        $this->assertInstanceOf(FileCache::class, $fileDriver);
    }
}