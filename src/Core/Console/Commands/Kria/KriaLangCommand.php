<?php

namespace Ludelix\Core\Console\Commands\Kria;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Kria Lang Command - Create Language Files
 *
 * Creates language files in frontend/lang directory
 *
 * @package Ludelix\Core\Console\Commands\Kria
 */
class KriaLangCommand extends BaseCommand
{
    /**
     * Command signature
     */
    protected string $signature = 'kria:lang [locale] [name] {--format=php}';

    /**
     * Command description
     */
    protected string $description = 'Create a new language file';

    /**
     * Execute language file creation command
     *
     * @param array $arguments Command arguments
     * @param array $options Command options
     * @return int Exit code
     */
    public function execute(array $arguments, array $options): int
    {
        $locale = $this->argument($arguments, 0, 'en');
        $name = $this->argument($arguments, 1, 'messages');
        $format = $this->option($options, 'format', 'php');

        $this->line("Creating language file: {$name}.{$format} for locale: {$locale}");

        // Create lang directory structure
        $this->createLangStructure($locale);

        // Create the language file
        $this->createLangFile($locale, $name, $format);

        $this->success("Language file created successfully!");
        $this->line("File: frontend/lang/{$locale}/{$name}.{$format}");

        return 0;
    }

    protected function createLangStructure(string $locale): void
    {
        $langPath = 'frontend/lang/' . $locale;
        
        if (!is_dir($langPath)) {
            mkdir($langPath, 0755, true);
            $this->line("Created directory: {$langPath}");
        }
    }

    protected function createLangFile(string $locale, string $name, string $format): void
    {
        $filePath = "frontend/lang/{$locale}/{$name}.{$format}";
        
        if (file_exists($filePath)) {
            $this->warning("File already exists: {$filePath}");
            return;
        }

        $content = $this->generateFileContent($name, $format, $locale);
        file_put_contents($filePath, $content);
        
        $this->line("Created file: {$filePath}");
    }

    protected function generateFileContent(string $name, string $format, string $locale): string
    {
        return match($format) {
            'php' => $this->generatePhpContent($name, $locale),
            'json' => $this->generateJsonContent($name, $locale),
            'yaml', 'yml' => $this->generateYamlContent($name, $locale),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}")
        };
    }

    protected function generatePhpContent(string $name, string $locale): string
    {
        $exampleKey = $this->getExampleKey($name);
        $exampleValue = $this->getExampleValue($exampleKey, $locale);
        
        return "<?php\n\nreturn [\n    '{$exampleKey}' => '{$exampleValue}',\n];\n";
    }

    protected function generateJsonContent(string $name, string $locale): string
    {
        $exampleKey = $this->getExampleKey($name);
        $exampleValue = $this->getExampleValue($exampleKey, $locale);
        
        return json_encode([
            $exampleKey => $exampleValue
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }

    protected function generateYamlContent(string $name, string $locale): string
    {
        $exampleKey = $this->getExampleKey($name);
        $exampleValue = $this->getExampleValue($exampleKey, $locale);
        
        return "{$exampleKey}: \"{$exampleValue}\"\n";
    }

    protected function getExampleKey(string $name): string
    {
        return match($name) {
            'messages' => 'welcome',
            'validation' => 'required',
            'auth' => 'failed',
            'passwords' => 'reset',
            'pagination' => 'previous',
            'users' => 'title',
            'products' => 'name',
            'orders' => 'status',
            default => 'example'
        };
    }

    protected function getExampleValue(string $key, string $locale): string
    {
        $examples = [
            'en' => [
                'welcome' => 'Welcome to our application!',
                'required' => 'The :field field is required.',
                'failed' => 'These credentials do not match our records.',
                'reset' => 'Your password has been reset!',
                'previous' => '&laquo; Previous',
                'title' => 'Title',
                'name' => 'Name',
                'status' => 'Status',
                'example' => 'Example translation'
            ],
            'pt_BR' => [
                'welcome' => 'Bem-vindo à nossa aplicação!',
                'required' => 'O campo :field é obrigatório.',
                'failed' => 'Essas credenciais não correspondem aos nossos registros.',
                'reset' => 'Sua senha foi redefinida!',
                'previous' => '&laquo; Anterior',
                'title' => 'Título',
                'name' => 'Nome',
                'status' => 'Status',
                'example' => 'Tradução de exemplo'
            ],
            'es' => [
                'welcome' => '¡Bienvenido a nuestra aplicación!',
                'required' => 'El campo :field es obligatorio.',
                'failed' => 'Estas credenciales no coinciden con nuestros registros.',
                'reset' => '¡Tu contraseña ha sido restablecida!',
                'previous' => '&laquo; Anterior',
                'title' => 'Título',
                'name' => 'Nombre',
                'status' => 'Estado',
                'example' => 'Traducción de ejemplo'
            ]
        ];

        return $examples[$locale][$key] ?? $examples['en'][$key] ?? 'Example translation';
    }
} 