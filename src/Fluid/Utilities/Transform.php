<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class Transform implements UtilityInterface
{
    // Valores de scale
    public const SCALE_VALUES = [
        '0' => '0',
        '50' => '.5',
        '75' => '.75',
        '90' => '.9',
        '95' => '.95',
        '100' => '1',
        '105' => '1.05',
        '110' => '1.1',
        '125' => '1.25',
        '150' => '1.5',
    ];

    // Valores de rotate
    public const ROTATE_VALUES = [
        '0' => '0deg',
        '1' => '1deg',
        '2' => '2deg',
        '3' => '3deg',
        '6' => '6deg',
        '12' => '12deg',
        '45' => '45deg',
        '90' => '90deg',
        '180' => '180deg',
    ];

    // Valores de translate
    public const TRANSLATE_VALUES = [
        'px' => '1px',
        '0' => '0px',
        '0.5' => '0.125rem',
        '1' => '0.25rem',
        '1.5' => '0.375rem',
        '2' => '0.5rem',
        '2.5' => '0.625rem',
        '3' => '0.75rem',
        '3.5' => '0.875rem',
        '4' => '1rem',
        '5' => '1.25rem',
        '6' => '1.5rem',
        '7' => '1.75rem',
        '8' => '2rem',
        '9' => '2.25rem',
        '10' => '2.5rem',
        '11' => '2.75rem',
        '12' => '3rem',
        '14' => '3.5rem',
        '16' => '4rem',
        '20' => '5rem',
        '24' => '6rem',
        '28' => '7rem',
        '32' => '8rem',
        '36' => '9rem',
        '40' => '10rem',
        '44' => '11rem',
        '48' => '12rem',
        '52' => '13rem',
        '56' => '14rem',
        '60' => '15rem',
        '64' => '16rem',
        '72' => '18rem',
        '80' => '20rem',
        '96' => '24rem',
        '1/2' => '50%',
        '1/3' => '33.333333%',
        '2/3' => '66.666667%',
        '1/4' => '25%',
        '2/4' => '50%',
        '3/4' => '75%',
        'full' => '100%',
    ];

    // Valores de skew
    public const SKEW_VALUES = [
        '0' => '0deg',
        '1' => '1deg',
        '2' => '2deg',
        '3' => '3deg',
        '6' => '6deg',
        '12' => '12deg',
    ];

    public static function getStyles(): array
    {
        $styles = [];

        // Transform utility base
        $styles['fl-transform'] = ['transform' => 'translateVar(--fl-translate-x, 0) translateY(var(--fl-translate-y, 0)) rotate(var(--fl-rotate, 0)) skewX(var(--fl-skew-x, 0)) skewY(var(--fl-skew-y, 0)) scaleX(var(--fl-scale-x, 1)) scaleY(var(--fl-scale-y, 1))'];
        $styles['fl-transform-cpu'] = ['transform' => 'translateVar(--fl-translate-x, 0) translateY(var(--fl-translate-y, 0)) rotate(var(--fl-rotate, 0)) skewX(var(--fl-skew-x, 0)) skewY(var(--fl-skew-y, 0)) scaleX(var(--fl-scale-x, 1)) scaleY(var(--fl-scale-y, 1))'];
        $styles['fl-transform-gpu'] = [
            'transform' => 'translate3d(var(--fl-translate-x, 0), var(--fl-translate-y, 0), 0) rotate(var(--fl-rotate, 0)) skewX(var(--fl-skew-x, 0)) skewY(var(--fl-skew-y, 0)) scaleX(var(--fl-scale-x, 1)) scaleY(var(--fl-scale-y, 1))'
        ];
        $styles['fl-transform-none'] = ['transform' => 'none'];

        // SCALE
        foreach (self::SCALE_VALUES as $key => $value) {
            $styles["fl-scale-$key"] = [
                '--fl-scale-x' => $value,
                '--fl-scale-y' => $value,
                'transform' => 'translateVar(--fl-translate-x, 0) translateY(var(--fl-translate-y, 0)) rotate(var(--fl-rotate, 0)) skewX(var(--fl-skew-x, 0)) skewY(var(--fl-skew-y, 0)) scaleX(var(--fl-scale-x, 1)) scaleY(var(--fl-scale-y, 1))'
            ];
            $styles["fl-scale-x-$key"] = [
                '--fl-scale-x' => $value,
                'transform' => 'translateVar(--fl-translate-x, 0) translateY(var(--fl-translate-y, 0)) rotate(var(--fl-rotate, 0)) skewX(var(--fl-skew-x, 0)) skewY(var(--fl-skew-y, 0)) scaleX(var(--fl-scale-x, 1)) scaleY(var(--fl-scale-y, 1))'
            ];
            $styles["fl-scale-y-$key"] = [
                '--fl-scale-y' => $value,
                'transform' => 'translateVar(--fl-translate-x, 0) translateY(var(--fl-translate-y, 0)) rotate(var(--fl-rotate, 0)) skewX(var(--fl-skew-x, 0)) skewY(var(--fl-skew-y, 0)) scaleX(var(--fl-scale-x, 1)) scaleY(var(--fl-scale-y, 1))'
            ];
        }

        // ROTATE
        foreach (self::ROTATE_VALUES as $key => $value) {
            $styles["fl-rotate-$key"] = [
                '--fl-rotate' => $value,
                'transform' => 'translateVar(--fl-translate-x, 0) translateY(var(--fl-translate-y, 0)) rotate(var(--fl-rotate, 0)) skewX(var(--fl-skew-x, 0)) skewY(var(--fl-skew-y, 0)) scaleX(var(--fl-scale-x, 1)) scaleY(var(--fl-scale-y, 1))'
            ];
            if ($key !== '0') {
                $styles["fl--rotate-$key"] = [
                    '--fl-rotate' => '-' . $value,
                    'transform' => 'translateVar(--fl-translate-x, 0) translateY(var(--fl-translate-y, 0)) rotate(var(--fl-rotate, 0)) skewX(var(--fl-skew-x, 0)) skewY(var(--fl-skew-y, 0)) scaleX(var(--fl-scale-x, 1)) scaleY(var(--fl-scale-y, 1))'
                ];
            }
        }

        // TRANSLATE
        foreach (self::TRANSLATE_VALUES as $key => $value) {
            $styles["fl-translate-x-$key"] = [
                '--fl-translate-x' => $value,
                'transform' => 'translateVar(--fl-translate-x, 0) translateY(var(--fl-translate-y, 0)) rotate(var(--fl-rotate, 0)) skewX(var(--fl-skew-x, 0)) skewY(var(--fl-skew-y, 0)) scaleX(var(--fl-scale-x, 1)) scaleY(var(--fl-scale-y, 1))'
            ];
            $styles["fl-translate-y-$key"] = [
                '--fl-translate-y' => $value,
                'transform' => 'translateVar(--fl-translate-x, 0) translateY(var(--fl-translate-y, 0)) rotate(var(--fl-rotate, 0)) skewX(var(--fl-skew-x, 0)) skewY(var(--fl-skew-y, 0)) scaleX(var(--fl-scale-x, 1)) scaleY(var(--fl-scale-y, 1))'
            ];
            
            if ($key !== '0') {
                $styles["fl--translate-x-$key"] = [
                    '--fl-translate-x' => '-' . $value,
                    'transform' => 'translateVar(--fl-translate-x, 0) translateY(var(--fl-translate-y, 0)) rotate(var(--fl-rotate, 0)) skewX(var(--fl-skew-x, 0)) skewY(var(--fl-skew-y, 0)) scaleX(var(--fl-scale-x, 1)) scaleY(var(--fl-scale-y, 1))'
                ];
                $styles["fl--translate-y-$key"] = [
                    '--fl-translate-y' => '-' . $value,
                    'transform' => 'translateVar(--fl-translate-x, 0) translateY(var(--fl-translate-y, 0)) rotate(var(--fl-rotate, 0)) skewX(var(--fl-skew-x, 0)) skewY(var(--fl-skew-y, 0)) scaleX(var(--fl-scale-x, 1)) scaleY(var(--fl-scale-y, 1))'
                ];
            }
        }

        // SKEW
        foreach (self::SKEW_VALUES as $key => $value) {
            $styles["fl-skew-x-$key"] = [
                '--fl-skew-x' => $value,
                'transform' => 'translateVar(--fl-translate-x, 0) translateY(var(--fl-translate-y, 0)) rotate(var(--fl-rotate, 0)) skewX(var(--fl-skew-x, 0)) skewY(var(--fl-skew-y, 0)) scaleX(var(--fl-scale-x, 1)) scaleY(var(--fl-scale-y, 1))'
            ];
            $styles["fl-skew-y-$key"] = [
                '--fl-skew-y' => $value,
                'transform' => 'translateVar(--fl-translate-x, 0) translateY(var(--fl-translate-y, 0)) rotate(var(--fl-rotate, 0)) skewX(var(--fl-skew-x, 0)) skewY(var(--fl-skew-y, 0)) scaleX(var(--fl-scale-x, 1)) scaleY(var(--fl-scale-y, 1))'
            ];
            
            if ($key !== '0') {
                $styles["fl--skew-x-$key"] = [
                    '--fl-skew-x' => '-' . $value,
                    'transform' => 'translateVar(--fl-translate-x, 0) translateY(var(--fl-translate-y, 0)) rotate(var(--fl-rotate, 0)) skewX(var(--fl-skew-x, 0)) skewY(var(--fl-skew-y, 0)) scaleX(var(--fl-scale-x, 1)) scaleY(var(--fl-scale-y, 1))'
                ];
                $styles["fl--skew-y-$key"] = [
                    '--fl-skew-y' => '-' . $value,
                    'transform' => 'translateVar(--fl-translate-x, 0) translateY(var(--fl-translate-y, 0)) rotate(var(--fl-rotate, 0)) skewX(var(--fl-skew-x, 0)) skewY(var(--fl-skew-y, 0)) scaleX(var(--fl-scale-x, 1)) scaleY(var(--fl-scale-y, 1))'
                ];
            }
        }

        // Transform origin
        $origins = [
            'center' => 'center',
            'top' => 'top',
            'top-right' => 'top right',
            'right' => 'right',
            'bottom-right' => 'bottom right',
            'bottom' => 'bottom',
            'bottom-left' => 'bottom left',
            'left' => 'left',
            'top-left' => 'top left',
        ];

        foreach ($origins as $key => $value) {
            $styles["fl-origin-$key"] = ['transform-origin' => $value];
        }

        return $styles;
    }
}