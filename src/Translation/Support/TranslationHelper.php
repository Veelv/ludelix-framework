<?php

namespace Ludelix\Translation\Support;

use Ludelix\Translation\Core\TranslatorService;

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
     * Translate text
     */
    public static function t(string $key, array $parameters = [], ?string $locale = null): string
    {
        if (!self::$translator) {
            return $key;
        }
        
        return self::$translator->translate($key, $parameters, $locale);
    }

    /**
     * Pluralize translation
     */
    public static function choice(string $key, int $count, array $parameters = [], ?string $locale = null): string
    {
        if (!self::$translator) {
            return $key;
        }
        
        return self::$translator->choice($key, $count, $parameters, $locale);
    }

    /**
     * Get current locale
     */
    public static function locale(): string
    {
        return self::$translator?->getLocale() ?? 'en';
    }
}