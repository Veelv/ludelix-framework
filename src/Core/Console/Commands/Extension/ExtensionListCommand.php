<?php

namespace Ludelix\Core\Console\Commands\Extension;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Extension List Command
 * 
 * Lists all installed and available extensions
 */
class ExtensionListCommand extends BaseCommand
{
    protected string $signature = 'extension:list [--format=] [--filter=]';
    protected string $description = 'List installed extensions';

    public function execute(array $arguments, array $options): int
    {
        $format = $this->option($options, 'format', 'table');
        $filter = $this->option($options, 'filter', '');

        $extensions = $this->engine->getExtensionManager()->getLoadedExtensions();

        if (empty($extensions)) {
            $this->info("No extensions installed.");
            return 0;
        }

        // Apply filter if specified
        if ($filter) {
            $extensions = array_filter($extensions, function ($name) use ($filter) {
                return strpos($name, $filter) !== false;
            }, ARRAY_FILTER_USE_KEY);
        }

        switch ($format) {
            case 'json':
                $this->outputJson($extensions);
                break;
            case 'csv':
                $this->outputCsv($extensions);
                break;
            default:
                $this->outputTable($extensions);
                break;
        }

        return 0;
    }

    protected function outputTable(array $extensions): void
    {
        $this->info("Installed Extensions:");
        $this->line("");

        $headers = ['Name', 'Version', 'Description', 'Type'];
        $rows = [];

        foreach ($extensions as $name => $data) {
            $rows[] = [
                $name,
                $data['version'] ?? 'Unknown',
                $data['description'] ?? 'No description',
                $data['type'] ?? 'Unknown'
            ];
        }

        $this->printTable($headers, $rows);
    }

    protected function outputJson(array $extensions): void
    {
        echo json_encode($extensions, JSON_PRETTY_PRINT) . "\n";
    }

    protected function outputCsv(array $extensions): void
    {
        echo "Name,Version,Description,Type\n";

        foreach ($extensions as $name => $data) {
            echo sprintf(
                '"%s","%s","%s","%s"' . "\n",
                $name,
                $data['version'] ?? 'Unknown',
                $data['description'] ?? 'No description',
                $data['type'] ?? 'Unknown'
            );
        }
    }

    protected function printTable(array $headers, array $rows): void
    {
        // Calculate column widths
        $widths = [];
        foreach ($headers as $i => $header) {
            $widths[$i] = strlen($header);
        }

        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $widths[$i] = max($widths[$i], strlen($cell));
            }
        }

        // Print header
        $this->printRow($headers, $widths);
        $this->printSeparator($widths);

        // Print rows
        foreach ($rows as $row) {
            $this->printRow($row, $widths);
        }
    }

    protected function printRow(array $cells, array $widths): void
    {
        $formatted = [];
        foreach ($cells as $i => $cell) {
            $formatted[] = str_pad($cell, $widths[$i]);
        }
        $this->line("  " . implode("  ", $formatted));
    }

    protected function printSeparator(array $widths): void
    {
        $separator = [];
        foreach ($widths as $width) {
            $separator[] = str_repeat('-', $width);
        }
        $this->line("  " . implode("  ", $separator));
    }
}