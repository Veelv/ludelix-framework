<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class Cursors implements UtilityInterface
{
    public const CURSORS = [
        'auto' => 'auto',
        'default' => 'default',
        'pointer' => 'pointer',
        'grab' => 'grab',
        'grabbing' => 'grabbing',
        'notAllowed' => 'not-allowed',
        'wait' => 'wait',
        'text' => 'text',
        'move' => 'move',
        'crosshair' => 'crosshair',
    ];

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::CURSORS as $key => $value) {
            $styles["fl-cursor-$key"] = ['cursor' => $value];
        }
        return $styles;
    }
}