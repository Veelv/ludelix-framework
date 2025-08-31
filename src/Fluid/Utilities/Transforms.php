<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class Transforms implements UtilityInterface
{
    public const SCALE = [
        'none' => 1,
        'sm' => 0.95,
        'md' => 1.05,
        'lg' => 1.1,
        'xl' => 1.2,
    ];

    public const ROTATE = [
        0 => '0deg',
        45 => '45deg',
        90 => '90deg',
        180 => '180deg',
        270 => '270deg',
    ];

    public const TRANSLATE = [
        'none' => '0px',
        'sm' => '4px',
        'md' => '8px',
        'lg' => '16px',
        'xl' => '24px',
    ];

    public const SKEW = [
        'none' => '0deg',
        'sm' => '2deg',
        'md' => '5deg',
        'lg' => '10deg',
    ];

    public const ORIGIN = [
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

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::SCALE as $key => $value) {
            $styles["fl-scale-$key"] = ['transform' => "scale($value)"];
        }
        foreach (self::ROTATE as $key => $value) {
            $styles["fl-rotate-$key"] = ['transform' => "rotate($value)"];
        }
        foreach (self::TRANSLATE as $key => $value) {
            $styles["fl-translate-$key"] = ['transform' => "translate($value)"];
        }
        foreach (self::SKEW as $key => $value) {
            $styles["fl-skew-$key"] = ['transform' => "skew($value)"];
        }
        foreach (self::ORIGIN as $key => $value) {
            $styles["fl-transform-origin-$key"] = ['transform-origin' => $value];
        }
        return $styles;
    }
}