<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class ZIndices implements UtilityInterface
{
    public const VALUES = [
        '0' => '0',
        '10' => '10',
        '20' => '20',
        '30' => '30',
        '40' => '40',
        '50' => '50',
        'auto' => 'auto',
    ];

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::VALUES as $key => $value) {
            $styles["fl-z-$key"] = ['z-index' => $value];
        }
        return $styles;
    }
}
