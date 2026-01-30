<?php

namespace Ludelix\Core\Console\Commands\Framework;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Cubby Link Command
 *
 * Cria um link simbólico [public/up] para [cubby/up] para servir arquivos públicos de upload
 */
class CubbyLinkCommand extends BaseCommand
{
    protected string $signature = 'cubby:link';
    protected string $description = 'Cria um link simbólico [public/up] para [cubby/up] para servir arquivos públicos de upload';

    public function execute(array $arguments, array $options): int
    {
        $this->info('🔗 Criando link simbólico para arquivos de upload...');
        $this->line('');

        // Calcula o caminho correto da aplicação (ludelix-app)
        $basePath = $this->getApplicationPath();
        $cubbyPath = $basePath . '/cubby/up';
        $publicUpPath = $basePath . '/public/up';

        $this->line("📁 Caminho da aplicação: {$basePath}");
        $this->line("📁 Cubby path: {$cubbyPath}");
        $this->line("📁 Public path: {$publicUpPath}");
        $this->line('');

        try {
            // Cria o diretório cubby/up se não existir
            if (!is_dir($cubbyPath)) {
                mkdir($cubbyPath, 0755, true);
                $this->info("✓ Diretório [cubby/up] criado.");
            } else {
                $this->info("✓ Diretório [cubby/up] já existe.");
            }

            // Verifica se já existe um link simbólico
            if (is_link($publicUpPath)) {
                $this->info("✓ O link simbólico [public/up] já existe.");
                $this->line("📁 Arquivos em cubby/up estão acessíveis em: /up/arquivo.ext");
                return 0;
            }

            // Verifica se existe uma pasta (não link) em public/up
            if (is_dir($publicUpPath)) {
                $this->error("❌ Já existe uma pasta [public/up].");
                $this->line("💡 Remova ou renomeie a pasta antes de criar o link simbólico.");
                return 1;
            }

            // Tenta criar o link simbólico
            $linkCreated = false;
            
            // Tenta symlink primeiro
            if (symlink($cubbyPath, $publicUpPath)) {
                $linkCreated = true;
                $this->success("✓ Link simbólico [public/up] criado com sucesso!");
            } else {
                // Se falhar, tenta criar uma cópia (para Windows)
                if (is_dir($cubbyPath)) {
                    // Cria uma cópia do diretório para funcionar no Windows
                    if ($this->copyDirectory($cubbyPath, $publicUpPath)) {
                        $linkCreated = true;
                        $this->success("✓ Diretório [public/up] criado como cópia de [cubby/up] (Windows).");
                        $this->line("💡 Para sincronização automática, considere usar ferramentas como rsync ou robocopy.");
                    }
                }
            }

            if ($linkCreated) {
                $this->line("📁 Arquivos em cubby/up agora estão acessíveis em: /up/arquivo.ext");
                $this->line("🔗 Link: {$publicUpPath} → {$cubbyPath}");
                return 0;
            } else {
                $this->error("❌ Falha ao criar o link simbólico.");
                $this->line("💡 No Windows, execute como administrador ou use uma cópia manual.");
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("❌ Erro ao criar link simbólico: " . $e->getMessage());
            return 1;
        }
    }

    private function getApplicationPath(): string
    {
        // Começa do diretório atual e sobe até encontrar o composer.json da aplicação
        $currentPath = getcwd();
        
        // Procura pelo composer.json que contém "ludelix-app" no nome
        while ($currentPath !== dirname($currentPath)) {
            $composerFile = $currentPath . '/composer.json';
            if (file_exists($composerFile)) {
                $composerContent = file_get_contents($composerFile);
                if (strpos($composerContent, '"name": "veelv/ludelix"') !== false) {
                    return $currentPath;
                }
            }
            $currentPath = dirname($currentPath);
        }
        
        // Fallback: usa o diretório atual de trabalho
        return getcwd();
    }

    private function copyDirectory(string $source, string $destination): bool
    {
        if (!is_dir($source)) {
            return false;
        }

        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $files = scandir($source);
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                $sourceFile = $source . '/' . $file;
                $destFile = $destination . '/' . $file;
                
                if (is_dir($sourceFile)) {
                    $this->copyDirectory($sourceFile, $destFile);
                } else {
                    copy($sourceFile, $destFile);
                }
            }
        }
        
        return true;
    }
} 