<?php

namespace Ludelix\Database\Evolution\Formats;

class JsonSchema
{
    public function export(array $schema): string
    {
        $export = [
            'metadata' => [
                'exported_at' => date('Y-m-d H:i:s'),
                'version' => '1.0',
                'tables_count' => count($schema)
            ],
            'schema' => $schema
        ];
        
        return json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function import(string $jsonContent): array
    {
        $data = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON format: ' . json_last_error_msg());
        }
        
        return $data['schema'] ?? [];
    }

    public function exportCompact(array $schema): string
    {
        return json_encode(['schema' => $schema]);
    }

    public function validate(string $jsonContent): array
    {
        $errors = [];
        $data = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = 'Invalid JSON: ' . json_last_error_msg();
            return $errors;
        }
        
        if (!isset($data['schema'])) {
            $errors[] = 'Missing schema key';
        }
        
        if (isset($data['schema']) && !is_array($data['schema'])) {
            $errors[] = 'Schema must be an array';
        }
        
        return $errors;
    }
}