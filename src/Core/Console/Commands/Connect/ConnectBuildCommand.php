<?php

namespace Ludelix\Core\Console\Commands\Connect;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

class ConnectBuildCommand extends BaseCommand
{
    protected string $signature = 'connect:build {--optimize : Otimizar assets para produção} {--minify : Minificar arquivos}';
    protected string $description = 'Compila assets do connect para produção';

    public function execute(array $arguments, array $options): int
    {
        $this->info('🏗️  Compilando assets do connect para produção...');

        $optimize = $this->hasOption($options, 'optimize');
        $minify = $this->hasOption($options, 'minify');

        try {
            if (!file_exists('package.json')) {
                $this->error('❌ package.json não encontrado. Execute "php mi connect" primeiro.');
                return 1;
            }
            if (!is_dir('node_modules')) {
                $this->info('📦 Instalando dependências...');
                $this->runCommand('npm install');
            }
            $this->info('🔨 Compilando assets...');
            $buildCommand = 'npm run build';
            if ($optimize) {
                $buildCommand .= ' --optimize';
            }
            if ($minify) {
                $buildCommand .= ' --minify';
            }
            $result = $this->runCommand($buildCommand);
            if ($result === 0) {
                $this->success('✅ Assets compilados com sucesso!');
                $this->info('📁 Arquivos gerados em: public/assets/');
                return 0;
            } else {
                $this->error('❌ Erro ao compilar assets');
                return 1;
            }
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