<?php

namespace Ludelix\Validation\Rules\File;

use Ludelix\Validation\Rules\Core\RuleContract;

/**
 * ImageRule - Validates that a field is a valid image file
 */
class ImageRule extends RuleContract
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
            if (!is_uploaded_file($value['tmp_name'])) {
                return false;
            }
            
            $imageInfo = getimagesize($value['tmp_name']);
            return $imageInfo !== false;
        }

        // Check if it's a file path
        if (is_string($value)) {
            if (!file_exists($value) || !is_file($value)) {
                return false;
            }
            
            $imageInfo = getimagesize($value);
            return $imageInfo !== false;
        }

        return false;
    }

    public function message(string $field, mixed $value, array $parameters = []): string
    {
        $translation = $this->trans();
        return $translation->t('validation.image', ['field' => $field]);
    }

    public function getName(): string
    {
        return 'image';
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