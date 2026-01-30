<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class ContainerQueries implements UtilityInterface
{
    public const TYPE = [
        'inlineSize' => 'inline-size',
        'blockSize' => 'block-size',
        'size' => 'size',
    ];

    public const NAME = [
        'main' => 'main',
        'sidebar' => 'sidebar',
        'content' => 'content',
    ];

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::TYPE as $key => $value) {
            $styles["fl-container-type-$key"] = ['container-type' => $value];
        }
        foreach (self::NAME as $key => $value) {
            $styles["fl-container-name-$key"] = ['container-name' => $value];
        }
        return $styles;
    }
}