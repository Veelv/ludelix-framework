<?php

namespace Ludelix\Core\Console\Commands\Kria;

use Ludelix\Core\Console\Commands\Core\BaseCommand;
use Ludelix\ApiExplorer\Scanner\ApiScanner;
use Ludelix\ApiExplorer\SDK\SdkGenerator;
use Ludelix\Bridge\Bridge;

/**
 * KriaSdkCommand - Generates a TypeScript SDK for the application.
 */
class KriaSdkCommand extends BaseCommand
{
    protected string $signature = 'kria:sdk [--output=]';
    protected string $description = 'Gera um SDK TypeScript baseado nas rotas e attributes da aplicação';

    public function execute(array $arguments, array $options): int
    {
        $this->info("🔍 Escaneando rotas da aplicação...");

        $router = Bridge::route();
        $scanner = new ApiScanner($router);
        $schema = $scanner->scan();

        if (empty($schema)) {
            $this->error("❌ Nenhuma rota válida para exportação encontrada.");
            return 1;
        }

        $this->info("🏗️ Gerando código TypeScript para " . count($schema) . " endpoints...");

        $generator = new SdkGenerator();
        $code = $generator->generate($schema);

        $outputFile = $this->option($options, 'output') ?? 'sdk-ludelix.ts';

        file_put_contents($outputFile, $code);

        $this->success("✨ SDK gerado com sucesso em: {$outputFile}");
        $this->info("💡 Dica: Importe o 'LudelixClient' no seu frontend para começar!");

        return 0;
    }
}
