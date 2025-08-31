<?php

namespace Ludelix\Database\Evolution\Formats;

class YamlSchema
{
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

    public function import(string $yamlContent): array
    {
        $data = yaml_parse($yamlContent);
        return $data['schema'] ?? [];
    }

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