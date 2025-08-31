<?php

namespace Ludelix\Validation\Rules\Basic;

use Ludelix\Interface\Validation\RuleInterface;
use Ludelix\Bridge\Bridge;

/**
 * RequiredRule - Validates that a field is required
 */
class RequiredRule implements RuleInterface
{
    public function passes(string $field, mixed $value, array $data = [], array $parameters = []): bool
    {
        if (is_null($value)) {
            return false;
        }
        
        if (is_string($value)) {
            return trim($value) !== '';
        }
        
        if (is_array($value)) {
            return !empty($value);
        }
        
        return true;
    }

    public function message(string $field, mixed $value, array $parameters = []): string
    {
        $translation = Bridge::trans();
        return $translation->t('validation.required', ['field' => $field]);
    }

    public function getName(): string
    {
        return 'required';
    }

    public function isImplicit(): bool
    {
        return false;
    }

    public function getDependencies(): array
    {
        return [];
    }
} 