<?php

namespace Ludelix\Routing\Dispatchers;

use Ludelix\Interface\Routing\RouteInterface;
use Ludelix\Routing\Binding\ModelBinder;
use Ludelix\PRT\Request;
use Ludelix\PRT\Response;
use Ludelix\Core\Container;
use Ludelix\Core\Logger;

/**
 * Route Dispatcher - Advanced Route Execution System
 * 
 * Handles the complete route execution pipeline including middleware,
 * model binding, handler resolution, and response generation.
 * 
 * @package Ludelix\Routing\Dispatchers
 * @author Ludelix Framework Team
 * @version 2.0.0
 */
class RouteDispatcher
{
    protected Container $container;
    protected ModelBinder $modelBinder;
    protected Logger $logger;
    protected array $config;

    public function __construct(
        Container $container,
        ModelBinder $modelBinder,
        Logger $logger,
        array $config = []
    ) {
        $this->container = $container;
        $this->modelBinder = $modelBinder;
        $this->logger = $logger;
        $this->config = $config;
    }

    public function dispatch(RouteInterface $route, Request $request, array $parameters = []): Response
    {
        $startTime = microtime(true);
        
        try {
            $boundParameters = $this->modelBinder->resolve($parameters);
            $response = $this->executeHandler($route, $request, $boundParameters);

            $duration = microtime(true) - $startTime;
            
            $this->logger->info('Route dispatched successfully', [
                'route' => $route->getName() ?? $route->getPath(),
                'handler' => $this->getHandlerName($route->getHandler()),
                'duration' => $duration,
                'status' => $response->getStatusCode()
            ]);

            return $response;
            
        } catch (\Throwable $e) {
            $this->logger->error('Route dispatch failed', [
                'route' => $route->getName() ?? $route->getPath(),
                'error' => $e->getMessage(),
                'duration' => microtime(true) - $startTime
            ]);
            
            throw $e;
        }
    }

    protected function executeHandler(RouteInterface $route, Request $request, array $parameters): Response
    {
        $handler = $route->getHandler();
        
        if (is_string($handler)) {
            return $this->executeStringHandler($handler, $request, $parameters);
        }
        
        if (is_callable($handler)) {
            return $this->executeCallableHandler($handler, $request, $parameters);
        }
        
        if (is_array($handler)) {
            return $this->executeArrayHandler($handler, $request, $parameters);
        }

        throw new \InvalidArgumentException('Invalid route handler type');
    }

    protected function executeStringHandler(string $handler, Request $request, array $parameters): Response
    {
        if (str_contains($handler, '@')) {
            [$controller, $method] = explode('@', $handler, 2);
            
            $controllerInstance = $this->container->make($controller);
            $result = $this->callControllerMethod($controllerInstance, $method, $request, $parameters);
            
            return $this->normalizeResponse($result);
        }

        $controllerInstance = $this->container->make($handler);
        $result = $this->callControllerMethod($controllerInstance, '__invoke', $request, $parameters);
        
        return $this->normalizeResponse($result);
    }

    protected function executeCallableHandler(callable $handler, Request $request, array $parameters): Response
    {
        $result = $handler($request, ...$parameters);
        return $this->normalizeResponse($result);
    }

    protected function executeArrayHandler(array $handler, Request $request, array $parameters): Response
    {
        [$controller, $method] = $handler;
        
        if (is_string($controller)) {
            $controller = $this->container->make($controller);
        }
        
        $result = $this->callControllerMethod($controller, $method, $request, $parameters);
        return $this->normalizeResponse($result);
    }

    protected function callControllerMethod(object $controller, string $method, Request $request, array $parameters): mixed
    {
        return $controller->$method($request, ...$parameters);
    }

    protected function normalizeResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return new Response(json_encode($result), 200, ['Content-Type' => 'application/json']);
        }

        return new Response((string) $result);
    }

    protected function getHandlerName(mixed $handler): string
    {
        if (is_string($handler)) {
            return $handler;
        }
        
        if (is_array($handler)) {
            return (is_string($handler[0]) ? $handler[0] : get_class($handler[0])) . '@' . $handler[1];
        }
        
        if (is_object($handler)) {
            return get_class($handler);
        }
        
        return 'closure';
    }
}