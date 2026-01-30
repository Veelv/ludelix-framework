<?php

namespace Ludelix\Validation\Support;

use Ludelix\Validation\Core\Validator;
use Ludelix\Validation\Core\ValidationEngine;
use Ludelix\Validation\Core\ValidationResult;
use Ludelix\Validation\Rules\Core\RuleBuilder;

/**
 * ValidationHelper - Advanced validation helper
 * 
 * Provides helper methods for validation operations
 */
class ValidationHelper
{
    protected Validator $validator;
    protected ValidationEngine $engine;
    protected RuleBuilder $builder;

    public function __construct()
    {
        $this->validator = new Validator();
        $this->engine = new ValidationEngine();
        $this->builder = new RuleBuilder();
    }

    /**
     * Quick validation
     */
    public function validate(array $data, array $rules, array $messages = []): ValidationResult
    {
        return $this->engine->validate($data, $rules, $messages);
    }

    /**
     * Validate single field
     */
    public function validateField(string $field, mixed $value, array $rules, array $data = []): ValidationResult
    {
        $fieldData = [$field => $value];
        $fieldRules = [$field => $rules];
        
        return $this->engine->validate($fieldData, $fieldRules);
    }

    /**
     * Check if value is valid email
     */
    public function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check if value is valid URL
     */
    public function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Check if value is valid IP
     */
    public function isValidIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Check if value is valid date
     */
    public function isValidDate(string $date, string $format = 'Y-m-d'): bool
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Check if value is numeric
     */
    public function isNumeric(mixed $value): bool
    {
        return is_numeric($value);
    }

    /**
     * Check if value is integer
     */
    public function isInteger(mixed $value): bool
    {
        return is_int($value) || (is_string($value) && ctype_digit($value));
    }

    /**
     * Check if value is float
     */
    public function isFloat(mixed $value): bool
    {
        return is_float($value) || (is_numeric($value) && str_contains((string) $value, '.'));
    }

    /**
     * Check if value is string
     */
    public function isString(mixed $value): bool
    {
        return is_string($value);
    }

    /**
     * Check if value is array
     */
    public function isArray(mixed $value): bool
    {
        return is_array($value);
    }

    /**
     * Check if value is boolean
     */
    public function isBoolean(mixed $value): bool
    {
        return is_bool($value) || in_array($value, [0, 1, '0', '1', true, false], true);
    }

    /**
     * Check if value is empty
     */
    public function isEmpty(mixed $value): bool
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
    public function isNotEmpty(mixed $value): bool
    {
        return !$this->isEmpty($value);
    }

    /**
     * Get string length
     */
    public function getLength(string $value): int
    {
        return mb_strlen($value);
    }

    /**
     * Get array count
     */
    public function getCount(array $value): int
    {
        return count($value);
    }

    /**
     * Check if value is between
     */
    public function isBetween(mixed $value, mixed $min, mixed $max): bool
    {
        if (is_numeric($value)) {
            return $value >= $min && $value <= $max;
        }

        if (is_string($value)) {
            $length = $this->getLength($value);
            return $length >= $min && $length <= $max;
        }

        if (is_array($value)) {
            $count = $this->getCount($value);
            return $count >= $min && $count <= $max;
        }

        return false;
    }

    /**
     * Check if value is in array
     */
    public function isIn(mixed $value, array $allowed): bool
    {
        return in_array($value, $allowed, true);
    }

    /**
     * Check if value is not in array
     */
    public function isNotIn(mixed $value, array $forbidden): bool
    {
        return !in_array($value, $forbidden, true);
    }

    /**
     * Check if value matches regex
     */
    public function matches(string $value, string $pattern): bool
    {
        return preg_match($pattern, $value) === 1;
    }

    /**
     * Check if value doesn't match regex
     */
    public function notMatches(string $value, string $pattern): bool
    {
        return preg_match($pattern, $value) === 0;
    }

    /**
     * Check if value starts with
     */
    public function startsWith(string $value, string $prefix): bool
    {
        return str_starts_with($value, $prefix);
    }

    /**
     * Check if value ends with
     */
    public function endsWith(string $value, string $suffix): bool
    {
        return str_ends_with($value, $suffix);
    }

    /**
     * Check if value contains
     */
    public function contains(string $value, string $needle): bool
    {
        return str_contains($value, $needle);
    }

    /**
     * Check if value doesn't contain
     */
    public function notContains(string $value, string $needle): bool
    {
        return !str_contains($value, $needle);
    }

    /**
     * Check if value is alpha
     */
    public function isAlpha(string $value): bool
    {
        return preg_match('/^[\p{L}\s]+$/u', $value);
    }

    /**
     * Check if value is alphanumeric
     */
    public function isAlphaNum(string $value): bool
    {
        return preg_match('/^[\p{L}\p{N}\s]+$/u', $value);
    }

    /**
     * Check if value is alpha dash
     */
    public function isAlphaDash(string $value): bool
    {
        return preg_match('/^[\p{L}\p{N}\s\-_]+$/u', $value);
    }

    /**
     * Check if value is lowercase
     */
    public function isLowercase(string $value): bool
    {
        return $value === strtolower($value);
    }

    /**
     * Check if value is uppercase
     */
    public function isUppercase(string $value): bool
    {
        return $value === strtoupper($value);
    }

    /**
     * Check if value is slug
     */
    public function isSlug(string $value): bool
    {
        return preg_match('/^[a-z0-9\-_]+$/', $value);
    }

    /**
     * Check if value is UUID
     */
    public function isUuid(string $value): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value);
    }

    /**
     * Check if value is ULID
     */
    public function isUlid(string $value): bool
    {
        return preg_match('/^[0-9A-Z]{26}$/', $value);
    }

    /**
     * Get rule builder
     */
    public function rules(): RuleBuilder
    {
        return $this->builder;
    }

    /**
     * Create validation result
     */
    public function createResult(bool $passed, array $errors = [], array $data = []): ValidationResult
    {
        return new ValidationResult($passed, $errors, $data);
    }

    /**
     * Format validation message
     */
    public function formatMessage(string $message, array $parameters = []): string
    {
        foreach ($parameters as $key => $value) {
            $message = str_replace(':' . $key, $value, $message);
        }
        return $message;
    }

    /**
     * Get validation statistics
     */
    public function getStats(): array
    {
        return [
            'validator_class' => get_class($this->validator),
            'engine_class' => get_class($this->engine),
            'builder_class' => get_class($this->builder),
        ];
    }
} 