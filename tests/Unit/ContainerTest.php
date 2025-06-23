<?php

namespace Ludelix\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ludelix\Core\Container;

class ContainerTest extends TestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testBasicBinding(): void
    {
        $this->container->bind('test', fn() => 'hello world');
        
        $this->assertTrue($this->container->bound('test'));
        $this->assertEquals('hello world', $this->container->make('test'));
    }

    public function testSingletonBinding(): void
    {
        $this->container->singleton('singleton', fn() => new \stdClass());
        
        $instance1 = $this->container->make('singleton');
        $instance2 = $this->container->make('singleton');
        
        $this->assertSame($instance1, $instance2);
    }

    public function testInstanceBinding(): void
    {
        $instance = new \stdClass();
        $this->container->instance('instance', $instance);
        
        $this->assertSame($instance, $this->container->make('instance'));
    }

    public function testAutomaticResolution(): void
    {
        $resolved = $this->container->make(\stdClass::class);
        
        $this->assertInstanceOf(\stdClass::class, $resolved);
    }
}