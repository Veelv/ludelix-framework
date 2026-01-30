<?php

namespace Ludelix\Exceptions\Validation;

/**
 * RuleNotFoundException - Thrown when validation rule is not found
 */
class RuleNotFoundException extends ValidationException
{
    protected string $ruleName;

    public function __construct(string $ruleName, string $message = '', int $code = 0, ?\Exception $previous = null)
    {
        $this->ruleName = $ruleName;
        $message = $message ?: "Validation rule '{$ruleName}' not found";
        parent::__construct($message, [], [], $code, $previous);
    }

    /**
     * Get rule name
     */
    public function getRuleName(): string
    {
        return $this->ruleName;
    }
} 