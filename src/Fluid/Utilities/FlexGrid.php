<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Fluid\Core\Config;
use Ludelix\Interface\Fluid\UtilityInterface;

class FlexGrid implements UtilityInterface
{
    public const FLEX = [
        1 => 1,
        'auto' => '1 1 auto',
        'initial' => '0 1 auto',
        'none' => 'none',
        'wrap' => [
            'noWrap' => 'nowrap',
            'wrap' => 'wrap',
            'wrapReverse' => 'wrap-reverse',
        ],
        'direction' => [
            'row' => 'row',
            'rowReverse' => 'row-reverse',
            'column' => 'column',
            'columnReverse' => 'column-reverse',
        ],
        'grow' => [
            0 => 0,
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
        ],
        'shrink' => [
            0 => 0,
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
        ],
        'basis' => [
            0 => '0%',
            1 => '8.333333%',
            2 => '16.666667%',
            3 => '25%',
            4 => '33.333333%',
            5 => '41.666667%',
            6 => '50%',
            7 => '58.333333%',
            8 => '66.666667%',
            9 => '75%',
            10 => '83.333333%',
            11 => '91.666667%',
            12 => '100%',
            'auto' => 'auto',
            'full' => '100%',
            'px' => '1px',
            '0.5' => '2px',
            '1.5' => '6px',
            '2.5' => '10px',
            '3.5' => '14px',
        ],
    ];

    public const GRID = [
        'columns' => [
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            6 => 6,
            8 => 8,
            12 => 12,
        ],
        'gap' => [],
        'container' => [
            'sm' => 480,
            'md' => 768,
            'lg' => 1024,
            'xl' => 1280,
            'max' => '100%',
        ],
        'templateAreas' => [
            'none' => 'none',
        ],
        'autoFlow' => [
            'row' => 'row',
            'column' => 'column',
            'dense' => 'dense',
        ],
        'row' => [
            'auto' => 'auto',
            'span1' => 'span 1',
            'span2' => 'span 2',
            'span3' => 'span 3',
        ],
        'column' => [
            'auto' => 'auto',
            'span1' => 'span 1',
            'span2' => 'span 2',
            'span3' => 'span 3',
        ],
    ];

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::FLEX as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $styles["fl-flex-$key-$subKey"] = ["flex-$key" => $subValue];
                }
            } else {
                $styles["fl-flex-$key"] = ['flex' => $value];
            }
        }
        foreach (self::GRID as $key => $value) {
            if (is_array($value) && $key !== 'templateAreas') {
                foreach ($value as $subKey => $subValue) {
                    $styles["fl-grid-$key-$subKey"] = ["grid-$key" => $subValue];
                }
            }
        }
        return $styles;
    }
}