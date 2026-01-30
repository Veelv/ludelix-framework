<?php

namespace Ludelix\Translation\Support;

use Ludelix\Translation\Core\TranslatorService;

/**
 * Trait para adicionar funcionalidade de translation
 * Pode ser usado em repositories e entities
 */
trait TranslatableTrait
{
    protected ?TranslatorService $translator = null;
    protected string $translationNamespace = '';
    protected string $currentLocale = 'en';

    /**
     * Set translator service
     */
    public function setTranslator(TranslatorService $translator): void
    {
        $this->translator = $translator;
    }

    /**
     * Get translator service
     */
    public function getTranslator(): ?TranslatorService
    {
        return $this->translator;
    }

    /**
     * Set translation namespace for this class
     */
    public function setTranslationNamespace(string $namespace): void
    {
        $this->translationNamespace = $namespace;
    }

    /**
     * Get translation namespace
     */
    public function getTranslationNamespace(): string
    {
        return $this->translationNamespace;
    }

    /**
     * Set current locale
     */
    public function setLocale(string $locale): void
    {
        $this->currentLocale = $locale;
        if ($this->translator) {
            $this->translator->setLocale($locale);
        }
    }

    /**
     * Get current locale
     */
    public function getLocale(): string
    {
        return $this->currentLocale;
    }

    /**
     * Translate a key
     */
    public function trans(string $key, array $parameters = [], ?string $locale = null): string
    {
        if (!$this->translator) {
            return $key;
        }

        $fullKey = $this->translationNamespace ? $this->translationNamespace . '.' . $key : $key;
        return $this->translator->translate($fullKey, $parameters, $locale);
    }

    /**
     * Translate with pluralization
     */
    public function transChoice(string $key, int $count, array $parameters = [], ?string $locale = null): string
    {
        if (!$this->translator) {
            return $key;
        }

        $fullKey = $this->translationNamespace ? $this->translationNamespace . '.' . $key : $key;
        return $this->translator->choice($fullKey, $count, $parameters, $locale);
    }

    /**
     * Check if translation exists
     */
    public function hasTrans(string $key, ?string $locale = null): bool
    {
        if (!$this->translator) {
            return false;
        }

        $fullKey = $this->translationNamespace ? $this->translationNamespace . '.' . $key : $key;
        $translation = $this->translator->translate($fullKey, [], $locale);
        return $translation !== $fullKey;
    }
} 