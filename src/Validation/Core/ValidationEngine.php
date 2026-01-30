<?php

namespace Ludelix\Validation\Core;

use Ludelix\Interface\Validation\ValidatorInterface;
use Ludelix\Validation\Rules\RuleFactory;
use Ludelix\Validation\Support\ValidationCache;
use Ludelix\Validation\Support\ValidationProfiler;
use Ludelix\Exceptions\Validation\ValidationFailedException;

/**
 * ValidationEngine - Advanced validation engine
 * 
 * Professional validation engine with caching, profiling, and advanced features
 */
class ValidationEngine implements ValidatorInterface
{
    protected RuleFactory $ruleFactory;
    protected ValidationCache $cache;
    protected ValidationProfiler $profiler;
    protected ValidationContext $context;
    protected ValidationBag $errorBag;
    protected array $data = [];
    protected array $rules = [];
    protected array $messages = [];
    protected array $customAttributes = [];
    protected array $errors = [];
    protected bool $stopOnFirstFailure = false;
    protected bool $enableCaching = true;
    protected bool $enableProfiling = true;

    public function __construct()
    {
        $this->ruleFactory = new RuleFactory();
        $this->cache = new ValidationCache();
        $this->profiler = new ValidationProfiler();
        $this->context = new ValidationContext();
        $this->errorBag = new ValidationBag();
    }

    /**
     * Validate data against rules with advanced features
     */
    public function validate(array $data, array $rules, array $messages = []): ValidationResult
    {
        $this->profiler->start('validation');
        
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = array_merge($this->messages, $messages);
        $this->errors = [];

        // Check cache first
        $cacheKey = $this->generateCacheKey($data, $rules);
        if ($this->enableCaching && $cachedResult = $this->cache->get($cacheKey)) {
            $this->profiler->end('validation');
            return $cachedResult;
        }

        // Parse and validate rules
        $this->parseRules();
        $this->validateFields();

        $result = new ValidationResult(
            empty($this->errors),
            $this->errors,
            $this->data
        );

        // Cache result
        if ($this->enableCaching) {
            $this->cache->set($cacheKey, $result);
        }

        $this->profiler->end('validation');

        return $result;
    }

    /**
     * Parse complex rule strings
     */
    protected function parseRules(): void
    {
        foreach ($this->rules as $field => $fieldRules) {
            if (is_string($fieldRules)) {
                $this->rules[$field] = $this->parseRuleString($fieldRules);
            }
        }
    }

    /**
     * Parse rule string into array
     */
    protected function parseRuleString(string $rules): array
    {
        return array_map('trim', explode('|', $rules));
    }

    /**
     * Validate all fields
     */
    protected function validateFields(): void
    {
        foreach ($this->rules as $field => $fieldRules) {
            $this->validateField($field, $fieldRules);
            
            if ($this->stopOnFirstFailure && !empty($this->errors[$field])) {
                break;
            }
        }
    }

    /**
     * Validate a single field
     */
    protected function validateField(string $field, array $rules): void
    {
        $value = $this->getFieldValue($field);
        $failedRules = [];

        foreach ($rules as $rule) {
            $ruleInstance = $this->createRule($rule);
            
            if (!$ruleInstance->passes($field, $value, $this->data)) {
                $failedRules[] = $ruleInstance->getName();
                $this->addError($field, $ruleInstance->message($field, $value));
                
                if ($this->stopOnFirstFailure) {
                    break;
                }
            }
        }

        if (!empty($failedRules)) {
            $this->context->addFailedField($field, $failedRules);
        }
    }

    /**
     * Create rule instance
     */
    protected function createRule(string $rule): \Ludelix\Interface\Validation\RuleInterface
    {
        return $this->ruleFactory->create($rule);
    }

    /**
     * Get field value using dot notation
     */
    protected function getFieldValue(string $field): mixed
    {
        $keys = explode('.', $field);
        $value = $this->data;

        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * Add validation error
     */
    protected function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
    }

    /**
     * Generate cache key
     */
    protected function generateCacheKey(array $data, array $rules): string
    {
        return md5(serialize($data) . serialize($rules));
    }

    /**
     * Check if validation passes
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if validation fails
     */
    public function fails(): bool
    {
        return !empty($this->errors);
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
    public function validated(): array
    {
        return $this->data;
    }

    /**
     * Set custom messages
     */
    public function setMessages(array $messages): self
    {
        $this->messages = array_merge($this->messages, $messages);
        return $this;
    }

    /**
     * Set custom attributes
     */
    public function setAttributes(array $attributes): self
    {
        $this->customAttributes = $attributes;
        return $this;
    }

    /**
     * Stop validation on first failure
     */
    public function stopOnFirstFailure(): self
    {
        $this->stopOnFirstFailure = true;
        return $this;
    }

    /**
     * Enable/disable caching
     */
    public function enableCaching(bool $enable = true): self
    {
        $this->enableCaching = $enable;
        return $this;
    }

    /**
     * Enable/disable profiling
     */
    public function enableProfiling(bool $enable = true): self
    {
        $this->enableProfiling = $enable;
        return $this;
    }

    /**
     * Get validation context
     */
    public function getContext(): ValidationContext
    {
        return $this->context;
    }

    /**
     * Get profiler
     */
    public function getProfiler(): ValidationProfiler
    {
        return $this->profiler;
    }
} 