<?php

namespace Ludelix\Bridge\Services;

use Ludelix\Translation\Core\TranslatorService;

/**
 * Bridge Translation Service
 */
class TranslationService
{
    protected TranslatorService $translator;

    public function __construct(TranslatorService $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Get translation
     */
    public function get(string $key, array $parameters = [], ?string $locale = null): string
    {
        return $this->translator->translate($key, $parameters, $locale);
    }

    /**
     * Translate with pluralization
     */
    public function choice(string $key, int $count, array $parameters = [], ?string $locale = null): string
    {
        return $this->translator->choice($key, $count, $parameters, $locale);
    }

    /**
     * Set locale
     */
    public function setLocale(string $locale): void
    {
        $this->translator->setLocale($locale);
    }

    /**
     * Get current locale
     */
    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }
}