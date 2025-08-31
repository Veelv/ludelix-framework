<?php

namespace Ludelix\Exceptions\Validation;

/**
 * ValidationFailedException - Thrown when validation fails
 * 
 * Specific exception for validation failures with detailed error information
 */
class ValidationFailedException extends ValidationException
{
    protected array $failedRules;
    protected array $failedFields;

    public function __construct(
        string $message = 'Validation failed',
        array $errors = [],
        array $data = [],
        array $failedRules = [],
        array $failedFields = [],
        int $code = 0,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $errors, $data, $code, $previous);
        $this->failedRules = $failedRules;
        $this->failedFields = $failedFields;
    }

    /**
     * Get failed rules
     */
    public function getFailedRules(): array
    {
        return $this->failedRules;
    }

    /**
     * Get failed fields
     */
    public function getFailedFields(): array
    {
        return $this->failedFields;
    }

    /**
     * Get failed rule for specific field
     */
    public function getFailedRule(string $field): ?string
    {
        return $this->failedRules[$field] ?? null;
    }

    /**
     * Check if field failed validation
     */
    public function fieldFailed(string $field): bool
    {
        return in_array($field, $this->failedFields);
    }

    /**
     * Get summary of validation failure
     */
    public function getSummary(): array
    {
        return [
            'message' => $this->getMessage(),
            'error_count' => $this->count(),
            'failed_fields' => $this->failedFields,
            'failed_rules' => $this->failedRules,
            'errors' => $this->errors,
        ];
    }
} 