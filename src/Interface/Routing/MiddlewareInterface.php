<?php

namespace Ludelix\Interface\Routing;

use Ludelix\PRT\Request;
use Ludelix\PRT\Response;

/**
 * Middleware Interface - Routing Middleware Contract
 * 
 * Defines the standard contract for HTTP middleware in the Ludelix routing system.
 * Middleware provides a convenient mechanism for filtering and modifying HTTP requests
 * entering your application and responses leaving your application.
 * 
 * Middleware Pattern:
 * 
 * Middleware operates in a pipeline pattern where each middleware can:
 * 1. Perform actions before the request reaches the route handler
 * 2. Pass the request to the next middleware in the pipeline
 * 3. Perform actions after the response is generated
 * 4. Terminate the request early by returning a response
 * 
 * Common Use Cases:
 * - Authentication and authorization
 * - CORS handling
 * - Rate limiting
 * - Request/response logging
 * - Request transformation
 * - Response caching
 * - Security headers
 * - CSRF protection
 * 
 * @package Ludelix\Interface\Routing
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 * 
 * @example Basic Middleware Implementation:
 * ```php
 * class LoggingMiddleware implements MiddlewareInterface
 * {
 *     public function handle(Request $request, callable $next): Response
 *     {
 *         // Before: Log the incoming request
 *         error_log("Request: {$request->getMethod()} {$request->getPath()}");
 *         
 *         // Pass to next middleware
 *         $response = $next($request);
 *         
 *         // After: Log the response
 *         error_log("Response: {$response->getStatusCode()}");
 *         
 *         return $response;
 *     }
 * }
 * ```
 * 
 * @example Middleware that Terminates Early:
 * ```php
 * class MaintenanceMiddleware implements MiddlewareInterface
 * {
 *     public function handle(Request $request, callable $next): Response
 *     {
 *         if ($this->isInMaintenanceMode()) {
 *             // Terminate early, don't call $next()
 *             return new Response('Service Unavailable', 503);
 *         }
 *         
 *         return $next($request);
 *     }
 * }
 * ```
 * 
 * @example Middleware with Request Modification:
 * ```php
 * class AddHeaderMiddleware implements MiddlewareInterface
 * {
 *     public function handle(Request $request, callable $next): Response
 *     {
 *         // Modify request before passing forward
 *         $request->setHeader('X-Custom-Header', 'value');
 *         
 *         $response = $next($request);
 *         
 *         // Modify response before returning
 *         $response->setHeader('X-Response-Time', microtime(true));
 *         
 *         return $response;
 *     }
 * }
 * ```
 */
interface MiddlewareInterface
{
    /**
     * Handle an incoming request and pass it through the middleware pipeline
     * 
     * This method receives the incoming request and a callable representing the next
     * middleware in the pipeline. The middleware can:
     * 
     * 1. Inspect or modify the request before calling $next()
     * 2. Call $next($request) to pass the request to the next middleware
     * 3. Inspect or modify the response returned from $next()
     * 4. Return a response early without calling $next() to terminate the pipeline
     * 
     * The $next callable has the signature: function(Request $request): Response
     * 
     * @param Request $request The incoming HTTP request
     * @param callable $next The next middleware in the pipeline
     * @return Response The HTTP response
     * 
     * @example Simple Pass-Through:
     * ```php
     * public function handle(Request $request, callable $next): Response
     * {
     *     return $next($request);
     * }
     * ```
     * 
     * @example Before and After Actions:
     * ```php
     * public function handle(Request $request, callable $next): Response
     * {
     *     // Before
     *     $this->logRequest($request);
     *     
     *     $response = $next($request);
     *     
     *     // After
     *     $this->logResponse($response);
     *     
     *     return $response;
     * }
     * ```
     * 
     * @example Early Termination:
     * ```php
     * public function handle(Request $request, callable $next): Response
     * {
     *     if (!$this->isAuthorized($request)) {
     *         return new Response('Unauthorized', 401);
     *     }
     *     
     *     return $next($request);
     * }
     * ```
     */
    public function handle(Request $request, callable $next): Response;
}
