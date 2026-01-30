<?php

namespace Ludelix\Validation\Core;

/**
 * ValidationBag - Container for validation errors and messages
 */
class ValidationBag
{
    protected array $errors = [];

    /**
     * Add an error message for a field
     */
    public function add(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * Get all errors
     */
    public function all(): array
    {
        return $this->errors;
    }

    /**
     * Get errors for a specific field
     */
    public function get(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Check if there are errors for a field
     */
    public function has(string $field): bool
    {
        return !empty($this->errors[$field]);
    }

    /**
     * Check if there are any errors
     */
    public function any(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get the first error for a field
     */
    public function first(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Clear all errors
     */
    public function clear(): void
    {
        $this->errors = [];
    }
} 