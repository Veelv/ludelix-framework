<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class Gradients implements UtilityInterface
{
    public static function getStyles(): array
    {
        $styles = [];
        
        // Obter cores da utility Colors
        $allColors = \Ludelix\Fluid\Utilities\Colors::COLORS;
        $prefix = 'fl-';

        // Gerar gradient from colors (como no Tailwind: from-blue-500)
        foreach ($allColors as $color => $shades) {
            if (is_array($shades)) {
                foreach ($shades as $shade => $value) {
                    $styles["{$prefix}from-$color-$shade"] = [
                        '--fl-gradient-from' => $value,
                        '--fl-gradient-stops' => 'var(--fl-gradient-from), var(--fl-gradient-to, rgba(255, 255, 255, 0))'
                    ];
                    $styles["{$prefix}to-$color-$shade"] = [
                        '--fl-gradient-to' => $value
                    ];
                    $styles["{$prefix}via-$color-$shade"] = [
                        '--fl-gradient-stops' => 'var(--fl-gradient-from), ' . $value . ', var(--fl-gradient-to, rgba(255, 255, 255, 0))'
                    ];
                }
            } else {
                $styles["{$prefix}from-$color"] = [
                    '--fl-gradient-from' => $shades,
                    '--fl-gradient-stops' => 'var(--fl-gradient-from), var(--fl-gradient-to, rgba(255, 255, 255, 0))'
                ];
                $styles["{$prefix}to-$color"] = [
                    '--fl-gradient-to' => $shades
                ];
                $styles["{$prefix}via-$color"] = [
                    '--fl-gradient-stops' => 'var(--fl-gradient-from), ' . $shades . ', var(--fl-gradient-to, rgba(255, 255, 255, 0))'
                ];
            }
        }

        return $styles;
    }
}