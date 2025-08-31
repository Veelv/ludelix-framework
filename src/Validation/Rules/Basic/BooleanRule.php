<?php

namespace Ludelix\Validation\Rules\Basic;

use Ludelix\Validation\Rules\Core\RuleContract;

/**
 * BooleanRule - Validates that a field is boolean
 */
class BooleanRule extends RuleContract
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

        return is_bool($value) || in_array($value, [0, 1, '0', '1', true, false], true);
    }

    public function message(string $field, mixed $value, array $parameters = []): string
    {
        $translation = $this->trans();
        return $translation->t('validation.boolean', ['field' => $field]);
    }

    public function getName(): string
    {
        return 'boolean';
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