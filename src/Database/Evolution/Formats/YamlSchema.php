<?php

namespace Ludelix\Database\Evolution\Formats;

/**
 * Handles YAML format schema export and import.
 *
 * Converts internal schema representations into YAML format, which is often preferred
 * for configuration and human readability compared to JSON or raw SQL.
 */
class YamlSchema
{
    /**
     * Exports the schema definition to a YAML string.
     *
     * Adds standard headers and timestamps to the output.
     *
     * @param array $schema The entire schema definition array.
     * @return string The YAML-formatted string.
     */
    public function export(array $schema): string
    {
        $yaml = "# Database Schema Export\n";
        $yaml .= "# Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        $yaml .= "schema:\n";

        foreach ($schema as $table => $definition) {
            $yaml .= $this->exportTable($table, $definition);
        }

        return $yaml;
    }

    /**
     * Imports schema definition from YAML content.
     *
     * Parses the YAML string and extracts the schema definition.
     *
     * @param string $yamlContent The raw YAML string.
     * @return array The schema definition array.
     */
    public function import(string $yamlContent): array
    {
        $data = yaml_parse($yamlContent);
        return $data['schema'] ?? [];
    }

    /**
     * Formats a single table definition into YAML.
     *
     * @param string $table      The table name.
     * @param array  $definition The table structure (columns, indexes).
     * @return string The YAML fragment for the table.
     */
    protected function exportTable(string $table, array $definition): string
    {
        $yaml = "  {$table}:\n";
        $yaml .= "    columns:\n";

        foreach ($definition['columns'] as $column) {
            $yaml .= $this->exportColumn($column);
        }

        if (!empty($definition['indexes'])) {
            $yaml .= "    indexes:\n";
            foreach ($definition['indexes'] as $index) {
                $yaml .= $this->exportIndex($index);
            }
        }

        $yaml .= "\n";
        return $yaml;
    }

    /**
     * Formats a single column definition into YAML.
     *
     * @param array $column The database column array.
     * @return string The YAML fragment for the column.
     */
    protected function exportColumn(array $column): string
    {
        $yaml = "      {$column['Field']}:\n";
        $yaml .= "        type: \"{$column['Type']}\"\n";
        $yaml .= "        nullable: " . ($column['Null'] === 'YES' ? 'true' : 'false') . "\n";

        if ($column['Default'] !== null) {
            $yaml .= "        default: \"{$column['Default']}\"\n";
        }

        if ($column['Key'] === 'PRI') {
            $yaml .= "        primary: true\n";
        }

        if ($column['Extra']) {
            $yaml .= "        extra: \"{$column['Extra']}\"\n";
        }

        return $yaml;
    }

    /**
     * Formats a single index definition into YAML.
     *
     * @param array $index The database index array.
     * @return string The YAML fragment for the index.
     */
    protected function exportIndex(array $index): string
    {
        $yaml = "      {$index['Key_name']}:\n";
        $yaml .= "        column: \"{$index['Column_name']}\"\n";
        $yaml .= "        unique: " . ($index['Non_unique'] == 0 ? 'true' : 'false') . "\n";

        if ($index['Index_type']) {
            $yaml .= "        type: \"{$index['Index_type']}\"\n";
        }

        return $yaml;
    }
}