<?php

namespace Ludelix\Translation\Support;

use Ludelix\Translation\Core\TranslatorService;
use Ludelix\Translation\Cache\TranslationCache;

/**
 * Translation Helper Functions
 */
class TranslationHelper
{
    protected static ?TranslatorService $translator = null;

    public static function setTranslator(TranslatorService $translator): void
    {
        self::$translator = $translator;
    }

    /**
     * Get or create translator instance
     */
    protected static function getTranslator(): TranslatorService
    {
        if (!self::$translator) {
            // Get the application base path from Bridge or use a fallback
            $basePath = self::getBasePath();
            $cache = new TranslationCache();
            self::$translator = new TranslatorService($basePath, $cache);
            self::$translator->setLocale('en'); // Default locale
        }
        
        return self::$translator;
    }

    /**
     * Get the base path for translations
     */
    protected static function getBasePath(): string
    {
        // Get the project root path - go up from vendor/ludelix/framework/src/Translation/Support
        $projectRoot = dirname(dirname(dirname(dirname(dirname(dirname(__DIR__))))));
        $langPath = $projectRoot . '/frontend/lang';
        
        return $langPath;
    }

    /**
     * Translate text
     */
    public static function t(string $key, array $parameters = [], ?string $locale = null): string
    {
        $translator = self::getTranslator();
        return $translator->translate($key, $parameters, $locale);
    }

    /**
     * Pluralize translation
     */
    public static function choice(string $key, int $count, array $parameters = [], ?string $locale = null): string
    {
        $translator = self::getTranslator();
        return $translator->choice($key, $count, $parameters, $locale);
    }

    /**
     * Get current locale
     */
    public static function locale(): string
    {
        $translator = self::getTranslator();
        return $translator->getLocale();
    }
}