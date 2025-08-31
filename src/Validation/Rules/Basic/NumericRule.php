<?php

namespace Ludelix\Validation\Rules\Basic;

use Ludelix\Validation\Rules\Core\RuleContract;

/**
 * NumericRule - Validates that a field is numeric
 */
class NumericRule extends RuleContract
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

        return is_numeric($value);
    }

    public function message(string $field, mixed $value, array $parameters = []): string
    {
        $translation = $this->trans();
        return $translation->t('validation.numeric', ['field' => $field]);
    }

    public function getName(): string
    {
        return 'numeric';
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