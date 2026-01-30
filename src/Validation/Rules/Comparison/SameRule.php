<?php

namespace Ludelix\Validation\Rules\Comparison;

use Ludelix\Validation\Rules\Core\RuleContract;

/**
 * SameRule - Validates that a field matches another field
 */
class SameRule extends RuleContract
{
    public function passes(string $field, mixed $value, array $data = [], array $parameters = []): bool
    {
        $this->setField($field);
        $this->setValue($value);
        $this->setData($data);
        $this->setParameters($parameters);

        $otherField = $this->getFirstParameter();
        if ($otherField === null) {
            return false;
        }

        $otherValue = $this->getFieldValue($otherField);
        
        return $value === $otherValue;
    }

    public function message(string $field, mixed $value, array $parameters = []): string
    {
        $translation = $this->trans();
        $otherField = $this->getFirstParameter();
        return $translation->t('validation.same', ['field' => $field, 'other' => $otherField]);
    }

    public function getName(): string
    {
        return 'same';
    }

    public function isImplicit(): bool
    {
        return false;
    }

    public function getDependencies(): array
    {
        $otherField = $this->getFirstParameter();
        return $otherField ? [$otherField] : [];
    }
} 