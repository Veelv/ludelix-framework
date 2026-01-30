<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class Filters implements UtilityInterface
{
    public const BLUR = [
        'none' => '0px',
        'sm' => '4px',
        'md' => '8px',
        'lg' => '16px',
        'xl' => '24px',
    ];

    public const BRIGHTNESS = [
        'none' => 1,
        'dim' => 0.8,
        'bright' => 1.2,
    ];

    public const CONTRAST = [
        'none' => 1,
        'low' => 0.8,
        'high' => 1.2,
    ];

    public const GRAYSCALE = [
        'none' => 0,
        'full' => 1,
    ];

    public const HUE_ROTATE = [
        'none' => '0deg',
        'sm' => '15deg',
        'md' => '30deg',
        'lg' => '60deg',
    ];

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::BLUR as $key => $value) {
            $styles["fl-filter-blur-$key"] = ['filter' => "blur($value)"];
        }
        foreach (self::BRIGHTNESS as $key => $value) {
            $styles["fl-filter-brightness-$key"] = ['filter' => "brightness($value)"];
        }
        foreach (self::CONTRAST as $key => $value) {
            $styles["fl-filter-contrast-$key"] = ['filter' => "contrast($value)"];
        }
        foreach (self::GRAYSCALE as $key => $value) {
            $styles["fl-filter-grayscale-$key"] = ['filter' => "grayscale($value)"];
        }
        foreach (self::HUE_ROTATE as $key => $value) {
            $styles["fl-filter-hue-rotate-$key"] = ['filter' => "hue-rotate($value)"];
        }
        return $styles;
    }
}