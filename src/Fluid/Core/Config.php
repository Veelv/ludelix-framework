<?php
namespace Ludelix\Fluid\Core;

use Ludelix\Fluid\Utilities\Colors;
use Ludelix\Fluid\Utilities\Cursors;
use Ludelix\Fluid\Utilities\Spacing;
use Ludelix\Fluid\Utilities\Sizes;
use Ludelix\Fluid\Utilities\Radio;
use Ludelix\Fluid\Utilities\Shadows;
use Ludelix\Fluid\Utilities\Decoration;
use Ludelix\Fluid\Utilities\Typography;
use Ludelix\Fluid\Utilities\Breakpoints;
use Ludelix\Fluid\Utilities\ZIndices;
use Ludelix\Fluid\Utilities\Transitions;
use Ludelix\Fluid\Utilities\Opacities;
use Ludelix\Fluid\Utilities\BorderWidths;
use Ludelix\Fluid\Utilities\Borders;
use Ludelix\Fluid\Utilities\Backgrounds;
use Ludelix\Fluid\Utilities\Gradients;
use Ludelix\Fluid\Utilities\FlexGrid;
use Ludelix\Fluid\Utilities\Display;
use Ludelix\Fluid\Utilities\Flex;
use Ludelix\Fluid\Utilities\Justify;
use Ludelix\Fluid\Utilities\Align;
use Ludelix\Fluid\Utilities\Position;
use Ludelix\Fluid\Utilities\Overflow;
use Ludelix\Fluid\Utilities\Transform;
use Ludelix\Fluid\Utilities\Effects;
use Ludelix\Fluid\Utilities\Filters;
use Ludelix\Fluid\Utilities\AspectRatio;
use Ludelix\Fluid\Utilities\Outlines;
use Ludelix\Fluid\Utilities\TextShadow;
use Ludelix\Fluid\Components\Button;
use Ludelix\Fluid\Components\Input;
use Ludelix\Fluid\Components\Card;
use Ludelix\Fluid\Components\BottomSheet;
use Ludelix\Fluid\Components\Badge;
use Ludelix\Fluid\Components\Avatar;
use Ludelix\Fluid\Components\Tooltip;
use Ludelix\Fluid\Components\Modal;
use Ludelix\Fluid\Utilities\Utils;



class Config
{
    private ConfigLoader $configLoader;

    /**
     * Utilities registradas para geração de CSS dinâmico.
     * Cada classe deve ter um método getStyles() que retorna as regras CSS.
     */
    private static array $utilityClasses = [
        'colors' => Colors::class,
        'bg' => Backgrounds::class,
        'spacing' => Spacing::class,
        'sizes' => Sizes::class,
        'shadows' => Shadows::class,
        'typography' => Typography::class,
        'breakpoints' => Breakpoints::class,
        'zIndices' => ZIndices::class,
        'transitions' => Transitions::class,
        'opacities' => Opacities::class,
        'borderWidths' => BorderWidths::class,
        'borders' => Borders::class,
        'gradients' => Gradients::class,
        'flexGrid' => FlexGrid::class,
        'display' => Display::class,
        'flex' => Flex::class,
        'justify' => Justify::class,
        'align' => Align::class,
        'decoration' => Decoration::class,
        'radius' => Radio::class,
        'cursors' => Cursors::class,
        'position' => Position::class,
        'overflow' => Overflow::class,
        'transform' => Transform::class,
        'effects' => Effects::class,
        'filters' => Filters::class,
        'aspectRatio' => AspectRatio::class,
        'outlines' => Outlines::class,
        'textShadow' => TextShadow::class,
        // Componentes
        'button' => Button::class,
        'input' => Input::class,
        'card' => Card::class,
        'badge' => Badge::class,
        'avatar' => Avatar::class,
        'tooltip' => Tooltip::class,
        'modal' => Modal::class,
        'bottomsheet' => BottomSheet::class,
    ];

    public function __construct()
    {
        $this->configLoader = new ConfigLoader();
    }

    /**
     * Tema padrão que define como as utilities são combinadas e usadas.
     */
    private static array $theme = [
        // Cores e estilos visuais
        'colors' => Colors::class,
        'bg' => Backgrounds::class,
        'spacing' => Spacing::class,
        'radius' => Radio::class,
        'shadows' => Shadows::class,

        'decoration' => Decoration::class,
        'cursors' => Cursors::class,

        // Tipografia e texto
        'typography' => Typography::class,

        // Layout e dimensionamento
        'sizes' => [
            'base' => Sizes::class,
            'width' => Sizes::SIZES,
            'height' => Sizes::SIZES,
            'min' => [
                'width' => Sizes::MIN_WIDTH,
                'height' => Sizes::MIN_HEIGHT
            ],
            'max' => [
                'width' => Sizes::MAX_WIDTH,
                'height' => Sizes::MAX_HEIGHT
            ]
        ],

        // Layout e posicionamento
        'layout' => [
            'display' => Display::class,
            'flex' => Flex::class,
            'grid' => FlexGrid::class,
            'justify' => [
                'content' => Justify::JUSTIFY_CONTENT,
                'items' => Justify::JUSTIFY_ITEMS,
                'self' => Justify::JUSTIFY_SELF
            ],
            'align' => [
                'items' => Align::ALIGN_ITEMS,
                'content' => Align::ALIGN_CONTENT,
                'self' => Align::ALIGN_SELF
            ]
        ],

        // Bordas e efeitos
        'borders' => BorderWidths::class,

        // Utilitários de layout
        'utils' => [
            'transitions' => Transitions::class,
            'opacity' => Opacities::class
        ],

        // Componentes (Restaurados)
        'components' => [
            'button' => Button::class,
            'input' => Input::class,
            'card' => Card::class,
            'badge' => Badge::class,
            'avatar' => Avatar::class,
            'tooltip' => Tooltip::class,
            'modal' => Modal::class,
            'bottomsheet' => BottomSheet::class
        ]
    ];

    /**
     * Retorna todas as classes de utilidade registradas
     *
     * @return array Lista de classes Utility
     */
    public function getUtilities(): array
    {
        return array_values(self::$utilityClasses);
    }

    /**
     * Retorna os estilos CSS de uma utility específica
     *
     * @param string $utility Nome da utility (ex: 'colors', 'spacing')
     * @return array|null Estilos CSS ou null se não encontrado
     */
    public function getUtilityStyles(string $utility): ?array
    {
        if (isset(self::$utilityClasses[$utility])) {
            $class = self::$utilityClasses[$utility];
            return method_exists($class, 'getStyles') ? $class::getStyles() : null;
        }
        return null;
    }

    /**
     * Acessa uma configuração específica do tema usando notação de ponto
     *
     * @param string $path Caminho da configuração (ex.: 'colors.primary', 'layout.flex')
     * @param mixed $default Valor padrão se não encontrado
     * @return mixed
     */
    public function get(string $path, $default = null)
    {
        // Primeiro tenta obter da configuração do usuário
        $userValue = $this->configLoader->get($path);
        if ($userValue !== null) {
            return $userValue;
        }

        // Fallback para o sistema original
        $keys = explode('.', $path);
        $current = self::$theme;

        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                return $default;
            }
            $current = $current[$key];

            // Se for uma classe, obtem os estilos/configuração
            if (is_string($current) && class_exists($current)) {
                $current = method_exists($current, 'getStyles')
                    ? $current::getStyles()
                    : (method_exists($current, 'getConfig') ? $current::getConfig() : []);
            }
        }

        return $current;
    }

    /**
     * Define uma configuração no tema
     *
     * @param string $path Caminho da configuração
     * @param mixed $value Valor a ser definido
     */
    public function set(string $path, $value): void
    {
        $keys = explode('.', $path);
        $current = &self::$theme;

        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                $current[$key] = $value;
            } else {
                if (!isset($current[$key]) || !is_array($current[$key])) {
                    $current[$key] = [];
                }
                $current = &$current[$key];
            }
        }
    }

    /**
     * Obtém o ConfigLoader
     */
    public function getConfigLoader(): ConfigLoader
    {
        return $this->configLoader;
    }

    /**
     * Obtém cores personalizadas do config/fluid.php
     */
    public function getCustomColors(): array
    {
        return $this->configLoader->getAllColors();
    }

    /**
     * Obtém espaçamentos personalizados
     */
    public function getCustomSpacing(): array
    {
        return $this->configLoader->getAllSpacing();
    }

    /**
     * Obtém breakpoints personalizados
     */
    public function getCustomScreens(): array
    {
        return $this->configLoader->getAllScreens();
    }

    /**
     * Obtém utilities personalizadas
     */
    public function getCustomUtilities(): array
    {
        return $this->configLoader->getCustomUtilities();
    }

    /**
     * Obtém componentes personalizados
     */
    public function getCustomComponents(): array
    {
        return $this->configLoader->getCustomComponents();
    }

    /**
     * Obtém prefixo das classes
     */
    public function getPrefix(): string
    {
        return $this->configLoader->getPrefix();
    }

    /**
     * Verifica se está em modo debug
     */
    public function isDebugMode(): bool
    {
        return $this->configLoader->isDebugMode();
    }
}