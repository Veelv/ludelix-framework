<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class Decoration implements UtilityInterface
{
    public const DECORATION_UTILITIES = [
        'line' => [
            'none' => 'none',
            'underline' => 'underline',
            'line-through' => 'line-through',
            'overline' => 'overline',
        ],
        'style' => [
            'solid' => 'solid',
            'double' => 'double',
            'dotted' => 'dotted',
            'dashed' => 'dashed',
            'wavy' => 'wavy',
        ],
        'thickness' => [
            'auto' => 'auto',
            'thin' => '1px',
            'medium' => '2px',
            'thick' => '4px',
        ]
    ];

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::DECORATION_UTILITIES as $property => $values) {
            foreach ($values as $key => $value) {
                if ($property === 'line') {
                    $styles["fl-decoration-$key"] = ["text-decoration" => $value];
                } else {
                    $styles["fl-decoration-$property-$key"] = ["text-decoration-$property" => $value];
                }
            }
        }
        return $styles;
    }
}
