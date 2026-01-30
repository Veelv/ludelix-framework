<?php

namespace Ludelix\Validation\Core;

/**
 * ValidationContext - Context for validation operations
 * 
 * Manages validation context, state, and metadata
 */
class ValidationContext
{
    protected array $data = [];
    protected array $rules = [];
    protected array $messages = [];
    protected array $attributes = [];
    protected array $failedFields = [];
    protected array $failedRules = [];
    protected array $validatedFields = [];
    protected array $skippedFields = [];
    protected array $metadata = [];
    protected bool $isValidating = false;
    protected string $currentField = '';
    protected string $currentRule = '';

    /**
     * Set validation data
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get validation data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set validation rules
     */
    public function setRules(array $rules): self
    {
        $this->rules = $rules;
        return $this;
    }

    /**
     * Get validation rules
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Set custom messages
     */
    public function setMessages(array $messages): self
    {
        $this->messages = $messages;
        return $this;
    }

    /**
     * Get custom messages
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Set custom attributes
     */
    public function setAttributes(array $attributes): self
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * Get custom attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Add failed field
     */
    public function addFailedField(string $field, array $rules = []): self
    {
        $this->failedFields[$field] = $rules;
        return $this;
    }

    /**
     * Get failed fields
     */
    public function getFailedFields(): array
    {
        return $this->failedFields;
    }

    /**
     * Add failed rule
     */
    public function addFailedRule(string $field, string $rule): self
    {
        if (!isset($this->failedRules[$field])) {
            $this->failedRules[$field] = [];
        }
        $this->failedRules[$field][] = $rule;
        return $this;
    }

    /**
     * Get failed rules
     */
    public function getFailedRules(): array
    {
        return $this->failedRules;
    }

    /**
     * Add validated field
     */
    public function addValidatedField(string $field): self
    {
        $this->validatedFields[] = $field;
        return $this;
    }

    /**
     * Get validated fields
     */
    public function getValidatedFields(): array
    {
        return $this->validatedFields;
    }

    /**
     * Add skipped field
     */
    public function addSkippedField(string $field): self
    {
        $this->skippedFields[] = $field;
        return $this;
    }

    /**
     * Get skipped fields
     */
    public function getSkippedFields(): array
    {
        return $this->skippedFields;
    }

    /**
     * Set metadata
     */
    public function setMetadata(string $key, mixed $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Get metadata
     */
    public function getMetadata(string $key = null): mixed
    {
        if ($key === null) {
            return $this->metadata;
        }
        return $this->metadata[$key] ?? null;
    }

    /**
     * Set current field
     */
    public function setCurrentField(string $field): self
    {
        $this->currentField = $field;
        return $this;
    }

    /**
     * Get current field
     */
    public function getCurrentField(): string
    {
        return $this->currentField;
    }

    /**
     * Set current rule
     */
    public function setCurrentRule(string $rule): self
    {
        $this->currentRule = $rule;
        return $this;
    }

    /**
     * Get current rule
     */
    public function getCurrentRule(): string
    {
        return $this->currentRule;
    }

    /**
     * Set validating state
     */
    public function setValidating(bool $isValidating): self
    {
        $this->isValidating = $isValidating;
        return $this;
    }

    /**
     * Check if currently validating
     */
    public function isValidating(): bool
    {
        return $this->isValidating;
    }

    /**
     * Check if field failed
     */
    public function fieldFailed(string $field): bool
    {
        return isset($this->failedFields[$field]);
    }

    /**
     * Check if field was validated
     */
    public function fieldValidated(string $field): bool
    {
        return in_array($field, $this->validatedFields);
    }

    /**
     * Check if field was skipped
     */
    public function fieldSkipped(string $field): bool
    {
        return in_array($field, $this->skippedFields);
    }

    /**
     * Get field value
     */
    public function getFieldValue(string $field): mixed
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
     * Clear context
     */
    public function clear(): self
    {
        $this->data = [];
        $this->rules = [];
        $this->messages = [];
        $this->attributes = [];
        $this->failedFields = [];
        $this->failedRules = [];
        $this->validatedFields = [];
        $this->skippedFields = [];
        $this->metadata = [];
        $this->isValidating = false;
        $this->currentField = '';
        $this->currentRule = '';
        return $this;
    }

    /**
     * Get context summary
     */
    public function getSummary(): array
    {
        return [
            'total_fields' => count($this->rules),
            'validated_fields' => count($this->validatedFields),
            'failed_fields' => count($this->failedFields),
            'skipped_fields' => count($this->skippedFields),
            'failed_rules' => count($this->failedRules),
            'metadata' => $this->metadata,
        ];
    }
} 