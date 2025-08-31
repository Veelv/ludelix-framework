<?php
// vendor/ludelix/framework/src/Fluid/Theme.php
// Classe para gerenciamento de temas do Fluid, importando Config.php.
// Permite selecionar e customizar temas, com integração ao Ludelix Framework e Ludou templates.

namespace Ludelix\Fluid\Core;

use Ludelix\Fluid\Core\Config;
use InvalidArgumentException;

class Theme
{
    private Config $config;
    private string $currentTheme = 'default';
    private array $customConfig = [];

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Obtém os estilos CSS para uma utility específica
     */
    public function getStyles(string $utility): ?string
    {
        // Procura na lista de utilities registradas
        foreach ($this->config->getUtilities() as $utilityClass) {
            if (method_exists($utilityClass, 'getStyle')) {
                $style = $utilityClass::getStyle($utility);
                if ($style !== null) {
                    return $style;
                }
            }
        }
        return null;
    }

    /**
     * Obtém os valores do tema atual
     */
    public function getThemeValues(): array
    {
        return [
            'colors' => [
                'accent' => [
                    '50' => 'var(--fl-accent-50)',
                    '100' => 'var(--fl-accent-100)',
                    '200' => 'var(--fl-accent-200)',
                    '300' => 'var(--fl-accent-300)',
                    '400' => 'var(--fl-accent-400)',
                    '500' => 'var(--fl-accent-500)',
                    '600' => 'var(--fl-accent-600)',
                    '700' => 'var(--fl-accent-700)',
                    '800' => 'var(--fl-accent-800)',
                    '900' => 'var(--fl-accent-900)',
                ]
            ]
        ];
    }

    /**
     * Acessa uma configuração específica do tema atual.
     *
     * @param string $path Caminho da configuração (ex.: 'colors.primary')
     * @param mixed $default Valor padrão
     * @return mixed
     */
    public function get(string $path, $default = null)
    {
        // Combina customConfig com getThemeValues
        $themeConfig = array_replace_recursive($this->getThemeValues(), $this->customConfig);
        
        $keys = explode('.', $path);
        $current = $themeConfig;

        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                return $default;
            }
            $current = $current[$key];
        }

        return $current;
    }

    /**
     * Define uma configuração customizada para o tema atual.
     *
     * @param string $path Caminho da configuração
     * @param mixed $value Valor
     * @return void
     */
    public function set(string $path, $value): void
    {
        $keys = explode('.', $path);
        $current = &$this->customConfig;

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
     * Mescla configurações base com customizações do tema.
     *
     * @param array $base Configuração base
     * @param array $custom Configuração customizada
     * @return array
     */
    private function mergeConfigs(array $base, array $custom): array
    {
        $merged = $base;
        foreach ($custom as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->mergeConfigs($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
        return $merged;
    }
}