<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class Spacing implements UtilityInterface
{
    // Valores de espaçamento (positivos)
    public const SPACING_VALUES = [
        'px' => '1px',
        '0' => '0px',
        '0.5' => '0.125rem',
        '1' => '0.25rem',
        '1.5' => '0.375rem',
        '2' => '0.5rem',
        '2.5' => '0.625rem',
        '3' => '0.75rem',
        '3.5' => '0.875rem',
        '4' => '1rem',
        '5' => '1.25rem',
        '6' => '1.5rem',
        '7' => '1.75rem',
        '8' => '2rem',
        '9' => '2.25rem',
        '10' => '2.5rem',
        '11' => '2.75rem',
        '12' => '3rem',
        '14' => '3.5rem',
        '16' => '4rem',
        '20' => '5rem',
        '24' => '6rem',
        '28' => '7rem',
        '32' => '8rem',
        '36' => '9rem',
        '40' => '10rem',
        '44' => '11rem',
        '48' => '12rem',
        '52' => '13rem',
        '56' => '14rem',
        '60' => '15rem',
        '64' => '16rem',
        '72' => '18rem',
        '80' => '20rem',
        '96' => '24rem',
        'auto' => 'auto',
    ];

    // Direções de espaçamento
    public const DIRECTIONS = [
        '' => ['margin', 'padding'], // Para m-4, p-4
        't' => ['margin-top', 'padding-top'],
        'r' => ['margin-right', 'padding-right'],
        'b' => ['margin-bottom', 'padding-bottom'],
        'l' => ['margin-left', 'padding-left'],
        'x' => ['margin-left', 'margin-right', 'padding-left', 'padding-right'],
        'y' => ['margin-top', 'margin-bottom', 'padding-top', 'padding-bottom'],
        's' => ['margin-inline-start', 'padding-inline-start'], // RTL support
        'e' => ['margin-inline-end', 'padding-inline-end'],
    ];

    public static function getStyles(): array
    {
        $styles = [];
        
        // Obter configuração personalizada
        try {
            $config = new \Ludelix\Fluid\Core\Config();
            $customSpacing = $config->getCustomSpacing();
            $prefix = $config->getPrefix();
        } catch (\Throwable $e) {
            $customSpacing = [];
            $prefix = 'fl-';
        }

        // Mesclar espaçamentos padrão com personalizados
        $allSpacing = array_merge(self::SPACING_VALUES, $customSpacing);
        
        foreach ($allSpacing as $size => $value) {
            foreach (self::DIRECTIONS as $direction => $properties) {
                
                // MARGIN
                $marginClass = $prefix . 'm' . ($direction ? $direction . '-' : '-') . $size;
                $negativeMarginClass = $prefix . '-m' . ($direction ? $direction . '-' : '-') . $size;
                
                if ($direction === '') {
                    // m-4
                    $styles[$marginClass] = ['margin' => $value];
                    if ($size !== '0' && $size !== 'auto') {
                        $styles[$negativeMarginClass] = ['margin' => '-' . $value];
                    }
                } elseif ($direction === 'x') {
                    // mx-4
                    $styles[$marginClass] = [
                        'margin-left' => $value,
                        'margin-right' => $value
                    ];
                    if ($size !== '0' && $size !== 'auto') {
                        $styles[$negativeMarginClass] = [
                            'margin-left' => '-' . $value,
                            'margin-right' => '-' . $value
                        ];
                    }
                } elseif ($direction === 'y') {
                    // my-4
                    $styles[$marginClass] = [
                        'margin-top' => $value,
                        'margin-bottom' => $value
                    ];
                    if ($size !== '0' && $size !== 'auto') {
                        $styles[$negativeMarginClass] = [
                            'margin-top' => '-' . $value,
                            'margin-bottom' => '-' . $value
                        ];
                    }
                } else {
                    // mt-4, mr-4, etc.
                    $property = $direction === 't' ? 'margin-top' :
                              ($direction === 'r' ? 'margin-right' :
                              ($direction === 'b' ? 'margin-bottom' :
                              ($direction === 'l' ? 'margin-left' :
                              ($direction === 's' ? 'margin-inline-start' : 'margin-inline-end'))));
                    
                    $styles[$marginClass] = [$property => $value];
                    if ($size !== '0' && $size !== 'auto') {
                        $styles[$negativeMarginClass] = [$property => '-' . $value];
                    }
                }

                // PADDING (apenas valores positivos)
                $paddingClass = $prefix . 'p' . ($direction ? $direction . '-' : '-') . $size;
                
                if ($direction === '') {
                    // p-4
                    $styles[$paddingClass] = ['padding' => $value];
                } elseif ($direction === 'x') {
                    // px-4
                    $styles[$paddingClass] = [
                        'padding-left' => $value,
                        'padding-right' => $value
                    ];
                } elseif ($direction === 'y') {
                    // py-4
                    $styles[$paddingClass] = [
                        'padding-top' => $value,
                        'padding-bottom' => $value
                    ];
                } else {
                    // pt-4, pr-4, etc.
                    $property = $direction === 't' ? 'padding-top' :
                              ($direction === 'r' ? 'padding-right' :
                              ($direction === 'b' ? 'padding-bottom' :
                              ($direction === 'l' ? 'padding-left' :
                              ($direction === 's' ? 'padding-inline-start' : 'padding-inline-end'))));
                    
                    $styles[$paddingClass] = [$property => $value];
                }
            }

            // SPACE BETWEEN (gap)
            $spaceXClass = $prefix . 'space-x-' . $size;
            $spaceYClass = $prefix . 'space-y-' . $size;
            
            $styles[$spaceXClass] = [
                '> :not([hidden]) ~ :not([hidden])' => [
                    'margin-left' => $value
                ]
            ];
            
            $styles[$spaceYClass] = [
                '> :not([hidden]) ~ :not([hidden])' => [
                    'margin-top' => $value
                ]
            ];

            // SPACE BETWEEN NEGATIVO
            if ($size !== '0' && $size !== 'auto') {
                $negativeSpaceXClass = $prefix . '-space-x-' . $size;
                $negativeSpaceYClass = $prefix . '-space-y-' . $size;
                
                $styles[$negativeSpaceXClass] = [
                    '> :not([hidden]) ~ :not([hidden])' => [
                        'margin-left' => '-' . $value
                    ]
                ];
                
                $styles[$negativeSpaceYClass] = [
                    '> :not([hidden]) ~ :not([hidden])' => [
                        'margin-top' => '-' . $value
                    ]
                ];
            }

            // GAP (para grid e flex)
            $gapClass = $prefix . 'gap-' . $size;
            $gapXClass = $prefix . 'gap-x-' . $size;
            $gapYClass = $prefix . 'gap-y-' . $size;

            $styles[$gapClass] = ['gap' => $value];
            $styles[$gapXClass] = ['column-gap' => $value];
            $styles[$gapYClass] = ['row-gap' => $value];
        }

        return $styles;
    }
}