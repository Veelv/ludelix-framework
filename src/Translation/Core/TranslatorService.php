<?php

namespace Ludelix\Translation\Core;

use Ludelix\Translation\Cache\TranslationCache;

/**
 * Advanced Translation Service
 * 
 * Multi-format translation system supporting PHP arrays and JSON files
 * with caching, pluralization, and parameter replacement.
 */
class TranslatorService
{
    protected string $locale = 'en';
    protected string $fallbackLocale = 'en';
    protected array $loaded = [];
    protected array $translations = [];
    protected string $basePath;
    protected TranslationCache $cache;

    public function __construct(string $basePath, TranslationCache $cache = null)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->cache = $cache ?? new TranslationCache();
    }

    /**
     * Set current locale
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * Get current locale
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Translate key with parameters
     * 
     * @param string $key Translation key (e.g., 'messages.welcome')
     * @param array $parameters Replacement parameters
     * @param string|null $locale Override locale
     * @return string Translated text
     */
    public function translate(string $key, array $parameters = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->locale;
        
        $translation = $this->get($key, $locale);
        
        if ($translation === $key && $locale !== $this->fallbackLocale) {
            $translation = $this->get($key, $this->fallbackLocale);
        }
        
        return $this->replaceParameters($translation, $parameters);
    }

    /**
     * Handle pluralization
     */
    public function choice(string $key, int $count, array $parameters = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->locale;
        $translation = $this->get($key, $locale);
        
        if ($translation === $key) {
            return $key;
        }

        $parts = explode('|', $translation);
        
        if (count($parts) === 1) {
            return $this->replaceParameters($translation, array_merge($parameters, ['count' => $count]));
        }

        $index = $count === 1 ? 0 : 1;
        $selected = $parts[$index] ?? $parts[0];
        
        return $this->replaceParameters($selected, array_merge($parameters, ['count' => $count]));
    }

    /**
     * Get translation for key
     */
    protected function get(string $key, string $locale): string
    {
        $this->loadTranslations($locale);
        
        $keys = explode('.', $key);
        $translation = $this->translations[$locale] ?? [];
        
        foreach ($keys as $segment) {
            if (!is_array($translation) || !isset($translation[$segment])) {
                return $key;
            }
            $translation = $translation[$segment];
        }
        
        return is_string($translation) ? $translation : $key;
    }

    /**
     * Load translations for locale
     */
    protected function loadTranslations(string $locale): void
    {
        if (isset($this->loaded[$locale])) {
            return;
        }

        $translations = [];
        $localePath = $this->basePath . '/' . $locale;
        
        if (!is_dir($localePath)) {
            $this->loaded[$locale] = true;
            return;
        }

        // Load PHP files
        $phpFiles = glob($localePath . '/*.php');
        foreach ($phpFiles as $file) {
            $key = basename($file, '.php');
            $content = include $file;
            if (is_array($content)) {
                $translations[$key] = $content;
            }
        }

        // Load JSON files
        $jsonFiles = glob($localePath . '/*.json');
        foreach ($jsonFiles as $file) {
            $key = basename($file, '.json');
            $content = json_decode(file_get_contents($file), true);
            if (is_array($content)) {
                $translations[$key] = $content;
            }
        }

        // Load YAML files
        $yamlFiles = glob($localePath . '/*.yaml');
        foreach ($yamlFiles as $file) {
            $key = basename($file, '.yaml');
            $content = $this->parseYamlFile($file);
            if (is_array($content)) {
                $translations[$key] = $content;
            }
        }

        // Load YML files (alternative YAML extension)
        $ymlFiles = glob($localePath . '/*.yml');
        foreach ($ymlFiles as $file) {
            $key = basename($file, '.yml');
            $content = $this->parseYamlFile($file);
            if (is_array($content)) {
                $translations[$key] = $content;
            }
        }

        $this->translations[$locale] = $translations;
        $this->loaded[$locale] = true;
    }

    /**
     * Parse YAML file
     */
    protected function parseYamlFile(string $file): array
    {
        if (!function_exists('yaml_parse_file')) {
            // Fallback to simple YAML parser if ext-yaml is not available
            return $this->parseYamlSimple(file_get_contents($file));
        }
        
        $content = yaml_parse_file($file);
        return is_array($content) ? $content : [];
    }

    /**
     * Simple YAML parser fallback
     */
    protected function parseYamlSimple(string $content): array
    {
        $lines = explode("\n", $content);
        $result = [];
        $currentKey = null;
        $currentIndent = 0;
        $stack = [&$result];
        
        foreach ($lines as $line) {
            $line = rtrim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            $indent = strlen($line) - strlen(ltrim($line));
            $line = trim($line);
            
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                $stack[count($stack) - 1][$key] = $value;
            }
        }
        
        return $result;
    }

    /**
     * Replace parameters in translation
     */
    protected function replaceParameters(string $translation, array $parameters): string
    {
        foreach ($parameters as $key => $value) {
            $translation = str_replace([':' . $key, '{' . $key . '}'], $value, $translation);
        }
        return $translation;
    }
}