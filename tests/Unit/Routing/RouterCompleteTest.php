<?php

namespace Ludelix\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Ludelix\Routing\Core\RouterComplete;
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

class RouterCompleteTest extends TestCase
{
    protected RouterComplete $router;
    protected RouteCollection $routes;

    protected function setUp(): void
    {
        $this->routes = new RouteCollection();
        $logger = new Logger();
        $container = new Container();
        $eventDispatcher = new EventDispatcher();
        
        $compiler = $this->createMock(RouteCompiler::class);
        $cache = $this->createMock(RouteCache::class);
        $resolver = new RouteResolver($container, $logger);
        $urlGenerator = new UrlGenerator($this->routes);
        $tenantManager = $this->createMock(TenantManager::class);
        
        $this->router = new RouterComplete(
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

    public function testBasicRouting(): void
    {
        $route = $this->router->get('/users', 'UserController@index');
        
        $this->assertEquals(['GET'], $route->getMethods());
        $this->assertEquals('/users', $route->getPath());
        $this->assertEquals('UserController@index', $route->getHandler());
    }

    public function testAllHttpMethods(): void
    {
        $this->router->get('/get', 'Controller@get');
        $this->router->post('/post', 'Controller@post');
        $this->router->put('/put', 'Controller@put');
        $this->router->patch('/patch', 'Controller@patch');
        $this->router->delete('/delete', 'Controller@delete');
        
        $this->assertEquals(5, $this->routes->count());
    }

    public function testSpecialRoutes(): void
    {
        $wsRoute = $this->router->websocket('/chat', 'ChatHandler');
        $gqlRoute = $this->router->graphql('/graphql', 'GraphQLHandler');
        $sseRoute = $this->router->sse('/events', 'EventHandler');
        
        $this->assertEquals(['WEBSOCKET'], $wsRoute->getMethods());
        $this->assertEquals(['POST', 'GET'], $gqlRoute->getMethods());
        $this->assertEquals(['GET'], $sseRoute->getMethods());
    }

    public function testRouteGroups(): void
    {
        $group = $this->router->group(['prefix' => 'api'], function($router) {
            $router->get('/users', 'UserController@index');
            $router->post('/users', 'UserController@store');
        });
        
        $this->assertInstanceOf(\Ludelix\Interface\Routing\RouteGroupInterface::class, $group);
    }

    public function testResourceRoutes(): void
    {
        $group = $this->router->resource('users', 'UserController');
        
        $this->assertInstanceOf(\Ludelix\Interface\Routing\RouteGroupInterface::class, $group);
    }

    public function testRouteNaming(): void
    {
        $route = $this->router->get('/users', 'UserController@index')
                              ->name('users.index');
        
        $this->assertEquals('users.index', $route->getName());
        $this->assertTrue($this->router->hasRoute('users.index'));
        $this->assertSame($route, $this->router->getRoute('users.index'));
    }

    public function testMiddleware(): void
    {
        $route = $this->router->get('/protected', 'Controller@protected')
                              ->middleware(['auth', 'throttle']);
        
        $this->assertEquals(['auth', 'throttle'], $route->getMiddleware());
    }

    public function testConstraints(): void
    {
        $route = $this->router->get('/users/{id}', 'UserController@show')
                              ->where(['id' => '[0-9]+']);
        
        $this->assertEquals(['id' => '[0-9]+'], $route->getConstraints());
    }

    public function testFluentInterface(): void
    {
        $router = $this->router->prefix('api')
                              ->namespace('App\\Controllers\\Api')
                              ->middleware(['api']);
        
        $this->assertSame($this->router, $router);
    }
}