<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Fluid\Core\Config;
use Ludelix\Interface\Fluid\UtilityInterface;

class Typography implements UtilityInterface
{
    public const FONT_FAMILY = [
        'heading' => 'System',
        'body' => 'System',
        'mono' => 'monospace',
    ];

    public const FONT_WEIGHT = [
        'thin' => '100',
        'extraLight' => '200',
        'light' => '300',
        'regular' => '400',
        'medium' => '500',
        'semiBold' => '600',
        'bold' => '700',
        'extraBold' => '800',
        'black' => '900',
    ];

    public const FONT_SIZE = [
        'xs' => 12,
        'sm' => 14,
        'base' => 16,
        'md' => 18,
        'lg' => 20,
        'xl' => 24,
        '2xl' => 30,
        '3xl' => 36,
        '4xl' => 40,
        '5xl' => 48,
        '6xl' => 60,
        '7xl' => 72,
        '8xl' => 96,
        '9xl' => 128,
    ];

    public const LINE_HEIGHT = [
        'none' => 1,
        'tight' => 1.25,
        'snug' => 1.375,
        'normal' => 1.5,
        'relaxed' => 1.625,
        'loose' => 2,
    ];

    /**
     * Retorna o estilo CSS para uma utility de tipografia
     */
    public static function getStyle(string $utility): ?string
    {
        // Processa tamanhos de fonte predefinidos (xs, sm, base, etc)
        if (isset(self::FONT_SIZE[$utility])) {
            $size = self::FONT_SIZE[$utility];
            $lineHeight = self::calculateLineHeight($size);
            return "font-size: {$size}px; line-height: {$lineHeight}px;";
        }

        // Processa pesos de fonte (bold, medium, etc)
        if (strpos($utility, 'font-') === 0) {
            $weight = substr($utility, 5);
            if (isset(self::FONT_WEIGHT[$weight])) {
                return "font-weight: " . self::FONT_WEIGHT[$weight] . ";";
            }
        }

        return null;
    }

    /**
     * Calcula a altura da linha baseada no tamanho da fonte
     */
    private static function calculateLineHeight(int $fontSize): float
    {
        return $fontSize * self::LINE_HEIGHT['normal'];
    }

    public const LETTER_SPACING = [
        'tighter' => -0.05,
        'tight' => -0.025,
        'normal' => 0,
        'wide' => 0.025,
        'wider' => 0.05,
        'widest' => 0.1,
    ];

    public const TEXT_ALIGN = [
        'left' => 'left',
        'center' => 'center',
        'right' => 'right',
        'justify' => 'justify',
    ];

    public const TEXT_TRANSFORM = [
        'none' => 'none',
        'uppercase' => 'uppercase',
        'lowercase' => 'lowercase',
        'capitalize' => 'capitalize',
    ];

    public const WHITE_SPACE = [
        'normal' => 'normal',
        'nowrap' => 'nowrap',
        'pre' => 'pre',
        'preWrap' => 'pre-wrap',
        'preLine' => 'pre-line',
    ];

    public const WORD_BREAK = [
        'normal' => 'normal',
        'breakAll' => 'break-all',
        'keepAll' => 'keep-all',
        'breakWord' => 'break-word',
    ];

    public const TEXT_OVERFLOW = [
        'clip' => 'clip',
        'ellipsis' => 'ellipsis',
    ];

    public const TEXT_STYLES = [
        'h1' => [
            'fontSize' => 36,
            'fontWeight' => '700',
            'lineHeight' => 1.2,
            'letterSpacing' => -0.025,
            'fontFamily' => 'heading',
        ],
        'h2' => [
            'fontSize' => 30,
            'fontWeight' => '600',
            'lineHeight' => 1.25,
            'letterSpacing' => -0.025,
            'fontFamily' => 'heading',
        ],
        'h3' => [
            'fontSize' => 24,
            'fontWeight' => '600',
            'lineHeight' => 1.3,
            'fontFamily' => 'heading',
        ],
        'h4' => [
            'fontSize' => 20,
            'fontWeight' => '600',
            'lineHeight' => 1.35,
            'fontFamily' => 'heading',
        ],
        'h5' => [
            'fontSize' => 18,
            'fontWeight' => '600',
            'lineHeight' => 1.4,
            'fontFamily' => 'heading',
        ],
        'h6' => [
            'fontSize' => 16,
            'fontWeight' => '600',
            'lineHeight' => 1.4,
            'fontFamily' => 'heading',
        ],
        'body1' => [
            'fontSize' => 16,
            'fontWeight' => '400',
            'lineHeight' => 1.5,
            'fontFamily' => 'body',
        ],
        'body2' => [
            'fontSize' => 14,
            'fontWeight' => '400',
            'lineHeight' => 1.43,
            'fontFamily' => 'body',
        ],
        'subtitle1' => [
            'fontSize' => 16,
            'fontWeight' => '500',
            'lineHeight' => 1.5,
            'fontFamily' => 'body',
        ],
        'subtitle2' => [
            'fontSize' => 14,
            'fontWeight' => '500',
            'lineHeight' => 1.43,
            'fontFamily' => 'body',
        ],
        'caption' => [
            'fontSize' => 12,
            'fontWeight' => '400',
            'lineHeight' => 1.33,
            'fontFamily' => 'body',
        ],
        'overline' => [
            'fontSize' => 10,
            'fontWeight' => '600',
            'lineHeight' => 1.6,
            'letterSpacing' => 0.5,
            'textTransform' => 'uppercase',
            'fontFamily' => 'body',
        ],
        'button' => [
            'fontSize' => 14,
            'fontWeight' => '500',
            'lineHeight' => 1.43,
            'letterSpacing' => 0.25,
            'textTransform' => 'uppercase',
            'fontFamily' => 'body',
        ],
        'code' => [
            'fontSize' => 14,
            'fontWeight' => '400',
            'lineHeight' => 1.5,
            'fontFamily' => 'mono',
        ],
    ];

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::FONT_FAMILY as $key => $value) {
            $styles["fl-font-$key"] = ['font-family' => $value];
        }
        foreach (self::FONT_WEIGHT as $key => $value) {
            $styles["fl-font-weight-$key"] = ['font-weight' => $value];
            // Atalhos estilo Tailwind para font-weight
            $styles["fl-font-$key"] = ['font-weight' => $value];
        }
        foreach (self::FONT_SIZE as $key => $value) {
            $styles["fl-text-$key"] = ['font-size' => $value . 'px'];
        }
        foreach (self::LINE_HEIGHT as $key => $value) {
            $styles["fl-leading-$key"] = ['line-height' => $value];
        }
        foreach (self::LETTER_SPACING as $key => $value) {
            $styles["fl-tracking-$key"] = ['letter-spacing' => $value . 'em'];
        }
        foreach (self::TEXT_ALIGN as $key => $value) {
            $styles["fl-text-align-$key"] = ['text-align' => $value];
        }
        foreach (self::TEXT_TRANSFORM as $key => $value) {
            $styles["fl-text-transform-$key"] = ['text-transform' => $value];
        }
        foreach (self::WHITE_SPACE as $key => $value) {
            $styles["fl-whitespace-$key"] = ['white-space' => $value];
        }
        foreach (self::WORD_BREAK as $key => $value) {
            $styles["fl-word-break-$key"] = ['word-break' => $value];
        }
        foreach (self::TEXT_OVERFLOW as $key => $value) {
            $styles["fl-text-overflow-$key"] = ['text-overflow' => $value];
        }
        foreach (self::TEXT_STYLES as $key => $value) {
            $styles["fl-text-style-$key"] = [
                'font-size' => $value['fontSize'] . 'px',
                'font-weight' => $value['fontWeight'],
                'line-height' => $value['lineHeight'],
                'letter-spacing' => isset($value['letterSpacing']) ? $value['letterSpacing'] . 'em' : null,
                'text-transform' => isset($value['textTransform']) ? $value['textTransform'] : null,
                'font-family' => $value['fontFamily'],
            ];
        }
        return $styles;
    }
}