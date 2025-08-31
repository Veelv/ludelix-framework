<?php

namespace Ludelix\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Ludelix\Routing\Core\Route;
use Ludelix\Routing\Core\RouteCollection;

class RouteCollectionTest extends TestCase
{
    public function testAddRoute(): void
    {
        $collection = new RouteCollection();
        $route = new Route(['GET'], '/users', 'UserController@index');
        
        $collection->add($route);
        
        $this->assertEquals(1, $collection->count());
        $this->assertContains($route, $collection->all());
    }

    public function testNamedRoutes(): void
    {
        $collection = new RouteCollection();
        $route = new Route(['GET'], '/users', 'UserController@index');
        $route->name('users.index');
        
        $collection->add($route);
        
        $this->assertTrue($collection->hasRoute('users.index'));
        $this->assertSame($route, $collection->getByName('users.index'));
        $this->assertNull($collection->getByName('nonexistent'));
    }

    public function testMethodIndex(): void
    {
        $collection = new RouteCollection();
        
        $getRoute = new Route(['GET'], '/users', 'UserController@index');
        $postRoute = new Route(['POST'], '/users', 'UserController@store');
        
        $collection->add($getRoute);
        $collection->add($postRoute);
        
        $getRoutes = $collection->getByMethod('GET');
        $postRoutes = $collection->getByMethod('POST');
        
        $this->assertCount(1, $getRoutes);
        $this->assertCount(1, $postRoutes);
        $this->assertContains($getRoute, $getRoutes);
        $this->assertContains($postRoute, $postRoutes);
    }

    public function testClear(): void
    {
        $collection = new RouteCollection();
        $route = new Route(['GET'], '/users', 'UserController@index');
        
        $collection->add($route);
        $this->assertEquals(1, $collection->count());
        
        $collection->clear();
        $this->assertEquals(0, $collection->count());
    }

    public function testCompilation(): void
    {
        $collection = new RouteCollection();
        
        $this->assertFalse($collection->isCompiled());
        
        $collection->compile();
        
        $this->assertTrue($collection->isCompiled());
    }
}