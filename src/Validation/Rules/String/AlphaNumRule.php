<?php

namespace Ludelix\Validation\Rules\String;

use Ludelix\Validation\Rules\Core\RuleContract;

/**
 * AlphaNumRule - Validates that a field contains only alphanumeric characters
 */
class AlphaNumRule extends RuleContract
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

        if (!is_string($value)) {
            return false;
        }

        return preg_match('/^[\p{L}\p{N}\s]+$/u', $value);
    }

    public function message(string $field, mixed $value, array $parameters = []): string
    {
        $translation = $this->trans();
        return $translation->t('validation.alpha_num', ['field' => $field]);
    }

    public function getName(): string
    {
        return 'alpha_num';
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