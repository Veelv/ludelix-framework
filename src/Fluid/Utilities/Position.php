<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class Position implements UtilityInterface
{
    public const POSITION = [
        'absolute' => 'absolute',
        'relative' => 'relative',
        'fixed' => 'fixed',
        'sticky' => 'sticky',
        'static' => 'static',
    ];

    public const Z_INDEX = [
        '0' => '0',
        '1' => '1',
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
        
        // Position
        foreach (self::POSITION as $key => $value) {
            $styles[$key] = ['position' => $value];
        }
        
        // Z-index
        foreach (self::Z_INDEX as $key => $value) {
            $styles["z-{$key}"] = ['z-index' => $value];
        }
        
        return $styles;
    }
}