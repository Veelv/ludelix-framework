<?php

namespace Ludelix\Core\Console\Commands\Connect;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

class ConnectDevCommand extends BaseCommand
{
    protected string $signature = 'connect:dev {--port=5173 : Porta do servidor de desenvolvimento} {--host=localhost : Host do servidor}';
    protected string $description = 'Inicia servidor de desenvolvimento do connect';

    public function execute(array $arguments, array $options): int
    {
        $this->info('🚀 Iniciando servidor de desenvolvimento do connect...');
        $port = $this->option($options, 'port', '5173');
        $host = $this->option($options, 'host', 'localhost');
        try {
            if (!file_exists('package.json')) {
                $this->error('❌ package.json não encontrado. Execute "php mi connect" primeiro.');
                return 1;
            }
            if (!is_dir('node_modules')) {
                $this->info('📦 Instalando dependências...');
                $this->runCommand('npm install');
            }
            $this->info("🌐 Servidor rodando em: http://{$host}:{$port}");
            $this->info('📝 Pressione Ctrl+C para parar o servidor');
            $this->line('');
            $devCommand = "npm run dev -- --host {$host} --port {$port}";
            return $this->runCommand($devCommand);
        } catch (\Exception $e) {
            $this->error("❌ Erro: " . $e->getMessage());
            return 1;
        }
    }

    protected function runCommand(string $command): int
    {
        $this->line("Executando: {$command}");
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $process = proc_open($command, $descriptors, $pipes);
        if (is_resource($process)) {
            while (!feof($pipes[1])) {
                $output = fgets($pipes[1]);
                if ($output) {
                    $this->line(trim($output));
                }
            }
            while (!feof($pipes[2])) {
                $error = fgets($pipes[2]);
                if ($error) {
                    $this->error(trim($error));
                }
            }
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $returnCode = proc_close($process);
            return $returnCode;
        }
        return 1;
    }
} 