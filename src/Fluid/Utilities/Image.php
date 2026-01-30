<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class Image implements UtilityInterface
{
    public const RESIZE_MODE = [
        'cover' => 'cover',
        'contain' => 'contain',
        'stretch' => 'stretch',
        'repeat' => 'repeat',
        'center' => 'center',
    ];

    public const IMAGE_STYLES = [
        'default' => ['resizeMode' => self::RESIZE_MODE['contain']],
        'cover' => ['resizeMode' => self::RESIZE_MODE['cover']],
        'stretch' => ['resizeMode' => self::RESIZE_MODE['stretch']],
        'repeat' => ['resizeMode' => self::RESIZE_MODE['repeat']],
        'center' => ['resizeMode' => self::RESIZE_MODE['center']],
    ];

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::IMAGE_STYLES as $key => $value) {
            $styles["fl-image-$key"] = ['object-fit' => $value['resizeMode']];
        }
        return $styles;
    }
}