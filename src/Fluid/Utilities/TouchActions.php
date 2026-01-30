<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class TouchActions implements UtilityInterface
{
    public const TOUCH_ACTIONS = [
        'auto' => 'auto',
        'none' => 'none',
        'panX' => 'pan-x',
        'panY' => 'pan-y',
        'manipulation' => 'manipulation',
    ];

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::TOUCH_ACTIONS as $key => $value) {
            $styles["fl-touch-action-$key"] = ['touch-action' => $value];
        }
        return $styles;
    }
}