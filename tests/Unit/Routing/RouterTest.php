<?php

namespace Ludelix\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Ludelix\Routing\Core\Router;
use Ludelix\Routing\Core\RouteCollection;
use Ludelix\Routing\Compilers\RouteCompiler;
use Ludelix\Routing\Cache\RouteCache;
use Ludelix\Routing\Resolvers\RouteResolver;
use Ludelix\Routing\Generators\UrlGenerator;
use Ludelix\Core\EventDispatcher;
use Ludelix\Core\Logger;
use Ludelix\Core\Container;
use Ludelix\Tenant\Core\TenantManager;
use Ludelix\PRT\Request;

class RouterTest extends TestCase
{
    protected Router $router;
    protected RouteCollection $routes;

    protected function setUp(): void
    {
        $this->routes = new RouteCollection();
        $logger = new Logger();
        $container = new Container();
        $eventDispatcher = new EventDispatcher();
        
        // Mock dependencies for testing
        $compiler = $this->createMock(RouteCompiler::class);
        $cache = $this->createMock(RouteCache::class);
        $resolver = new RouteResolver($container, $logger);
        $urlGenerator = new UrlGenerator($this->routes);
        $tenantManager = $this->createMock(TenantManager::class);
        
        $this->router = new Router(
            $this->routes,
            $compiler,
            $cache,
            $resolver,
            $urlGenerator,
            $eventDispatcher,
            $logger,
            $tenantManager
        );
    }

    public function testGetRoute(): void
    {
        $route = $this->router->get('/users', 'UserController@index');
        
        $this->assertEquals(['GET'], $route->getMethods());
        $this->assertEquals('/users', $route->getPath());
        $this->assertEquals('UserController@index', $route->getHandler());
    }

    public function testPostRoute(): void
    {
        $route = $this->router->post('/users', 'UserController@store');
        
        $this->assertEquals(['POST'], $route->getMethods());
        $this->assertEquals('/users', $route->getPath());
    }

    public function testRouteWithMiddleware(): void
    {
        $route = $this->router->get('/users', 'UserController@index')
                              ->middleware(['auth', 'throttle']);
        
        $this->assertEquals(['auth', 'throttle'], $route->getMiddleware());
    }

    public function testRouteWithName(): void
    {
        $route = $this->router->get('/users', 'UserController@index')
                              ->name('users.index');
        
        $this->assertEquals('users.index', $route->getName());
        $this->assertTrue($this->routes->hasRoute('users.index'));
    }

    public function testRouteWithConstraints(): void
    {
        $route = $this->router->get('/users/{id}', 'UserController@show')
                              ->where(['id' => '[0-9]+']);
        
        $this->assertEquals(['id' => '[0-9]+'], $route->getConstraints());
    }

    public function testMultipleMethodRoute(): void
    {
        $route = $this->router->match(['GET', 'POST'], '/users', 'UserController@handle');
        
        $this->assertEquals(['GET', 'POST'], $route->getMethods());
    }

    public function testAnyMethodRoute(): void
    {
        $route = $this->router->any('/users', 'UserController@handle');
        
        $methods = $route->getMethods();
        $this->assertContains('GET', $methods);
        $this->assertContains('POST', $methods);
        $this->assertContains('PUT', $methods);
        $this->assertContains('DELETE', $methods);
    }

    public function testWebSocketRoute(): void
    {
        $route = $this->router->websocket('/chat', 'ChatHandler');
        
        $this->assertEquals(['WEBSOCKET'], $route->getMethods());
        $this->assertEquals('websocket', $route->getOptions()['protocol']);
    }

    public function testGraphQLRoute(): void
    {
        $route = $this->router->graphql('/graphql', 'GraphQLHandler');
        
        $this->assertEquals(['POST', 'GET'], $route->getMethods());
        $this->assertEquals('graphql', $route->getOptions()['protocol']);
    }

    public function testRouteGroup(): void
    {
        $group = $this->router->group(['prefix' => 'api', 'middleware' => ['auth']], function($router) {
            $router->get('/users', 'UserController@index');
        });
        
        $this->assertInstanceOf(\Ludelix\Interface\Routing\RouteGroupInterface::class, $group);
    }
}