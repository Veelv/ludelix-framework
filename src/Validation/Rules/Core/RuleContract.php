<?php

namespace Ludelix\Validation\Rules\Core;

use Ludelix\Bridge\Bridge;

/**
 * RuleContract - Advanced contract for validation rules
 * 
 * Provides advanced functionality for validation rules
 */
abstract class RuleContract implements RuleInterface
{
    protected array $parameters = [];
    protected array $data = [];
    protected string $field = '';
    protected mixed $value = null;

    /**
     * Set rule parameters
     */
    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * Get rule parameters
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

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
     * Set current field
     */
    public function setField(string $field): self
    {
        $this->field = $field;
        return $this;
    }

    /**
     * Get current field
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Set current value
     */
    public function setValue(mixed $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Get current value
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Get translation helper
     */
    protected function trans(): mixed
    {
        return Bridge::trans();
    }

    /**
     * Get field value from data
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
     * Check if field exists in data
     */
    protected function hasField(string $field): bool
    {
        $keys = explode('.', $field);
        $data = $this->data;

        foreach ($keys as $key) {
            if (!isset($data[$key])) {
                return false;
            }
            $data = $data[$key];
        }

        return true;
    }

    /**
     * Get custom attribute name
     */
    protected function getAttributeName(string $field): string
    {
        // This would be overridden by the validator to provide custom attribute names
        return $field;
    }

    /**
     * Format error message with parameters
     */
    protected function formatMessage(string $message, array $parameters = []): string
    {
        foreach ($parameters as $key => $value) {
            $message = str_replace(':' . $key, $value, $message);
        }
        return $message;
    }

    /**
     * Check if value is empty
     */
    protected function isEmpty(mixed $value): bool
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return empty($value);
        }

        return false;
    }

    /**
     * Check if value is not empty
     */
    protected function isNotEmpty(mixed $value): bool
    {
        return !$this->isEmpty($value);
    }

    /**
     * Get parameter by index
     */
    protected function getParameter(int $index, mixed $default = null): mixed
    {
        return $this->parameters[$index] ?? $default;
    }

    /**
     * Get first parameter
     */
    protected function getFirstParameter(mixed $default = null): mixed
    {
        return $this->getParameter(0, $default);
    }

    /**
     * Get second parameter
     */
    protected function getSecondParameter(mixed $default = null): mixed
    {
        return $this->getParameter(1, $default);
    }

    /**
     * Check if parameter exists
     */
    protected function hasParameter(int $index): bool
    {
        return isset($this->parameters[$index]);
    }

    /**
     * Get all parameters
     */
    protected function getAllParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Validate rule parameters
     */
    protected function validateParameters(array $required = []): bool
    {
        foreach ($required as $index => $type) {
            if (!$this->hasParameter($index)) {
                return false;
            }

            $parameter = $this->getParameter($index);
            if (!$this->validateParameterType($parameter, $type)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate parameter type
     */
    protected function validateParameterType(mixed $parameter, string $type): bool
    {
        switch ($type) {
            case 'string':
                return is_string($parameter);
            case 'integer':
                return is_int($parameter) || (is_string($parameter) && is_numeric($parameter));
            case 'float':
                return is_float($parameter) || is_int($parameter) || (is_string($parameter) && is_numeric($parameter));
            case 'array':
                return is_array($parameter);
            case 'boolean':
                return is_bool($parameter);
            default:
                return true;
        }
    }
} 