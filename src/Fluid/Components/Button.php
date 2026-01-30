<?php
namespace Ludelix\Fluid\Components;

use Ludelix\Interface\Fluid\UtilityInterface;

class Button implements UtilityInterface
{
    /**
     * Define default styles for the Button component.
     * 
     * @return array
     */
    public static function getStyles(): array
    {
        return [
            'fl-btn' => [
                'display' => 'inline-flex',
                'align-items' => 'center',
                'justify-content' => 'center',
                'padding' => '0.5rem 1rem',
                'font-weight' => '500',
                'border-radius' => '0.375rem',
                'transition' => 'all 0.2s',
                'cursor' => 'pointer',
                'border' => '1px solid transparent',
            ],
            'fl-btn-primary' => [
                'background-color' => 'var(--fl-color-primary)',
                'color' => '#ffffff',
            ],
            'fl-btn-secondary' => [
                'background-color' => 'var(--fl-color-secondary)',
                'color' => '#ffffff',
            ],
            'fl-btn-outline' => [
                'background-color' => 'transparent',
                'border-color' => 'currentColor',
            ]
        ];
    }
}
