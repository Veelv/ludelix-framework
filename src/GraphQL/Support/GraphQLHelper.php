<?php

namespace Ludelix\GraphQL\Support;

use Ludelix\GraphQL\Core\SchemaBuilder;
use Ludelix\GraphQL\Core\QueryExecutor;

/**
 * GraphQL Helper
 * 
 * Helper functions for GraphQL operations
 */
class GraphQLHelper
{
    protected static ?QueryExecutor $executor = null;

    /**
     * Set query executor
     */
    public static function setExecutor(QueryExecutor $executor): void
    {
        self::$executor = $executor;
    }

    /**
     * Execute GraphQL query
     */
    public static function query(string $query, array $variables = []): array
    {
        if (!self::$executor) {
            throw new \RuntimeException('GraphQL executor not set');
        }
        
        return self::$executor->execute($query, $variables);
    }

    /**
     * Create schema builder
     */
    public static function schema(): SchemaBuilder
    {
        return new SchemaBuilder();
    }

    /**
     * Format GraphQL response
     */
    public static function formatResponse(array $data, array $errors = null): array
    {
        $response = ['data' => $data];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        return $response;
    }

    /**
     * Validate GraphQL query syntax
     */
    public static function validateQuery(string $query): array
    {
        $errors = [];
        
        // Basic syntax validation
        if (empty(trim($query))) {
            $errors[] = 'Query cannot be empty';
        }
        
        // Check for balanced braces
        $openBraces = substr_count($query, '{');
        $closeBraces = substr_count($query, '}');
        
        if ($openBraces !== $closeBraces) {
            $errors[] = 'Unbalanced braces in query';
        }
        
        return $errors;
    }

    /**
     * Extract operation name from query
     */
    public static function getOperationName(string $query): ?string
    {
        if (preg_match('/(?:query|mutation)\s+(\w+)/', $query, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Check if query is introspection
     */
    public static function isIntrospectionQuery(string $query): bool
    {
        return str_contains($query, '__schema') || str_contains($query, '__type');
    }
}