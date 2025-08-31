<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class Justify implements UtilityInterface
{
    public const JUSTIFY_CONTENT = [
        'start' => 'flex-start',
        'end' => 'flex-end',
        'center' => 'center',
        'between' => 'space-between',
        'around' => 'space-around',
        'evenly' => 'space-evenly',
        'spaceBetween' => 'space-between',
        'spaceAround' => 'space-around',
        'spaceEvenly' => 'space-evenly',
        'flexStart' => 'flex-start',
        'flexEnd' => 'flex-end',
        'stretch' => 'stretch',
        'baseline' => 'baseline',
    ];

    public const JUSTIFY_ITEMS = [
        'start' => 'start',
        'end' => 'end',
        'center' => 'center',
        'stretch' => 'stretch',
        'baseline' => 'baseline',
        'auto' => 'auto',
    ];

    public const JUSTIFY_SELF = [
        'auto' => 'auto',
        'start' => 'start',
        'end' => 'end',
        'center' => 'center',
        'stretch' => 'stretch',
        'baseline' => 'baseline',
    ];

    public static function getStyles(): array
    {
        $styles = [];
        
        // Justify Content (padrão detalhado)
        foreach (self::JUSTIFY_CONTENT as $key => $value) {
            $styles["fl-justify-content-$key"] = ['justify-content' => $value];
        }
        
        // Justify Items
        foreach (self::JUSTIFY_ITEMS as $key => $value) {
            $styles["fl-justify-items-$key"] = ['justify-items' => $value];
        }
        
        // Justify Self
        foreach (self::JUSTIFY_SELF as $key => $value) {
            $styles["fl-justify-self-$key"] = ['justify-self' => $value];
        }
        
        // Atalhos estilo Tailwind para justify-content
        foreach (self::JUSTIFY_CONTENT as $key => $value) {
            $styles["fl-justify-$key"] = ['justify-content' => $value];
        }
        
        return $styles;
    }
}