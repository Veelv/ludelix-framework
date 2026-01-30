<?php
namespace Ludelix\Fluid\Components;

use Ludelix\Interface\Fluid\UtilityInterface;

class Tooltip implements UtilityInterface
{
    public static function getStyles(): array
    {
        return [
            'fl-tooltip' => [
                'position' => 'absolute',
                'z-index' => '1070',
                'display' => 'block',
                'margin' => '0',
                'font-family' => 'var(--fl-font-sans, system-ui, sans-serif)',
                'font-style' => 'normal',
                'font-weight' => '400',
                'line-height' => '1.5',
                'text-align' => 'left',
                'text-decoration' => 'none',
                'text-shadow' => 'none',
                'text-transform' => 'none',
                'letter-spacing' => 'normal',
                'word-break' => 'normal',
                'white-space' => 'normal',
                'word-spacing' => 'normal',
                'line-break' => 'auto',
                'font-size' => '0.875rem',
                'word-wrap' => 'break-word',
                'opacity' => '0',
                'transition' => 'opacity 0.2s',
            ],
            'fl-tooltip.show' => [
                'opacity' => '1',
            ],
            'fl-tooltip-inner' => [
                'max-width' => '200px',
                'padding' => '0.25rem 0.5rem',
                'color' => '#fff',
                'text-align' => 'center',
                'background-color' => '#000',
                'border-radius' => '0.25rem',
            ],
            'fl-tooltip-arrow' => [
                'position' => 'absolute',
                'display' => 'block',
                'width' => '0.8rem',
                'height' => '0.4rem',
            ],
            'fl-tooltip-arrow::before' => [
                'position' => 'absolute',
                'content' => '""',
                'border-color' => 'transparent',
                'border-style' => 'solid',
            ]
        ];
    }
}
