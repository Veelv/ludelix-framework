<?php

namespace Ludelix\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ludelix\Core\Config;

class ConfigTest extends TestCase
{
    protected Config $config;

    protected function setUp(): void
    {
        $this->config = new Config();
    }

    public function testSetAndGet(): void
    {
        $this->config->set('test.key', 'value');
        
        $this->assertEquals('value', $this->config->get('test.key'));
        $this->assertTrue($this->config->has('test.key'));
    }

    public function testNestedKeys(): void
    {
        $this->config->set('app.name', 'Ludelix');
        $this->config->set('app.debug', true);
        
        $this->assertEquals('Ludelix', $this->config->get('app.name'));
        $this->assertTrue($this->config->get('app.debug'));
    }

    public function testDefaultValue(): void
    {
        $this->assertEquals('default', $this->config->get('nonexistent', 'default'));
        $this->assertNull($this->config->get('nonexistent'));
    }

    public function testForget(): void
    {
        $this->config->set('temp', 'value');
        $this->assertTrue($this->config->has('temp'));
        
        $this->config->forget('temp');
        $this->assertFalse($this->config->has('temp'));
    }

    public function testAll(): void
    {
        $this->config->set('key1', 'value1');
        $this->config->set('key2', 'value2');
        
        $all = $this->config->all();
        
        $this->assertArrayHasKey('key1', $all);
        $this->assertArrayHasKey('key2', $all);
    }
}