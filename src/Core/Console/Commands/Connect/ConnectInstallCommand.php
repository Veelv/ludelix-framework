<?php

namespace Ludelix\Core\Console\Commands\Connect;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

class ConnectInstallCommand extends BaseCommand
{
    protected string $signature = 'connect:install {--force : Forçar reinstalação} {--clean : Limpar cache antes de instalar}';
    protected string $description = 'Instala dependências do connect (npm/yarn)';

    public function execute(array $arguments, array $options): int
    {
        $this->info('📦 Instalando dependências do connect...');
        $force = $this->hasOption($options, 'force');
        $clean = $this->hasOption($options, 'clean');
        try {
            if (!file_exists('package.json')) {
                $this->error('❌ package.json não encontrado. Execute "php mi connect" primeiro.');
                return 1;
            }
            if ($clean) {
                $this->info('🧹 Limpando cache...');
                $this->runCommand('npm cache clean --force');
            }
            if ($force && is_dir('node_modules')) {
                $this->info('🗑️  Removendo node_modules...');
                $this->removeDirectory('node_modules');
            }
            $this->info('📥 Instalando dependências...');
            $installCommand = 'npm install';
            if ($force) {
                $installCommand .= ' --force';
            }
            $result = $this->runCommand($installCommand);
            if ($result === 0) {
                $this->success('✅ Dependências instaladas com sucesso!');
                $this->info('📁 node_modules criado');
                return 0;
            } else {
                $this->error('❌ Erro ao instalar dependências');
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

    protected function removeDirectory(string $path): void
    {
        if (is_dir($path)) {
            $files = array_diff(scandir($path), ['.', '..']);
            foreach ($files as $file) {
                $filePath = $path . DIRECTORY_SEPARATOR . $file;
                if (is_dir($filePath)) {
                    $this->removeDirectory($filePath);
                } else {
                    unlink($filePath);
                }
            }
            rmdir($path);
        }
    }
} 