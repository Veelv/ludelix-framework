<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class Opacities implements UtilityInterface
{
    public const OPACITIES = [
        0 => 0,
        10 => 0.1,
        20 => 0.2,
        30 => 0.3,
        40 => 0.4,
        50 => 0.5,
        60 => 0.6,
        70 => 0.7,
        80 => 0.8,
        90 => 0.9,
        100 => 1,
    ];

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::OPACITIES as $key => $value) {
            $styles["fl-opacity-$key"] = ['opacity' => $value];
        }
        return $styles;
    }
}