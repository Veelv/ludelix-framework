<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class Borders implements UtilityInterface
{
    public const BORDER_STYLES = [
        'none' => 'none',
        'solid' => 'solid',
        'dashed' => 'dashed',
        'dotted' => 'dotted',
        'double' => 'double',
        'groove' => 'groove',
        'ridge' => 'ridge',
        'inset' => 'inset',
        'outset' => 'outset',
    ];

    public static function getStyles(): array
    {
        $styles = [];
        
        // Obter cores da utility Colors
        $allColors = \Ludelix\Fluid\Utilities\Colors::COLORS;
        $prefix = 'fl-';

        // Gerar border colors
        foreach ($allColors as $color => $shades) {
            if (is_array($shades)) {
                foreach ($shades as $shade => $value) {
                    $styles["{$prefix}border-$color-$shade"] = ['border-color' => $value];
                }
            } else {
                $styles["{$prefix}border-$color"] = ['border-color' => $shades];
            }
        }
        
        // Border styles
        foreach (self::BORDER_STYLES as $key => $value) {
            $styles["{$prefix}border-style-$key"] = ['border-style' => $value];
        }

        return $styles;
    }
}