<?php

namespace Ludelix\Translation\Core;

/**
 * Locale Loader
 * 
 * Handles loading and parsing of translation files
 */
class LocaleLoader
{
    protected string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Load translations for locale
     */
    public function load(string $locale): array
    {
        $translations = [];
        $localePath = $this->basePath . '/' . $locale;
        
        if (!is_dir($localePath)) {
            return $translations;
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

        return $translations;
    }

    /**
     * Get available locales
     */
    public function getAvailableLocales(): array
    {
        $locales = [];
        $directories = glob($this->basePath . '/*', GLOB_ONLYDIR);
        
        foreach ($directories as $dir) {
            $locales[] = basename($dir);
        }
        
        return $locales;
    }
}