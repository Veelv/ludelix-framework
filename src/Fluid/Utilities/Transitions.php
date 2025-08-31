<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class Transitions implements UtilityInterface
{
    public const DURATION = [
        'fastest' => 100,
        'faster' => 150,
        'fast' => 200,
        'normal' => 300,
        'slow' => 500,
        'slower' => 750,
        'slowest' => 1000,
    ];

    public const EASING = [
        'linear' => 'linear',
        'ease' => 'ease',
        'easeIn' => 'ease-in',
        'easeOut' => 'ease-out',
        'easeInOut' => 'ease-in-out',
        'easeInQuad' => 'cubic-bezier(0.55, 0.085, 0.68, 0.53)',
        'easeOutQuad' => 'cubic-bezier(0.25, 0.46, 0.45, 0.94)',
        'easeInOutQuad' => 'cubic-bezier(0.455, 0.03, 0.515, 0.955)',
    ];

    public const PROPERTY = [
        'common' => 'background-color, border-color, color, fill, stroke, opacity, box-shadow, transform',
        'colors' => 'background-color, border-color, color, fill, stroke',
        'dimensions' => 'width, height',
        'position' => 'left, right, top, bottom',
        'all' => 'all',
    ];

    public const KEYFRAMES = [
        'fadeIn' => [
            'from' => ['opacity' => 0],
            'to' => ['opacity' => 1],
        ],
        'slideIn' => [
            'from' => ['transform' => 'translateY(20px)', 'opacity' => 0],
            'to' => ['transform' => 'translateY(0)', 'opacity' => 1],
        ],
        'scale' => [
            'from' => ['transform' => 'scale(0.95)', 'opacity' => 0],
            'to' => ['transform' => 'scale(1)', 'opacity' => 1],
        ],
        'bounce' => [
            '0%' => ['transform' => 'translateY(0)'],
            '50%' => ['transform' => 'translateY(-10px)'],
            '100%' => ['transform' => 'translateY(0)'],
        ],
    ];

    public const ANIMATION = [
        'fadeIn' => ['keyframe' => 'fadeIn', 'duration' => 300, 'easing' => 'easeInOut'],
        'slideIn' => ['keyframe' => 'slideIn', 'duration' => 400, 'easing' => 'easeOut'],
        'scaleIn' => ['keyframe' => 'scale', 'duration' => 200, 'easing' => 'easeIn'],
        'bounce' => ['keyframe' => 'bounce', 'duration' => 600, 'easing' => 'easeInOut'],
    ];

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::DURATION as $key => $value) {
            $styles["fl-transition-duration-$key"] = ['transition-duration' => $value . 'ms'];
        }
        foreach (self::EASING as $key => $value) {
            $styles["fl-transition-timing-$key"] = ['transition-timing-function' => $value];
        }
        foreach (self::PROPERTY as $key => $value) {
            $styles["fl-transition-property-$key"] = ['transition-property' => $value];
        }
        foreach (self::ANIMATION as $key => $value) {
            $styles["fl-animate-$key"] = [
                'animation' => sprintf(
                    '%s %sms %s',
                    $value['keyframe'],
                    $value['duration'],
                    $value['easing']
                ),
            ];
        }
        return $styles;
    }
}