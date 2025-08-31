<?php

namespace Ludelix\Validation\Rules\Basic;

use Ludelix\Validation\Rules\Core\RuleContract;

/**
 * ArrayRule - Validates that a field is an array
 */
class ArrayRule extends RuleContract
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

        return is_array($value);
    }

    public function message(string $field, mixed $value, array $parameters = []): string
    {
        $translation = $this->trans();
        return $translation->t('validation.array', ['field' => $field]);
    }

    public function getName(): string
    {
        return 'array';
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