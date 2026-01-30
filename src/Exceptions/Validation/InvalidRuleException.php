<?php

namespace Ludelix\Exceptions\Validation;

/**
 * InvalidRuleException - Thrown when validation rule is invalid
 */
class InvalidRuleException extends ValidationException
{
    protected string $ruleName;
    protected array $parameters;

    public function __construct(string $ruleName, array $parameters = [], string $message = '', int $code = 0, ?\Exception $previous = null)
    {
        $this->ruleName = $ruleName;
        $this->parameters = $parameters;
        $message = $message ?: "Invalid validation rule '{$ruleName}' with parameters: " . json_encode($parameters);
        parent::__construct($message, [], [], $code, $previous);
    }

    /**
     * Get rule name
     */
    public function getRuleName(): string
    {
        return $this->ruleName;
    }

    /**
     * Get rule parameters
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
} 