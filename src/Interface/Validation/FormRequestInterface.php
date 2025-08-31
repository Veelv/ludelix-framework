<?php

namespace Ludelix\Interface\Validation;

use Ludelix\PRT\Request;
use Ludelix\Validation\Core\ValidationResult;

/**
 * FormRequestInterface - Form request contract
 * 
 * Defines the contract for form validation requests
 */
interface FormRequestInterface
{
    /**
     * Get validation rules
     */
    public function rules(): array;

    /**
     * Get custom validation messages
     */
    public function messages(): array;

    /**
     * Get custom attribute names
     */
    public function attributes(): array;

    /**
     * Authorize the request
     */
    public function authorize(): bool;

    /**
     * Validate the request
     */
    public function validate(): ValidationResult;

    /**
     * Get validated data
     */
    public function validated(): array;

    /**
     * Get validation errors
     */
    public function errors(): array;

    /**
     * Check if validation failed
     */
    public function fails(): bool;

    /**
     * Check if validation passed
     */
    public function passes(): bool;

    /**
     * Get request instance
     */
    public function getRequest(): Request;

    /**
     * Get specific input value
     */
    public function input(string $key, mixed $default = null): mixed;

    /**
     * Get all input data
     */
    public function all(): array;
} 