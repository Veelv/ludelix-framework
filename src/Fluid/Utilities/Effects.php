<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class Effects implements UtilityInterface
{
    // Valores de opacity
    public const OPACITY_VALUES = [
        '0' => '0',
        '5' => '0.05',
        '10' => '0.1',
        '15' => '0.15',
        '20' => '0.2',
        '25' => '0.25',
        '30' => '0.3',
        '35' => '0.35',
        '40' => '0.4',
        '45' => '0.45',
        '50' => '0.5',
        '55' => '0.55',
        '60' => '0.6',
        '65' => '0.65',
        '70' => '0.7',
        '75' => '0.75',
        '80' => '0.8',
        '85' => '0.85',
        '90' => '0.9',
        '95' => '0.95',
        '100' => '1',
    ];

    // Valores de blur
    public const BLUR_VALUES = [
        'none' => '0',
        'sm' => '4px',
        '' => '8px',
        'md' => '12px',
        'lg' => '16px',
        'xl' => '24px',
        '2xl' => '40px',
        '3xl' => '64px',
    ];

    // Box shadows
    public const BOX_SHADOWS = [
        'sm' => '0 1px 2px 0 rgb(0 0 0 / 0.05)',
        '' => '0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)',
        'md' => '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
        'lg' => '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
        'xl' => '0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1)',
        '2xl' => '0 25px 50px -12px rgb(0 0 0 / 0.25)',
        'inner' => 'inset 0 2px 4px 0 rgb(0 0 0 / 0.05)',
        'none' => 'none',
    ];

    // Drop shadows
    public const DROP_SHADOWS = [
        'sm' => '0 1px 1px rgb(0 0 0 / 0.05)',
        '' => '0 1px 2px rgb(0 0 0 / 0.1), 0 1px 1px rgb(0 0 0 / 0.06)',
        'md' => '0 4px 3px rgb(0 0 0 / 0.07), 0 2px 2px rgb(0 0 0 / 0.06)',
        'lg' => '0 10px 8px rgb(0 0 0 / 0.04), 0 4px 3px rgb(0 0 0 / 0.1)',
        'xl' => '0 20px 13px rgb(0 0 0 / 0.03), 0 8px 5px rgb(0 0 0 / 0.08)',
        '2xl' => '0 25px 25px rgb(0 0 0 / 0.15)',
        'none' => 'none',
    ];

    // Brightness values
    public const BRIGHTNESS_VALUES = [
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
        '200' => '2',
    ];

    // Contrast values
    public const CONTRAST_VALUES = [
        '0' => '0',
        '50' => '.5',
        '75' => '.75',
        '100' => '1',
        '125' => '1.25',
        '150' => '1.5',
        '200' => '2',
    ];

    // Grayscale values
    public const GRAYSCALE_VALUES = [
        '0' => '0',
        '100' => '100%',
    ];

    // Hue rotate values
    public const HUE_ROTATE_VALUES = [
        '0' => '0deg',
        '15' => '15deg',
        '30' => '30deg',
        '60' => '60deg',
        '90' => '90deg',
        '180' => '180deg',
    ];

    // Invert values
    public const INVERT_VALUES = [
        '0' => '0',
        '100' => '100%',
    ];

    // Saturate values
    public const SATURATE_VALUES = [
        '0' => '0',
        '50' => '.5',
        '100' => '1',
        '150' => '1.5',
        '200' => '2',
    ];

    // Sepia values
    public const SEPIA_VALUES = [
        '0' => '0',
        '100' => '100%',
    ];

    public static function getStyles(): array
    {
        $styles = [];

        // OPACITY
        foreach (self::OPACITY_VALUES as $key => $value) {
            $styles["fl-opacity-$key"] = ['opacity' => $value];
        }

        // BOX SHADOW
        foreach (self::BOX_SHADOWS as $key => $value) {
            $shadowClass = $key === '' ? 'fl-shadow' : "fl-shadow-$key";
            $styles[$shadowClass] = ['box-shadow' => $value];
        }

        // DROP SHADOW
        foreach (self::DROP_SHADOWS as $key => $value) {
            $shadowClass = $key === '' ? 'fl-drop-shadow' : "fl-drop-shadow-$key";
            $styles[$shadowClass] = ['filter' => "drop-shadow($value)"];
        }

        // BLUR
        foreach (self::BLUR_VALUES as $key => $value) {
            $blurClass = $key === '' ? 'fl-blur' : "fl-blur-$key";
            $styles[$blurClass] = ['filter' => "blur($value)"];
        }

        // BRIGHTNESS
        foreach (self::BRIGHTNESS_VALUES as $key => $value) {
            $styles["fl-brightness-$key"] = ['filter' => "brightness($value)"];
        }

        // CONTRAST
        foreach (self::CONTRAST_VALUES as $key => $value) {
            $styles["fl-contrast-$key"] = ['filter' => "contrast($value)"];
        }

        // GRAYSCALE
        foreach (self::GRAYSCALE_VALUES as $key => $value) {
            $styles["fl-grayscale-$key"] = ['filter' => "grayscale($value)"];
        }
        $styles['fl-grayscale'] = ['filter' => 'grayscale(100%)'];

        // HUE ROTATE
        foreach (self::HUE_ROTATE_VALUES as $key => $value) {
            $styles["fl-hue-rotate-$key"] = ['filter' => "hue-rotate($value)"];
        }

        // INVERT
        foreach (self::INVERT_VALUES as $key => $value) {
            $styles["fl-invert-$key"] = ['filter' => "invert($value)"];
        }
        $styles['fl-invert'] = ['filter' => 'invert(100%)'];

        // SATURATE
        foreach (self::SATURATE_VALUES as $key => $value) {
            $styles["fl-saturate-$key"] = ['filter' => "saturate($value)"];
        }

        // SEPIA
        foreach (self::SEPIA_VALUES as $key => $value) {
            $styles["fl-sepia-$key"] = ['filter' => "sepia($value)"];
        }
        $styles['fl-sepia'] = ['filter' => 'sepia(100%)'];

        // BACKDROP BLUR
        foreach (self::BLUR_VALUES as $key => $value) {
            $backdropClass = $key === '' ? 'fl-backdrop-blur' : "fl-backdrop-blur-$key";
            $styles[$backdropClass] = ['backdrop-filter' => "blur($value)"];
        }

        // BACKDROP BRIGHTNESS
        foreach (self::BRIGHTNESS_VALUES as $key => $value) {
            $styles["fl-backdrop-brightness-$key"] = ['backdrop-filter' => "brightness($value)"];
        }

        // BACKDROP CONTRAST
        foreach (self::CONTRAST_VALUES as $key => $value) {
            $styles["fl-backdrop-contrast-$key"] = ['backdrop-filter' => "contrast($value)"];
        }

        // BACKDROP GRAYSCALE
        foreach (self::GRAYSCALE_VALUES as $key => $value) {
            $styles["fl-backdrop-grayscale-$key"] = ['backdrop-filter' => "grayscale($value)"];
        }
        $styles['fl-backdrop-grayscale'] = ['backdrop-filter' => 'grayscale(100%)'];

        // BACKDROP HUE ROTATE
        foreach (self::HUE_ROTATE_VALUES as $key => $value) {
            $styles["fl-backdrop-hue-rotate-$key"] = ['backdrop-filter' => "hue-rotate($value)"];
        }

        // BACKDROP INVERT
        foreach (self::INVERT_VALUES as $key => $value) {
            $styles["fl-backdrop-invert-$key"] = ['backdrop-filter' => "invert($value)"];
        }
        $styles['fl-backdrop-invert'] = ['backdrop-filter' => 'invert(100%)'];

        // BACKDROP OPACITY
        foreach (self::OPACITY_VALUES as $key => $value) {
            $styles["fl-backdrop-opacity-$key"] = ['backdrop-filter' => "opacity($value)"];
        }

        // BACKDROP SATURATE
        foreach (self::SATURATE_VALUES as $key => $value) {
            $styles["fl-backdrop-saturate-$key"] = ['backdrop-filter' => "saturate($value)"];
        }

        // BACKDROP SEPIA
        foreach (self::SEPIA_VALUES as $key => $value) {
            $styles["fl-backdrop-sepia-$key"] = ['backdrop-filter' => "sepia($value)"];
        }
        $styles['fl-backdrop-sepia'] = ['backdrop-filter' => 'sepia(100%)'];

        // MISC
        $styles['fl-filter-none'] = ['filter' => 'none'];
        $styles['fl-backdrop-filter-none'] = ['backdrop-filter' => 'none'];

        return $styles;
    }
}