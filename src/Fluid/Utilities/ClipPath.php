<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class ClipPath implements UtilityInterface
{
    public const CLIP_PATH = [
        'none' => 'none',
        'circle' => 'circle(50% at 50% 50%)',
        'ellipse' => 'ellipse(50% 35% at 50% 50%)',
        'polygon' => [
            'triangle' => 'polygon(50% 0%, 0% 100%, 100% 100%)',
            'hexagon' => 'polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%)',
        ],
    ];

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::CLIP_PATH as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $styles["fl-clip-$key-$subKey"] = ['clip-path' => $subValue];
                }
            } else {
                $styles["fl-clip-$key"] = ['clip-path' => $value];
            }
        }
        return $styles;
    }
}