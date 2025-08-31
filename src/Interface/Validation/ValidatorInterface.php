<?php

namespace Ludelix\Interface\Validation;

use Ludelix\Validation\Core\ValidationResult;

/**
 * ValidatorInterface - Main validation interface
 * 
 * Defines the contract for validation operations
 */
interface ValidatorInterface
{
    /**
     * Validate data against rules
     */
    public function validate(array $data, array $rules, array $messages = []): ValidationResult;

    /**
     * Check if validation passes
     */
    public function passes(): bool;

    /**
     * Check if validation fails
     */
    public function fails(): bool;

    /**
     * Get validation errors
     */
    public function errors(): array;

    /**
     * Get validated data
     */
    public function validated(): array;

    /**
     * Set custom messages
     */
    public function setMessages(array $messages): self;

    /**
     * Set custom attributes
     */
    public function setAttributes(array $attributes): self;

    /**
     * Stop validation on first failure
     */
    public function stopOnFirstFailure(): self;
} 