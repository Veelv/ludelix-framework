<?php

namespace Ludelix\Validation\Rules\Numeric;

use Ludelix\Validation\Rules\Core\RuleContract;

/**
 * MinRule - Validates that a field has a minimum value/length
 */
class MinRule extends RuleContract
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
        if ($min === null) {
            return false;
        }

        $min = (float) $min;

        if (is_numeric($value)) {
            return (float) $value >= $min;
        }

        if (is_string($value)) {
            return mb_strlen($value) >= $min;
        }

        if (is_array($value)) {
            return count($value) >= $min;
        }

        return false;
    }

    public function message(string $field, mixed $value, array $parameters = []): string
    {
        $translation = $this->trans();
        $min = $this->getFirstParameter();
        
        if (is_numeric($value)) {
            return $translation->t('validation.min.numeric', ['field' => $field, 'min' => $min]);
        }
        
        if (is_string($value)) {
            return $translation->t('validation.min.string', ['field' => $field, 'min' => $min]);
        }
        
        if (is_array($value)) {
            return $translation->t('validation.min.array', ['field' => $field, 'min' => $min]);
        }
        
        return $translation->t('validation.min', ['field' => $field, 'min' => $min]);
    }

    public function getName(): string
    {
        return 'min';
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