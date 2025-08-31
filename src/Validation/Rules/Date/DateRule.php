<?php

namespace Ludelix\Validation\Rules\Date;

use Ludelix\Validation\Rules\Core\RuleContract;

/**
 * DateRule - Validates that a field is a valid date
 */
class DateRule extends RuleContract
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

        $format = $this->getFirstParameter() ?? 'Y-m-d';
        $date = \DateTime::createFromFormat($format, $value);
        
        return $date && $date->format($format) === $value;
    }

    public function message(string $field, mixed $value, array $parameters = []): string
    {
        $translation = $this->trans();
        return $translation->t('validation.date', ['field' => $field]);
    }

    public function getName(): string
    {
        return 'date';
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