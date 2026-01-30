<?php

namespace Ludelix\Fluid\Core;

class AdvancedParser
{
    // Pseudo-classes suportadas
    public const PSEUDO_CLASSES = [
        'hover' => ':hover',
        'focus' => ':focus',
        'active' => ':active',
        'visited' => ':visited',
        'disabled' => ':disabled',
        'checked' => ':checked',
        'focus-within' => ':focus-within',
        'focus-visible' => ':focus-visible',
        'target' => ':target',
        'first' => ':first-child',
        'last' => ':last-child',
        'odd' => ':nth-child(odd)',
        'even' => ':nth-child(even)',
        'first-of-type' => ':first-of-type',
        'last-of-type' => ':last-of-type',
        'empty' => ':empty',
        'file' => '::file-selector-button',
        'marker' => '::marker',
        'before' => '::before',
        'after' => '::after',
        'first-letter' => '::first-letter',
        'first-line' => '::first-line',
        'selection' => '::selection',
        'backdrop' => '::backdrop',
        'placeholder' => '::placeholder',
    ];

    // Estados de grupo
    public const GROUP_STATES = [
        'group-hover' => '.group:hover &',
        'group-focus' => '.group:focus &',
        'group-active' => '.group:active &',
        'group-visited' => '.group:visited &',
        'group-target' => '.group:target &',
        'group-first' => '.group:first-child &',
        'group-last' => '.group:last-child &',
        'group-odd' => '.group:nth-child(odd) &',
        'group-even' => '.group:nth-child(even) &',
        'group-focus-within' => '.group:focus-within &',
        'group-checked' => '.group:checked &',
        'group-disabled' => '.group:disabled &',
    ];

    // Breakpoints responsivos
    public const BREAKPOINTS = [
        'sm' => '640px',   // @media (min-width: 640px)
        'md' => '768px',   // @media (min-width: 768px)
        'lg' => '1024px',  // @media (min-width: 1024px)
        'xl' => '1280px',  // @media (min-width: 1280px)
        '2xl' => '1536px', // @media (min-width: 1536px)
    ];

    // Estados especiais que precisam de contexto extra
    public const SPECIAL_STATES = [
        'dark' => '@media (prefers-color-scheme: dark)',
        'motion-safe' => '@media (prefers-reduced-motion: no-preference)',
        'motion-reduce' => '@media (prefers-reduced-motion: reduce)',
        'contrast-more' => '@media (prefers-contrast: more)',
        'contrast-less' => '@media (prefers-contrast: less)',
        'print' => '@media print',
        'portrait' => '@media (orientation: portrait)',
        'landscape' => '@media (orientation: landscape)',
    ];

    /**
     * Parse uma classe complexa como "hover:bg-blue-500" ou "md:group-hover:scale-95"
     */
    public function parseClass(string $class): array
    {
        $parts = explode(':', $class);
        $baseClass = array_pop($parts); // A última parte é sempre a classe base
        $modifiers = $parts; // Todo o resto são modificadores

        // Remove o prefixo fl- da classe base se existir
        if (str_starts_with($baseClass, 'fl-')) {
            $baseClass = substr($baseClass, 3);
        }

        $parsed = [
            'base_class' => $baseClass,
            'modifiers' => $modifiers,
            'pseudo_class' => null,
            'group_state' => null,
            'breakpoint' => null,
            'special_state' => null,
            'is_negative' => false,
        ];

        // Processar cada modificador
        foreach ($modifiers as $modifier) {
            if (isset(self::PSEUDO_CLASSES[$modifier])) {
                $parsed['pseudo_class'] = $modifier;
            } elseif (isset(self::GROUP_STATES[$modifier])) {
                $parsed['group_state'] = $modifier;
            } elseif (isset(self::BREAKPOINTS[$modifier])) {
                $parsed['breakpoint'] = $modifier;
            } elseif (isset(self::SPECIAL_STATES[$modifier])) {
                $parsed['special_state'] = $modifier;
            }
        }

        // Verificar se é valor negativo
        if (str_starts_with($baseClass, '-')) {
            $parsed['is_negative'] = true;
            $parsed['base_class'] = substr($baseClass, 1); // Remove o -
        }

        return $parsed;
    }

    /**
     * Gera o seletor CSS completo baseado no parsing
     */
    public function generateSelector(string $originalClass, array $parsed): string
    {
        // Remove o prefixo fl- se existir
        $class = str_starts_with($originalClass, 'fl-') ? substr($originalClass, 3) : $originalClass;
        
        // Escapa caracteres especiais no nome da classe
        $escapedClass = preg_quote($class, '/');
        
        // Se tem grupo, modifica o seletor
        if ($parsed['group_state']) {
            $groupState = self::GROUP_STATES[$parsed['group_state']];
            // Para estados de grupo, substitui o & pelo seletor da classe
            $selector = str_replace('&', '.fl-' . $escapedClass, $groupState);
        } else {
            $selector = '.fl-' . $escapedClass;
        }
        
        // Se tem pseudo-classe ou pseudo-elemento, adiciona
        if ($parsed['pseudo_class']) {
            $pseudo = self::PSEUDO_CLASSES[$parsed['pseudo_class']] ?? '';
            // Se o seletor já contiver &, substitui, senão concatena
            if (str_contains($pseudo, '&')) {
                $selector = str_replace('&', $selector, $pseudo);
            } else {
                $selector .= $pseudo;
            }
        }

        return $selector;
    }

    /**
     * Gera media query se necessário
     */
    public function generateMediaQuery(array $parsed): ?string
    {
        $queries = [];
        
        // Adiciona breakpoint se existir
        if ($parsed['breakpoint']) {
            $breakpoint = self::BREAKPOINTS[$parsed['breakpoint']];
            $queries[] = "(min-width: {$breakpoint})";
        }
        
        // Adiciona estado especial se existir
        if ($parsed['special_state']) {
            // Remove o @media do início se existir
            $specialState = preg_replace('/^@media\s*/', '', self::SPECIAL_STATES[$parsed['special_state']]);
            $queries[] = $specialState;
        }
        
        // Se não houver queries, retorna null
        if (empty($queries)) {
            return null;
        }
        
        // Combina múltiplas queries com AND
        return '@media ' . implode(' and ', $queries);
    }

    /**
     * Verifica se uma classe precisa de processamento avançado
     */
    public function needsAdvancedProcessing(string $class): bool
    {
        // Se contém : ou começa com -, precisa de processamento avançado
        return str_contains($class, ':') || str_starts_with($class, 'fl--');
    }

    /**
     * Normaliza uma classe removendo prefixos fl-
     */
    public function normalizeClass(string $class): string
    {
        if (str_starts_with($class, 'fl-')) {
            return substr($class, 3);
        }
        return $class;
    }
}