<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class ContentVisibility implements UtilityInterface
{
    public const CONTENT_VISIBILITY = [
        'visible' => 'visible',
        'hidden' => 'hidden',
        'auto' => 'auto',
    ];

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::CONTENT_VISIBILITY as $key => $value) {
            $styles["fl-content-visibility-$key"] = ['content-visibility' => $value];
        }
        return $styles;
    }
}