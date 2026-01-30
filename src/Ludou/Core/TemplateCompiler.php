<?php

namespace Ludelix\Ludou\Core;

use Ludelix\Interface\Template\TemplateCompilerInterface;

/**
 * SharpTemplate Compiler
 * 
 * Compiles .ludou templates with Sharp syntax (#[]) into executable PHP code
 */
class TemplateCompiler implements TemplateCompilerInterface
{
    protected array $directives = [];
    protected ?\Ludelix\Fluid\Integration\LudouHook $fluidHook = null;

    public function __construct()
    {
        $this->registerDirectives();
        $this->initializeFluid();
    }

    protected function initializeFluid(): void
    {
        if ($this->fluidHook === null && class_exists('\\Ludelix\\Fluid\\Integration\\LudouHook')) {
            try {
                // Usar o Bridge para obter as instâncias corretas do Fluid
                if (class_exists('\\Ludelix\\Bridge\\Bridge')) {
                    $bridge = \Ludelix\Bridge\Bridge::instance();
                    if ($bridge->has(\Ludelix\Fluid\Integration\LudouHook::class)) {
                        $this->fluidHook = $bridge->make(\Ludelix\Fluid\Integration\LudouHook::class);
                        error_log('[Ludou] FluidHook inicializado com sucesso');
                    } else {
                        error_log('[Ludou] Bridge não tem LudouHook');
                    }
                }
            } catch (\Throwable $e) {
                // Log error if logger is available
                try {
                    $logger = \Ludelix\Bridge\Bridge::instance()->get('logger');
                    if ($logger) {
                        $logger->error('[Ludou] Failed to initialize Fluid: ' . $e->getMessage());
                    }
                } catch (\Throwable $e) {
                }
            }
        }
    }

    public function compile(string $template, array $functions = [], array $filters = []): string
    {
        // Simple approach - functions and filters will be available in renderer context
        $compiled = "<?php\nglobal \$__slots;\nif (!isset(\$__slots)) \$__slots = [];\n\$__currentSlot = null;\n// Template compiled\n?>\n";

        // APLICAR FLUID HOOK ANTES de processar as expressões Sharp
        $logger = null;
        try {
            $logger = \Ludelix\Bridge\Bridge::instance()->get('logger');
        } catch (\Throwable $e) {
        }

        if ($this->fluidHook !== null) {
            try {
                if ($logger)
                    $logger->debug('[Ludou] Aplicando Fluid beforeRender...');
                $template = $this->fluidHook->beforeRender($template);
                if ($logger)
                    $logger->debug('[Ludou] Fluid beforeRender aplicado com sucesso');
            } catch (\Throwable $e) {
                if ($logger)
                    $logger->error('[Ludou] Error processing Fluid beforeRender: ' . $e->getMessage());
            }
        } else {
            if ($logger)
                $logger->debug('[Ludou] FluidHook é NULL - aplicando fallback manual');
            // Fallback manual para processar classes fl-*
            $template = preg_replace_callback('/#\[([^\]]*fl-[^\]]*)\]/', function ($matches) {
                $classes = str_replace('|', ' ', $matches[1]);
                return 'class="' . $classes . '"';
            }, $template);
            if ($logger)
                $logger->debug('[Ludou] Fallback manual aplicado');
        }

        // Remove Ludou comments (#[-- ... --])
        $template = $this->compileComments($template);

        // Process Connect directives BEFORE Sharp expressions
        $template = $this->compileConnectDirectives($template);

        // Process Sharp expressions #[...] APÓS aplicar Fluid
        $template = preg_replace_callback('/#\[([^\]]+)\]/', function ($matches) {
            return $this->compileExpression(trim($matches[1]));
        }, $template);

        // Log início do parser
        $logger = null;
        try {
            $logger = \Ludelix\Bridge\Bridge::instance()->get('logger');
        } catch (\Throwable $e) {
        }
        if ($logger) {
            $logger->debug('[Ludou] Iniciando parsing de slots');
        }
        // Suporte a #slot $variavel
        $template = preg_replace_callback('/#slot\s*\$([a-zA-Z0-9_]+)/', function ($matches) {
            return "<?php ob_start(); \$__currentSlot = \strval(\${$matches[1]}); ?>";
        }, $template);
        // Suporte a #slot['nome']
        $template = preg_replace_callback('/#slot\s*\[\'([^\']+)\'\]/', function ($matches) {
            return "<?php ob_start(); \$__currentSlot = '{$matches[1]}'; ?>";
        }, $template);
        $template = preg_replace('/#endslot/', '<?php $__slots[$__currentSlot] = ob_get_clean(); ?>', $template);
        // Suporte a #yield nome
        $template = preg_replace_callback('/#yield\s+([a-zA-Z_][a-zA-Z0-9_]*)/', function ($matches) {
            $name = $matches[1];
            return "<?php echo isset(\$__slots['$name']) ? \$__slots['$name'] : ''; ?>";
        }, $template);
        // Suporte a #yield['nome']
        $template = preg_replace_callback('/#yield\s*\[\'([^\']+)\'\]/', function ($matches) {
            $name = $matches[1];
            return "<?php echo isset(\$__slots['$name']) ? \$__slots['$name'] : ''; ?>";
        }, $template);
        // Suporte a #yield $variavel
        $template = preg_replace_callback('/#yield\s*\$([a-zA-Z0-9_]+)/', function ($matches) {
            $name = "\${$matches[1]}";
            return "<?php echo isset(\$__slots[$name]) ? \$__slots[$name] : ''; ?>";
        }, $template);
        // Suporte a fallback: #yield nome ?? 'padrão' ou #yield nome default 'padrão'
        $template = preg_replace_callback('/#yield\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\?\?\s*([\'\"])(.*?)\2/', function ($matches) {
            $name = $matches[1];
            $default = $matches[3];
            return "<?php echo isset(\$__slots['$name']) ? \$__slots['$name'] : '$default'; ?>";
        }, $template);
        $template = preg_replace_callback('/#yield\s+([a-zA-Z_][a-zA-Z0-9_]*)\s+default\s+([\'\"])(.*?)\2/', function ($matches) {
            $name = $matches[1];
            $default = $matches[3];
            return "<?php echo isset(\$__slots['$name']) ? \$__slots['$name'] : '$default'; ?>";
        }, $template);
        if ($logger) {
            $logger->debug('[Ludou] Parsing de slots finalizado');
        }

        // Check if template extends a layout
        $layoutName = null;
        $layoutContent = '';
        if (preg_match("/#extends\['([^']+)'\]/", $template, $matches)) {
            $layoutName = $matches[1];
            $template = preg_replace("/#extends\['([^']+)'\]/", '', $template);

            // Load layout
            $layoutPath = $this->findLayoutPath($layoutName);
            if ($logger) {
                $logger->debug('Layout path: ' . $layoutPath);
            }
            if ($layoutPath) {
                $layoutContent = file_get_contents($layoutPath);

                // APLICAR FLUID HOOK NO LAYOUT TAMBÉM
                if ($this->fluidHook !== null) {
                    try {
                        $layoutContent = $this->fluidHook->beforeRender($layoutContent);
                    } catch (\Throwable $e) {
                        // Silently fail
                    }
                } else {
                    // Fallback manual para processar classes fl-* no layout
                    $layoutContent = preg_replace_callback('/#\[([^\]]*fl-[^\]]*)\]/', function ($matches) {
                        $classes = str_replace('|', ' ', $matches[1]);
                        return 'class="' . $classes . '"';
                    }, $layoutContent);
                }

                // Process Sharp expressions in layout APÓS aplicar Fluid
                $layoutContent = preg_replace_callback('/#\[([^\]]+)\]/', function ($matches) {
                    return $this->compileExpression(trim($matches[1]));
                }, $layoutContent);

                // Process yield in layout
                $layoutContent = preg_replace_callback('/#yield\s+(\w+)/', function ($matches) {
                    $sectionName = $matches[1];
                    return "<?php echo isset(\$__slots['$sectionName']) ? \$__slots['$sectionName'] : ''; ?>";
                }, $layoutContent);
            }
        }

        // Process directives with proper capture groups
        $template = preg_replace_callback('/#if\s*\(([^)]+)\)/', function ($matches) {
            $condition = $matches[1];
            // Add $ to variables that don't already have it
            $condition = preg_replace('/(?<!\$)\b([a-zA-Z_][a-zA-Z0-9_]*)\b(?!\s*[\(\'"])/', '$$1', $condition);
            return "<?php if ($condition): ?>";
        }, $template);

        // Suporte para #if[condição] com colchetes
        $template = preg_replace_callback('/#if\s*\[([^\]]+)\]/', function ($matches) {
            $condition = $matches[1];
            // Add $ to variables that don't already have it
            $condition = preg_replace('/(?<!\$)\b([a-zA-Z_][a-zA-Z0-9_]*)\b(?!\s*[\(\'"])/', '$$1', $condition);
            return "<?php if ($condition): ?>";
        }, $template);

        $template = preg_replace_callback('/#elseif\s*\(([^)]+)\)/', function ($matches) {
            $condition = $matches[1];
            // Add $ to variables that don't already have it
            $condition = preg_replace('/(?<!\$)\b([a-zA-Z_][a-zA-Z0-9_]*)\b(?!\s*[\(\'"])/', '$$1', $condition);
            return "<?php elseif ($condition): ?>";
        }, $template);

        // Suporte para #elseif[condição] com colchetes
        $template = preg_replace_callback('/#elseif\s*\[([^\]]+)\]/', function ($matches) {
            $condition = $matches[1];
            // Add $ to variables that don't already have it
            $condition = preg_replace('/(?<!\$)\b([a-zA-Z_][a-zA-Z0-9_]*)\b(?!\s*[\(\'"])/', '$$1', $condition);
            return "<?php elseif ($condition): ?>";
        }, $template);

        $template = preg_replace('/#else/', '<?php else: ?>', $template);
        $template = preg_replace('/#endif/', '<?php endif; ?>', $template);

        // Process foreach with proper syntax
        $template = preg_replace_callback('/#foreach\s*\(([^)]+)\)/', function ($matches) {
            $expression = $matches[1];
            // Handle "array as item" syntax
            if (strpos($expression, ' as ') !== false) {
                return "<?php foreach ($expression): ?>";
            }
            // Handle simple array
            return "<?php foreach ($expression as \$item): ?>";
        }, $template);

        $template = preg_replace('/#endforeach/', '<?php endforeach; ?>', $template);

        // Process #include['template', {contexto}]
        $template = preg_replace_callback(
            "/#include\s*\[([^\]]+)\]/",
            function ($matches) {
                $args = trim($matches[1]);
                // Divide por vírgula fora de chaves
                $parts = preg_split('/,(?=(?:[^\{\}]|\{[^\}]*\})*$)/', $args);
                $templateName = trim($parts[0], "'\" ");
                $context = isset($parts[1]) ? trim($parts[1]) : '';
                // Gera código PHP para renderizar o template incluído
                $php = "<?php echo \\Ludelix\\Ludou\\Core\\TemplateCompiler::includeTemplate('{$templateName}'";
                if ($context) {
                    $php .= ", {$context}";
                } else {
                    $php .= ", []";
                }
                $php .= "); ?>";
                return $php;
            },
            $template
        );

        // Suporte a #[t('chave', args)] no template (chama LudouFunctions::apply)
        $template = preg_replace_callback('/#\[t\(([^\)]*)\)\]/', function ($matches) {
            $args = explode(',', $matches[1], 2);
            $key = trim($args[0], "'\" ");
            $vars = isset($args[1]) ? trim($args[1]) : '';
            if ($vars !== '') {
                return "<?php echo \\Ludelix\\Ludou\\Partials\\LudouFunctions::apply('t', '{$key}', {$vars}); ?>";
            } else {
                return "<?php echo \\Ludelix\\Ludou\\Partials\\LudouFunctions::apply('t', '{$key}'); ?>";
            }
        }, $template);

        // Conteúdo final sem aplicar Fluid hook aqui (será aplicado no renderer)
        $finalContent = $layoutContent ? $template . "\n" . $layoutContent : $template;

        return $compiled . $finalContent;
    }

    public function directive(string $name, callable $handler): void
    {
        $this->directives["/#$name/"] = $handler;
    }

    public function needsRecompilation(string $templatePath, string $compiledPath): bool
    {
        return !file_exists($compiledPath) || filemtime($templatePath) > filemtime($compiledPath);
    }

    protected function compileExpression(string $expression): string
    {
        // Se a expressão já foi processada pelo Fluid (contém class="..."), retorna como está
        if (strpos($expression, 'class="') !== false) {
            return $expression;
        }

        // USAR FLUID HOOK PARA PROCESSAR CLASSES FL-*
        if (strpos($expression, 'fl-') !== false && $this->fluidHook !== null) {
            try {
                return $this->fluidHook->beforeRender('#[' . $expression . ']');
            } catch (\Throwable $e) {
                // Fallback em caso de erro
                $classes = explode('|', $expression);
                $classString = implode(' ', array_map('trim', $classes));
                return "class=\"{$classString}\"";
            }
        }

        // Hotwire for Fluid classes, bypassing the disabled service provider.
        if (strpos($expression, 'fl-') !== false && strpos($expression, '|') !== false) {
            $classes = explode('|', $expression);
            $classString = implode(' ', array_map('trim', $classes));
            if (preg_match('/^[a-zA-Z0-9\s\-:]+$/', $classString)) {
                return "class=\"{$classString}\"";
            }
        }

        // --- DIRETIVAS ESPECIAIS (Short-circuit) ---
        // Trata #[CSRF] e #[CSRF()] para gerar o input completo.
        if (preg_match('/^CSRF(\s*\(\s*\))?$/i', $expression)) {
            return '<?php echo (new \Ludelix\Security\CsrfManager())->generateInput(); ?>';
        }

        // Trata #[CSRF_TOKEN] e #[CSRF_TOKEN()] para gerar apenas o token.
        if (preg_match('/^CSRF_TOKEN(\s*\(\s*\))?$/i', $expression)) {
            return '<?php echo (new \Ludelix\Security\CsrfManager())->getToken(); ?>';
        }

        // --- LÓGICA EXISTENTE ---
        $autoEscape = true;
        $cfg = null;
        try {
            $config = \Ludelix\Bridge\Bridge::instance()->get('config');
            if ($config) {
                $cfg = $config->get('ludou');
            }
        } catch (\Throwable $e) {
        }
        if ($cfg && isset($cfg['security']['auto_escape'])) {
            $autoEscape = $cfg['security']['auto_escape'];
        }
        // Bloqueia qualquer uso de t como filtro ou variável
        if (preg_match('/^t(\s*\|.*)?$/', trim($expression))) {
            $msg = htmlspecialchars("'t' não é filtro nem variável. Use #[t('chave')] para tradução.", ENT_QUOTES, 'UTF-8');
            return $this->errorMessage($msg, $expression, '', 0);
        }
        // Tratamento especial para #[t('chave', ...)]
        if (preg_match('/^t\s*\((.*)\)$/', trim($expression), $matches)) {
            $args = explode(',', $matches[1], 2);
            $key = trim($args[0], "'\" ");
            $vars = isset($args[1]) ? trim($args[1]) : '';
            if ($vars !== '') {
                return "<?php echo \\Ludelix\\Ludou\\Partials\\LudouFunctions::apply('t', '{$key}', {$vars}); ?>";
            } else {
                return "<?php echo \\Ludelix\\Ludou\\Partials\\LudouFunctions::apply('t', '{$key}'); ?>";
            }
        }
        // Tratamento especial para #[asset('caminho')]
        if (preg_match('/^asset\s*\((.*)\)$/', trim($expression), $matches)) {
            $args = explode(',', $matches[1], 2);
            $file = trim($args[0], "'\" ");
            $vars = isset($args[1]) ? trim($args[1]) : '';
            if ($vars !== '') {
                return "<?php echo \\Ludelix\\Ludou\\Partials\\LudouFunctions::asset('{$file}', {$vars}); ?>";
            } else {
                return "<?php echo \\Ludelix\\Ludou\\Partials\\LudouFunctions::asset('{$file}'); ?>";
            }
        }
        // Função: nomeFuncao(...)
        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/', trim($expression), $matches)) {
            $name = $matches[1];
            $argsRaw = trim($matches[2]);
            $argsList = [];
            if ($argsRaw !== '') {
                $argsSplit = preg_split('/,(?=(?:[^\'\"]|\'[^"]*\'|\"[^\"]*\")*$)/', $argsRaw);
                foreach ($argsSplit as $arg) {
                    $argsList[] = trim($arg);
                }
            }
            $argsPHP = implode(', ', $argsList);
            return "<?php echo \\Ludelix\\Ludou\\Partials\\LudouFunctions::apply('{$name}'" . ($argsPHP ? ", {$argsPHP}" : "") . "); ?>";
        }
        // Pipe universal: $variavel, funcao(), filtro
        if (preg_match('/\|/', $expression)) {
            $parts = preg_split('/\s*\|\s*/', $expression);
            $php = null;
            $isLastJson = (trim(end($parts)) === 'json');
            $isRaw = in_array(trim(end($parts)), ['raw', 'safe']);
            foreach ($parts as $i => $part) {
                $part = trim($part);
                // Função ou filtro com argumentos nomeados: nome(args)
                if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/', $part, $matches)) {
                    $name = $matches[1];
                    $argsRaw = trim($matches[2]);
                    $argsList = [];
                    $namedArgs = [];
                    if ($argsRaw !== '') {
                        $argsSplit = preg_split('/,(?=(?:[^\'\"]|\'[^\']*\'|\"[^\"]*\")*$)/', $argsRaw);
                        foreach ($argsSplit as $arg) {
                            $arg = trim($arg);
                            if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*(.+)$/', $arg, $am)) {
                                $namedArgs[$am[1]] = $am[2];
                            } else if ($arg !== '') {
                                $argsList[] = $arg;
                            }
                        }
                    }
                    if ($php && $i > 0) {
                        $argsList[] = $php;
                    }
                    $argsPHP = [];
                    foreach ($argsList as $a)
                        $argsPHP[] = $a;
                    foreach ($namedArgs as $k => $v)
                        $argsPHP[] = "'{$k}' => {$v}";
                    $argsPHPstr = implode(', ', $argsPHP);
                    $ludouFunctions = [
                        'range',
                        'count',
                        'merge',
                        'sort',
                        'filter',
                        'keys',
                        'values',
                        'now',
                        'date',
                        'min',
                        'max',
                        'random',
                        'sum',
                        'avg',
                        'empty',
                        'inArray',
                        'isNumeric',
                        'isString',
                        'isArray',
                        'startsWith',
                        'endsWith',
                        'split',
                        'length',
                        'json',
                        'type'
                    ];
                    if (in_array($name, $ludouFunctions)) {
                        $php = "\\Ludelix\\Ludou\\Partials\\LudouFunctions::apply('{$name}'" . ($argsPHPstr ? ", {$argsPHPstr}" : "") . ")";
                    } else {
                        $php = "\\Ludelix\\Ludou\\Partials\\LudouFilters::apply('{$name}'" . ($argsPHPstr ? ", {$argsPHPstr}" : "") . ")";
                    }
                } else if (preg_match('/^\$/', $part)) {
                    if ($isLastJson && $i === count($parts) - 2) {
                        $php = "(isset({$part}) ? {$part} : '')";
                    } else {
                        $php = "(isset({$part}) ? (is_array({$part}) ? json_encode({$part}) : {$part}) : '')";
                    }
                } else if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $part)) {
                    if ($i === 0) {
                        $php = "\\Ludelix\\Ludou\\Partials\\LudouFilters::apply('{$part}', '')";
                    } else {
                        $php = "\\Ludelix\\Ludou\\Partials\\LudouFilters::apply('{$part}', {$php})";
                    }
                } else {
                    if (preg_match('/\$[a-zA-Z_][a-zA-Z0-9_]*\[[a-zA-Z_][a-zA-Z0-9_]*\]/', $part)) {
                        $msg = htmlspecialchars('Erro de sintaxe: Use aspas para índices de array. Exemplo correto: $user["name"]', ENT_QUOTES, 'UTF-8');
                        return $this->errorMessage($msg, $expression, '');
                    }
                    $php = $part;
                }
            }
            if ($autoEscape && !$isRaw) {
                return "<?php echo htmlspecialchars({$php}, ENT_QUOTES, 'UTF-8'); ?>";
            } else {
                return "<?php echo {$php}; ?>";
            }
        }
        // Fora do pipe: função/filtro com argumentos nomeados
        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/', trim($expression), $matches)) {
            $name = $matches[1];
            $argsRaw = trim($matches[2]);
            $argsList = [];
            $namedArgs = [];
            if ($argsRaw !== '') {
                $argsSplit = preg_split('/,(?=(?:[^\'\"]|\'[^\']*\'|\"[^\"]*\")*$)/', $argsRaw);
                foreach ($argsSplit as $arg) {
                    $arg = trim($arg);
                    if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*(.+)$/', $arg, $am)) {
                        $namedArgs[$am[1]] = $am[2];
                    } else if ($arg !== '') {
                        $argsList[] = $arg;
                    }
                }
            }
            $argsPHP = [];
            foreach ($argsList as $a)
                $argsPHP[] = $a;
            foreach ($namedArgs as $k => $v)
                $argsPHP[] = "'{$k}' => {$v}";
            $argsPHPstr = implode(', ', $argsPHP);
            $ludouFunctions = [
                'range',
                'count',
                'merge',
                'sort',
                'filter',
                'keys',
                'values',
                'now',
                'date',
                'min',
                'max',
                'random',
                'sum',
                'avg',
                'empty',
                'inArray',
                'isNumeric',
                'isString',
                'isArray',
                'startsWith',
                'endsWith',
                'split',
                'length',
                'json',
                'type'
            ];
            if (in_array($name, $ludouFunctions)) {
                $php = "\\Ludelix\\Ludou\\Partials\\LudouFunctions::apply('{$name}'" . ($argsPHPstr ? ", {$argsPHPstr}" : "") . ")";
            } else {
                $php = "\\Ludelix\\Ludou\\Partials\\LudouFilters::apply('{$name}'" . ($argsPHPstr ? ", {$argsPHPstr}" : "") . ")";
            }
            if ($autoEscape) {
                return "<?php echo htmlspecialchars({$php}, ENT_QUOTES, 'UTF-8'); ?>";
            } else {
                return "<?php echo {$php}; ?>";
            }
        }
        // Fora do pipe: variável só se começar com $
        if (preg_match('/^\$/', $expression)) {
            if ($autoEscape) {
                return "<?php echo htmlspecialchars((isset({$expression}) ? (is_array({$expression}) ? json_encode({$expression}) : {$expression}) : ''), ENT_QUOTES, 'UTF-8'); ?>";
            } else {
                return "<?php echo (isset({$expression}) ? (is_array({$expression}) ? json_encode({$expression}) : {$expression}) : ''); ?>";
            }
        }
        // Fora do pipe: nome simples SEM $ é filtro
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $expression)) {
            $php = "\\Ludelix\\Ludou\\Partials\\LudouFilters::apply('{$expression}', '')";
            if ($autoEscape) {
                return "<?php echo htmlspecialchars({$php}, ENT_QUOTES, 'UTF-8'); ?>";
            } else {
                return "<?php echo {$php}; ?>";
            }
        }

        // Handle ternary operator: variable ?? 'default'
        if (strpos($expression, '??') !== false) {
            $parts = explode('??', $expression, 2);
            $variable = trim($parts[0]);
            $default = trim($parts[1]);
            $default = trim($default, "'\"");
            // Só aceita variável com $
            if (preg_match('/^\$/', $variable)) {
                return "<?php echo isset({$variable}) ? {$variable} : '{$default}'; ?>";
            } else {
                $msg = htmlspecialchars("Só é permitido variável com $ em ternário. Exemplo: #[\$user ?? 'padrão']", ENT_QUOTES, 'UTF-8');
                return $this->errorMessage($msg, $expression, '');
            }
        }

        // Handle ternary with condition: condition ? 'true' : 'false'
        if (strpos($expression, '?') !== false && strpos($expression, ':') !== false && strpos($expression, '??') === false) {
            // Só aceita variável com $ na condição
            $expression = preg_replace('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*\?/', '\$$1 ?', $expression);
            return "<?php echo $expression; ?>";
        }

        // Função: nome(args) com ou sem espaços (fora do pipe)
        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/', trim($expression), $matches)) {
            $func = $matches[1];
            $args = trim($matches[2]);
            // Verifica se algum argumento parece variável mas não começa com $
            foreach (preg_split('/,/', $args) as $arg) {
                $arg = trim($arg);
                if ($arg !== '' && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $arg)) {
                    $msg = htmlspecialchars("Funções só aceitam variáveis com $. Use #[count(\$user)] e não #[count(user)].", ENT_QUOTES, 'UTF-8');
                    return $this->errorMessage($msg, $expression, '');
                }
            }
            $ludouFunctions = [
                'range',
                'count',
                'merge',
                'sort',
                'filter',
                'keys',
                'values',
                'now',
                'date',
                'min',
                'max',
                'random',
                'sum',
                'avg',
                'empty',
                'inArray',
                'isNumeric',
                'isString',
                'isArray',
                'startsWith',
                'endsWith',
                'split',
                'length',
                'json',
                'type'
            ];
            if (in_array($func, $ludouFunctions)) {
                return "<?php echo \\Ludelix\\Ludou\\Partials\\LudouFunctions::apply('{$func}'" . ($args ? ", {$args}" : "") . "); ?>";
            }
            // Senão, função PHP nativa
            return "<?php echo {$func}({$args}); ?>";
        }

        // Fallback - treat as PHP expression
        return "<?php echo $expression; ?>";
    }

    protected function findLayoutPath(string $layoutName): ?string
    {
        $layoutName = str_replace('.', '/', $layoutName);

        // Obter o diretório raiz do projeto de forma dinâmica
        $projectRoot = $this->getProjectRoot();

        // Definir diretórios de templates de forma configurável
        $templateDirectories = $this->getTemplateDirectories($projectRoot);

        // Buscar o layout em todos os diretórios possíveis
        foreach ($templateDirectories as $templateDir) {
            $possiblePaths = [
                $templateDir . '/layouts/' . $layoutName . '.ludou',
                $templateDir . '/partials/' . $layoutName . '.ludou',
                $templateDir . '/' . $layoutName . '.ludou',
            ];

            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    error_log("Layout found at: " . $path);
                    return $path;
                }
            }
        }

        // Se não encontrou, logar todos os diretórios verificados para debug
        error_log("Layout not found for: " . $layoutName);
        error_log("Searched in directories: " . implode(', ', $templateDirectories));

        error_log("Layout not found for: " . $layoutName);
        return null;
    }

    /**
     * Obtém o diretório raiz do projeto de forma dinâmica
     */
    protected function getProjectRoot(): string
    {
        // Tentar diferentes métodos para encontrar o diretório raiz
        $possibleRoots = [
            // Se estamos no diretório do projeto
            getcwd(),
            // Se estamos em um subdiretório, tentar subir até encontrar composer.json
            $this->findRootByComposer(),
            // Usar __DIR__ do framework como fallback
            dirname(dirname(dirname(__DIR__))),
        ];

        foreach ($possibleRoots as $root) {
            if ($this->isValidProjectRoot($root)) {
                return $root;
            }
        }

        // Fallback para o diretório atual
        return getcwd();
    }

    /**
     * Encontra o diretório raiz procurando por composer.json
     */
    protected function findRootByComposer(): string
    {
        $currentDir = getcwd();
        $maxDepth = 10; // Limitar a profundidade para evitar loop infinito

        for ($i = 0; $i < $maxDepth; $i++) {
            if (file_exists($currentDir . '/composer.json')) {
                return $currentDir;
            }

            $parentDir = dirname($currentDir);
            if ($parentDir === $currentDir) {
                break; // Chegamos ao diretório raiz do sistema
            }
            $currentDir = $parentDir;
        }

        return getcwd();
    }

    /**
     * Verifica se um diretório é uma raiz válida do projeto
     */
    protected function isValidProjectRoot(string $path): bool
    {
        return file_exists($path . '/composer.json') ||
            file_exists($path . '/frontend/templates') ||
            file_exists($path . '/config/ludou.php');
    }

    /**
     * Obtém os diretórios de templates de forma configurável
     */
    protected function getTemplateDirectories(string $projectRoot): array
    {
        $directories = [];

        // Diretórios padrão do framework
        $defaultDirs = [
            $projectRoot . '/frontend/templates',
            $projectRoot . '/resources/templates',
            $projectRoot . '/resources/views',
            $projectRoot . '/templates',
            $projectRoot . '/views',
        ];

        // Adicionar diretórios padrão
        foreach ($defaultDirs as $dir) {
            if (is_dir($dir)) {
                $directories[] = $dir;
            }
        }

        // Tentar carregar configuração do ludou.php se existir
        $configPath = $projectRoot . '/config/ludou.php';
        if (file_exists($configPath)) {
            try {
                $config = include $configPath;
                if (isset($config['paths']) && is_array($config['paths'])) {
                    foreach ($config['paths'] as $path) {
                        $fullPath = $projectRoot . '/' . ltrim($path, '/');
                        if (is_dir($fullPath)) {
                            $directories[] = $fullPath;
                        }
                    }
                }
            } catch (\Exception $e) {
                error_log("Error loading ludou config: " . $e->getMessage());
            }
        }

        // Adicionar diretórios específicos do ambiente se detectados
        $environmentDirs = $this->getEnvironmentSpecificDirectories($projectRoot);
        $directories = array_merge($directories, $environmentDirs);

        // Se nenhum diretório foi encontrado, usar o padrão
        if (empty($directories)) {
            $directories[] = $projectRoot . '/frontend/templates';
        }

        return array_unique($directories);
    }

    /**
     * Obtém diretórios específicos do ambiente de execução
     */
    protected function getEnvironmentSpecificDirectories(string $projectRoot): array
    {
        $directories = [];

        // Detectar se estamos em um ambiente de desenvolvimento
        $isDev = defined('APP_ENV') && APP_ENV === 'development';

        // Em desenvolvimento, adicionar mais diretórios para debug
        if ($isDev) {
            $devDirs = [
                $projectRoot . '/dev/templates',
                $projectRoot . '/development/templates',
            ];

            foreach ($devDirs as $dir) {
                if (is_dir($dir)) {
                    $directories[] = $dir;
                }
            }
        }

        // Detectar se estamos em um ambiente de produção
        $isProd = defined('APP_ENV') && APP_ENV === 'production';

        // Em produção, priorizar diretórios otimizados
        if ($isProd) {
            $prodDirs = [
                $projectRoot . '/dist/templates',
                $projectRoot . '/build/templates',
            ];

            foreach ($prodDirs as $dir) {
                if (is_dir($dir)) {
                    $directories[] = $dir;
                }
            }
        }

        return $directories;
    }

    /**
     * Compile Connect directives (#[connect:*])
     */
    protected function compileConnectDirectives(string $template): string
    {
        // #[connect:vite ['entry1.tsx', 'entry2.tsx']]
        $template = preg_replace_callback(
            '/#\[connect:vite\s+(\[.*?\])\s*(?:(\w+)=([\'"])(.*?)\3)*\]/',
            function ($matches) {
                $entries = $matches[1];
                return "<?php echo \\Ludelix\\Bridge\\Bridge::instance()->get('vite')->renderVite({$entries}); ?>";
            },
            $template
        );

        // #[connect:head]
        $template = preg_replace(
            '/#\[connect:head\]/',
            '<?php echo \\Ludelix\\Bridge\\Bridge::instance()->get(\'connect.helper\')->renderHead($page ?? []); ?>',
            $template
        );

        // #[connect:root]
        $template = preg_replace(
            '/#\[connect:root\]/',
            '<?php echo \\Ludelix\\Bridge\\Bridge::instance()->get(\'connect.helper\')->renderRoot($page ?? []); ?>',
            $template
        );

        // #[connect:meta]
        $template = preg_replace(
            '/#\[connect:meta\]/',
            '<?php echo \\Ludelix\\Bridge\\Bridge::instance()->get(\'connect.helper\')->renderMeta($page ?? []); ?>',
            $template
        );

        // #[connect:title]
        $template = preg_replace(
            '/#\[connect:title\]/',
            '<?php echo \\Ludelix\\Bridge\\Bridge::instance()->get(\'connect.helper\')->renderTitle($page ?? []); ?>',
            $template
        );

        // #[class ['dark' => $isDark, 'light' => !$isDark]]
        $template = preg_replace_callback(
            '/#\[class\s+(\[.*?\])\]/',
            function ($matches) {
                $classes = $matches[1];
                return '<?php echo \'class="\' . \\Ludelix\\Ludou\\Partials\\LudouFunctions::classNames(' . $classes . ') . \'"\'; ?>';
            },
            $template
        );

        return $template;
    }

    protected function registerDirectives(): void
    {
        $this->directives = [
            '/#if\s*\(([^)]+)\)/' => '<?php if ($1): ?>',
            '/#elseif\s*\(([^)]+)\)/' => '<?php elseif ($1): ?>',
            '/#else/' => '<?php else: ?>',
            '/#endif/' => '<?php endif; ?>',
            '/#foreach\s*\(([^)]+)\)/' => '<?php foreach ($1): ?>',
            '/#endforeach/' => '<?php endforeach; ?>',
            '/#\[connect\]/' => '<?php if (Bridge::isConnectRequest()): ?>
                <div id="app" data-page="<?php echo json_encode($__connectData ?? []); ?>"></div>
            <?php else: ?>
                <div id="app">
                    <?php echo $__content ?? ""; ?>
                </div>
            <?php endif; ?>',
        ];
    }

    public static function includeTemplate($template, $context = [])
    {
        // Busca o caminho do template
        $compiler = new self();
        $path = $compiler->findLayoutPath($template);
        if (!$path) {
            return "<span style='color:red'>Partial '{$template}' não encontrado</span>";
        }
        // Compila se necessário
        $compiledPath = $path . '.php';
        if ($compiler->needsRecompilation($path, $compiledPath)) {
            $source = file_get_contents($path);

            // Processa o template com o Fluid se disponível
            if ($compiler->fluidHook !== null) {
                try {
                    $source = $compiler->fluidHook->beforeRender($source);
                } catch (\Throwable $e) {
                    // Log error if logger is available
                    try {
                        $logger = \Ludelix\Bridge\Bridge::instance()->get('logger');
                        if ($logger) {
                            $logger->error('[Ludou] Error processing Fluid template: ' . $e->getMessage());
                        }
                    } catch (\Throwable $e) {
                        // Silently fail if logger is not available
                    }
                }
            }

            $compiled = $compiler->compile($source);
            file_put_contents($compiledPath, $compiled);
        }
        // Isola contexto
        extract($context, EXTR_SKIP);
        ob_start();
        include $compiledPath;
        return ob_get_clean();
    }

    /**
     * Remove Ludou comments from template
     * Syntax: #[-- comment --]
     */
    protected function compileComments(string $template): string
    {
        return preg_replace('/#\[\s*--.*?--\s*\]/s', '', $template);
    }

    protected function errorMessage($msg, $expression = '', $template = '', $offset = 0)
    {
        $debug = false;
        $cfg = null;
        try {
            $config = \Ludelix\Bridge\Bridge::instance()->get('config');
            if ($config) {
                $cfg = $config->get('ludou');
            }
        } catch (\Throwable $e) {
        }
        if ($cfg && isset($cfg['compilation']['debug_mode'])) {
            $debug = $cfg['compilation']['debug_mode'];
        }
        $context = '';
        $line = 0;
        $col = 0;
        if ($template && $expression) {
            $pos = strpos($template, $expression, $offset);
            if ($pos !== false) {
                $before = substr($template, 0, $pos);
                $line = substr_count($before, "\n") + 1;
                $col = $pos - strrpos($before, "\n");
                $lines = explode("\n", $template);
                $start = max(0, $line - 3);
                $end = min(count($lines) - 1, $line + 1);
                $contextLines = array_slice($lines, $start, $end - $start + 1, true);
                $context = "<pre style='background:#222;color:#fff;padding:8px;border-radius:4px;font-size:13px'>";
                foreach ($contextLines as $i => $l) {
                    $n = $i + $start + 1;
                    $hl = ($n == $line) ? "background:#a00;color:#fff;" : "";
                    $context .= "<span style='display:block;{$hl}'>" . str_pad($n, 3, ' ', STR_PAD_LEFT) . ": " . htmlspecialchars($l) . "</span>";
                }
                $context .= "</pre>";
            }
        }
        $html = "<div style='color:#fff;background:#a00;padding:8px;border-radius:4px;font-family:monospace'>";
        $html .= "<b>Erro no template</b>: " . htmlspecialchars($msg);
        if ($line)
            $html .= "<br><b>Linha:</b> $line, <b>Coluna:</b> $col";
        if ($debug && $context)
            $html .= $context;
        $html .= "</div>";
        return $html;
    }
}