<?php

namespace Ludelix\Validation\Rules\File;

use Ludelix\Validation\Rules\Core\RuleContract;

/**
 * FileRule - Validates that a field is a valid file
 */
class FileRule extends RuleContract
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

        // Check if it's a file upload
        if (is_array($value) && isset($value['tmp_name'])) {
            return is_uploaded_file($value['tmp_name']);
        }

        // Check if it's a file path
        if (is_string($value)) {
            return file_exists($value) && is_file($value);
        }

        return false;
    }

    public function message(string $field, mixed $value, array $parameters = []): string
    {
        $translation = $this->trans();
        return $translation->t('validation.file', ['field' => $field]);
    }

    public function getName(): string
    {
        return 'file';
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