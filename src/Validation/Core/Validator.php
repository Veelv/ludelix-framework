<?php

namespace Ludelix\Validation\Core;

use Ludelix\Bridge\Bridge;
use Ludelix\Interface\Validation\ValidatorInterface;
use Ludelix\Interface\Validation\RuleInterface;
use Ludelix\Validation\Rules\RuleFactory;
use Ludelix\Exceptions\Validation\ValidationException;
use Ludelix\Exceptions\Validation\RuleNotFoundException;

/**
 * Validator - Core validation system for Ludelix Framework
 * 
 * Professional validation engine with advanced features
 */
class Validator implements ValidatorInterface
{
    protected array $data = [];
    protected array $rules = [];
    protected array $messages = [];
    protected array $customAttributes = [];
    protected array $errors = [];
    protected bool $stopOnFirstFailure = false;
    protected ValidationBag $errorBag;
    protected RuleFactory $ruleFactory;

    public function __construct()
    {
        $this->errorBag = new ValidationBag();
        $this->ruleFactory = new RuleFactory();
        $this->messages = $this->getDefaultMessages();
    }

    /**
     * Validate data against rules
     */
    public function validate(array $data, array $rules, array $messages = []): ValidationResult
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = array_merge($this->messages, $messages);
        $this->errors = [];

        foreach ($this->rules as $field => $fieldRules) {
            $this->validateField($field, $fieldRules);
            
            if ($this->stopOnFirstFailure && !empty($this->errors[$field])) {
                break;
            }
        }

        return new ValidationResult(
            empty($this->errors),
            $this->errors,
            $this->data
        );
    }

    /**
     * Validate a single field
     */
    protected function validateField(string $field, array $rules): void
    {
        $value = $this->getFieldValue($field);

        foreach ($rules as $rule) {
            $ruleInstance = $this->createRule($rule);
            
            if (!$ruleInstance->passes($field, $value, $this->data)) {
                $this->addError($field, $ruleInstance->message($field, $value));
                
                if ($this->stopOnFirstFailure) {
                    break;
                }
            }
        }
    }

    /**
     * Create rule instance
     */
    protected function createRule(string $rule): RuleInterface
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
     * Get default validation messages
     */
    protected function getDefaultMessages(): array
    {
        $translation = Bridge::trans();
        
        return [
            'required' => $translation->t('validation.required'),
            'email' => $translation->t('validation.email'),
            'min' => $translation->t('validation.min'),
            'max' => $translation->t('validation.max'),
            'string' => $translation->t('validation.string'),
            'numeric' => $translation->t('validation.numeric'),
            'unique' => $translation->t('validation.unique'),
            'exists' => $translation->t('validation.exists'),
            'url' => $translation->t('validation.url'),
            'ip' => $translation->t('validation.ip'),
            'date' => $translation->t('validation.date'),
            'file' => $translation->t('validation.file'),
            'image' => $translation->t('validation.image'),
            'same' => $translation->t('validation.same'),
            'different' => $translation->t('validation.different'),
            'alpha' => $translation->t('validation.alpha'),
            'alpha_num' => $translation->t('validation.alpha_num'),
            'alpha_dash' => $translation->t('validation.alpha_dash'),
            'regex' => $translation->t('validation.regex'),
            'not_regex' => $translation->t('validation.not_regex'),
        ];
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
} 