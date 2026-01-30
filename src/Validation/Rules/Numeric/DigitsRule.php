<?php

namespace Ludelix\Validation\Rules\Numeric;

use Ludelix\Validation\Rules\Core\RuleContract;

/**
 * DigitsRule - Validates that a field has a specific number of digits
 */
class DigitsRule extends RuleContract
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

        $digits = $this->getFirstParameter();
        if ($digits === null) {
            return false;
        }

        if (!is_numeric($value)) {
            return false;
        }

        $value = (string) $value;
        $value = str_replace(['-', '.'], '', $value);
        
        return strlen($value) == $digits;
    }

    public function message(string $field, mixed $value, array $parameters = []): string
    {
        $translation = $this->trans();
        $digits = $this->getFirstParameter();
        return $translation->t('validation.digits', ['field' => $field, 'digits' => $digits]);
    }

    public function getName(): string
    {
        return 'digits';
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