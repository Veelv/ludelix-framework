<?php

namespace Ludelix\Ludou\Core;

/**
 * Expression Evaluator
 * 
 * Evaluates Sharp expressions and applies filters
 */
class ExpressionEvaluator
{
    protected array $functions = [];
    protected array $filters = [];

    public function __construct(array $functions = [], array $filters = [])
    {
        $this->functions = array_merge([
            't' => [$this, 'translate'],
            'choice' => [$this, 'choice'],
            'asset' => [$this, 'asset']
        ], $functions);
        $this->filters = $filters;
    }

    public function evaluate(string $expression, array $context = []): mixed
    {
        // Function call: t('key', params)
        if (preg_match("/^(\w+)\(([^)]*)\)$/", $expression, $matches)) {
            $func = $matches[1];
            $params = $this->parseParams($matches[2], $context);
            
            if (isset($this->functions[$func])) {
                return call_user_func_array($this->functions[$func], $params);
            }
        }

        // Variable access: $variable
        if (preg_match("/^\$(\w+)$/", $expression, $matches)) {
            $var = $matches[1];
            return $context[$var] ?? null;
        }

        // String literal
        if (preg_match("/^'([^']*)'$/", $expression, $matches)) {
            return $matches[1];
        }

        return $expression;
    }

    public function applyFilters(mixed $value, array $filters): mixed
    {
        foreach ($filters as $filter) {
            if (isset($this->filters[$filter])) {
                $value = $this->filters[$filter]($value);
            }
        }
        return $value;
    }

    protected function parseParams(string $params, array $context): array
    {
        if (empty(trim($params))) {
            return [];
        }

        // Simple parameter parsing - can be enhanced
        $parts = explode(',', $params);
        $result = [];

        foreach ($parts as $part) {
            $part = trim($part);
            
            // String literal
            if (preg_match("/^'([^']*)'$/", $part, $matches)) {
                $result[] = $matches[1];
            }
            // Variable
            elseif (preg_match("/^\$(\w+)$/", $part, $matches)) {
                $result[] = $context[$matches[1]] ?? null;
            }
            // Array (basic support)
            elseif (str_starts_with($part, '[') && str_ends_with($part, ']')) {
                $result[] = []; // Simplified array parsing
            }
            else {
                $result[] = $part;
            }
        }

        return $result;
    }

    /**
     * Translate text
     */
    public function translate(string $key, array $parameters = []): string
    {
        // Mock translation - would integrate with TranslatorService
        $translations = [
            'welcome' => 'Welcome, :name!',
            'hello' => 'Hello, :name!',
            'goodbye' => 'Goodbye!',
            'user.profile' => 'User Profile'
        ];
        
        $translation = $translations[$key] ?? $key;
        
        foreach ($parameters as $param => $value) {
            $translation = str_replace(':' . $param, $value, $translation);
        }
        
        return $translation;
    }

    /**
     * Pluralize translation
     */
    public function choice(string $key, int $count, array $parameters = []): string
    {
        $translation = $this->translate($key, array_merge($parameters, ['count' => $count]));
        
        $parts = explode('|', $translation);
        if (count($parts) === 1) {
            return $translation;
        }
        
        $index = $count === 1 ? 0 : 1;
        return $parts[$index] ?? $parts[0];
    }

    /**
     * Get asset URL
     */
    public function asset(string $path): string
    {
        // Mock asset URL - would integrate with AssetManager
        return '/assets/' . ltrim($path, '/');
    }
}
