<?php

namespace Ludelix\Validation\Rules\Numeric;

use Ludelix\Validation\Rules\Core\RuleContract;

/**
 * MaxRule - Validates that a field has a maximum value/length
 */
class MaxRule extends RuleContract
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

        $max = $this->getFirstParameter();
        if ($max === null) {
            return false;
        }

        $max = (float) $max;

        if (is_numeric($value)) {
            return (float) $value <= $max;
        }

        if (is_string($value)) {
            return mb_strlen($value) <= $max;
        }

        if (is_array($value)) {
            return count($value) <= $max;
        }

        return false;
    }

    public function message(string $field, mixed $value, array $parameters = []): string
    {
        $translation = $this->trans();
        $max = $this->getFirstParameter();
        
        if (is_numeric($value)) {
            return $translation->t('validation.max.numeric', ['field' => $field, 'max' => $max]);
        }
        
        if (is_string($value)) {
            return $translation->t('validation.max.string', ['field' => $field, 'max' => $max]);
        }
        
        if (is_array($value)) {
            return $translation->t('validation.max.array', ['field' => $field, 'max' => $max]);
        }
        
        return $translation->t('validation.max', ['field' => $field, 'max' => $max]);
    }

    public function getName(): string
    {
        return 'max';
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