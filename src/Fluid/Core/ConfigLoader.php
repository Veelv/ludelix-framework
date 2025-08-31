<?php

namespace Ludelix\Fluid\Core;

class ConfigLoader
{
    private string $configPath;
    private array $userConfig = [];
    private array $defaultConfig = [];

    public function __construct(string $configPath = null)
    {
        $this->configPath = $configPath ?: $this->findConfigPath();
        $this->loadConfigurations();
    }

    /**
     * Encontra o caminho do arquivo de configuração
     */
    private function findConfigPath(): string
    {
        // Tenta diferentes localizações
        $possiblePaths = [
            getcwd() . '/config/fluid.php',
            __DIR__ . '/../../../../../../config/fluid.php',
            dirname(__DIR__, 7) . '/config/fluid.php',
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Retorna path padrão mesmo se não existir
        return getcwd() . '/config/fluid.php';
    }

    /**
     * Carrega as configurações do usuário e padrão
     */
    private function loadConfigurations(): void
    {
        // Carregar configuração do usuário se existir
        if (file_exists($this->configPath)) {
            $this->userConfig = include $this->configPath;
        }

        // Configurações padrão
        $this->defaultConfig = $this->getDefaultConfig();
    }

    /**
     * Retorna as configurações padrão
     */
    private function getDefaultConfig(): array
    {
        return [
            'prefix' => 'fl-',
            'debug' => false,
            'theme' => [
                'colors' => [],
                'spacing' => [],
                'fontFamily' => [
                    'sans' => ['system-ui', 'sans-serif'],
                    'serif' => ['Georgia', 'serif'],
                    'mono' => ['monospace'],
                ],
                'fontSize' => [],
                'screens' => [
                    'sm' => '640px',
                    'md' => '768px', 
                    'lg' => '1024px',
                    'xl' => '1280px',
                    '2xl' => '1536px',
                ],
                'boxShadow' => [],
                'borderRadius' => [],
                'zIndex' => [],
                'transitionDuration' => [],
                'transitionTimingFunction' => [],
                'extend' => [],
            ],
            'variants' => [
                'extend' => [],
            ],
            'utilities' => [],
            'components' => [],
            'plugins' => [],
            'safelist' => [],
            'blocklist' => [],
        ];
    }

    /**
     * Obtém uma configuração específica com merge profundo
     */
    public function get(string $key, $default = null)
    {
        return $this->getFromConfig($this->getMergedConfig(), $key, $default);
    }

    /**
     * Obtém configuração mesclada (padrão + usuário)
     */
    public function getMergedConfig(): array
    {
        return $this->deepMerge($this->defaultConfig, $this->userConfig);
    }

    /**
     * Obtém configuração do tema com extensões aplicadas
     */
    public function getTheme(): array
    {
        $config = $this->getMergedConfig();
        $theme = $config['theme'] ?? [];
        $extend = $theme['extend'] ?? [];

        // Aplicar extensões
        foreach ($extend as $key => $values) {
            if (isset($theme[$key]) && is_array($theme[$key]) && is_array($values)) {
                $theme[$key] = $this->deepMerge($theme[$key], $values);
            } else {
                $theme[$key] = $values;
            }
        }

        // Remover 'extend' do tema final
        unset($theme['extend']);

        return $theme;
    }

    /**
     * Verifica se uma classe está na safelist
     */
    public function isInSafelist(string $class): bool
    {
        $safelist = $this->get('safelist', []);
        
        foreach ($safelist as $item) {
            if (is_string($item) && $item === $class) {
                return true;
            }
            
            if (is_array($item) && isset($item['pattern'])) {
                $pattern = trim($item['pattern'], '/');
                if (preg_match('/' . $pattern . '/', $class)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Verifica se uma classe está na blocklist
     */
    public function isInBlocklist(string $class): bool
    {
        $blocklist = $this->get('blocklist', []);
        return in_array($class, $blocklist);
    }

    /**
     * Obtém utilities personalizadas
     */
    public function getCustomUtilities(): array
    {
        return $this->get('utilities', []);
    }

    /**
     * Obtém componentes personalizados
     */
    public function getCustomComponents(): array
    {
        return $this->get('components', []);
    }

    /**
     * Obtém plugins registrados
     */
    public function getPlugins(): array
    {
        return $this->get('plugins', []);
    }

    /**
     * Obtém prefixo das classes
     */
    public function getPrefix(): string
    {
        return $this->get('prefix', 'fl-');
    }

    /**
     * Verifica se está em modo debug
     */
    public function isDebugMode(): bool
    {
        return $this->get('debug', false);
    }

    /**
     * Obtém valor de uma configuração usando notação de ponto
     */
    private function getFromConfig(array $config, string $key, $default = null)
    {
        $keys = explode('.', $key);
        $current = $config;

        foreach ($keys as $k) {
            if (!isset($current[$k])) {
                return $default;
            }
            $current = $current[$k];
        }

        return $current;
    }

    /**
     * Merge profundo de arrays
     */
    private function deepMerge(array $array1, array $array2): array
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->deepMerge($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Recarrega as configurações
     */
    public function reload(): void
    {
        $this->loadConfigurations();
    }

    /**
     * Obtém todas as cores configuradas (incluindo extensões)
     */
    public function getAllColors(): array
    {
        $theme = $this->getTheme();
        return $theme['colors'] ?? [];
    }

    /**
     * Obtém todos os espaçamentos configurados
     */
    public function getAllSpacing(): array
    {
        $theme = $this->getTheme();
        return $theme['spacing'] ?? [];
    }

    /**
     * Obtém todos os breakpoints configurados
     */
    public function getAllScreens(): array
    {
        $theme = $this->getTheme();
        return $theme['screens'] ?? [];
    }

    /**
     * Obtém todas as famílias de fonte configuradas
     */
    public function getAllFontFamilies(): array
    {
        $theme = $this->getTheme();
        return $theme['fontFamily'] ?? [];
    }

    /**
     * Obtém todos os tamanhos de fonte configurados
     */
    public function getAllFontSizes(): array
    {
        $theme = $this->getTheme();
        return $theme['fontSize'] ?? [];
    }
}