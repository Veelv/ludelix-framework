<?php
namespace Ludelix\Fluid\Components;

use Ludelix\Interface\Fluid\UtilityInterface;

class Modal implements UtilityInterface
{
    public static function getStyles(): array
    {
        return [
            // Container/Backdrop
            'fl-modal' => [
                'position' => 'fixed',
                'top' => '0',
                'left' => '0',
                'z-index' => '1050',
                'display' => 'none',
                'width' => '100%',
                'height' => '100%',
                'overflow-x' => 'hidden',
                'overflow-y' => 'auto',
                'outline' => '0',
                'background-color' => 'rgba(0, 0, 0, 0.5)',
                'backdrop-filter' => 'blur(4px)',
                'transition' => 'opacity 0.15s linear',
            ],
            // State: Open
            'fl-modal.show' => [
                'display' => 'block',
            ],
            // Dialog Wrapper
            'fl-modal-dialog' => [
                'position' => 'relative',
                'width' => 'auto',
                'margin' => '1.75rem auto',
                'pointer-events' => 'none',
                'max-width' => '500px',
                'transition' => 'transform 0.3s ease-out',
                'transform' => 'translate(0, -50px)',
            ],
            // State: Slide in
            'fl-modal.show .fl-modal-dialog' => [
                'transform' => 'none',
            ],
            // Content
            'fl-modal-content' => [
                'position' => 'relative',
                'display' => 'flex',
                'flex-direction' => 'column',
                'width' => '100%',
                'pointer-events' => 'auto',
                'background-color' => '#fff',
                'background-clip' => 'padding-box',
                'border' => '1px solid rgba(0, 0, 0, 0.2)',
                'border-radius' => '0.5rem',
                'outline' => '0',
                'box-shadow' => '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
            ],
            // Header
            'fl-modal-header' => [
                'display' => 'flex',
                'align-items' => 'center',
                'justify-content' => 'space-between',
                'padding' => '1rem 1rem',
                'border-bottom' => '1px solid #e2e8f0',
                'border-top-left-radius' => 'calc(0.5rem - 1px)',
                'border-top-right-radius' => 'calc(0.5rem - 1px)',
            ],
            // Title
            'fl-modal-title' => [
                'margin-bottom' => '0',
                'line-height' => '1.5',
                'font-size' => '1.25rem',
                'font-weight' => '600',
            ],
            // Body
            'fl-modal-body' => [
                'position' => 'relative',
                'flex' => '1 1 auto',
                'padding' => '1rem',
            ],
            // Footer
            'fl-modal-footer' => [
                'display' => 'flex',
                'flex-wrap' => 'wrap',
                'align-items' => 'center',
                'justify-content' => 'flex-end',
                'padding' => '0.75rem',
                'border-top' => '1px solid #e2e8f0',
                'border-bottom-right-radius' => 'calc(0.5rem - 1px)',
                'border-bottom-left-radius' => 'calc(0.5rem - 1px)',
                'gap' => '0.5rem',
            ],
        ];
    }
}
