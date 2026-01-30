<?php

namespace Ludelix\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Ludelix\Routing\Core\Route;

class RouteTest extends TestCase
{
    public function testRouteCreation(): void
    {
        $route = new Route(['GET'], '/users', 'UserController@index');

        $this->assertEquals(['GET'], $route->getMethods());
        $this->assertEquals('/users', $route->getPath());
        $this->assertEquals('UserController@index', $route->getHandler());
    }

    public function testRouteWithParameters(): void
    {
        $route = new Route(['GET'], '/users/{id}', 'UserController@show');

        $this->assertTrue($route->matches('GET', '/users/123'));
        $this->assertFalse($route->matches('POST', '/users/123'));
        $this->assertFalse($route->matches('GET', '/users'));
    }

    public function testRouteConstraints(): void
    {
        $route = new Route(['GET'], '/users/{id}', 'UserController@show');
        $route->where(['id' => '[0-9]+']);

        $this->assertTrue($route->matches('GET', '/users/123'));
        $this->assertFalse($route->matches('GET', '/users/abc'));
    }

    public function testRouteMiddleware(): void
    {
        $route = new Route(['GET'], '/users', 'UserController@index');
        $route->middleware(['auth', 'throttle']);

        $this->assertEquals(['auth', 'throttle'], $route->getMiddleware());
    }

    public function testRouteName(): void
    {
        $route = new Route(['GET'], '/users', 'UserController@index');
        $route->name('users.index');

        $this->assertEquals('users.index', $route->getName());
    }

    public function testRouteToArray(): void
    {
        $route = new Route(['GET'], '/users/{id}', 'UserController@show');
        $route->name('users.show')
              ->middleware(['auth'])
              ->where(['id' => '[0-9]+']);

        $array = $route->toArray();

        $this->assertEquals(['GET'], $array['methods']);
        $this->assertEquals('/users/{id}', $array['path']);
        $this->assertEquals('UserController@show', $array['handler']);
        $this->assertEquals('users.show', $array['name']);
        $this->assertEquals(['auth'], $array['middleware']);
        $this->assertEquals(['id' => '[0-9]+'], $array['constraints']);
    }
}