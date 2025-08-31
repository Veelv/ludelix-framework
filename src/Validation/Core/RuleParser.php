<?php

namespace Ludelix\Validation\Core;

/**
 * RuleParser - Advanced rule parsing system
 * 
 * Parses complex validation rule strings into structured data
 */
class RuleParser
{
    protected array $parsedRules = [];
    protected array $ruleCache = [];

    /**
     * Parse rule string into structured array
     */
    public function parse(string $rules): array
    {
        $cacheKey = md5($rules);
        
        if (isset($this->ruleCache[$cacheKey])) {
            return $this->ruleCache[$cacheKey];
        }

        $parsed = [];
        $ruleArray = $this->splitRules($rules);

        foreach ($ruleArray as $rule) {
            $parsedRule = $this->parseSingleRule($rule);
            if ($parsedRule) {
                $parsed[] = $parsedRule;
            }
        }

        $this->ruleCache[$cacheKey] = $parsed;
        return $parsed;
    }

    /**
     * Split rules string into individual rules
     */
    protected function splitRules(string $rules): array
    {
        return array_map('trim', explode('|', $rules));
    }

    /**
     * Parse a single rule
     */
    protected function parseSingleRule(string $rule): ?array
    {
        // Handle conditional rules
        if (str_contains($rule, ':')) {
            return $this->parseConditionalRule($rule);
        }

        // Handle simple rules
        return $this->parseSimpleRule($rule);
    }

    /**
     * Parse conditional rule (e.g., required_if:field,value)
     */
    protected function parseConditionalRule(string $rule): array
    {
        $parts = explode(':', $rule, 2);
        $ruleName = trim($parts[0]);
        $parameters = $this->parseParameters($parts[1] ?? '');

        return [
            'name' => $ruleName,
            'parameters' => $parameters,
            'type' => 'conditional',
            'original' => $rule,
        ];
    }

    /**
     * Parse simple rule (e.g., required, email)
     */
    protected function parseSimpleRule(string $rule): array
    {
        return [
            'name' => trim($rule),
            'parameters' => [],
            'type' => 'simple',
            'original' => $rule,
        ];
    }

    /**
     * Parse rule parameters
     */
    protected function parseParameters(string $parameters): array
    {
        if (empty($parameters)) {
            return [];
        }

        // Handle complex parameters with quotes
        if (preg_match_all('/"([^"]*)"|\'([^\']*)\'|([^,]+)/', $parameters, $matches)) {
            $parsed = [];
            foreach ($matches[0] as $match) {
                $clean = trim($match, '"\'');
                if (!empty($clean)) {
                    $parsed[] = $clean;
                }
            }
            return $parsed;
        }

        // Simple comma-separated parameters
        return array_map('trim', explode(',', $parameters));
    }

    /**
     * Parse array of rules for multiple fields
     */
    public function parseRules(array $rules): array
    {
        $parsed = [];
        
        foreach ($rules as $field => $fieldRules) {
            if (is_string($fieldRules)) {
                $parsed[$field] = $this->parse($fieldRules);
            } elseif (is_array($fieldRules)) {
                $parsed[$field] = $fieldRules;
            }
        }

        return $parsed;
    }

    /**
     * Validate rule syntax
     */
    public function validateRule(string $rule): bool
    {
        try {
            $this->parse($rule);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get rule dependencies
     */
    public function getDependencies(array $rules): array
    {
        $dependencies = [];

        foreach ($rules as $field => $fieldRules) {
            if (is_string($fieldRules)) {
                $parsedRules = $this->parse($fieldRules);
                foreach ($parsedRules as $rule) {
                    if (isset($rule['parameters'])) {
                        foreach ($rule['parameters'] as $param) {
                            if (str_contains($param, '.')) {
                                $dependencies[$field][] = $param;
                            }
                        }
                    }
                }
            }
        }

        return $dependencies;
    }

    /**
     * Extract field names from rules
     */
    public function extractFields(array $rules): array
    {
        $fields = [];

        foreach ($rules as $field => $fieldRules) {
            $fields[] = $field;
            
            if (is_string($fieldRules)) {
                $parsedRules = $this->parse($fieldRules);
                foreach ($parsedRules as $rule) {
                    if (isset($rule['parameters'])) {
                        foreach ($rule['parameters'] as $param) {
                            if (str_contains($param, '.')) {
                                $fields[] = $param;
                            }
                        }
                    }
                }
            }
        }

        return array_unique($fields);
    }

    /**
     * Build rule string from parsed rules
     */
    public function buildRuleString(array $parsedRules): string
    {
        $ruleStrings = [];

        foreach ($parsedRules as $rule) {
            $ruleString = $rule['name'];
            
            if (!empty($rule['parameters'])) {
                $ruleString .= ':' . implode(',', $rule['parameters']);
            }
            
            $ruleStrings[] = $ruleString;
        }

        return implode('|', $ruleStrings);
    }

    /**
     * Clear cache
     */
    public function clearCache(): void
    {
        $this->ruleCache = [];
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        return [
            'cached_rules' => count($this->ruleCache),
            'memory_usage' => memory_get_usage(true),
        ];
    }
} 