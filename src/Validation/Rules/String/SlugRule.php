<?php

namespace Ludelix\Validation\Rules\String;

use Ludelix\Validation\Rules\Core\RuleContract;

/**
 * SlugRule - Validates that a field is a valid slug
 */
class SlugRule extends RuleContract
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

        return preg_match('/^[a-z0-9\-_]+$/', $value);
    }

    public function message(string $field, mixed $value, array $parameters = []): string
    {
        $translation = $this->trans();
        return $translation->t('validation.slug', ['field' => $field]);
    }

    public function getName(): string
    {
        return 'slug';
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