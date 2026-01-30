<?php

namespace Ludelix\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Ludelix\Routing\Resolvers\RouteResolver;
use Ludelix\Routing\Core\Route;
use Ludelix\Routing\Core\RouteCollection;
use Ludelix\Core\Container;
use Ludelix\Core\Logger;

class RouteResolverTest extends TestCase
{
    protected RouteResolver $resolver;
    protected RouteCollection $routes;

    protected function setUp(): void
    {
        $container = new Container();
        $logger = new Logger();
        $this->resolver = new RouteResolver($container, $logger);
        $this->routes = new RouteCollection();
    }

    public function testRouteFound(): void
    {
        $route = new Route(['GET'], '/users/{id}', 'UserController@show');
        $this->routes->add($route);

        $result = $this->resolver->resolveRoute('GET', '/users/123', $this->routes);

        $this->assertEquals('found', $result['status']);
        $this->assertSame($route, $result['route']);
        $this->assertEquals(['id' => '123'], $result['parameters']);
    }

    public function testRouteNotFound(): void
    {
        $result = $this->resolver->resolveRoute('GET', '/nonexistent', $this->routes);

        $this->assertEquals('not_found', $result['status']);
    }

    public function testMethodNotAllowed(): void
    {
        $route = new Route(['POST'], '/users', 'UserController@store');
        $this->routes->add($route);

        $result = $this->resolver->resolveRoute('GET', '/users', $this->routes);

        $this->assertEquals('method_not_allowed', $result['status']);
        $this->assertEquals(['POST'], $result['allowed_methods']);
    }

    public function testParameterExtraction(): void
    {
        $route = new Route(['GET'], '/users/{id}/posts/{postId}', 'PostController@show');
        $this->routes->add($route);

        $result = $this->resolver->resolveRoute('GET', '/users/123/posts/456', $this->routes);

        $this->assertEquals('found', $result['status']);
        $this->assertEquals([
            'id' => '123',
            'postId' => '456'
        ], $result['parameters']);
    }

    public function testConstraintMatching(): void
    {
        $route = new Route(['GET'], '/users/{id}', 'UserController@show');
        $route->where(['id' => '[0-9]+']);
        $this->routes->add($route);

        // Should match numeric ID
        $result1 = $this->resolver->resolveRoute('GET', '/users/123', $this->routes);
        $this->assertEquals('found', $result1['status']);

        // Should not match non-numeric ID
        $result2 = $this->resolver->resolveRoute('GET', '/users/abc', $this->routes);
        $this->assertEquals('not_found', $result2['status']);
    }
}