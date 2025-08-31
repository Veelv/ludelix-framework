<?php

namespace Ludelix\GraphQL\Core;

/**
 * GraphQL Resolver
 * 
 * Base class for GraphQL resolvers
 */
abstract class Resolver
{
    protected array $context = [];

    /**
     * Set resolver context
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    /**
     * Get context value
     */
    protected function getContext(string $key = null): mixed
    {
        if ($key === null) {
            return $this->context;
        }
        
        return $this->context[$key] ?? null;
    }

    /**
     * Resolve field
     */
    abstract public function resolve(array $root, array $args, array $context, array $info): mixed;

    /**
     * Validate arguments
     */
    protected function validateArgs(array $args, array $rules): array
    {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            if ($rule['required'] && !isset($args[$field])) {
                $errors[] = "Field '{$field}' is required";
            }
            
            if (isset($args[$field]) && isset($rule['type'])) {
                $value = $args[$field];
                $type = $rule['type'];
                
                if (!$this->validateType($value, $type)) {
                    $errors[] = "Field '{$field}' must be of type {$type}";
                }
            }
        }
        
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }
        
        return $args;
    }

    /**
     * Validate type
     */
    protected function validateType(mixed $value, string $type): bool
    {
        return match($type) {
            'String' => is_string($value),
            'Int' => is_int($value),
            'Float' => is_float($value) || is_int($value),
            'Boolean' => is_bool($value),
            'ID' => is_string($value) || is_int($value),
            default => true
        };
    }
}