<?php

namespace Ludelix\Validation\Rules\Basic;

use Ludelix\Validation\Rules\Core\RuleContract;

/**
 * StringRule - Validates that a field is a string
 */
class StringRule extends RuleContract
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

        return is_string($value);
    }

    public function message(string $field, mixed $value, array $parameters = []): string
    {
        $translation = $this->trans();
        return $translation->t('validation.string', ['field' => $field]);
    }

    public function getName(): string
    {
        return 'string';
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