<?php

namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class Shadows implements UtilityInterface
{
    public const SHADOWS = [
        'none' => [
            'shadowColor' => 'transparent',
            'shadowOffset' => ['width' => 0, 'height' => 0],
            'shadowOpacity' => 0,
            'shadowRadius' => 0,
            'elevation' => 0,
        ],
        'xs' => [
            'shadowOffset' => ['width' => 0, 'height' => 1],
            'shadowOpacity' => 0.03,
            'shadowRadius' => 1,
            'elevation' => 1,
        ],
        'sm' => [
            'shadowOffset' => ['width' => 0, 'height' => 1],
            'shadowOpacity' => 0.05,
            'shadowRadius' => 2,
            'elevation' => 2,
        ],
        'base' => [
            'shadowOffset' => ['width' => 0, 'height' => 1],
            'shadowOpacity' => 0.1,
            'shadowRadius' => 3,
            'elevation' => 3,
        ],
        'md' => [
            'shadowOffset' => ['width' => 0, 'height' => 4],
            'shadowOpacity' => 0.15,
            'shadowRadius' => 6,
            'elevation' => 6,
        ],
        'lg' => [
            'shadowOffset' => ['width' => 0, 'height' => 10],
            'shadowOpacity' => 0.15,
            'shadowRadius' => 15,
            'elevation' => 15,
        ],
        'xl' => [
            'shadowOffset' => ['width' => 0, 'height' => 20],
            'shadowOpacity' => 0.25,
            'shadowRadius' => 25,
            'elevation' => 25,
        ],
        '2xl' => [
            'shadowOffset' => ['width' => 0, 'height' => 25],
            'shadowOpacity' => 0.25,
            'shadowRadius' => 50,
            'elevation' => 50,
        ],
    ];

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::SHADOWS as $key => $value) {
            if ($key === 'none') {
                $styles["fl-shadow-$key"] = [
                    'box-shadow' => 'none',
                ];
            } else {
                $styles["fl-shadow-$key"] = [
                    'box-shadow' => sprintf(
                        '%spx %spx %spx rgba(0, 0, 0, %s)',
                        $value['shadowOffset']['width'],
                        $value['shadowOffset']['height'],
                        $value['shadowRadius'],
                        $value['shadowOpacity']
                    ),
                ];
            }
        }
        return $styles;
    }
}