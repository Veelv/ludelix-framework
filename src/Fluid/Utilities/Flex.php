<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class Flex implements UtilityInterface
{
    public const DISPLAY = [
        'flex' => 'flex',
        'inline-flex' => 'inline-flex'
    ];

    public const DIRECTION = [
        'row' => 'row',
        'row-reverse' => 'row-reverse',
        'col' => 'column',
        'col-reverse' => 'column-reverse'
    ];

    public const WRAP = [
        'wrap' => 'wrap',
        'wrap-reverse' => 'wrap-reverse',
        'nowrap' => 'nowrap'
    ];

    public const GROW = [
        '0' => '0',
        '1' => '1'
    ];

    public const SHRINK = [
        '0' => '0',
        '1' => '1'
    ];

    /**
     * Retorna todos os estilos CSS para utilities flex
     */
    public static function getStyles(): array
    {
        $styles = [];

        // Display
        foreach (self::DISPLAY as $key => $value) {
            $styles[$key] = ['display' => $value];
        }

        // Direction
        foreach (self::DIRECTION as $key => $value) {
            $styles["flex-{$key}"] = ['flex-direction' => $value];
        }

        // Wrap
        foreach (self::WRAP as $key => $value) {
            $styles["flex-{$key}"] = ['flex-wrap' => $value];
        }

        // Grow
        foreach (self::GROW as $key => $value) {
            $styles["flex-grow-{$key}"] = ['flex-grow' => $value];
        }

        // Shrink
        foreach (self::SHRINK as $key => $value) {
            $styles["flex-shrink-{$key}"] = ['flex-shrink' => $value];
        }

        return $styles;
    }
}
