<?php

namespace Ludelix\Validation\Rules\Core;

/**
 * RuleBuilder - Builder pattern for validation rules
 * 
 * Provides fluent interface for building complex validation rules
 */
class RuleBuilder
{
    protected array $rules = [];
    protected array $messages = [];
    protected array $attributes = [];
    protected array $conditions = [];

    /**
     * Add a required rule
     */
    public function required(string $field): self
    {
        $this->rules[$field][] = 'required';
        return $this;
    }

    /**
     * Add a string rule
     */
    public function string(string $field): self
    {
        $this->rules[$field][] = 'string';
        return $this;
    }

    /**
     * Add an email rule
     */
    public function email(string $field): self
    {
        $this->rules[$field][] = 'email';
        return $this;
    }

    /**
     * Add a numeric rule
     */
    public function numeric(string $field): self
    {
        $this->rules[$field][] = 'numeric';
        return $this;
    }

    /**
     * Add a min rule
     */
    public function min(string $field, int $min): self
    {
        $this->rules[$field][] = "min:{$min}";
        return $this;
    }

    /**
     * Add a max rule
     */
    public function max(string $field, int $max): self
    {
        $this->rules[$field][] = "max:{$max}";
        return $this;
    }

    /**
     * Add a between rule
     */
    public function between(string $field, int $min, int $max): self
    {
        $this->rules[$field][] = "between:{$min},{$max}";
        return $this;
    }

    /**
     * Add a unique rule
     */
    public function unique(string $field, string $table, string $column = null): self
    {
        $rule = "unique:{$table}";
        if ($column) {
            $rule .= ",{$column}";
        }
        $this->rules[$field][] = $rule;
        return $this;
    }

    /**
     * Add a exists rule
     */
    public function exists(string $field, string $table, string $column = null): self
    {
        $rule = "exists:{$table}";
        if ($column) {
            $rule .= ",{$column}";
        }
        $this->rules[$field][] = $rule;
        return $this;
    }

    /**
     * Add a same rule
     */
    public function same(string $field, string $otherField): self
    {
        $this->rules[$field][] = "same:{$otherField}";
        return $this;
    }

    /**
     * Add a different rule
     */
    public function different(string $field, string $otherField): self
    {
        $this->rules[$field][] = "different:{$otherField}";
        return $this;
    }

    /**
     * Add a regex rule
     */
    public function regex(string $field, string $pattern): self
    {
        $this->rules[$field][] = "regex:{$pattern}";
        return $this;
    }

    /**
     * Add a date rule
     */
    public function date(string $field): self
    {
        $this->rules[$field][] = 'date';
        return $this;
    }

    /**
     * Add a before rule
     */
    public function before(string $field, string $date): self
    {
        $this->rules[$field][] = "before:{$date}";
        return $this;
    }

    /**
     * Add an after rule
     */
    public function after(string $field, string $date): self
    {
        $this->rules[$field][] = "after:{$date}";
        return $this;
    }

    /**
     * Add a file rule
     */
    public function file(string $field): self
    {
        $this->rules[$field][] = 'file';
        return $this;
    }

    /**
     * Add an image rule
     */
    public function image(string $field): self
    {
        $this->rules[$field][] = 'image';
        return $this;
    }

    /**
     * Add a mimes rule
     */
    public function mimes(string $field, array $mimes): self
    {
        $mimeList = implode(',', $mimes);
        $this->rules[$field][] = "mimes:{$mimeList}";
        return $this;
    }

    /**
     * Add a custom rule
     */
    public function custom(string $field, callable $callback): self
    {
        $ruleName = 'custom_' . uniqid();
        $this->rules[$field][] = $ruleName;
        $this->conditions[$ruleName] = $callback;
        return $this;
    }

    /**
     * Add a conditional rule
     */
    public function when(string $field, callable $condition, callable $callback): self
    {
        $ruleName = 'conditional_' . uniqid();
        $this->rules[$field][] = $ruleName;
        $this->conditions[$ruleName] = [
            'condition' => $condition,
            'callback' => $callback
        ];
        return $this;
    }

    /**
     * Add a custom message
     */
    public function message(string $field, string $rule, string $message): self
    {
        $key = "{$field}.{$rule}";
        $this->messages[$key] = $message;
        return $this;
    }

    /**
     * Add a custom attribute name
     */
    public function attribute(string $field, string $name): self
    {
        $this->attributes[$field] = $name;
        return $this;
    }

    /**
     * Add multiple rules at once
     */
    public function rules(string $field, array $rules): self
    {
        foreach ($rules as $rule) {
            $this->rules[$field][] = $rule;
        }
        return $this;
    }

    /**
     * Add rules for multiple fields
     */
    public function fields(array $fields, array $rules): self
    {
        foreach ($fields as $field) {
            foreach ($rules as $rule) {
                $this->rules[$field][] = $rule;
            }
        }
        return $this;
    }

    /**
     * Get built rules
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Get custom messages
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Get custom attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Get conditions
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * Build validation array
     */
    public function build(): array
    {
        return [
            'rules' => $this->rules,
            'messages' => $this->messages,
            'attributes' => $this->attributes,
            'conditions' => $this->conditions,
        ];
    }

    /**
     * Clear all rules
     */
    public function clear(): self
    {
        $this->rules = [];
        $this->messages = [];
        $this->attributes = [];
        $this->conditions = [];
        return $this;
    }

    /**
     * Create a new builder instance
     */
    public static function create(): self
    {
        return new self();
    }
} 