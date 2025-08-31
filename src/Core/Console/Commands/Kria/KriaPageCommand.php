<?php

namespace Ludelix\Core\Console\Commands\Kria;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Kria Page Command - Create Ludou Page
 * 
 * Creates a new Ludou page with complete structure in frontend/templates/screens
 * 
 * @package Ludelix\Core\Console\Commands\Kria
 * @author Ludelix Framework Team
 * @version 2.0.0
 * @since 1.0.0
 */
class KriaPageCommand extends BaseCommand
{
    /**
     * Command signature
     */
    protected string $signature = 'kria:page <name>';

    /**
     * Command description
     */
    protected string $description = 'Create a new Ludou page';

    /**
     * Execute page creation command
     * 
     * @param array $arguments Command arguments
     * @param array $options Command options
     * @return int Exit code
     */
    public function execute(array $arguments, array $options): int
    {
        $name = $this->argument($arguments, 0);
        
        if (!$name) {
            $this->error('Page name is required');
            return 1;
        }

        $pageName = $this->formatPageName($name);
        $pageFile = "frontend/templates/screens/{$pageName}.ludou";

        // Check if page already exists
        if (file_exists($pageFile)) {
            $this->error("Page '{$pageName}' already exists");
            return 1;
        }

        // Create directories if needed
        $this->ensureDirectoryExists('frontend/templates/screens');

        // Create page file
        $this->createPageFile($pageFile, $pageName);

        $this->success("Page '{$pageName}' created successfully!");
        $this->line("Location: {$pageFile}");

        return 0;
    }

    /**
     * Format page name
     * 
     * @param string $name Raw name
     * @return string Formatted name
     */
    protected function formatPageName(string $name): string
    {
        return strtolower(str_replace([' ', '_'], '-', $name));
    }

    /**
     * Create page file with Ludou structure
     * 
     * @param string $file File path
     * @param string $name Page name
     */
    protected function createPageFile(string $file, string $name): void
    {
        $title = ucwords(str_replace('-', ' ', $name));
        $templatePath = __DIR__ . '/../Templates/Generators/page.lux';
        if (!file_exists($templatePath)) {
            $this->error('Page template not found: ' . $templatePath);
            return;
        }
        $template = file_get_contents($templatePath);
        $replacements = [
            '{{title}}' => $title,
        ];
        $content = str_replace(array_keys($replacements), array_values($replacements), $template);
        file_put_contents($file, $content);
    }

    /**
     * Ensure directory exists
     * 
     * @param string $path Directory path
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}