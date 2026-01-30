<?php

namespace Ludelix\GraphQL\Core;

/**
 * GraphQL Query Executor
 * 
 * Executes GraphQL queries and mutations
 */
class QueryExecutor
{
    protected array $schema;
    protected array $context = [];

    public function __construct(array $schema)
    {
        $this->schema = $schema;
    }

    /**
     * Set execution context
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    /**
     * Execute GraphQL query
     */
    public function execute(string $query, array $variables = []): array
    {
        try {
            $parsed = $this->parseQuery($query);
            $result = $this->executeOperation($parsed, $variables);
            
            return [
                'data' => $result,
                'errors' => null
            ];
        } catch (\Throwable $e) {
            return [
                'data' => null,
                'errors' => [
                    [
                        'message' => $e->getMessage(),
                        'locations' => [],
                        'path' => []
                    ]
                ]
            ];
        }
    }

    /**
     * Parse GraphQL query
     */
    protected function parseQuery(string $query): array
    {
        // Simple query parsing - in real implementation would use proper parser
        $query = trim($query);
        
        if (str_starts_with($query, 'query')) {
            return [
                'operation' => 'query',
                'fields' => $this->extractFields($query)
            ];
        } elseif (str_starts_with($query, 'mutation')) {
            return [
                'operation' => 'mutation',
                'fields' => $this->extractFields($query)
            ];
        }
        
        // Default to query
        return [
            'operation' => 'query',
            'fields' => $this->extractFields($query)
        ];
    }

    /**
     * Extract fields from query
     */
    protected function extractFields(string $query): array
    {
        // Simple field extraction - would be more sophisticated in real implementation
        preg_match('/\{([^}]+)\}/', $query, $matches);
        
        if (!isset($matches[1])) {
            return [];
        }
        
        $fieldsStr = trim($matches[1]);
        $fields = [];
        
        foreach (explode(',', $fieldsStr) as $field) {
            $field = trim($field);
            if (!empty($field)) {
                // Extract field name and arguments
                if (preg_match('/(\w+)(\([^)]*\))?/', $field, $fieldMatches)) {
                    $fieldName = $fieldMatches[1];
                    $args = [];
                    
                    if (isset($fieldMatches[2])) {
                        // Parse arguments - simplified
                        $argsStr = trim($fieldMatches[2], '()');
                        if (!empty($argsStr)) {
                            foreach (explode(',', $argsStr) as $arg) {
                                if (preg_match('/(\w+):\s*"([^"]*)"/', trim($arg), $argMatches)) {
                                    $args[$argMatches[1]] = $argMatches[2];
                                } elseif (preg_match('/(\w+):\s*(\d+)/', trim($arg), $argMatches)) {
                                    $args[$argMatches[1]] = (int)$argMatches[2];
                                }
                            }
                        }
                    }
                    
                    $fields[$fieldName] = ['args' => $args];
                }
            }
        }
        
        return $fields;
    }

    /**
     * Execute operation
     */
    protected function executeOperation(array $parsed, array $variables): array
    {
        $operation = $parsed['operation'];
        $fields = $parsed['fields'];
        $result = [];
        
        foreach ($fields as $fieldName => $fieldInfo) {
            $args = $fieldInfo['args'];
            
            // Replace variables
            foreach ($args as $key => $value) {
                if (is_string($value) && str_starts_with($value, '$')) {
                    $varName = substr($value, 1);
                    if (isset($variables[$varName])) {
                        $args[$key] = $variables[$varName];
                    }
                }
            }
            
            $result[$fieldName] = $this->resolveField($operation, $fieldName, $args);
        }
        
        return $result;
    }

    /**
     * Resolve field
     */
    protected function resolveField(string $operation, string $fieldName, array $args): mixed
    {
        $resolvers = $this->schema['resolvers'] ?? [];
        $operationType = $operation === 'mutation' ? 'Mutation' : 'Query';
        
        if (isset($resolvers[$operationType][$fieldName])) {
            $resolver = $resolvers[$operationType][$fieldName];
            
            if (is_callable($resolver)) {
                return $resolver([], $args, $this->context, []);
            }
            
            if ($resolver instanceof Resolver) {
                return $resolver->resolve([], $args, $this->context, []);
            }
        }
        
        // Default resolver
        return null;
    }
}