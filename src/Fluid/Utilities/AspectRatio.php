<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class AspectRatio implements UtilityInterface
{
    public const ASPECT_RATIO = [
        'square' => '1/1',
        'video' => '16/9',
        'photo' => '4/3',
        'portrait' => '3/4',
        'auto' => 'auto',
    ];

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::ASPECT_RATIO as $key => $value) {
            $styles["fl-aspect-$key"] = ['aspect-ratio' => $value];
        }
        return $styles;
    }
}