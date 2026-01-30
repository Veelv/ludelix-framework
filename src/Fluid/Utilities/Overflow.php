<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class Overflow implements UtilityInterface
{
    public const OVERFLOW = [
        'visible' => 'visible',
        'hidden' => 'hidden',
        'scroll' => 'scroll',
        'auto' => 'auto',
        'clip' => 'clip',
    ];

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::OVERFLOW as $key => $value) {
            $styles["fl-overflow-$key"] = ['overflow' => $value];
            $styles["fl-overflow-x-$key"] = ['overflow-x' => $value];
            $styles["fl-overflow-y-$key"] = ['overflow-y' => $value];
        }
        return $styles;
    }
}