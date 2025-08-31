<?php

namespace Ludelix\Exceptions\Validation;

use Exception;

/**
 * ValidationException - Base validation exception
 * 
 * Thrown when validation fails
 */
class ValidationException extends Exception
{
    protected array $errors;
    protected array $data;

    public function __construct(string $message = '', array $errors = [], array $data = [], int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
        $this->data = $data;
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get validation data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get first error message
     */
    public function getFirstError(): ?string
    {
        foreach ($this->errors as $field => $messages) {
            if (!empty($messages)) {
                return $messages[0];
            }
        }
        return null;
    }

    /**
     * Check if field has errors
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * Get errors for specific field
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }
} 