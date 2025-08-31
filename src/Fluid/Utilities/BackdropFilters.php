<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class BackdropFilters implements UtilityInterface
{
    public const BLUR = [
        'none' => '0px',
        'sm' => '4px',
        'md' => '8px',
        'lg' => '16px',
    ];

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::BLUR as $key => $value) {
            $styles["fl-backdrop-blur-$key"] = ['backdrop-filter' => "blur($value)"];
        }
        return $styles;
    }
}