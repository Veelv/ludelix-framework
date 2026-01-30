<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class Breakpoints implements UtilityInterface
{
    public static function getStyles(): array
    {
        $styles = [];

        // Obter configuração
        try {
            $config = new \Ludelix\Fluid\Core\Config();
            $screens = $config->getCustomScreens();
            $prefix = $config->getPrefix();
        } catch (\Throwable $e) {
            $screens = [
                'sm' => '640px',
                'md' => '768px',
                'lg' => '1024px',
                'xl' => '1280px',
                '2xl' => '1536px',
            ];
            $prefix = 'fl-';
        }

        // 1. Gerar variáveis CSS para os breakpoints no :root
        $rootVars = [];
        foreach ($screens as $name => $width) {
            $rootVars["--{$prefix}screen-{$name}"] = $width;
        }
        $styles[':root'] = $rootVars;

        // 2. Gerar classe .container
        // A classe container define largura 100% e depois max-widths fixos em cada breakpoint
        $containerClass = $prefix . 'container';

        $styles[$containerClass] = [
            'width' => '100%',
            'margin-right' => 'auto',
            'margin-left' => 'auto',
            'padding-right' => '1rem', // padding padrão
            'padding-left' => '1rem',
        ];

        // Media queries para cada breakpoint
        // Nota: Em uma implementação real do motor, as media queries seriam tratadas separadamente.
        // Aqui, retornamos a definição de max-width que deve ser aplicada.
        // O motor Fluid precisaria saber lidar com '@media' nas chaves ou estruturar isso diferentemente.
        // Assumindo que o array de estilos suporta aninhamento ou chaves de media query:

        foreach ($screens as $name => $width) {
            $mediaQuery = "@media (min-width: {$width})";
            $styles[$mediaQuery] = [
                ".{$containerClass}" => [
                    'max-width' => $width
                ]
            ];
        }

        return $styles;
    }
}
