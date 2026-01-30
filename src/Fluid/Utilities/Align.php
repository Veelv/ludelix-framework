<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class Align implements UtilityInterface
{
    public const ALIGN_ITEMS = [
        'start' => 'flex-start',
        'end' => 'flex-end',
        'center' => 'center',
        'baseline' => 'baseline',
        'stretch' => 'stretch',
        'flexStart' => 'flex-start',
        'flexEnd' => 'flex-end',
    ];

    public const ALIGN_CONTENT = [
        'start' => 'flex-start',
        'end' => 'flex-end',
        'center' => 'center',
        'between' => 'space-between',
        'around' => 'space-around',
        'evenly' => 'space-evenly',
        'baseline' => 'baseline',
        'stretch' => 'stretch',
        'spaceBetween' => 'space-between',
        'spaceAround' => 'space-around',
        'spaceEvenly' => 'space-evenly',
        'flexStart' => 'flex-start',
        'flexEnd' => 'flex-end',
    ];

    public const ALIGN_SELF = [
        'auto' => 'auto',
        'start' => 'flex-start',
        'end' => 'flex-end',
        'center' => 'center',
        'baseline' => 'baseline',
        'stretch' => 'stretch',
        'flexStart' => 'flex-start',
        'flexEnd' => 'flex-end',
    ];

    public const TEXT_ALIGN = [
        'left' => 'left',
        'center' => 'center',
        'right' => 'right',
        'justify' => 'justify',
        'start' => 'start',
        'end' => 'end',
    ];

    public static function getStyles(): array
    {
        $styles = [];
        
        // Align Items (padrão detalhado)
        foreach (self::ALIGN_ITEMS as $key => $value) {
            $styles["fl-align-items-$key"] = ['align-items' => $value];
        }
        
        // Align Content
        foreach (self::ALIGN_CONTENT as $key => $value) {
            $styles["fl-align-content-$key"] = ['align-content' => $value];
        }
        
        // Align Self
        foreach (self::ALIGN_SELF as $key => $value) {
            $styles["fl-align-self-$key"] = ['align-self' => $value];
        }
        
        // Text Align
        foreach (self::TEXT_ALIGN as $key => $value) {
            $styles["fl-text-align-$key"] = ['text-align' => $value];
        }
        
        // Atalhos estilo Tailwind para align-items
        foreach (self::ALIGN_ITEMS as $key => $value) {
            $styles["fl-items-$key"] = ['align-items' => $value];
        }
        
        return $styles;
    }
}