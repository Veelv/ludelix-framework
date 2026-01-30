<?php
namespace Ludelix\Fluid\Components;

use Ludelix\Interface\Fluid\UtilityInterface;

class Input implements UtilityInterface
{
    public static function getStyles(): array
    {
        return [
            'fl-input' => [
                'display' => 'block',
                'width' => '100%',
                'padding' => '0.5rem 0.75rem',
                'font-size' => '1rem',
                'line-height' => '1.5',
                'color' => 'var(--fl-color-text)',
                'background-color' => '#fff',
                'background-clip' => 'padding-box',
                'border' => '1px solid #ced4da',
                'border-radius' => '0.375rem',
                'transition' => 'border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out',
            ]
        ];
    }
}
