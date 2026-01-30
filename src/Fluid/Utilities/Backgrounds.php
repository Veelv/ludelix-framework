<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Fluid\Core\Config;
use Ludelix\Interface\Fluid\UtilityInterface;

class Backgrounds implements UtilityInterface
{
    public const POSITION = [
        'center' => 'center',
        'top' => 'top',
        'bottom' => 'bottom',
        'left' => 'left',
        'right' => 'right',
        'topLeft' => 'top left',
        'topRight' => 'top right',
        'bottomLeft' => 'bottom left',
        'bottomRight' => 'bottom right',
    ];

    public const SIZE = [
        'auto' => 'auto',
        'cover' => 'cover',
        'contain' => 'contain',
    ];

    public const REPEAT = [
        'noRepeat' => 'no-repeat',
        'repeat' => 'repeat',
        'repeatX' => 'repeat-x',
        'repeatY' => 'repeat-y',
        'space' => 'space',
        'round' => 'round',
    ];

    public const BLEND_MODE = [
        'normal' => 'normal',
        'multiply' => 'multiply',
        'screen' => 'screen',
        'overlay' => 'overlay',
        'darken' => 'darken',
        'lighten' => 'lighten',
        'colorDodge' => 'color-dodge',
        'colorBurn' => 'color-burn',
        'hardLight' => 'hard-light',
        'softLight' => 'soft-light',
        'difference' => 'difference',
        'exclusion' => 'exclusion',
    ];

    public const GRADIENT_DIRECTIONS = [
        't' => 'to top',
        'tr' => 'to top right', 
        'r' => 'to right',
        'br' => 'to bottom right',
        'b' => 'to bottom',
        'bl' => 'to bottom left',
        'l' => 'to left',
        'tl' => 'to top left',
    ];

    public static function getStyles(): array
    {
        $styles = [];

        // Obter configuração personalizada
        try {
            $config = new \Ludelix\Fluid\Core\Config();
            $customColors = $config->getCustomColors();
            $prefix = $config->getPrefix();
        } catch (\Throwable $e) {
            $customColors = [];
            $prefix = 'fl-';
        }

        // Mesclar cores padrão com personalizadas
        $allColors = array_merge(\Ludelix\Fluid\Utilities\Colors::COLORS, $customColors);

        // Gerar background colors
        foreach ($allColors as $color => $shades) {
            if (is_array($shades)) {
                foreach ($shades as $shade => $value) {
                    $styles["{$prefix}bg-$color-$shade"] = ['background-color' => $value];
                }
            } else {
                $styles["{$prefix}bg-$color"] = ['background-color' => $shades];
            }
        }
        
        // Background properties
        foreach (self::POSITION as $key => $value) {
            $styles["{$prefix}bg-position-$key"] = ['background-position' => $value];
        }
        foreach (self::SIZE as $key => $value) {
            $styles["{$prefix}bg-size-$key"] = ['background-size' => $value];
        }
        foreach (self::REPEAT as $key => $value) {
            $styles["{$prefix}bg-repeat-$key"] = ['background-repeat' => $value];
        }
        foreach (self::BLEND_MODE as $key => $value) {
            $styles["{$prefix}bg-blend-$key"] = ['background-blend-mode' => $value];
        }
        // Gradient directions (como no Tailwind)
        foreach (self::GRADIENT_DIRECTIONS as $key => $value) {
            $styles["{$prefix}bg-$key"] = [
                'background-image' => "linear-gradient($value, var(--fl-gradient-stops))",
            ];
        }
        
        return $styles;
    }
}