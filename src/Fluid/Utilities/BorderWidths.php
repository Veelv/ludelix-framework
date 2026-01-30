<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class BorderWidths implements UtilityInterface
{
    public const BORDER_WIDTHS = [
        'none' => 0,
        'hairline' => 0.5,
        'thin' => 1,
        'medium' => 2,
        'thick' => 4,
        'heavy' => 8,
    ];

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::BORDER_WIDTHS as $key => $value) {
            $styles["fl-border-$key"] = ['border-width' => $value . 'px'];
        }
        return $styles;
    }
}