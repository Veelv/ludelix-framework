<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class Colors implements UtilityInterface
{
    // Cores básicas estilo Tailwind
    public const COLORS = [
        // Grayscale
        'cloud' => [
            50 => '#fafcfd',
            100 => '#f3f6f9',
            200 => '#e5e9f0',
            300 => '#d0d6e2',
            400 => '#9aa4ba',
            500 => '#6a768d',
            600 => '#4d586f',
            700 => '#3a455b',
            800 => '#252f3e',
            900 => '#161c2d',
            950 => '#070912',
        ],
        'smoke' => [
            50 => '#fbfbfc',
            100 => '#f5f6f8',
            200 => '#e8eaee',
            300 => '#d6d9df',
            400 => '#a3a8b3',
            500 => '#727781',
            600 => '#535861',
            700 => '#40454e',
            800 => '#272c37',
            900 => '#181b24',
            950 => '#090c11',
        ],
        'steel' => [
            50 => '#fcfcfc',
            100 => '#f6f7f8',
            200 => '#e9eaed',
            300 => '#d8dade',
            400 => '#a5a9b2',
            500 => '#747880',
            600 => '#545860',
            700 => '#41444c',
            800 => '#282b31',
            900 => '#191b20',
            950 => '#0b0c0f',
        ],
        'charcoal' => [
            50 => '#fbfbf9',
            100 => '#f6f6f4',
            200 => '#e9e8e6',
            300 => '#d7d6d3',
            400 => '#a9a7a3',
            500 => '#797670',
            600 => '#595650',
            700 => '#46433f',
            800 => '#2b2927',
            900 => '#1d1b19',
            950 => '#0e0c0a',
        ],
        'rock' => [
            50 => '#fbfaf8',
            100 => '#f6f5f3',
            200 => '#e9e8e5',
            300 => '#d8d6d2',
            400 => '#aaa8a4',
            500 => '#7a7771',
            600 => '#5a5650',
            700 => '#46433e',
            800 => '#2b2926',
            900 => '#1e1c18',
            950 => '#0f0d0a',
        ],

        // Vibrant Colors
        'cherry' => [
            50 => '#fff2f4',
            100 => '#fee4e7',
            200 => '#ffccd1',
            300 => '#fda8ae',
            400 => '#fb757b',
            500 => '#f5424c',
            600 => '#e22631',
            700 => '#bf1c26',
            800 => '#9e1721',
            900 => '#84191d',
            950 => '#4a0b10',
        ],
        'coral' => [
            50 => '#fff7ed',
            100 => '#ffedd6',
            200 => '#fed8ac',
            300 => '#fdbf76',
            400 => '#fb963e',
            500 => '#f97618',
            600 => '#ea5b0d',
            700 => '#c3450b',
            800 => '#9c370f',
            900 => '#7e3011',
            950 => '#451809',
        ],
        'sunset' => [
            50 => '#fffeeb',
            100 => '#fef3c6',
            200 => '#fde78a',
            300 => '#fcd44d',
            400 => '#fbc024',
            500 => '#f59f0c',
            600 => '#d67906',
            700 => '#b35509',
            800 => '#93420e',
            900 => '#793610',
            950 => '#451c04',
        ],
        'lemon' => [
            50 => '#fefde8',
            100 => '#fef9c4',
            200 => '#fef089',
            300 => '#fddf48',
            400 => '#facc16',
            500 => '#e9ac08',
            600 => '#cb8c05',
            700 => '#a56407',
            800 => '#874f0e',
            900 => '#724112',
            950 => '#432206',
        ],
        'mint' => [
            50 => '#f8fee7',
            100 => '#eefccb',
            200 => '#dbf99e',
            300 => '#c1f365',
            400 => '#a6e536',
            500 => '#87cc17',
            600 => '#68a30e',
            700 => '#507d0f',
            800 => '#426213',
            900 => '#385415',
            950 => '#1c2f06',
        ],
        'forest' => [
            50 => '#f1fdf4',
            100 => '#dffce7',
            200 => '#bdf7d1',
            300 => '#8aefad',
            400 => '#4ede81',
            500 => '#25c55f',
            600 => '#19a34b',
            700 => '#17803e',
            800 => '#186535',
            900 => '#16532e',
            950 => '#072e16',
        ],
        'emerald' => [
            50 => '#eefdf5',
            100 => '#d3fae5',
            200 => '#aaf3d1',
            300 => '#71e7b8',
            400 => '#37d39a',
            500 => '#13b982',
            600 => '#08966a',
            700 => '#067858',
            800 => '#076046',
            900 => '#074e3c',
            950 => '#032c22',
        ],
        'turquoise' => [
            50 => '#f1fdfa',
            100 => '#cefbf1',
            200 => '#9cf6e4',
            300 => '#60ead4',
            400 => '#2fd4bf',
            500 => '#15b8a7',
            600 => '#0e9489',
            700 => '#10766f',
            800 => '#125e5a',
            900 => '#144e4b',
            950 => '#052f2f',
        ],
        'sky' => [
            50 => '#edfeff',
            100 => '#d0faff',
            200 => '#a6f3fd',
            300 => '#69e8fa',
            400 => '#24d3ef',
            500 => '#07b6d5',
            600 => '#0a91b3',
            700 => '#0f7491',
            800 => '#165e76',
            900 => '#174e64',
            950 => '#093344',
        ],
        'ocean' => [
            50 => '#f1f9ff',
            100 => '#e1f2fe',
            200 => '#bbe6fd',
            300 => '#7ed3fc',
            400 => '#3abdf8',
            500 => '#0fa5ea',
            600 => '#0384c8',
            700 => '#0469a2',
            800 => '#085986',
            900 => '#0d4a6f',
            950 => '#082f4a',
        ],
        'sapphire' => [
            50 => '#f0f6ff',
            100 => '#dcfafe',
            200 => '#c0dbfe',
            300 => '#94c5fd',
            400 => '#61a5fa',
            500 => '#3c82f7',
            600 => '#2663ec',
            700 => '#1e4ed9',
            800 => '#1f40b0',
            900 => '#1f3a8b',
            950 => '#182555',
        ],
        'lavender' => [
            50 => '#f0f2ff',
            100 => '#e1e7ff',
            200 => '#c8d2fe',
            300 => '#a6b4fc',
            400 => '#828cf8',
            500 => '#6466f2',
            600 => '#5046e6',
            700 => '#4438cb',
            800 => '#3830a4',
            900 => '#322e82',
            950 => '#1f1b4c',
        ],
        'plum' => [
            50 => '#f6f3ff',
            100 => '#eee9fe',
            200 => '#ded6fe',
            300 => '#c5b5fd',
            400 => '#a88bfb',
            500 => '#8c5cf7',
            600 => '#7d3aee',
            700 => '#6e28da',
            800 => '#5c21b7',
            900 => '#4d1d96',
            950 => '#2f1066',
        ],
        'violet' => [
            50 => '#fbf5ff',
            100 => '#f4e8ff',
            200 => '#ead5ff',
            300 => '#d9b4fe',
            400 => '#c185fc',
            500 => '#a956f8',
            600 => '#9433eb',
            700 => '#7d3aee',
            800 => '#6c21a9',
            900 => '#591c88',
            950 => '#3c0765',
        ],
        'blossom' => [
            50 => '#fef4ff',
            100 => '#fae8ff',
            200 => '#f5d0ff',
            300 => '#f0abfc',
            400 => '#e879f9',
            500 => '#da46f0',
            600 => '#c526d4',
            700 => '#a51cb0',
            800 => '#871990',
            900 => '#711a76',
            950 => '#4b044f',
        ],
        'rose' => [
            50 => '#fef2f9',
            100 => '#fce7f4',
            200 => '#fbcee9',
            300 => '#f9a8d5',
            400 => '#f472b7',
            500 => '#ed489a',
            600 => '#dc2778',
            700 => '#bf185e',
            800 => '#9e174e',
            900 => '#841844',
            950 => '#510725',
        ],
        'ruby' => [
            50 => '#fff1f3',
            100 => '#ffe4e7',
            200 => '#fecdd4',
            300 => '#fda4b0',
            400 => '#fb7186',
            500 => '#f43f5f',
            600 => '#e21d49',
            700 => '#bf123d',
            800 => '#9f123a',
            900 => '#881338',
            950 => '#4d051a',
        ],

        // Special Colors
        'white' => '#ffffff',
        'black' => '#000000',
        'transparent' => 'transparent',
        'current' => 'currentColor',
    ];

    public static function getStyles(): array
    {
        $styles = [];

        // Obter configuração personalizada
        try {
            $config = new \Ludelix\Fluid\Core\Config();
            $customColors = $config->getCustomColors();
            $prefix = $config->getPrefix();
        } catch (\Throwable $e) {
            $customColors = [];
            $prefix = 'fl-';
        }

        // Mesclar cores padrão com personalizadas
        $allColors = array_merge(self::COLORS, $customColors);

        // Gerar apenas estilos de texto (color)
        foreach ($allColors as $color => $shades) {
            if (is_array($shades)) {
                foreach ($shades as $shade => $value) {
                    // Text colors apenas
                    $styles["{$prefix}text-$color-$shade"] = ['color' => $value];
                    // Ring colors (focus rings) - mantém pois é específico para acessibilidade
                    $styles["{$prefix}ring-$color-$shade"] = ['--fl-ring-color' => $value];
                }
            } else {
                // Cores especiais (white, black, etc.)
                $styles["{$prefix}text-$color"] = ['color' => $shades];
                $styles["{$prefix}ring-$color"] = ['--fl-ring-color' => $shades];
            }
        }

        return $styles;
    }
}