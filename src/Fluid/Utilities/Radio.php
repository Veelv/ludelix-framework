<?php

namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class Radio implements UtilityInterface
{
    public const RADIO = [
        'none' => 0,
        'sm' => 2,
        'base' => 4,
        'md' => 6,
        'lg' => 8,
        'xl' => 12,
        '2xl' => 16,
        '3xl' => 24,
        'full' => 9999,
    ];

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::RADIO as $key => $value) {
            if ($key === 'base') {
                $styles["fl-radius"] = ['border-radius' => $value . 'px'];
            } elseif ($key === 'full') {
                $styles["fl-radius-full"] = ['border-radius' => '9999px'];
            } else {
                $styles["fl-radius-$key"] = ['border-radius' => $value . 'px'];
            }
        }
        return $styles;
    }
}