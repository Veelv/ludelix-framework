<?php

namespace Ludelix\Validation\Rules\Comparison;

use Ludelix\Validation\Rules\Core\RuleContract;

/**
 * GteRule - Validates that a field is greater than or equal to another field
 */
class GteRule extends RuleContract
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
        
        if (!is_numeric($value) || !is_numeric($otherValue)) {
            return false;
        }

        return (float) $value >= (float) $otherValue;
    }

    public function message(string $field, mixed $value, array $parameters = []): string
    {
        $translation = $this->trans();
        $otherField = $this->getFirstParameter();
        return $translation->t('validation.gte', ['field' => $field, 'other' => $otherField]);
    }

    public function getName(): string
    {
        return 'gte';
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