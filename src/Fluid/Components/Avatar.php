<?php
namespace Ludelix\Fluid\Components;

use Ludelix\Interface\Fluid\UtilityInterface;

class Avatar implements UtilityInterface
{
    public static function getStyles(): array
    {
        return [
            'fl-avatar' => [
                'position' => 'relative',
                'display' => 'inline-block',
                'width' => '3rem',
                'height' => '3rem',
                'border-radius' => '50%',
                'overflow' => 'hidden',
                'background-color' => '#e2e8f0',
            ]
        ];
    }
}
