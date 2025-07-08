<?php

namespace Ludelix\Core\Security;

/**
 * Validator
 * 
 * Validates input data with security rules
 */
class Validator
{
    protected array $data = [];
    protected array $rules = [];
    protected array $errors = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    /**
     * Validate data
     */
    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $rules) {
            $this->validateField($field, $rules);
        }

        return empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Validate single field
     */
    protected function validateField(string $field, string|array $rules): void
    {
        $value = $this->data[$field] ?? null;
        $rules = is_string($rules) ? explode('|', $rules) : $rules;

        foreach ($rules as $rule) {
            $this->applyRule($field, $value, $rule);
        }
    }

    /**
     * Apply validation rule
     */
    protected function applyRule(string $field, mixed $value, string $rule): void
    {
        [$ruleName, $parameters] = $this->parseRule($rule);

        switch ($ruleName) {
            case 'required':
                if (empty($value)) {
                    $this->addError($field, "The {$field} field is required");
                }
                break;

            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "The {$field} must be a valid email");
                }
                break;

            case 'min':
                $min = (int)$parameters[0];
                if ($value && strlen($value) < $min) {
                    $this->addError($field, "The {$field} must be at least {$min} characters");
                }
                break;

            case 'max':
                $max = (int)$parameters[0];
                if ($value && strlen($value) > $max) {
                    $this->addError($field, "The {$field} must not exceed {$max} characters");
                }
                break;

            case 'numeric':
                if ($value && !is_numeric($value)) {
                    $this->addError($field, "The {$field} must be numeric");
                }
                break;

            case 'alpha':
                if ($value && !ctype_alpha($value)) {
                    $this->addError($field, "The {$field} must contain only letters");
                }
                break;

            case 'alphanumeric':
                if ($value && !ctype_alnum($value)) {
                    $this->addError($field, "The {$field} must contain only letters and numbers");
                }
                break;

            case 'url':
                if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, "The {$field} must be a valid URL");
                }
                break;

            case 'ip':
                if ($value && !filter_var($value, FILTER_VALIDATE_IP)) {
                    $this->addError($field, "The {$field} must be a valid IP address");
                }
                break;

            case 'regex':
                $pattern = $parameters[0];
                if ($value && !preg_match($pattern, $value)) {
                    $this->addError($field, "The {$field} format is invalid");
                }
                break;

            case 'safe_html':
                if ($value && $this->containsUnsafeHtml($value)) {
                    $this->addError($field, "The {$field} contains unsafe HTML");
                }
                break;

            case 'no_sql_injection':
                if ($value && $this->containsSqlInjection($value)) {
                    $this->addError($field, "The {$field} contains potentially dangerous content");
                }
                break;

            case 'no_xss':
                if ($value && $this->containsXss($value)) {
                    $this->addError($field, "The {$field} contains potentially dangerous scripts");
                }
                break;
        }
    }

    /**
     * Parse rule string
     */
    protected function parseRule(string $rule): array
    {
        if (str_contains($rule, ':')) {
            [$name, $params] = explode(':', $rule, 2);
            return [$name, explode(',', $params)];
        }

        return [$rule, []];
    }

    /**
     * Add validation error
     */
    protected function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    /**
     * Check for unsafe HTML
     */
    protected function containsUnsafeHtml(string $value): bool
    {
        $dangerousTags = ['<script', '<iframe', '<object', '<embed', '<form'];
        
        foreach ($dangerousTags as $tag) {
            if (stripos($value, $tag) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for SQL injection patterns
     */
    protected function containsSqlInjection(string $value): bool
    {
        $patterns = [
            '/union\s+select/i',
            '/drop\s+table/i',
            '/delete\s+from/i',
            '/insert\s+into/i',
            '/update\s+set/i',
            '/exec\s*\(/i',
            '/script\s*:/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for XSS patterns
     */
    protected function containsXss(string $value): bool
    {
        $patterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe/i',
            '/eval\s*\(/i',
            '/expression\s*\(/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitize input
     */
    public function sanitize(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize string
     */
    protected function sanitizeString(string $value): string
    {
        // Remove null bytes
        $value = str_replace("\0", '', $value);
        
        // Trim whitespace
        $value = trim($value);
        
        // Escape HTML entities
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        
        return $value;
    }
}