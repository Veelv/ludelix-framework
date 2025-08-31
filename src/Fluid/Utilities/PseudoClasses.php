<?php

namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class PseudoClasses implements UtilityInterface
{
    // Pseudo-classes suportadas
    public const PSEUDO_CLASSES = [
        'hover' => '&:hover',
        'focus' => '&:focus',
        'active' => '&:active',
        'visited' => '&:visited',
        'disabled' => '&:disabled',
        'checked' => '&:checked',
        'focus-within' => '&:focus-within',
        'focus-visible' => '&:focus-visible',
        'target' => '&:target',
        'first' => '&:first-child',
        'last' => '&:last-child',
        'odd' => '&:nth-child(odd)',
        'even' => '&:nth-child(even)',
        'first-of-type' => '&:first-of-type',
        'last-of-type' => '&:last-of-type',
        'empty' => '&:empty',
    ];

    // Pseudo-elementos suportados
    public const PSEUDO_ELEMENTS = [
        'before' => '&::before',
        'after' => '&::after',
        'placeholder' => '&::placeholder',
        'selection' => '&::selection',
        'first-letter' => '&::first-letter',
        'first-line' => '&::first-line',
        'file' => '&::file-selector-button',
        'marker' => '&::marker',
        'backdrop' => '&::backdrop',
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
        'sm' => '640px',
        'md' => '768px',
        'lg' => '1024px',
        'xl' => '1280px',
        '2xl' => '1536px',
    ];

    /**
     * Retorna os estilos base para as pseudo-classes e elementos
     * 
     * @return array
     */
    public static function getStyles(): array
    {
        $styles = [];
        
        // Adiciona as pseudo-classes
        foreach (self::PSEUDO_CLASSES as $key => $selector) {
            $styles["fl-$key"] = [
                'pseudo' => $key,
                'selector' => $selector
            ];
        }

        // Adiciona os pseudo-elementos
        foreach (self::PSEUDO_ELEMENTS as $key => $selector) {
            $styles["fl-$key"] = [
                'pseudo' => $key,
                'selector' => $selector
            ];
        }

        // Adiciona os estados de grupo
        foreach (self::GROUP_STATES as $key => $selector) {
            $styles["fl-$key"] = [
                'pseudo' => $key,
                'selector' => $selector
            ];
        }

        return $styles;
    }
}
