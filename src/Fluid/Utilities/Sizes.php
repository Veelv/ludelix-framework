<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class Sizes implements UtilityInterface
{
    public const SIZES = [
        'xs' => 16,
        'sm' => 24,
        'md' => 32,
        'lg' => 40,
        'xl' => 48,
        '2xl' => 56,
        '3xl' => 64,
        '4xl' => 72,
        '5xl' => 80,
        '6xl' => 96,
        'px' => 1,
        '0' => 0,
        '0.5' => 2,
        '1' => 4,
        '1.5' => 6,
        '2' => 8,
        '2.5' => 10,
        '3' => 12,
        '3.5' => 14,
        '4' => 16,
        '5' => 20,
        '6' => 24,
        '7' => 28,
        '8' => 32,
        '9' => 36,
        '10' => 40,
        '11' => 44,
        '12' => 48,
        '14' => 56,
        '16' => 64,
        '20' => 80,
        '24' => 96,
        '28' => 112,
        '32' => 128,
        '36' => 144,
        '40' => 160,
        '44' => 176,
        '48' => 192,
        '52' => 208,
        '56' => 224,
        '60' => 240,
        '64' => 256,
        '72' => 288,
        '80' => 320,
        '96' => 384,
        '1/2' => '50%',
        '1/3' => '33.333333%',
        '2/3' => '66.666667%',
        '1/4' => '25%',
        '2/4' => '50%',
        '3/4' => '75%',
        '1/5' => '20%',
        '2/5' => '40%',
        '3/5' => '60%',
        '4/5' => '80%',
        '1/6' => '16.666667%',
        '2/6' => '33.333333%',
        '3/6' => '50%',
        '4/6' => '66.666667%',
        '5/6' => '83.333333%',
        '1/12' => '8.333333%',
        '2/12' => '16.666667%',
        '3/12' => '25%',
        '4/12' => '33.333333%',
        '5/12' => '41.666667%',
        '6/12' => '50%',
        '7/12' => '58.333333%',
        '8/12' => '66.666667%',
        '9/12' => '75%',
        '10/12' => '83.333333%',
        '11/12' => '91.666667%',
        'auto' => 'auto',
        'full' => '100%',
        'screen' => '100vh',
        'min' => 'min-content',
        'max' => 'max-content',
        'fit' => 'fit-content',
    ];

    public const MIN_WIDTH = [
        '0' => '0px',
        'full' => '100%',
        'min' => 'min-content',
        'max' => 'max-content',
        'fit' => 'fit-content',
        'xs' => '20rem',
        'sm' => '24rem',
        'md' => '28rem',
        'lg' => '32rem',
        'xl' => '36rem',
        '2xl' => '42rem',
        '3xl' => '48rem',
        '4xl' => '56rem',
        '5xl' => '64rem',
        '6xl' => '72rem',
        '7xl' => '80rem',
    ];

    public const MAX_WIDTH = [
        '0' => '0px',
        'none' => 'none',
        'full' => '100%',
        'min' => 'min-content',
        'max' => 'max-content',
        'fit' => 'fit-content',
        'prose' => '65ch',
        'xs' => '20rem',
        'sm' => '24rem',
        'md' => '28rem',
        'lg' => '32rem',
        'xl' => '36rem',
        '2xl' => '42rem',
        '3xl' => '48rem',
        '4xl' => '56rem',
        '5xl' => '64rem',
        '6xl' => '72rem',
        '7xl' => '80rem',
        'screen' => [
            'sm' => '640px',
            'md' => '768px',
            'lg' => '1024px',
            'xl' => '1280px',
            '2xl' => '1536px',
        ],
    ];

    public const MIN_HEIGHT = [
        '0' => '0px',
        'full' => '100%',
        'screen' => '100vh',
        'min' => 'min-content',
        'max' => 'max-content',
        'fit' => 'fit-content',
    ];

    public const MAX_HEIGHT = [
        '0' => '0px',
        'none' => 'none',
        'full' => '100%',
        'screen' => '100vh',
        'min' => 'min-content',
        'max' => 'max-content',
        'fit' => 'fit-content',
    ];

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::SIZES as $key => $value) {
            $styles["fl-w-$key"] = ['width' => is_numeric($value) ? $value . 'px' : $value];
            $styles["fl-h-$key"] = ['height' => is_numeric($value) ? $value . 'px' : $value];
        }
        foreach (self::MIN_WIDTH as $key => $value) {
            $styles["fl-min-w-$key"] = ['min-width' => $value];
        }
        foreach (self::MAX_WIDTH as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $styles["fl-max-w-$key-$subKey"] = ['max-width' => $subValue];
                }
            } else {
                $styles["fl-max-w-$key"] = ['max-width' => $value];
            }
        }
        foreach (self::MIN_HEIGHT as $key => $value) {
            $styles["fl-min-h-$key"] = ['min-height' => $value];
        }
        foreach (self::MAX_HEIGHT as $key => $value) {
            $styles["fl-max-h-$key"] = ['max-height' => $value];
        }
        return $styles;
    }
}