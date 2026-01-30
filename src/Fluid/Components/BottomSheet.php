<?php
namespace Ludelix\Fluid\Components;

use Ludelix\Interface\Fluid\UtilityInterface;

class BottomSheet implements UtilityInterface
{
    public static function getStyles(): array
    {
        return [
            // Container/Backdrop
            'fl-bottomsheet' => [
                'position' => 'fixed',
                'top' => '0',
                'left' => '0',
                'z-index' => '1050',
                'display' => 'none',
                'width' => '100%',
                'height' => '100%',
                'background-color' => 'rgba(0, 0, 0, 0.5)',
                'backdrop-filter' => 'blur(4px)',
                'transition' => 'opacity 0.3s ease',
            ],
            // State: Open
            'fl-bottomsheet.show' => [
                'display' => 'block',
            ],
            // Content Wrapper (The sliding part)
            'fl-bottomsheet-content' => [
                'position' => 'absolute',
                'bottom' => '0',
                'left' => '0',
                'width' => '100%',
                'background-color' => '#fff',
                'border-top-left-radius' => '1.25rem',
                'border-top-right-radius' => '1.25rem',
                'padding' => '1.5rem',
                'transform' => 'translateY(100%)',
                'transition' => 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
                'box-shadow' => '0 -10px 25px rgba(0, 0, 0, 0.1)',
            ],
            // State: Show sliding
            'fl-bottomsheet.show .fl-bottomsheet-content' => [
                'transform' => 'translateY(0)',
            ],
            // Handle (The little bar at the top for visual affordance)
            'fl-bottomsheet-handle' => [
                'width' => '40px',
                'height' => '4px',
                'background-color' => '#e2e8f0',
                'border-radius' => '2px',
                'margin' => '0 auto 1.5rem auto',
            ],
            // Header
            'fl-bottomsheet-header' => [
                'display' => 'flex',
                'align-items' => 'center',
                'justify-content' => 'space-between',
                'margin-bottom' => '1rem',
            ],
            // Title
            'fl-bottomsheet-title' => [
                'font-size' => '1.25rem',
                'font-weight' => '600',
                'color' => '#1a202c',
                'margin' => '0',
            ],
            // Body
            'fl-bottomsheet-body' => [
                'margin-bottom' => '1.5rem',
            ]
        ];
    }
}
