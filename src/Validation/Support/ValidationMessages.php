<?php

namespace Ludelix\Validation\Support;

use Ludelix\Bridge\Bridge;

/**
 * ValidationMessages - Structured validation messages
 * 
 * Manages validation messages with advanced features
 */
class ValidationMessages
{
    protected array $messages = [];
    protected array $attributes = [];
    protected array $customMessages = [];
    protected string $locale = 'en';

    public function __construct()
    {
        $this->loadDefaultMessages();
    }

    /**
     * Load default validation messages
     */
    protected function loadDefaultMessages(): void
    {
        $translation = Bridge::trans();
        
        $this->messages = [
            // Basic rules
            'required' => $translation->t('validation.required'),
            'string' => $translation->t('validation.string'),
            'numeric' => $translation->t('validation.numeric'),
            'boolean' => $translation->t('validation.boolean'),
            'array' => $translation->t('validation.array'),
            
            // String rules
            'email' => $translation->t('validation.email'),
            'url' => $translation->t('validation.url'),
            'ip' => $translation->t('validation.ip'),
            'regex' => $translation->t('validation.regex'),
            'not_regex' => $translation->t('validation.not_regex'),
            'alpha' => $translation->t('validation.alpha'),
            'alpha_num' => $translation->t('validation.alpha_num'),
            'alpha_dash' => $translation->t('validation.alpha_dash'),
            'lowercase' => $translation->t('validation.lowercase'),
            'uppercase' => $translation->t('validation.uppercase'),
            'slug' => $translation->t('validation.slug'),
            'uuid' => $translation->t('validation.uuid'),
            'ulid' => $translation->t('validation.ulid'),
            'starts_with' => $translation->t('validation.starts_with'),
            'ends_with' => $translation->t('validation.ends_with'),
            'contains' => $translation->t('validation.contains'),
            'not_contains' => $translation->t('validation.not_contains'),
            
            // Numeric rules
            'integer' => $translation->t('validation.integer'),
            'float' => $translation->t('validation.float'),
            'decimal' => $translation->t('validation.decimal'),
            'between' => $translation->t('validation.between'),
            'min' => $translation->t('validation.min'),
            'max' => $translation->t('validation.max'),
            'size' => $translation->t('validation.size'),
            'digits' => $translation->t('validation.digits'),
            'digits_between' => $translation->t('validation.digits_between'),
            'multiple_of' => $translation->t('validation.multiple_of'),
            'divisible_by' => $translation->t('validation.divisible_by'),
            
            // Comparison rules
            'same' => $translation->t('validation.same'),
            'different' => $translation->t('validation.different'),
            'gt' => $translation->t('validation.gt'),
            'gte' => $translation->t('validation.gte'),
            'lt' => $translation->t('validation.lt'),
            'lte' => $translation->t('validation.lte'),
            
            // Date rules
            'date' => $translation->t('validation.date'),
            'date_equals' => $translation->t('validation.date_equals'),
            'date_format' => $translation->t('validation.date_format'),
            'before' => $translation->t('validation.before'),
            'before_or_equal' => $translation->t('validation.before_or_equal'),
            'after' => $translation->t('validation.after'),
            'after_or_equal' => $translation->t('validation.after_or_equal'),
            'timezone' => $translation->t('validation.timezone'),
            
            // File rules
            'file' => $translation->t('validation.file'),
            'image' => $translation->t('validation.image'),
            'video' => $translation->t('validation.video'),
            'audio' => $translation->t('validation.audio'),
            'mimes' => $translation->t('validation.mimes'),
            'mimetypes' => $translation->t('validation.mimetypes'),
            'dimensions' => $translation->t('validation.dimensions'),
            'max_width' => $translation->t('validation.max_width'),
            'max_height' => $translation->t('validation.max_height'),
            'min_width' => $translation->t('validation.min_width'),
            'min_height' => $translation->t('validation.min_height'),
            
            // Database rules
            'unique' => $translation->t('validation.unique'),
            'exists' => $translation->t('validation.exists'),
            'distinct' => $translation->t('validation.distinct'),
            
            // Network rules
            'active_url' => $translation->t('validation.active_url'),
            'dns' => $translation->t('validation.dns'),
            'ipv4' => $translation->t('validation.ipv4'),
            'ipv6' => $translation->t('validation.ipv6'),
        ];
    }

    /**
     * Get message for rule
     */
    public function get(string $rule, string $field = '', array $parameters = []): string
    {
        $message = $this->customMessages[$rule] ?? $this->messages[$rule] ?? $rule;
        
        // Replace field placeholder
        $message = str_replace(':field', $field, $message);
        
        // Replace parameter placeholders
        foreach ($parameters as $key => $value) {
            $message = str_replace(':' . $key, $value, $message);
        }
        
        return $message;
    }

    /**
     * Set custom message for rule
     */
    public function set(string $rule, string $message): self
    {
        $this->customMessages[$rule] = $message;
        return $this;
    }

    /**
     * Set multiple custom messages
     */
    public function setMultiple(array $messages): self
    {
        $this->customMessages = array_merge($this->customMessages, $messages);
        return $this;
    }

    /**
     * Get custom attribute name
     */
    public function getAttribute(string $field): string
    {
        return $this->attributes[$field] ?? $field;
    }

    /**
     * Set custom attribute name
     */
    public function setAttribute(string $field, string $name): self
    {
        $this->attributes[$field] = $name;
        return $this;
    }

    /**
     * Set multiple custom attributes
     */
    public function setAttributes(array $attributes): self
    {
        $this->attributes = array_merge($this->attributes, $attributes);
        return $this;
    }

    /**
     * Set locale
     */
    public function setLocale(string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Get locale
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Get all messages
     */
    public function all(): array
    {
        return array_merge($this->messages, $this->customMessages);
    }

    /**
     * Get custom messages
     */
    public function getCustomMessages(): array
    {
        return $this->customMessages;
    }

    /**
     * Get attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Clear custom messages
     */
    public function clearCustomMessages(): self
    {
        $this->customMessages = [];
        return $this;
    }

    /**
     * Clear attributes
     */
    public function clearAttributes(): self
    {
        $this->attributes = [];
        return $this;
    }

    /**
     * Clear all
     */
    public function clear(): self
    {
        $this->customMessages = [];
        $this->attributes = [];
        return $this;
    }

    /**
     * Check if message exists
     */
    public function has(string $rule): bool
    {
        return isset($this->messages[$rule]) || isset($this->customMessages[$rule]);
    }

    /**
     * Check if attribute exists
     */
    public function hasAttribute(string $field): bool
    {
        return isset($this->attributes[$field]);
    }

    /**
     * Get message count
     */
    public function count(): int
    {
        return count($this->messages) + count($this->customMessages);
    }

    /**
     * Get attribute count
     */
    public function attributeCount(): int
    {
        return count($this->attributes);
    }

    /**
     * Export messages to array
     */
    public function toArray(): array
    {
        return [
            'messages' => $this->all(),
            'attributes' => $this->attributes,
            'custom_messages' => $this->customMessages,
            'locale' => $this->locale,
        ];
    }

    /**
     * Import messages from array
     */
    public function fromArray(array $data): self
    {
        if (isset($data['custom_messages'])) {
            $this->setMultiple($data['custom_messages']);
        }
        
        if (isset($data['attributes'])) {
            $this->setAttributes($data['attributes']);
        }
        
        if (isset($data['locale'])) {
            $this->setLocale($data['locale']);
        }
        
        return $this;
    }
} 