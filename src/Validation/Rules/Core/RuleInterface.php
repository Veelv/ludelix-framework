<?php

namespace Ludelix\Validation\Rules\Core;

/**
 * RuleInterface - Base interface for validation rules
 * 
 * Defines the contract for all validation rules
 */
interface RuleInterface
{
    /**
     * Check if the validation rule passes
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $data All validation data
     * @param array $parameters Rule parameters
     * @return bool
     */
    public function passes(string $field, mixed $value, array $data = [], array $parameters = []): bool;

    /**
     * Get the validation error message
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $parameters Rule parameters
     * @return string
     */
    public function message(string $field, mixed $value, array $parameters = []): string;

    /**
     * Get rule name
     */
    public function getName(): string;

    /**
     * Check if rule is implicit
     */
    public function isImplicit(): bool;

    /**
     * Get rule dependencies
     */
    public function getDependencies(): array;
} 