<?php
namespace Ludelix\Fluid\Components;

use Ludelix\Interface\Fluid\UtilityInterface;

class Card implements UtilityInterface
{
    public static function getStyles(): array
    {
        return [
            'fl-card' => [
                'position' => 'relative',
                'display' => 'flex',
                'flex-direction' => 'column',
                'min-width' => '0',
                'word-wrap' => 'break-word',
                'background-color' => '#fff',
                'background-clip' => 'border-box',
                'border' => '1px solid rgba(0,0,0,.125)',
                'border-radius' => '0.25rem',
            ]
        ];
    }
}
