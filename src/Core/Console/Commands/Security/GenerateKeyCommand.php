<?php

namespace Ludelix\Core\Console\Commands\Security;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Generate Application Key Command
 * 
 * Gera uma chave de aplicação forte e segura
 */
class GenerateKeyCommand extends BaseCommand
{
    protected string $name = 'key:generate';
    protected string $description = 'Generate a new application key';
    protected array $options = [
        'force' => 'Force generation even if key exists',
        'show' => 'Show the generated key',
    ];

    public function execute(array $arguments, array $options): int
    {
        try {
            $this->info("🔑 Generating application key...");
            
            // Gerar chave forte
            $key = $this->generateStrongKey();
            
            // Verificar se já existe chave
            $envFile = '.env';
            $envContent = file_exists($envFile) ? file_get_contents($envFile) : '';
            
            // Debug: verificar se APP_KEY existe no conteúdo
            $hasAppKey = false;
            $existingKey = '';
            // Remover BOM se existir
            $envContent = str_replace("\xEF\xBB\xBF", '', $envContent);
            
            // Laravel-like APP_KEY detection
            $lines = explode("\n", $envContent);
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (str_starts_with($trimmed, '#')) continue;
                if (preg_match('/^APP_KEY=(.*)$/', $trimmed, $matches)) {
                    $hasAppKey = true;
                    $existingKey = isset($matches[1]) ? trim($matches[1]) : '';
                    break;
                }
            }
            // Verificar se key está vazia ou não existe
            $keyIsEmpty = empty(trim($existingKey)) || 
                         $existingKey === 'null' || 
                         $existingKey === '""' || 
                         $existingKey === "''" ||
                         $existingKey === '=' ||
                         strlen(trim($existingKey)) === 0;
            
            if ($hasAppKey && !$keyIsEmpty && !($options['force'] ?? false)) {
                $this->error("❌ Application key already exists. Use --force to regenerate.");
                return 1;
            }
            
            // Atualizar arquivo .env
            $this->updateEnvFile($envFile, $key);
            
            $this->success("✅ Application key generated successfully!");
            
            if ($options['show'] ?? false) {
                $this->line("🔑 Key: {$key}");
            } else {
                $this->line("🔑 Key has been set in .env file");
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to generate key: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Gera uma chave forte
     */
    private function generateStrongKey(): string
    {
        // Gerar 32 bytes aleatórios
        $bytes = random_bytes(32);
        
        // Codificar em base64
        $key = 'base64:' . base64_encode($bytes);
        
        return $key;
    }

    /**
     * Atualiza o arquivo .env
     */
    private function updateEnvFile(string $envFile, string $key): void
    {
        $content = file_exists($envFile) ? file_get_contents($envFile) : '';
        $content = str_replace("\xEF\xBB\xBF", '', $content);
        $lines = explode("\n", $content);
        $newLines = [];
        $appKeySet = false;
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (preg_match('/^APP_KEY=/',$trimmed)) {
                if (!$appKeySet) {
                    $newLines[] = "APP_KEY={$key}";
                    $appKeySet = true;
                }
                // Remove duplicatas
                continue;
            }
            $newLines[] = $line;
        }
        if (!$appKeySet) {
            // Adiciona APP_KEY no topo igual Laravel
            array_unshift($newLines, "APP_KEY={$key}");
        }
        $newContent = implode("\n", $newLines);
        file_put_contents($envFile, $newContent);
    }
} 