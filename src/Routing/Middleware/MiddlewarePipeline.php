<?php

namespace Ludelix\Routing\Middleware;

use Ludelix\Interface\Routing\MiddlewareInterface;
use Ludelix\PRT\Request;
use Ludelix\PRT\Response;
use Ludelix\Interface\Logging\LoggerInterface;

/**
 * Middleware Pipeline - Middleware Execution Pipeline
 * 
 * Executes middleware in a pipeline pattern, building a nested chain of
 * middleware handlers that process the request and response.
 * 
 * @package Ludelix\Routing\Middleware
 * @author Ludelix Framework Team
 * @version 1.0.0
 */
class MiddlewarePipeline
{
    protected LoggerInterface $logger;
    protected array $middleware = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Set middleware stack
     * 
     * @param array $middleware Array of [MiddlewareInterface, parameters] pairs
     * @return self
     */
    public function through(array $middleware): self
    {
        $this->middleware = $middleware;
        return $this;
    }

    /**
     * Execute the middleware pipeline
     * 
     * @param Request $request Initial request
     * @param callable $destination Final destination (route handler)
     * @return Response Final response
     */
    public function then(Request $request, callable $destination): Response
    {
        if (empty($this->middleware)) {
            return $destination($request);
        }

        // Build the pipeline from the inside out
        $pipeline = $this->buildPipeline($destination);

        try {
            $startTime = microtime(true);
            $response = $pipeline($request);
            $duration = microtime(true) - $startTime;

            $this->logger->debug("Middleware pipeline executed", [
                'middleware_count' => count($this->middleware),
                'duration' => $duration
            ]);

            return $response;

        } catch (\Throwable $e) {
            $this->logger->error("Middleware pipeline failed", [
                'error' => $e->getMessage(),
                'middleware' => $this->getMiddlewareNames()
            ]);

            throw $e;
        }
    }

    /**
     * Build the middleware pipeline
     * 
     * @param callable $destination Final destination
     * @return callable Pipeline callable
     */
    protected function buildPipeline(callable $destination): callable
    {
        // Reverse the middleware array so we build from the inside out
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            function ($next, $middlewareData) {
                [$middleware, $parameters] = $middlewareData;

                return function (Request $request) use ($middleware, $parameters, $next) {
                    return $this->executeMiddleware($middleware, $request, $parameters, $next);
                };
            },
            $destination
        );

        return $pipeline;
    }

    /**
     * Execute a single middleware
     * 
     * @param MiddlewareInterface $middleware Middleware instance
     * @param Request $request Request object
     * @param array $parameters Middleware parameters
     * @param callable $next Next handler in pipeline
     * @return Response
     */
    protected function executeMiddleware(
        MiddlewareInterface $middleware,
        Request $request,
        array $parameters,
        callable $next
    ): Response {
        $startTime = microtime(true);
        $middlewareName = get_class($middleware);

        try {
            // If middleware has parameters, inject them
            if (!empty($parameters)) {
                $request->setAttribute('middleware_parameters', $parameters);
            }

            $response = $middleware->handle($request, $next);

            $duration = microtime(true) - $startTime;
            $this->logger->debug("Middleware executed: {$middlewareName}", [
                'duration' => $duration,
                'parameters' => $parameters
            ]);

            return $response;

        } catch (\Throwable $e) {
            $this->logger->error("Middleware failed: {$middlewareName}", [
                'error' => $e->getMessage(),
                'parameters' => $parameters
            ]);

            throw $e;
        }
    }

    /**
     * Get names of middleware in pipeline
     * 
     * @return array
     */
    protected function getMiddlewareNames(): array
    {
        return array_map(
            fn($data) => get_class($data[0]),
            $this->middleware
        );
    }

    /**
     * Add middleware to the pipeline
     * 
     * @param MiddlewareInterface $middleware Middleware instance
     * @param array $parameters Middleware parameters
     * @return self
     */
    public function pipe(MiddlewareInterface $middleware, array $parameters = []): self
    {
        $this->middleware[] = [$middleware, $parameters];
        return $this;
    }

    /**
     * Get middleware count
     * 
     * @return int
     */
    public function count(): int
    {
        return count($this->middleware);
    }

    /**
     * Clear the pipeline
     * 
     * @return self
     */
    public function clear(): self
    {
        $this->middleware = [];
        return $this;
    }
}
