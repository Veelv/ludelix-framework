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

        $this->translations[$locale] = $translations;
        $this->loaded[$locale] = true;
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