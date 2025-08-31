<?php

namespace Ludelix\Validation\Rules\Numeric;

use Ludelix\Validation\Rules\Core\RuleContract;

/**
 * IntegerRule - Validates that a field is an integer
 */
class IntegerRule extends RuleContract
{
    public function passes(string $field, mixed $value, array $data = [], array $parameters = []): bool
    {
        $this->setField($field);
        $this->setValue($value);
        $this->setData($data);
        $this->setParameters($parameters);

        if ($this->isEmpty($value)) {
            return true; // Let required rule handle empty values
        }

        return is_int($value) || (is_string($value) && ctype_digit($value));
    }

    public function message(string $field, mixed $value, array $parameters = []): string
    {
        $translation = $this->trans();
        return $translation->t('validation.integer', ['field' => $field]);
    }

    public function getName(): string
    {
        return 'integer';
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