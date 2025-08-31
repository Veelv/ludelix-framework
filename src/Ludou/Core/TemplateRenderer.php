<?php

namespace Ludelix\Ludou\Core;

use Ludelix\Interface\Template\TemplateRendererInterface;

/**
 * Template Renderer
 * 
 * Executes compiled templates with data context
 */
class TemplateRenderer implements TemplateRendererInterface
{
    protected array $globals = [];
    public array $functions = [];
    public array $filters = [];

    public function render(string $compiledTemplate, array $data = []): string
    {
        // Merge globals with data, ensuring all variables exist
        $templateData = array_merge($this->globals, $data);
        
        // Ensure all variables exist to prevent undefined variable warnings
        $defaults = [
            'title' => 'Ludelix Framework',
            'version' => '1.0.0',
            'environment' => 'development',
            'php_version' => PHP_VERSION,
            'server' => 'Built-in',

            'message' => '',
            'status' => '',
            'error' => '',
            'content' => '',
            'app' => null,
            'config' => null,
            'route' => null,
            'csrf_token' => '',
            'date' => date('Y-m-d H:i:s'),
            'asset' => '',
            'service' => null
        ];
        
        $templateData = array_merge($defaults, $templateData);
        
        // Extract variables safely
        extract($templateData, EXTR_SKIP);
        
        // Make renderer available for compiled template
        $renderer = $this;
        $__sections = [];
        $__currentSection = null;
        $__slots = [];
        $__currentSlot = null;
        
        ob_start();
        eval('?>' . $compiledTemplate);
        $html = ob_get_clean();
        
        // APLICAR FLUID HOOK APÓS RENDERIZAÇÃO COMPLETA
        $html = $this->processFluidClasses($html);
        
        return $html;
    }

    private function processFluidClasses(string $html): string
    {
        try {
            // Aplicação direta do Fluid
            $config = new \Ludelix\Fluid\Core\Config();
            
            // Lista de utilities disponíveis no diretório
            $utilities = [
                // Layout
                \Ludelix\Fluid\Utilities\Display::class,
                \Ludelix\Fluid\Utilities\Flex::class,
                \Ludelix\Fluid\Utilities\FlexGrid::class,
                \Ludelix\Fluid\Utilities\ContainerQueries::class,
                \Ludelix\Fluid\Utilities\ContentVisibility::class,
                
                // Posicionamento
                \Ludelix\Fluid\Utilities\Position::class,
                \Ludelix\Fluid\Utilities\Overflow::class,
                \Ludelix\Fluid\Utilities\Align::class,
                \Ludelix\Fluid\Utilities\Justify::class,
                
                // Tamanhos e Espaçamentos
                \Ludelix\Fluid\Utilities\Sizes::class,
                \Ludelix\Fluid\Utilities\Spacing::class,
                \Ludelix\Fluid\Utilities\AspectRatio::class,
                
                // Tipografia
                \Ludelix\Fluid\Utilities\Typography::class,
                \Ludelix\Fluid\Utilities\TextShadow::class,
                \Ludelix\Fluid\Utilities\Decoration::class,
                
                // Cores e Fundos
                \Ludelix\Fluid\Utilities\Colors::class,
                \Ludelix\Fluid\Utilities\Backgrounds::class,
                \Ludelix\Fluid\Utilities\Gradients::class,
                \Ludelix\Fluid\Utilities\Opacities::class,
                
                // Bordas
                \Ludelix\Fluid\Utilities\Borders::class,
                \Ludelix\Fluid\Utilities\BorderWidths::class,
                
                // Efeitos
                \Ludelix\Fluid\Utilities\Effects::class,
                \Ludelix\Fluid\Utilities\Filters::class,
                \Ludelix\Fluid\Utilities\BackdropFilters::class,
                \Ludelix\Fluid\Utilities\Shadows::class,
                
                // Transformações
                \Ludelix\Fluid\Utilities\Transform::class,
                \Ludelix\Fluid\Utilities\Transforms::class,
                \Ludelix\Fluid\Utilities\Transitions::class,
                
                // Interatividade
                \Ludelix\Fluid\Utilities\TouchActions::class,
                \Ludelix\Fluid\Utilities\Cursors::class,
                \Ludelix\Fluid\Utilities\Radio::class,
                
                // Imagens
                \Ludelix\Fluid\Utilities\Image::class,
                \Ludelix\Fluid\Utilities\ClipPath::class,
                
                // Pseudo-classes e estados
                \Ludelix\Fluid\Utilities\PseudoClasses::class,
            ];
            
            // Filtra apenas as classes que existem
            $utilities = array_filter($utilities, function($class) {
                return class_exists($class);
            });
            
            $generator = new \Ludelix\Fluid\Core\AdvancedGenerator($config, $utilities);
            $result = $generator->processHTML($html);
            
            // Se o resultado estiver vazio, retorna o HTML original
            return !empty(trim($result)) ? $result : $html;
            
        } catch (\Throwable $e) {
            error_log('[Ludou] Error processing Fluid: ' . $e->getMessage());
            error_log($e->getTraceAsString());
            return $html; // Retorna HTML original em caso de erro
        }
    }

    public function setGlobals(array $globals): void
    {
        $this->globals = $globals;
    }

    public function addGlobal(string $key, mixed $value): void
    {
        $this->globals[$key] = $value;
    }

    public function setFunctions(array $functions): void
    {
        $this->functions = $functions;
    }

    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }


}