<?php

namespace Ludelix\GraphQL\Core;

/**
 * GraphQL Schema Builder
 * 
 * Builds GraphQL schema from type definitions and resolvers
 */
class SchemaBuilder
{
    protected array $types = [];
    protected array $queries = [];
    protected array $mutations = [];
    protected array $resolvers = [];

    /**
     * Add type definition
     */
    public function addType(string $name, array $definition): self
    {
        $this->types[$name] = $definition;
        return $this;
    }

    /**
     * Add query field
     */
    public function addQuery(string $name, array $definition): self
    {
        $this->queries[$name] = $definition;
        return $this;
    }

    /**
     * Add mutation field
     */
    public function addMutation(string $name, array $definition): self
    {
        $this->mutations[$name] = $definition;
        return $this;
    }

    /**
     * Add resolver
     */
    public function addResolver(string $type, string $field, $resolver): self
    {
        $this->resolvers[$type][$field] = $resolver;
        return $this;
    }

    /**
     * Build schema
     */
    public function build(): array
    {
        return [
            'types' => $this->types,
            'queries' => $this->queries,
            'mutations' => $this->mutations,
            'resolvers' => $this->resolvers
        ];
    }

    /**
     * Generate SDL (Schema Definition Language)
     */
    public function toSDL(): string
    {
        $sdl = '';
        
        // Types
        foreach ($this->types as $name => $definition) {
            $sdl .= "type {$name} {\n";
            foreach ($definition['fields'] as $field => $type) {
                $sdl .= "  {$field}: {$type}\n";
            }
            $sdl .= "}\n\n";
        }
        
        // Query
        if (!empty($this->queries)) {
            $sdl .= "type Query {\n";
            foreach ($this->queries as $name => $definition) {
                $args = '';
                if (!empty($definition['args'])) {
                    $argList = [];
                    foreach ($definition['args'] as $arg => $type) {
                        $argList[] = "{$arg}: {$type}";
                    }
                    $args = '(' . implode(', ', $argList) . ')';
                }
                $sdl .= "  {$name}{$args}: {$definition['type']}\n";
            }
            $sdl .= "}\n\n";
        }
        
        // Mutation
        if (!empty($this->mutations)) {
            $sdl .= "type Mutation {\n";
            foreach ($this->mutations as $name => $definition) {
                $args = '';
                if (!empty($definition['args'])) {
                    $argList = [];
                    foreach ($definition['args'] as $arg => $type) {
                        $argList[] = "{$arg}: {$type}";
                    }
                    $args = '(' . implode(', ', $argList) . ')';
                }
                $sdl .= "  {$name}{$args}: {$definition['type']}\n";
            }
            $sdl .= "}\n\n";
        }
        
        return $sdl;
    }
}