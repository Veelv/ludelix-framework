<?php

namespace Ludelix\Validation\FormRequest;

use Ludelix\Interface\Validation\FormRequestInterface;
use Ludelix\PRT\Request;
use Ludelix\Validation\Core\ValidationResult;
use Ludelix\Validation\Core\Validator;
use Ludelix\Validation\Core\ValidationEngine;
use Ludelix\Validation\Support\ValidationHelper;

/**
 * FormRequest - Advanced form request base class
 * 
 * Professional form request system with advanced features
 */
abstract class FormRequest implements FormRequestInterface
{
    protected Request $request;
    protected Validator $validator;
    protected ValidationEngine $engine;
    protected ValidationHelper $helper;
    protected ValidationResult $validationResult;
    protected array $validatedData = [];
    protected array $errors = [];
    protected bool $isValidated = false;

    public function __construct(Request $request = null)
    {
        $this->request = $request ?? new Request();
        $this->validator = new Validator();
        $this->engine = new ValidationEngine();
        $this->helper = new ValidationHelper();
    }

    /**
     * Get validation rules
     */
    abstract public function rules(): array;

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Get custom attribute names
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * Authorize the request
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the request
     */
    public function validate(): ValidationResult
    {
        if (!$this->authorize()) {
            throw new \Exception('Request not authorized');
        }

        $data = $this->all();
        $rules = $this->rules();
        $messages = $this->messages();
        $attributes = $this->attributes();

        $this->validator->setMessages($messages);
        $this->validator->setAttributes($attributes);

        $this->validationResult = $this->engine->validate($data, $rules, $messages);
        
        if ($this->validationResult->passes()) {
            $this->validatedData = $this->validationResult->data();
            $this->isValidated = true;
        } else {
            $this->errors = $this->validationResult->errors();
        }

        return $this->validationResult;
    }

    /**
     * Get validated data
     */
    public function validated(): array
    {
        if (!$this->isValidated) {
            $this->validate();
        }
        return $this->validatedData;
    }

    /**
     * Get validation errors
     */
    public function errors(): array
    {
        if (!$this->isValidated) {
            $this->validate();
        }
        return $this->errors;
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        if (!$this->isValidated) {
            $this->validate();
        }
        return $this->validationResult->fails();
    }

    /**
     * Check if validation passed
     */
    public function passes(): bool
    {
        if (!$this->isValidated) {
            $this->validate();
        }
        return $this->validationResult->passes();
    }

    /**
     * Get request instance
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Get specific input value
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->request->input($key, $default);
    }

    /**
     * Get all input data
     */
    public function all(): array
    {
        return $this->request->all();
    }

    /**
     * Get only specific fields
     */
    public function only(array $keys): array
    {
        return $this->request->only($keys);
    }

    /**
     * Get all except specific fields
     */
    public function except(array $keys): array
    {
        return $this->request->except($keys);
    }

    /**
     * Check if input exists
     */
    public function has(string $key): bool
    {
        return $this->request->has($key);
    }

    /**
     * Get validated field value
     */
    public function getValidated(string $key, mixed $default = null): mixed
    {
        return $this->validatedData[$key] ?? $default;
    }

    /**
     * Get first error for field
     */
    public function getError(string $field): ?string
    {
        return $this->validationResult->first($field);
    }

    /**
     * Check if field has errors
     */
    public function hasError(string $field): bool
    {
        return $this->validationResult->has($field);
    }

    /**
     * Get all errors for field
     */
    public function getFieldErrors(string $field): array
    {
        return $this->validationResult->get($field);
    }

    /**
     * Get validation result
     */
    public function getValidationResult(): ValidationResult
    {
        if (!$this->isValidated) {
            $this->validate();
        }
        return $this->validationResult;
    }

    /**
     * Get validation helper
     */
    public function getHelper(): ValidationHelper
    {
        return $this->helper;
    }

    /**
     * Get validation engine
     */
    public function getEngine(): ValidationEngine
    {
        return $this->engine;
    }

    /**
     * Set custom validation messages
     */
    public function setMessages(array $messages): self
    {
        $this->validator->setMessages($messages);
        return $this;
    }

    /**
     * Set custom attributes
     */
    public function setAttributes(array $attributes): self
    {
        $this->validator->setAttributes($attributes);
        return $this;
    }

    /**
     * Enable/disable caching
     */
    public function enableCaching(bool $enable = true): self
    {
        $this->engine->enableCaching($enable);
        return $this;
    }

    /**
     * Enable/disable profiling
     */
    public function enableProfiling(bool $enable = true): self
    {
        $this->engine->enableProfiling($enable);
        return $this;
    }

    /**
     * Stop validation on first failure
     */
    public function stopOnFirstFailure(): self
    {
        $this->validator->stopOnFirstFailure();
        $this->engine->stopOnFirstFailure();
        return $this;
    }

    /**
     * Get validation context
     */
    public function getContext()
    {
        return $this->engine->getContext();
    }

    /**
     * Get profiler
     */
    public function getProfiler()
    {
        return $this->engine->getProfiler();
    }
} 