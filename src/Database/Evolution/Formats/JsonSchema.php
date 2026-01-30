<?php

namespace Ludelix\Database\Evolution\Formats;

use Exception;

/**
 * Handles JSON format schema export and import.
 *
 * Provides capabilities to convert internal schema array representations into JSON
 * files and vice versa. It facilitates schema storage in a human-readable but structured format.
 */
class JsonSchema
{
    /**
     * Exports the schema definition to a formatted JSON string.
     *
     * Adds metadata (timestamp, version, table count) to the export structure.
     *
     * @param array $schema The schema definition array.
     * @return string The JSON-encoded string.
     */
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

    /**
     * Imports schema definition from a JSON string.
     *
     * Decodes the JSON content and extracts the 'schema' key.
     *
     * @param string $jsonContent The JSON string to import.
     * @return array The schema definition array.
     * @throws Exception If the JSON is invalid.
     */
    public function import(string $jsonContent): array
    {
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON format: ' . json_last_error_msg());
        }

        return $data['schema'] ?? [];
    }

    /**
     * Exports the schema in a compact JSON format (minimal whitespace).
     *
     * Useful for storing schema snapshots where readability is less critical than size.
     *
     * @param array $schema The schema definition array.
     * @return string The compact JSON-encoded string.
     */
    public function exportCompact(array $schema): string
    {
        return json_encode(['schema' => $schema]);
    }

    /**
     * Validates the structure and syntax of a JSON schema string.
     *
     * Checks for JSON syntax errors and mandatory root keys.
     *
     * @param string $jsonContent The JSON string to validate.
     * @return array List of error messages (empty if valid).
     */
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