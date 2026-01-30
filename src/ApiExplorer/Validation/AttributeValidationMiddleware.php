<?php

namespace Ludelix\ApiExplorer\Validation;

use Ludelix\PRT\Request;
use Ludelix\PRT\Response;
use Ludelix\ApiExplorer\Validation\AttributeValidator;
use ReflectionMethod;

/**
 * AttributeValidationMiddleware - Automatic validation for API Explorer attributes.
 */
class AttributeValidationMiddleware
{
    protected AttributeValidator $validator;

    public function __construct(AttributeValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Handle the request and perform automatic validation.
     */
    public function handle(Request $request, callable $next): Response
    {
        $handler = $request->getAttribute('_handler');

        if ($handler && is_string($handler) && str_contains($handler, '@')) {
            [$controller, $method] = explode('@', $handler, 2);

            if (class_exists($controller) && method_exists($controller, $method)) {
                $reflection = new ReflectionMethod($controller, $method);
                $errors = $this->validator->validate($request, $reflection);

                if ($errors) {
                    return new Response(json_encode([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $errors
                    ]), 422, ['Content-Type' => 'application/json']);
                }
            }
        }

        return $next($request);
    }
}
