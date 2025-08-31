<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Fluid\Core\Config;
use Ludelix\Interface\Fluid\UtilityInterface;

class TextShadow implements UtilityInterface
{
    public const TEXT_SHADOW = [
        'none' => [
            'offsetX' => 0,
            'offsetY' => 0,
            'blurRadius' => 0,
            'color' => 'transparent',
        ],
        'sm' => [
        'offsetX' => 1,
        'offsetY' => 1,
        'blurRadius' => 2,
        'opacity' => 0.5,
        ],
        'base' => [
        'offsetX' => 2,
        'offsetY' => 2,
        'blurRadius' => 4,
        'opacity' => 0.5,
        ],
        'md' => [
        'offsetX' => 3,
        'offsetY' => 3,
        'blurRadius' => 6,
        'opacity' => 0.5,
        ],
        'lg' => [
        'offsetX' => 4,
        'offsetY' => 4,
        'blurRadius' => 8,
        'opacity' => 0.5,
        ],
    ];

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::TEXT_SHADOW as $key => $value) {
            if ($key === 'none') {
                $styles["fl-text-shadow-$key"] = [
                    'text-shadow' => 'none',
                ];
            } else {
                $styles["fl-text-shadow-$key"] = [
                    'text-shadow' => sprintf(
                        '%spx %spx %spx rgba(0, 0, 0, %s)',
                        $value['offsetX'],
                        $value['offsetY'],
                        $value['blurRadius'],
                        $value['opacity']
                    ),
                ];
            }
        }
        return $styles;
    }
}