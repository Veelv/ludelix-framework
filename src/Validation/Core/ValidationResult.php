<?php

namespace Ludelix\Validation\Core;

use Ludelix\Interface\Validation\ValidationResultInterface;

/**
 * ValidationResult - Result of validation process
 * 
 * Contains validation status, errors, and validated data
 */
class ValidationResult implements ValidationResultInterface
{
    protected bool $passed;
    protected array $errors;
    protected array $data;

    public function __construct(bool $passed, array $errors, array $data)
    {
        $this->passed = $passed;
        $this->errors = $errors;
        $this->data = $data;
    }

    /**
     * Check if validation passed
     */
    public function passes(): bool
    {
        return $this->passed;
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !$this->passed;
    }

    /**
     * Get validation errors
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get validated data
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * Get first error for a field
     */
    public function first(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Get all errors for a field
     */
    public function get(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Check if field has errors
     */
    public function has(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * Get all error messages as flat array
     */
    public function all(): array
    {
        $messages = [];
        foreach ($this->errors as $field => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $messages[] = $error;
            }
        }
        return $messages;
    }

    /**
     * Get error count
     */
    public function count(): int
    {
        $count = 0;
        foreach ($this->errors as $fieldErrors) {
            $count += count($fieldErrors);
        }
        return $count;
    }

    /**
     * Check if any errors exist
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get fields with errors
     */
    public function getFieldsWithErrors(): array
    {
        return array_keys($this->errors);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'passed' => $this->passed,
            'errors' => $this->errors,
            'data' => $this->data,
            'count' => $this->count(),
        ];
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
} 