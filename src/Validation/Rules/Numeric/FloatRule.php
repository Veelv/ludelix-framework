<?php

namespace Ludelix\Validation\Rules\Numeric;

use Ludelix\Validation\Rules\Core\RuleContract;

/**
 * FloatRule - Validates that a field is a float
 */
class FloatRule extends RuleContract
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

        return is_float($value) || is_int($value) || (is_numeric($value) && str_contains((string) $value, '.'));
    }

    public function message(string $field, mixed $value, array $parameters = []): string
    {
        $translation = $this->trans();
        return $translation->t('validation.float', ['field' => $field]);
    }

    public function getName(): string
    {
        return 'float';
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