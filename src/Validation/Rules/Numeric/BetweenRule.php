<?php

namespace Ludelix\Validation\Rules\Numeric;

use Ludelix\Validation\Rules\Core\RuleContract;

/**
 * BetweenRule - Validates that a field is between two values
 */
class BetweenRule extends RuleContract
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

        $min = $this->getFirstParameter();
        $max = $this->getSecondParameter();
        
        if ($min === null || $max === null) {
            return false;
        }

        $min = (float) $min;
        $max = (float) $max;

        if (is_numeric($value)) {
            return (float) $value >= $min && (float) $value <= $max;
        }

        if (is_string($value)) {
            $length = mb_strlen($value);
            return $length >= $min && $length <= $max;
        }

        if (is_array($value)) {
            $count = count($value);
            return $count >= $min && $count <= $max;
        }

        return false;
    }

    public function message(string $field, mixed $value, array $parameters = []): string
    {
        $translation = $this->trans();
        $min = $this->getFirstParameter();
        $max = $this->getSecondParameter();
        
        if (is_numeric($value)) {
            return $translation->t('validation.between.numeric', ['field' => $field, 'min' => $min, 'max' => $max]);
        }
        
        if (is_string($value)) {
            return $translation->t('validation.between.string', ['field' => $field, 'min' => $min, 'max' => $max]);
        }
        
        if (is_array($value)) {
            return $translation->t('validation.between.array', ['field' => $field, 'min' => $min, 'max' => $max]);
        }
        
        return $translation->t('validation.between', ['field' => $field, 'min' => $min, 'max' => $max]);
    }

    public function getName(): string
    {
        return 'between';
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