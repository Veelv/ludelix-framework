<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Fluid\Core\Config;
use Ludelix\Interface\Fluid\UtilityInterface;

class Outlines implements UtilityInterface
{
    public const WIDTH = [
        'none' => 0,
        'thin' => 1,
        'medium' => 2,
        'thick' => 4,
    ];

    public const STYLE = [
        'none' => 'none',
        'solid' => 'solid',
        'dashed' => 'dashed',
        'dotted' => 'dotted',
    ];

    public const COLOR = [
        'primary' => '#C63438',
        'secondary' => '#B18B3F',
        'focus' => '#3b82f6',
        'transparent' => 'transparent',
    ];

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::WIDTH as $key => $value) {
            $styles["fl-outline-width-$key"] = ['outline-width' => $value . 'px'];
        }
        foreach (self::STYLE as $key => $value) {
            $styles["fl-outline-style-$key"] = ['outline-style' => $value];
        }
        foreach (self::COLOR as $key => $value) {
            $styles["fl-outline-color-$key"] = ['outline-color' => $value];
        }
        return $styles;
    }
}