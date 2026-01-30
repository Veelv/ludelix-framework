<?php

namespace Ludelix\Interface\Validation;

/**
 * ValidationResultInterface - Interface for validation results
 * 
 * Defines the contract for validation result objects
 */
interface ValidationResultInterface
{
    /**
     * Check if validation passed
     */
    public function passes(): bool;

    /**
     * Check if validation failed
     */
    public function fails(): bool;

    /**
     * Get validation errors
     */
    public function errors(): array;

    /**
     * Get validated data
     */
    public function data(): array;

    /**
     * Get first error for a field
     */
    public function first(string $field): ?string;

    /**
     * Get all errors for a field
     */
    public function get(string $field): array;

    /**
     * Check if field has errors
     */
    public function has(string $field): bool;

    /**
     * Get all error messages as flat array
     */
    public function all(): array;

    /**
     * Get error count
     */
    public function count(): int;

    /**
     * Check if any errors exist
     */
    public function hasErrors(): bool;

    /**
     * Get fields with errors
     */
    public function getFieldsWithErrors(): array;

    /**
     * Convert to array
     */
    public function toArray(): array;

    /**
     * Convert to JSON
     */
    public function toJson(): string;
} 