<?php
namespace Ludelix\Fluid\Integration;

use Ludelix\Fluid\Core\Parser;
use Ludelix\Fluid\Core\Compiler;
use Ludelix\Fluid\Core\Generator;
use Ludelix\Fluid\Core\AdvancedGenerator;

class LudouHook
{
    private Parser $parser;
    private Compiler $compiler;
    private AdvancedGenerator $generator;

    public function __construct(Parser $parser, Compiler $compiler, AdvancedGenerator $generator)
    {
        $this->parser = $parser;
        $this->compiler = $compiler;
        $this->generator = $generator;
    }

    /**
     * Processa diretivas Fluid em templates Ludou ANTES da renderização
     */
    public function beforeRender(string $content): string
    {
        // Primeiro, converte #[...] para class="..."
        $processedContent = preg_replace_callback('/#\[(.*?)\]/', function($matches) {
            // Pega as classes dentro de #[...]
            $classString = $matches[1];
            
            // Se não há classes Fluid, retorna como está
            if (strpos($classString, 'fl-') === false) {
                return 'class="' . $classString . '"';
            }

            // Mantém as classes fl-* (NÃO remove o prefixo!)
            $classes = explode('|', $classString);
            $processedClasses = [];

            foreach ($classes as $class) {
                $class = trim($class);
                if (!empty($class)) {
                    $processedClasses[] = $class;
                }
            }

            return 'class="' . implode(' ', $processedClasses) . '"';
        }, $content);

        // Agora processa o HTML para extrair classes Fluid e gerar CSS
        try {
            return $this->generator->processHTML($processedContent);
        } catch (\Throwable $e) {
            // Log error if logger is available
            try {
                $logger = \Ludelix\Bridge\Bridge::instance()->get('logger');
                if ($logger) {
                    $logger->error('[Fluid] Error processing HTML: ' . $e->getMessage());
                }
            } catch (\Throwable $e) {
                // Silently fail if logger is not available
            }
            
            // Return processed content without CSS injection if processing fails
            return $processedContent;
        }
    }

    /**
     * Processa o HTML final DEPOIS da renderização para injetar CSS
     */
    public function afterRender(string $html): string
    {
        try {
            // Usa o Generator para processar o HTML e injetar CSS
            return $this->generator->processHTML($html);
        } catch (\Throwable $e) {
            // Log error if logger is available
            try {
                $logger = \Ludelix\Bridge\Bridge::instance()->get('logger');
                if ($logger) {
                    $logger->error('[Fluid] Error processing HTML: ' . $e->getMessage());
                }
            } catch (\Throwable $e) {
                // Silently fail if logger is not available
            }
            
            // Return original HTML if processing fails
            return $html;
        }
    }
}