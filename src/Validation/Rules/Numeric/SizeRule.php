<?php

namespace Ludelix\Validation\Rules\Numeric;

use Ludelix\Validation\Rules\Core\RuleContract;

/**
 * SizeRule - Validates that a field has a specific size
 */
class SizeRule extends RuleContract
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

        $size = $this->getFirstParameter();
        if ($size === null) {
            return false;
        }

        $size = (float) $size;

        if (is_numeric($value)) {
            return (float) $value == $size;
        }

        if (is_string($value)) {
            return mb_strlen($value) == $size;
        }

        if (is_array($value)) {
            return count($value) == $size;
        }

        return false;
    }

    public function message(string $field, mixed $value, array $parameters = []): string
    {
        $translation = $this->trans();
        $size = $this->getFirstParameter();
        
        if (is_numeric($value)) {
            return $translation->t('validation.size.numeric', ['field' => $field, 'size' => $size]);
        }
        
        if (is_string($value)) {
            return $translation->t('validation.size.string', ['field' => $field, 'size' => $size]);
        }
        
        if (is_array($value)) {
            return $translation->t('validation.size.array', ['field' => $field, 'size' => $size]);
        }
        
        return $translation->t('validation.size', ['field' => $field, 'size' => $size]);
    }

    public function getName(): string
    {
        return 'size';
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